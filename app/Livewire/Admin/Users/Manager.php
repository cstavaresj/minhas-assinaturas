<?php

namespace App\Livewire\Admin\Users;

use App\Models\User;
use App\Services\PasswordSecurityService;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Livewire\Component;
use Livewire\WithPagination;

class Manager extends Component
{
    use WithPagination;

    protected string $paginationTheme = 'bootstrap';

    public string $search = '';
    public string $statusFilter = 'all';
    public int $perPage = 10;

    public bool $showFormModal = false;
    public bool $showDeleteModal = false;
    public bool $editing = false;
    public ?int $editingUserId = null;
    public ?int $deletingUserId = null;
    public string $deletingUserName = '';

    public string $name = '';
    public string $email = '';
    public string $password = '';
    public string $password_confirmation = '';
    public string $role = 'user';
    public string $status = 'active';

    public function downloadLgpdReport(int $userId)
    {
        $this->authorizeAdmin();

        $user = User::withTrashed()->findOrFail($userId);

        $data = [
            'report_type' => 'Solicitação LGPD - Dados Pessoais (Visão Administrativa)',
            'generated_at' => now()->toIso8601String(),
            'compliance' => 'Relatório gerado conforme Art. 18 da LGPD. Por diretrizes de segurança (Privacy by Design), administradores não possuem acesso técnico aos dados de assinaturas dos usuários. O download completo de dados, incluindo detalhes de serviços e valores, é restrito exclusivamente ao próprio titular da conta.',
            'personal_data' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'status' => $user->status_label,
                'registered_at' => $user->created_at->toIso8601String(),
                'lgpd_consent_at' => $user->lgpd_consent_at?->toIso8601String(),
                'account_state' => $user->trashed() ? 'Desativada (Soft Delete)' : 'Ativa',
                'deleted_at' => $user->deleted_at?->toIso8601String(),
            ],
            'audit' => [
                'admin_responsible' => Auth::user()->name,
                'admin_email' => Auth::user()->email,
            ]
        ];

        $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

        activity()
            ->performedOn($user)
            ->event('admin_lgpd_export')
            ->log('Administrador exportou relatório LGPD do usuário ' . $user->email);

        return response()->streamDownload(function () use ($json) {
            echo $json;
        }, 'Relatorio_LGPD_' . Str::slug($user->name) . '.json', [
            'Content-Type' => 'application/json',
        ]);
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingStatusFilter(): void
    {
        $this->resetPage();
    }

    public function openCreateModal(): void
    {
        $this->resetForm();
        $this->editing = false;
        $this->showFormModal = true;
    }

    /**
     * Verifica se o admin está editando a si mesmo.
     */
    public function isEditingSelf(): bool
    {
        return $this->editing && $this->editingUserId === (int) Auth::id();
    }

    public function openEditModal(int $userId): void
    {
        if ($userId === 1 && (int) Auth::id() !== 1) {
            session()->flash('error', 'Você não tem permissão para editar o administrador principal.');
            return;
        }

        $user = User::withTrashed()->findOrFail($userId);

        $this->editing = true;
        $this->editingUserId = $user->id;
        $this->name = (string) $user->name;
        $this->email = (string) $user->email;
        $this->role = (string) ($user->getRoleNames()->first() ?? 'user');
        $this->status = (string) ($user->status ?? 'active');
        $this->password = '';
        $this->password_confirmation = '';
        $this->showFormModal = true;
    }

    public function closeFormModal(): void
    {
        $this->showFormModal = false;
    }

    public function save(): void
    {
        $this->authorizeAdmin();

        $data = $this->validate($this->rules());

        if ($this->editing && $this->editingUserId) {
            if ($this->editingUserId === 1 && (int) Auth::id() !== 1) {
                session()->flash('error', 'Você não tem permissão para editar o administrador principal.');
                return;
            }

            $user = User::withTrashed()->findOrFail($this->editingUserId);

            if (blank($data['password'])) {
                unset($data['password']);
            } else {
                $data['password'] = PasswordSecurityService::hashPassword((string) $data['password']);
                $data['created_via_google'] = false;
            }

            // Admin não pode alterar seu próprio status ou role
            if ($this->isEditingSelf()) {
                unset($data['status'], $data['role']);
            }

            unset($data['password_confirmation'], $data['role']);
            $user->update($data);
        } else {
            unset($data['password_confirmation']);
            $data['lgpd_consent_at'] = now();
            $data['password'] = PasswordSecurityService::hashPassword((string) $data['password']);
            $data['created_via_google'] = false;
            $user = User::create($data);
        }

        // Admin não pode rebaixar ou alterar sua própria role
        if (! $this->isEditingSelf()) {
            $user->syncRoles([$this->role]);
        }

        $this->showFormModal = false;
        $this->resetForm();

        session()->flash('status', $this->editing ? 'Usuário atualizado com sucesso.' : 'Usuário criado com sucesso.');
    }

