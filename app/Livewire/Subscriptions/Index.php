<?php

namespace App\Livewire\Subscriptions;

use App\Models\Category;
use App\Models\Subscription;
use App\Services\CacheService;
use App\Services\ReportService;
use App\Services\SubscriptionImportService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;

class Index extends Component
{
    use WithFileUploads; // Removido WithPagination para evitar que o Livewire force a URL

    public int $page = 1;

    // protected string $paginationTheme = 'bootstrap'; // Não necessário com paginação manual

    public $csvFile;

    public $importStatus = '';

    public bool $ignoreDuplicates = true;

    public bool $showImportModal = false;

    public array $importSummary = [
        'total' => 0,
        'duplicates' => 0,
        'new' => 0,
    ];

    // public array $tempImportData = []; // Removido para evitar erro de snapshot grande
    public array $selectedIds = [];

    public bool $selectAll = false;

    /**
     * Neutraliza possíveis células de fórmula de planilha e remove espaços laterais.
     */
    private function sanitizeImportedText(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = trim($value);
        if ($value === '') {
            return null;
        }

        // Prefixos iniciadores de fórmula em Excel/Sheets.
        if (preg_match('/^[=+\-@]/', $value) === 1) {
            return "'".$value;
        }

        return $value;
    }

    /**
     * Aceita apenas URLs válidas com protocolo http/https.
     */
    private function sanitizeImportedUrl(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = trim($value);
        if ($value === '') {
            return null;
        }

        $value = preg_replace('/[\x00-\x1F\x7F]/u', '', $value) ?? '';
        if ($value === '' || filter_var($value, FILTER_VALIDATE_URL) === false) {
            return null;
        }

        $scheme = strtolower((string) parse_url($value, PHP_URL_SCHEME));
        if (! in_array($scheme, ['http', 'https'], true)) {
            return null;
        }

        return $value;
    }

    /**
     * Neutraliza células potencialmente interpretadas como fórmula no CSV exportado.
     */
    private function sanitizeExportText(mixed $value): string
    {
        $text = trim((string) ($value ?? ''));
        if ($text === '') {
            return '';
        }

        if (str_starts_with($text, "'")) {
            return $text;
        }

        if (preg_match('/^[\t\r\n ]*[=+\-@]/', $text) === 1) {
            return "'".$text;
        }

        return $text;
    }

    public function updatedCsvFile()
    {
        if ($this->csvFile) {
            $this->prepareImport();
        }
    }

    public function prepareImport()
    {
        $this->validate([
            'csvFile' => 'required|extensions:csv,txt|max:1024',
        ]);

        $token = auth()->user()->privacyToken?->token;
        if (! $token) {
            session()->flash('error', 'Token de privacidade nÃƒÂ£o encontrado ao preparar importaÃƒÂ§ÃƒÂ£o.');

            return;
        }

        try {
            $path = $this->csvFile->getRealPath();
            $content = file_get_contents($path);

            Log::info('CSV import prepare started', [
                'user_id' => auth()->id(),
                'privacy_token_prefix' => substr($token, 0, 8),
                'original_name' => $this->csvFile->getClientOriginalName(),
                'mime_type' => $this->csvFile->getMimeType(),
                'size' => $this->csvFile->getSize(),
                'real_path' => $path,
                'path_exists' => $path ? file_exists($path) : false,
                'path_readable' => $path ? is_readable($path) : false,
            ]);

            // Remove BOM do Excel
            $content = preg_replace('/^\xEF\xBB\xBF/', '', $content);

            // Salva em arquivo temporário para usar fgetcsv
            $tempStream = fopen('php://temp', 'r+');
            fwrite($tempStream, $content);
            rewind($tempStream);

            // Detecta delimitador pela primeira linha
            $firstLine = fgets($tempStream);
            rewind($tempStream);
            $delimiter = str_contains($firstLine, ';') ? ';' : ',';

            $data = [];
            $header = fgetcsv($tempStream, 0, $delimiter); // Pula cabeçalho

            while (($row = fgetcsv($tempStream, 0, $delimiter)) !== false) {
                // Limpa campos vazios ou com apenas espaços que o fgetcsv pode trazer
                if (count($row) === 1 && $row[0] === null) {
                    continue;
                }
                $data[] = $row;
            }
            fclose($tempStream);

            if (empty($data)) {
                session()->flash('error', 'Nenhum dado encontrado após o cabeçalho.');

                return;
            }

            session()->put('temp_import_data_'.auth()->id(), $data);

            $total = count($data);
            $namesInCsv = array_unique(array_filter(array_map(fn ($row) => trim($row[0] ?? ''), $data)));

            $existingNames = Subscription::where('privacy_token', $token)
                ->whereIn('name', $namesInCsv)
                ->pluck('name')
                ->map(fn ($n) => strtolower($n))
                ->toArray();

            $duplicates = 0;
            foreach ($data as $row) {
                if (! empty($row[0]) && in_array(strtolower(trim($row[0])), $existingNames)) {
                    $duplicates++;
                }
            }

            $this->importSummary = [
                'total' => $total,
                'duplicates' => $duplicates,
                'new' => $total - $duplicates,
            ];

            $this->showImportModal = true;
        } catch (\Throwable $e) {
            Log::error('CSV import prepare failed', [
                'user_id' => auth()->id(),
                'exception' => $e::class,
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);

            session()->flash('error', 'Erro ao ler arquivo: ['.$e::class.'] '.$e->getMessage());
        }
    }

    public string $search = '';

    public string $statusFilter = 'all';

    public string $categoryFilter = 'all';

    public string $sortColumn = 'next_billing_date';

    public string $sortDirection = 'asc';

    public int $perPage = 10;

    public function updatedSelectAll($value): void
    {
        if ($value) {
            $token = auth()->user()->privacyToken?->token;
            $this->selectedIds = Subscription::byPrivacyToken($token)
                ->pluck('id')
                ->map(fn ($id) => (string) $id)
                ->toArray();
        } else {
            $this->selectedIds = [];
        }
    }

    public function deleteSelected(): void
    {
        if (empty($this->selectedIds)) {
            return;
        }

        $token = auth()->user()->privacyToken?->token;
        Subscription::byPrivacyToken($token)
            ->whereIn('id', $this->selectedIds)
            ->delete();

        app(CacheService::class)->invalidateUserCache($token);

        $count = count($this->selectedIds);
        $this->selectedIds = [];
        $this->selectAll = false;

        session()->flash('success', "{$count} assinaturas excluídas com sucesso!");
    }

    public function sortBy(string $column): void
    {
        if ($this->sortColumn === $column) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortColumn = $column;
            $this->sortDirection = 'asc';
        }
    }

