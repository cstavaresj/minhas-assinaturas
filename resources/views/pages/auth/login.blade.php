@extends('layouts.auth.simple')

@section('content')
    <div class="container d-flex flex-column align-items-center justify-content-center min-vh-100 py-5">
        <div class="col-12 col-md-8 col-lg-5 col-xl-4">
            
            <div class="text-center mb-4">
                <a href="{{ route('home') }}" class="text-decoration-none">
                    <div class="bg-primary bg-opacity-10 p-3 rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width: 80px; height: 80px;">
                        <i class="bi bi-shield-check text-primary" style="font-size: 2rem;"></i>
                    </div>
                    <h2 class="text-white fw-bold h4 mb-1">Minhas Assinaturas</h2>
                    <p class="text-primary opacity-75 small">Gerencie suas assinaturas com privacidade.</p>
                </a>
            </div>

            <div class="card shadow-lg auth-card-glass" style="border-radius: 20px;">
                <div class="card-body p-4 p-md-5">
                    <h5 class="text-white fw-bold mb-4 text-center">Entrar no Painel</h5>

                    <form method="POST" action="{{ route('login') }}">
                        @csrf

                        @if (session('status'))
                            <div class="alert alert-success py-2" role="alert" style="font-size: 0.85rem;">
                                {{ session('status') }}
                            </div>
                        @endif

                        @if ($errors->has('email'))
                            <div class="alert alert-danger py-2" role="alert" style="font-size: 0.85rem;">
                                {{ $errors->first('email') }}
                            </div>
                        @endif

                        <!-- Email -->
                        <div class="mb-3">
                            <label for="email" class="form-label text-light fw-medium">E-mail</label>
                            <input id="email" type="email" 
                                class="form-control bg-dark text-white border-secondary @error('email') is-invalid @enderror" 
                                name="email" value="{{ old('email') }}" required autofocus autocomplete="username"
                                style="border-radius: 12px; padding: 12px 16px;">

                        </div>

                        <!-- Senha -->
                        <div class="mb-3">
                            <label for="password" class="form-label text-light fw-medium">Senha</label>
                            <input id="password" type="password" 
                                class="form-control bg-dark text-white border-secondary @error('password') is-invalid @enderror" 
                                name="password" required autocomplete="current-password"
                                style="border-radius: 12px; padding: 12px 16px;">

                        </div>

                        <!-- Lembrar-me -->
                        <div class="mb-4 d-flex justify-content-between align-items-center">
                            <div class="form-check">
                                <input class="form-check-input bg-dark border-secondary" type="checkbox" name="remember" id="remember_me">
                                <label class="form-check-label text-secondary small" for="remember_me">
                                    Lembrar de mim
                                </label>
                            </div>
                            {{-- Link de recuperação de senha removido (Privacidade/Sem E-mail) --}}
                        </div>

                        <div class="d-grid mb-4">
                            <button type="submit" class="btn btn-primary fw-bold py-2 shadow-sm" style="border-radius: 50px;">
                                <i class="bi bi-box-arrow-in-right me-2"></i>Acessar Painel
                            </button>
                        </div>

                        @if(config('services.google.enabled'))
                            <div class="d-grid mb-4">
                                <a href="{{ route('auth.google.redirect') }}" class="btn btn-outline-light fw-bold py-2" style="border-radius: 50px;">
                                    <i class="bi bi-google me-2"></i>Entrar com Google
                                </a>
                            </div>
                        @endif


                        @if(Route::has('register'))
                            <div class="text-center">
                                <span class="text-secondary small">Não tem uma conta?</span>
                                <a href="{{ route('register') }}" class="text-primary fw-bold text-decoration-none small ms-1">Criar Agora</a>
                            </div>
                        @else
                            <div class="text-center">
                                <p class="text-secondary small mb-0">O registro público está temporariamente desativado.</p>
                                <a href="{{ route('home') }}" class="text-primary fw-bold text-decoration-none small">Voltar ao Início</a>
                            </div>
                        @endif
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
