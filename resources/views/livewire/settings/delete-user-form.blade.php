<?php

use App\Concerns\PasswordValidationRules;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Livewire\Component;

new class extends Component {
    use PasswordValidationRules;

    public bool $showDeleteModal = false;
    public string $password = '';

    public function openDeleteModal(): void
    {
        $this->resetErrorBag();
        $this->password = '';
        $this->showDeleteModal = true;
    }

    public function cancelDelete(): void
    {
        $this->showDeleteModal = false;
        $this->password = '';
    }

    public function deleteUser(): void
    {
        $this->validate([
            'password' => $this->currentPasswordRules(),
        ]);

        if (Auth::user()->hasRole('admin')) {
            $this->addError('password', 'Administradores não podem excluir a própria conta por motivos de segurança.');
            return;
        }

        $user = Auth::user();
        $user->update(['status' => 'inactive']);
        $user->delete();

        Auth::guard('web')->logout();
        Session::invalidate();
        Session::regenerateToken();
        session()->flash('status', 'Conta excluida com sucesso.');

        $this->redirect('/', navigate: true);
    }
}; ?>

<section class="mt-4 border-top border-danger pt-4" x-data="{ open: false }">
    <div class="mb-3">
        <h5 class="text-danger fw-bold mb-1"><i class="bi bi-exclamation-triangle-fill me-2"></i>Excluir conta</h5>
        <p class="text-danger small opacity-75 mb-3">Ao excluir sua conta, todos os seus dados e assinaturas serão perdidos permanentemente. Esta ação não pode ser desfeita.</p>
        
        <button type="button" @click="open = !open" class="btn btn-sm btn-link text-danger text-decoration-none p-0 fw-bold">
            <span x-show="!open"><i class="bi bi-chevron-down me-1"></i> Mostrar opções de exclusão</span>
            <span x-show="open"><i class="bi bi-chevron-up me-1"></i> Esconder opções de exclusão</span>
        </button>
    </div>

    <div x-show="open" x-collapse x-cloak class="mt-3">
        <div class="card border-danger bg-dark bg-opacity-10 shadow-sm">
            <div class="card-body py-4 text-center">
                <p class="text-danger fw-medium mb-4">Você tem certeza que deseja prosseguir? Esta ação é irreversível.</p>
                <button type="button" class="btn btn-danger fw-bold px-4" wire:click="openDeleteModal" data-test="delete-user-button">
                    <i class="bi bi-trash3-fill me-2"></i>Excluir Minha Conta Permanentemente
                </button>
            </div>
        </div>
    </div>

    @if($showDeleteModal)
        <div class="position-fixed top-0 start-0 w-100 h-100 d-flex align-items-center justify-content-center" style="background: rgba(0,0,0,.8); z-index:1060;">
            <div class="card bg-dark border-danger shadow-lg mx-3" style="width: min(500px, 100%); border-radius: 15px;">
                <div class="card-header border-danger bg-danger bg-opacity-10 py-3 text-center">
                    <h5 class="mb-0 text-danger fw-bold">Confirmar Exclusão</h5>
                </div>
                <form wire:submit="deleteUser">
                    <div class="card-body py-4 px-4">
                        <p class="text-danger text-center small mb-4">Por segurança, digite sua senha para confirmar que você realmente deseja excluir sua conta.</p>
                        
                        <div class="mb-3">
                            <label class="form-label text-danger small fw-bold">Sua Senha</label>
                            <input
                                type="password"
                                wire:model="password"
                                class="form-control bg-dark text-danger border-danger @error('password') is-invalid @enderror"
                                placeholder="Digite sua senha atual"
                                autocomplete="current-password"
                                style="padding: 12px;"
                            >
                            @error('password')
                                <div class="invalid-feedback d-block mt-2">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    <div class="card-footer border-0 bg-transparent d-flex justify-content-center gap-2 pb-4">
                        <button type="button" class="btn btn-outline-secondary px-4" wire:click="cancelDelete">Cancelar</button>
                        <button type="submit" class="btn btn-danger px-4 fw-bold shadow-sm" data-test="confirm-delete-user-button">Sim, Excluir Conta</button>
                    </div>
                </form>
            </div>
        </div>
    @endif
</section>