    public function updatingCategoryFilter(): void
    {
        $this->resetPage();
    }

    public bool $showFormModal = false;

    public bool $showDeleteModal = false;

    public ?string $editingId = null;

    public ?string $deletingId = null;

    public string $deletingName = '';

    public function gotoPage($page)
    {
        $this->page = $page;
    }

    public function nextPage()
    {
        $this->page++;
    }

    public function previousPage()
    {
        if ($this->page > 1) {
            $this->page--;
        }
    }

    public function resetPage()
    {
        $this->page = 1;
    }

    // Form fields
    public string $name = '';

    public ?int $category_id = null;

    public bool $isCreatingCategory = false;

    public string $newCategoryName = '';

    public string $newCategoryColor = '#0F6CBD';

    public string $selectedCategoryColor = '#0F6CBD';

    public string $billing_cycle = 'monthly';

    public ?int $custom_cycle_interval = null;

    public string $custom_cycle_period = 'months';

    public string $amount = '';

    public string $currency = 'BRL';

    public string $start_date = '';

    public string $next_billing_date = '';

    public string $status = 'active';

    public ?string $cancelled_at = null;

    public bool $auto_renew = true;

    public bool $is_domain = false;

    public string $notes = '';

    public string $service_url = '';

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatedName($value): void
    {
        if ($this->editingId) {
            return; // Only autofill when creating new
        }

        $service = strtolower(trim($value));

        $templates = [
            'netflix' => ['amount' => '39.90', 'cycle' => 'monthly', 'color' => '#E50914'],
            'spotify' => ['amount' => '21.90', 'cycle' => 'monthly', 'color' => '#1DB954'],
            'amazon prime' => ['amount' => '19.90', 'cycle' => 'monthly', 'color' => '#00A8E1'],
            'amazon' => ['amount' => '19.90', 'cycle' => 'monthly', 'color' => '#00A8E1'],
            'youtube premium' => ['amount' => '24.90', 'cycle' => 'monthly', 'color' => '#FF0000'],
            'disney+' => ['amount' => '33.90', 'cycle' => 'monthly', 'color' => '#113CCF'],
            'disney plus' => ['amount' => '33.90', 'cycle' => 'monthly', 'color' => '#113CCF'],
            'hbo max' => ['amount' => '34.90', 'cycle' => 'monthly', 'color' => '#5C068C'],
            'max' => ['amount' => '34.90', 'cycle' => 'monthly', 'color' => '#002BE7'],
            'globoplay' => ['amount' => '24.90', 'cycle' => 'monthly', 'color' => '#FA0054'],
            'apple tv' => ['amount' => '21.90', 'cycle' => 'monthly', 'color' => '#FFFFFF'],
            'apple music' => ['amount' => '21.90', 'cycle' => 'monthly', 'color' => '#FA243C'],
            'chatgpt' => ['amount' => '110.00', 'cycle' => 'monthly', 'color' => '#10A37F'],
            'github copilot' => ['amount' => '55.00', 'cycle' => 'monthly', 'color' => '#FAFBFC'],
        ];

        foreach ($templates as $key => $template) {
            if (str_contains($service, $key)) {
                if (empty($this->amount)) {
                    $this->amount = $template['amount'];
                }
                $this->billing_cycle = $template['cycle'];
                break;
            }
        }
    }