    public function confirmDeleteUser(int $userId): void
    {
        $this->authorizeAdmin();

        if ($userId === 1) {
            session()->flash('error', 'O administrador principal não pode ser excluído.');
            return;
        }

        if ((int) Auth::id() === $userId) {
            session()->flash('error', 'Você não pode excluir a própria conta.');
            return;
        }

        $user = User::withTrashed()->findOrFail($userId);
        $this->deletingUserId = $user->id;
        $this->deletingUserName = (string) $user->name;
        $this->showDeleteModal = true;
    }

    public function cancelDelete(): void
    {
        $this->showDeleteModal = false;
        $this->deletingUserId = null;
        $this->deletingUserName = '';
    }

    public function deleteUser(int $userId): void
    {
        $this->authorizeAdmin();

        if ($userId === 1) {
            session()->flash('error', 'O administrador principal não pode ser excluído.');
            return;
        }

        if ((int) Auth::id() === $userId) {
            session()->flash('error', 'Você não pode excluir a própria conta.');
            return;
        }

        $user = User::withTrashed()->findOrFail($userId);
        $user->forceDelete();

        if ($this->deletingUserId === $userId) {
            $this->cancelDelete();
        }

        session()->flash('status', 'Usuário excluído permanentemente com sucesso.');
    }

    public function softDeleteUser(int $userId): void
    {
        $this->authorizeAdmin();

        if ($userId === 1) {
            session()->flash('error', 'O administrador principal não pode ser desativado.');
            return;
        }

        if ((int) Auth::id() === $userId) {
            session()->flash('error', 'Você não pode desativar a própria conta.');
            return;
        }

        $user = User::findOrFail($userId);
        $user->delete();

        session()->flash('status', 'Usuário desativado com sucesso.');
    }
    public function restoreUser(int $userId): void
    {
        $this->authorizeAdmin();

        $user = User::withTrashed()->findOrFail($userId);
        if ($user->trashed()) {
            $user->restore();
            session()->flash('status', 'Usuário restaurado com sucesso.');
        }
    }

    public function unlockUser(int $userId): void
    {
        $this->authorizeAdmin();
        $user = User::withTrashed()->findOrFail($userId);
        
        $email = Str::lower($user->email);
        
        // 1. Restaurar status e usuário se necessário
        $user->update(['status' => 'active']);
        if ($user->trashed()) {
            $user->restore();
        }

        // 2. Coletar IPs conhecidos para este usuário (do log de atividades)
        $ips = \Spatie\Activitylog\Models\Activity::where('properties->email', $email)
            ->latest()
            ->take(10)
            ->get()
            ->map(function($activity) {
                if (isset($activity->properties['ip'])) return $activity->properties['ip'];
                if (method_exists($activity, 'getExtraProperty')) return $activity->getExtraProperty('ip');
                if (is_iterable($activity->properties)) {
                    foreach ($activity->properties as $key => $value) {
                        if ($key === 'ip') return $value;
                    }
                }
                return null;
            })
            ->push(request()->ip())
            ->push('127.0.0.1')
            ->push('::1')
            ->filter()
            ->unique();

        foreach ($ips as $ip) {
            $throttleKey = Str::transliterate($email.'|'.$ip);
            
            // Lista exaustiva de chaves para garantir o reset em qualquer driver
            $keys = [
                'login:'.$throttleKey,
                'login:'.Str::lower($email).'|'.$ip,
                'login:'.$ip,
                $throttleKey,
                Str::lower($email).'|'.$ip,
                $ip
            ];
            
            foreach ($keys as $k) {
                \Illuminate\Support\Facades\RateLimiter::clear($k);
                \Illuminate\Support\Facades\Cache::forget($k);
                \Illuminate\Support\Facades\Cache::forget($k.':timer');
            }
        }
        
        // Reset global por email
        \Illuminate\Support\Facades\RateLimiter::clear('login:'.Str::transliterate($email));
        \Illuminate\Support\Facades\Cache::forget('login:'.Str::transliterate($email));
        \Illuminate\Support\Facades\Cache::forget('login:'.Str::transliterate($email).':timer');

        // FALLBACK NUCLEAR: Remove fisicamente qualquer arquivo de timer no cache
        $cachePath = storage_path('framework/cache/data');
        if (is_dir($cachePath)) {
            $dir = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($cachePath));
            $now = time();
            foreach ($dir as $file) {
                if ($file->isFile() && $file->getFilename() !== '.gitignore') {
                    $content = @file_get_contents($file->getPathname());
                    if ($content && strlen($content) > 10) {
                        $val = substr($content, 10);
                        if (strpos($val, 'i:') === 0) {
                            $num = (int)substr($val, 2, -1);
                            // Se for um timer (timestamp futuro), removemos
                            if ($num > $now - 60 && $num < $now + 7200) {
                                @unlink($file->getPathname());
                            }
                        }
                    }
                }
            }
        }

