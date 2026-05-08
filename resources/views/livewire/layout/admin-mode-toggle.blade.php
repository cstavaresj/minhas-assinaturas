<?php

use Livewire\Component;

new class extends Component {
    public bool $adminMode = true;

    public function mount(): void
    {
        $this->adminMode = session('admin_mode', true);
    }

    public function toggle(): void
    {
        $this->adminMode = !$this->adminMode;
        session(['admin_mode' => $this->adminMode]);
        
        $this->js('window.location.reload()');
    }
}; ?>

<a href="{{ url('admin-mode/toggle') }}" 
   style="display: block; text-decoration: none; cursor: pointer; position: relative; z-index: 10;" 
   onclick="console.log('Botão clicado!');">
    <div class="form-check form-switch d-flex align-items-center gap-2 bg-dark bg-opacity-50 px-3 py-1 border border-secondary rounded-pill shadow-sm" style="pointer-events: none;">
        <input class="form-check-input ms-0" type="checkbox" role="switch" id="adminModeSwitch" style="width: 2.2em; height: 1.1em;" {{ $adminMode ? 'checked' : '' }}>
        <label class="form-check-label small fw-bold text-white mb-0" for="adminModeSwitch" style="user-select: none;">
            Modo Admin: <span class="{{ $adminMode ? 'text-primary' : 'text-secondary' }}">{{ $adminMode ? 'ON' : 'OFF' }}</span>
        </label>
    </div>
</a>