    public function updatedCategoryId($value): void
    {
        if ($value) {
            $category = Category::find($value);
            if ($category) {
                $this->selectedCategoryColor = $category->color;
            }
        }
    }

    public function updatingStatusFilter(): void
    {
        $this->resetPage();
    }

    protected function rules(): array
    {
        $rules = [
            'name' => 'required|string|max:80',
            'billing_cycle' => 'required|in:monthly,yearly,quarterly,semiannual,custom',
            'amount' => 'required|numeric|min:0|max:99999999',
            'currency' => 'required|string|size:3',
            'start_date' => 'required|date',
            'next_billing_date' => 'nullable|date',
        ];

        if ($this->billing_cycle === 'custom') {
            $rules['custom_cycle_interval'] = 'required|integer|min:1';
            $rules['custom_cycle_period'] = 'required|in:days,months,years';
        }

        $rules += [
            'status' => 'required|in:active,paused,cancelled',
            'cancelled_at' => 'nullable|date',
            'auto_renew' => 'boolean',
            'is_domain' => 'boolean',
            'notes' => 'nullable|string|max:255',
            'service_url' => 'nullable|url|max:255',
        ];

        if ($this->isCreatingCategory) {
            $rules['newCategoryName'] = 'required|string|max:50';
            $rules['newCategoryColor'] = 'required|string|size:7';
        } else {
            $rules['category_id'] = 'nullable|exists:categories,id';
        }

        return $rules;
    }

    public function openCreateModal(): void
    {
        $this->resetForm();
        $this->showFormModal = true;
    }

    public function openEditModal(string $id): void
    {
        $this->resetErrorBag();

        $token = auth()->user()->privacyToken?->token;
        $subscription = Subscription::byPrivacyToken($token)->findOrFail($id);

        $this->editingId = $subscription->id;
        $this->name = $subscription->name;
        $this->category_id = $subscription->category_id;
        $this->billing_cycle = $subscription->billing_cycle;
        $this->custom_cycle_interval = $subscription->custom_cycle_interval;
        $this->custom_cycle_period = $subscription->custom_cycle_period ?? 'months';
        $this->amount = (string) $subscription->amount;
        $this->currency = $subscription->currency ?? 'BRL';
        $this->start_date = $subscription->start_date->format('Y-m-d');
        $this->next_billing_date = $subscription->next_billing_date ? $subscription->next_billing_date->format('Y-m-d') : '';
        $this->status = $subscription->status;
        $this->cancelled_at = $subscription->cancelled_at ? $subscription->cancelled_at->format('Y-m-d') : '';
        $this->auto_renew = (bool) $subscription->auto_renew;
        $this->is_domain = (bool) $subscription->is_domain;
        $this->notes = (string) $subscription->notes;
        $this->service_url = (string) $subscription->service_url;

        if ($this->category_id) {
            $category = Category::find($this->category_id);
            $this->selectedCategoryColor = $category->color ?? '#0F6CBD';
        }

        $this->showFormModal = true;
    }

    public function closeFormModal(): void
    {
        $this->showFormModal = false;
        $this->resetForm();
    }