        activity('seguranca')
            ->performedOn($user)
            ->causedBy(auth()->user())
            ->log("Usuário {$email} desbloqueado administrativamente.");

        session()->flash('status', "O acesso de {$email} foi liberado com sucesso. Todos os bloqueios foram removidos.");

        $this->dispatch('swal', [
            'title' => 'ACESSO LIBERADO!',
            'text' => "Todos os bloqueios e temporizadores de segurança para {$email} foram resetados.",
            'icon' => 'success'
        ]);
    }

    public function render()
    {
        $users = User::query()
            ->withTrashed()
            ->with('roles')
            ->when($this->search !== '', function ($query) {
                $term = '%' . trim($this->search) . '%';

                $query->where(function ($subQuery) use ($term) {
                    $subQuery->where('name', 'like', $term)
                        ->orWhere('email', 'like', $term);
                });
            })
            ->when($this->statusFilter !== 'all', function ($query) {
                if ($this->statusFilter === 'deleted') {
                    $query->onlyTrashed();
                    return;
                }

                $query->whereNull('deleted_at')
                    ->where('status', $this->statusFilter);
            })
            ->latest()
            ->paginate($this->perPage);

        // Adiciona flag de bloqueio temporário (throttle) para cada usuário
        $users->getCollection()->transform(function ($user) {
            $email = Str::lower($user->email);
            $user->is_throttled = false;
            $user->throttle_mins = 0;
            
            // Tenta adivinhar qual chave está bloqueando
            $ipsToTest = [
                request()->ip(),
                \Spatie\Activitylog\Models\Activity::where('properties->email', $user->email)->latest()->value('properties->ip')
            ];

            foreach (array_unique(array_filter($ipsToTest)) as $ip) {
                $rawKey = $email.'|'.$ip;
                $possibleKeys = [$rawKey, 'login:'.$rawKey, 'login'.$rawKey];
                
                foreach ($possibleKeys as $pk) {
                    $seconds = \Illuminate\Support\Facades\RateLimiter::availableIn(Str::transliterate($pk));
                    if ($seconds > 0) {
                        $user->is_throttled = true;
                        $user->throttle_mins = ceil($seconds / 60);
                        break 2;
                    }
                }
            }
            
            return $user;
        });

        return view('livewire.admin.users.manager', [
            'users' => $users,
        ]);
    }

    protected function rules(): array
    {
        $base = [
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($this->editingUserId),
            ],
            'role' => ['required', Rule::in(['admin', 'user'])],
            'status' => ['required', Rule::in(['active', 'inactive', 'blocked'])],
        ];

        if ($this->editing) {
            $base['password'] = ['nullable', 'confirmed', 'min:8'];
        } else {
            $base['password'] = ['required', 'confirmed', 'min:8'];
        }

        $base['password_confirmation'] = ['nullable'];

        return $base;
    }

    protected function resetForm(): void
    {
        $this->editingUserId = null;
        $this->name = '';
        $this->email = '';
        $this->password = '';
        $this->password_confirmation = '';
        $this->role = 'user';
        $this->status = 'active';
    }

    protected function authorizeAdmin(): void
    {
        $user = Auth::user();

        abort_unless($user instanceof User && $user->hasRole('admin'), 403);
    }
}