    public function exportCsv()
    {
        $token = auth()->user()->privacyToken?->token;
        if (! $token) {
            session()->flash('error', 'Token de privacidade não encontrado.');

            return;
        }

        $subscriptions = Subscription::byPrivacyToken($token)->with('category')->get();

        $headers = [
            'Content-type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename=Minhas_Assinaturas.csv',
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0',
        ];

        $callback = function () use ($subscriptions) {
            $file = fopen('php://output', 'w');

            // Add BOM to fix UTF-8 in Excel
            fwrite($file, $bom = (chr(0xEF).chr(0xBB).chr(0xBF)));

            // Headers (Now 13 columns including all fields)
            fputcsv($file, ['Nome', 'URL', 'Valor', 'Moeda', 'Ciclo', 'Intervalo_Custom', 'Periodo_Custom', 'Categoria', 'Início', 'Vencimento', 'Status', 'Auto_Renew', 'Notas'], ';');

            foreach ($subscriptions as $sub) {
                fputcsv($file, [
                    $this->sanitizeExportText($sub->name),
                    $this->sanitizeExportText($sub->service_url),
                    number_format($sub->amount, 2, ',', ''),
                    $sub->currency ?? 'BRL',
                    $sub->billing_cycle,
                    $sub->custom_cycle_interval,
                    $sub->custom_cycle_period,
                    $this->sanitizeExportText($sub->category->name ?? 'Sem categoria'),
                    $sub->start_date ? $sub->start_date->format('d/m/Y') : '',
                    $sub->next_billing_date ? $sub->next_billing_date->format('d/m/Y') : '',
                    $sub->status,
                    $sub->auto_renew ? 'Sim' : 'Não',
                    $this->sanitizeExportText($sub->notes),
                ], ';');
            }

            fclose($file);
        };

        activity()->event('csv_export')->log('Usuário exportou suas assinaturas via CSV.');

        return response()->streamDownload($callback, 'Minhas_Assinaturas.csv', $headers);
    }

    public function confirmImport()
    {
        try {
            Log::info('confirmImport chamado');
            $token = auth()->user()->privacyToken?->token;
            if (! $token) {
                Log::warning('confirmImport: Token não encontrado');
                session()->flash('error', 'Token de privacidade não encontrado.');
                $this->reset(['csvFile', 'showImportModal', 'importSummary']);

                return;
            }

            $importedCount = 0;
            $skippedCount = 0;

            $tempData = session()->get('temp_import_data_'.auth()->id(), []);
            Log::info('confirmImport: Registros para processar: '.count($tempData));

            if (empty($tempData)) {
                Log::warning('confirmImport: Dados temporários vazios na sessão');
                session()->flash('error', 'Dados da importação expiraram ou não foram encontrados.');
                $this->reset(['csvFile', 'showImportModal', 'importSummary']);

                return;
            }

            $importService = app(SubscriptionImportService::class);

            foreach ($tempData as $row) {
                try {
                    $result = $importService->importRow($token, $row, $this->ignoreDuplicates);

                    if ($result['status'] === 'imported') {
                        $importedCount++;
                    } else {
                        $skippedCount++;
                    }
                } catch (\Exception $e) {
                    Log::error('Erro ao importar linha: '.$e->getMessage());
                    $skippedCount++;
                }
            }

            $message = "{$importedCount} assinaturas importadas.";
            if ($skippedCount > 0) {
                $message .= " {$skippedCount} falhas ou duplicadas ignoradas.";
            }

            $this->importStatus = $message;
            app(CacheService::class)->invalidateUserCache($token);

            session()->forget('temp_import_data_'.auth()->id());
            $this->reset(['csvFile', 'showImportModal', 'importSummary']);
            session()->flash('success', $this->importStatus);
            Log::info('confirmImport finalizado: '.$message);
        } catch (\Throwable $e) {
            Log::error('Erro fatal no confirmImport: '.$e->getMessage());
            session()->flash('error', 'Ocorreu um erro crítico na importação: '.$e->getMessage());
            $this->reset(['csvFile', 'showImportModal', 'importSummary']);
        }
    }

    public function cancelImport()
    {
        Log::info('cancelImport chamado');
        session()->forget('temp_import_data_'.auth()->id());
        $this->reset(['csvFile', 'showImportModal', 'importSummary']);
    }

    public function save(): void
    {
        try {
            $data = $this->validate();

            $token = auth()->user()->privacyToken?->token;

            if (! $token) {
                session()->flash('error', 'Token de privacidade inválido.');

                return;
            }

            // Limpeza de campos vazios para evitar erro de formato SQL
            $data['next_billing_date'] = ! empty($data['next_billing_date']) ? $data['next_billing_date'] : null;
            $data['cancelled_at'] = ! empty($data['cancelled_at']) ? $data['cancelled_at'] : null;
            $data['custom_cycle_interval'] = ! empty($data['custom_cycle_interval']) ? $data['custom_cycle_interval'] : null;
            $data['custom_cycle_period'] = ! empty($data['custom_cycle_period']) ? $data['custom_cycle_period'] : null;

            if ($this->isCreatingCategory) {
                $category = Category::create([
                    'privacy_token' => $token,
                    'name' => $data['newCategoryName'],
                    'slug' => Str::slug($data['newCategoryName'].'-'.uniqid()),
                    'color' => $data['newCategoryColor'],
                    'icon' => 'bi-tag',
                    'is_system' => false,
                ]);
                $data['category_id'] = $category->id;
                unset($data['newCategoryName'], $data['newCategoryColor']);
            } elseif ($this->category_id) {
                // Atualiza a cor da categoria existente se for uma categoria do próprio usuário
                $category = Category::where('id', $this->category_id)
                    ->where('privacy_token', $token)
                    ->first();

                if ($category && $category->color !== $this->selectedCategoryColor) {
                    $category->update(['color' => $this->selectedCategoryColor]);
                    app(CacheService::class)->invalidateUserCache($token);
                }
            }

            if ($this->status === 'cancelled' && empty($data['cancelled_at'])) {
                $data['cancelled_at'] = now();
            }

            if ($this->editingId) {
                $subscription = Subscription::byPrivacyToken($token)->findOrFail($this->editingId);
                $subscription->update($data);
                session()->flash('success', 'Assinatura atualizada com sucesso!');
            } else {
                $data['privacy_token'] = $token;
                Subscription::create($data);
                session()->flash('success', 'Assinatura criada com sucesso!');
            }

            app(CacheService::class)->invalidateUserCache($token);

            $this->showFormModal = false;
            $this->resetForm();

        } catch (ValidationException $e) {
            throw $e; // Deixa o Livewire lidar com erros de validação
        } catch (\Exception $e) {
            Log::error('Erro ao salvar assinatura: '.$e->getMessage());
            session()->flash('error', 'Ocorreu um erro inesperado ao salvar: '.$e->getMessage());
        }
    }

    public function confirmDelete(string $id): void
    {
        $token = auth()->user()->privacyToken?->token;
        $subscription = Subscription::byPrivacyToken($token)->findOrFail($id);

        $this->deletingId = $subscription->id;
        $this->deletingName = $subscription->name;
        $this->showDeleteModal = true;
    }

    public function cancelDelete(): void
    {
        $this->showDeleteModal = false;
        $this->deletingId = null;
        $this->deletingName = '';
    }

    public function deleteSubscription(): void
    {
        if ($this->deletingId) {
            $token = auth()->user()->privacyToken?->token;
            $subscription = Subscription::byPrivacyToken($token)->findOrFail($this->deletingId);
            $subscription->delete();

            app(CacheService::class)->invalidateUserCache($token);

            session()->flash('success', 'Assinatura excluída com sucesso!');
        }

        $this->showDeleteModal = false;
        $this->deletingId = null;
        $this->deletingName = '';
    }

    protected function resetForm(): void
    {
        $this->resetErrorBag();
        $this->editingId = null;
        $this->name = '';
        $this->category_id = null;
        $this->isCreatingCategory = false;
        $this->newCategoryName = '';
        $this->newCategoryColor = '#0F6CBD';
        $this->billing_cycle = 'monthly';
        $this->custom_cycle_interval = null;
        $this->custom_cycle_period = 'months';
        $this->amount = '';
        $this->currency = 'BRL';
        $this->start_date = now()->format('Y-m-d');
        $this->next_billing_date = now()->addMonth()->format('Y-m-d');
        $this->status = 'active';
        $this->cancelled_at = null;
        $this->auto_renew = true;
        $this->is_domain = false;
        $this->notes = '';
        $this->service_url = '';
    }

    public function render()
    {
        $token = auth()->user()->privacyToken?->token;
        if ($token) {
            app(ReportService::class)->syncSubscriptions($token);
        }

        $query = Subscription::query()
            ->byPrivacyToken($token)
            ->with('category')
            ->when($this->search !== '', function ($q) {
                $q->where('name', 'like', '%'.trim($this->search).'%');
            });

        if ($this->categoryFilter !== 'all' && $this->categoryFilter !== 'none') {
            $query->where('category_id', $this->categoryFilter);
        } elseif ($this->categoryFilter === 'none') {
            $query->whereNull('category_id');
        }

        if ($this->statusFilter !== 'all') {
            $query->where('status', $this->statusFilter);
        }

        $total = $query->count();
        $subscriptions = $query->orderBy($this->sortColumn, $this->sortDirection)
            ->offset(($this->page - 1) * $this->perPage)
            ->limit($this->perPage)
            ->get();

        return view('livewire.subscriptions.index', [
            'subscriptions' => $subscriptions,
            'categories' => Category::where('is_system', true)
                ->orderBy('name')
                ->get(),
            'totalPages' => ceil($total / $this->perPage),
            'totalRecords' => $total,
        ]);
    }
}
