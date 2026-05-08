<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-bs-theme="dark">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>Minhas Assinaturas | Gerenciamento e Controle de Assinaturas Digitais</title>
    <link rel="icon" type="image/png" href="{{ asset('favicon.png') }}">
    <meta name="description" content="Controle todas as suas assinaturas digitais em um só lugar. Receba alertas de vencimento, veja gráficos de gastos e economize dinheiro evitando renovações automáticas esquecidas.">
    <meta name="keywords" content="gerenciamento de assinaturas, controle financeiro, assinaturas digitais, alertas de vencimento, economia, streaming, domínios">
    <meta name="author" content="Minhas Assinaturas">

    <!-- Open Graph / Facebook -->
    <meta property="og:type" content="website">
    <meta property="og:url" content="{{ url('/') }}">
    <meta property="og:title" content="Minhas Assinaturas | Domine suas Assinaturas">
    <meta property="og:description" content="Pare de perder dinheiro com renovações automáticas! Controle Netflix, Spotify, domínios e muito mais em um painel único e seguro.">
    <meta property="og:image" content="{{ asset('assets/img/og-image.png') }}">

    <!-- Twitter -->
    <meta property="twitter:card" content="summary_large_image">
    <meta property="twitter:url" content="{{ url('/') }}">
    <meta property="twitter:title" content="Minhas Assinaturas | Domine suas Assinaturas">
    <meta property="twitter:description" content="Controle total das suas assinaturas digitais. Simples, seguro e eficiente.">
    <meta property="twitter:image" content="{{ asset('assets/img/og-image.png') }}">

    <link rel="canonical" href="{{ url('/') }}">

    <!-- Google Fonts: Inter -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;800&display=swap" rel="stylesheet">

    <!-- CSS e JS Compilado (Bootstrap + Icons) -->
    @vite(['resources/css/app.scss', 'resources/js/app.js'])
    {{-- @livewireStyles removido: inject_assets=true já injeta automaticamente --}}

    <style>
        body {
            font-family: 'Inter', sans-serif;
            background-color: #0b0f19;
            color: #f8f9fa;
        }

        .navbar-brand {
            font-weight: 800;
            letter-spacing: -0.5px;
            color: #fff !important;
        }
        
        .hero-section {
            padding: 80px 0 80px 0;
            background: radial-gradient(circle at 50% -20%, #1a233a, #0b0f19);
            position: relative;
            overflow: hidden;
        }

        .hero-section::before {
            content: '';
            position: absolute;
            width: 500px;
            height: 500px;
            background: rgba(13, 110, 253, 0.15); /* Primary glow */
            border-radius: 50%;
            top: -200px;
            left: -150px;
            filter: blur(100px);
            z-index: 0;
        }

        .hero-content {
            position: relative;
            z-index: 1;
        }

        .hero-title {
            font-weight: 800;
            font-size: 3.5rem;
            line-height: 1.1;
            margin-bottom: 24px;
            background: linear-gradient(90deg, #fff, #a0b0d0);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .hero-subtitle {
            font-weight: 300;
            font-size: 1.25rem;
            color: #a0b0d0;
            margin-bottom: 40px;
            line-height: 1.6;
        }

        .btn-custom-primary {
            background-color: #0d6efd;
            border-color: #0d6efd;
            padding: 12px 32px;
            font-weight: 600;
            border-radius: 50px;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(13, 110, 253, 0.4);
        }

        .btn-custom-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(13, 110, 253, 0.6);
            background-color: #0b5ed7;
        }

        .btn-custom-secondary {
            background-color: transparent;
            color: #fff;
            border: 1px solid rgba(255,255,255,0.2);
            padding: 12px 32px;
            font-weight: 600;
            border-radius: 50px;
            transition: all 0.3s ease;
        }

        .btn-custom-secondary:hover {
            border-color: #fff;
            background-color: rgba(255,255,255,0.05);
        }

        .feature-box {
            background: rgba(255, 255, 255, 0.02);
            border: 1px solid rgba(255, 255, 255, 0.05);
            border-radius: 16px;
            padding: 30px;
            height: 100%;
            transition: transform 0.3s, background 0.3s;
        }
        
        .feature-box:hover {
            transform: translateY(-5px);
            background: rgba(255, 255, 255, 0.04);
        }

        .feature-icon {
            font-size: 2.5rem;
            color: #0d6efd;
            margin-bottom: 20px;
        }

        .footer {
            border-top: 1px solid rgba(255,255,255,0.05);
            padding: 40px 0;
            color: #6c757d;
        }

        /* Marquee Animation */
        .marquee-container {
            overflow: hidden;
            user-select: none;
            display: flex;
            gap: 40px;
            padding: 40px 0;
            position: relative;
        }

        .marquee-content {
            display: flex;
            flex-shrink: 0;
            gap: 60px;
            align-items: center;
            justify-content: space-around;
            min-width: 100%;
            animation: scroll 30s linear infinite;
        }

        @keyframes scroll {
            from { transform: translateX(0); }
            to { transform: translateX(-100%); }
        }

        .brand-logo {
            filter: grayscale(1) opacity(0.5);
            transition: all 0.3s ease;
            height: 30px;
            object-fit: contain;
        }

        .brand-logo:hover {
            filter: grayscale(0) opacity(1);
            transform: scale(1.1);
        }

        /* Efeitos de Luz Dinâmicos */
        .dynamic-glow {
            position: absolute;
            width: 600px;
            height: 600px;
            background: radial-gradient(circle, rgba(13, 110, 253, 0.1) 0%, transparent 70%);
            border-radius: 50%;
            filter: blur(80px);
            z-index: 1;
            pointer-events: none;
            transition: transform 0.2s ease-out;
        }

        #dynamic-glow-1 {
            top: 10%;
            left: -10%;
        }

        .spotlight-area {
            overflow: hidden;
        }

        .spotlight {
            position: absolute;
            width: 800px;
            height: 800px;
            background: radial-gradient(circle at center, rgba(13, 110, 253, 0.05) 0%, transparent 60%);
            border-radius: 50%;
            pointer-events: none;
            z-index: 1;
            transform: translate(-50%, -50%);
            mix-blend-mode: screen;
        }

        .card-hover-effect {
            transition: all 0.4s cubic-bezier(0.165, 0.84, 0.44, 1);
        }

        .card-hover-effect:hover {
            transform: translateY(-10px) scale(1.02);
            background: rgba(13, 110, 253, 0.08) !important;
            border-color: rgba(13, 110, 253, 0.3) !important;
            box-shadow: 0 20px 40px rgba(0,0,0,0.4);
        }

        .backdrop-blur {
            backdrop-filter: blur(10px);
        }
    </style>
</head>
<body>
    @if (session('status'))
        <div class="container pt-3">
            <div class="alert alert-success mb-0" role="alert">
                {{ session('status') }}
            </div>
        </div>
    @endif

    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-transparent pt-4">
        <div class="container">
            <a class="navbar-brand" href="/">
                <i class="bi bi-shield-check text-primary fs-3 me-2"></i>
                <span>Minhas Assinaturas</span>
            </a>
            <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto align-items-center gap-3">
                    @if (Route::has('login'))
                        @auth
                            <li class="nav-item">
                                <a href="{{ url('/dashboard') }}" class="nav-link fw-semibold">Interface do Painel</a>
                            </li>
                        @else
                            <li class="nav-item">
                                <a href="{{ route('login') }}" class="nav-link fw-semibold text-white">Entrar</a>
                            </li>
                            @if (Route::has('register'))
                                <li class="nav-item">
                                    @if(Route::has('register'))
                                        <a href="{{ route('register') }}" class="btn btn-primary px-4 fw-semibold" style="border-radius: 50px;">Criar Conta</a>
                                    @else
                                        <button type="button" class="btn btn-outline-primary px-4 fw-semibold" style="border-radius: 50px;" data-bs-toggle="modal" data-bs-target="#accessRequestModal">Solicitar Acesso</button>
                                    @endif
                                </li>
                            @endif
                        @endauth
                    @endif
                </ul>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="hero-section text-center pb-0">
        <div class="container hero-content">
            <div class="row justify-content-center">
                <div class="col-lg-8">
                    <h1 class="hero-title">Retome o controle total das suas assinaturas</h1>
                    <p class="hero-subtitle">
                        Rastreie vencimentos, licenças de software, serviços de streaming e muito mais de forma unificada. Obtenha segurança real e não pague mais por assinaturas de renovação automática que você não deseja manter.
                    </p>
                    <div class="d-flex gap-3 justify-content-center mt-4">
                        @if(Route::has('register'))
                            <a href="{{ route('register') }}" class="btn btn-custom-primary text-decoration-none">
                                <i class="bi bi-rocket-takeoff me-2"></i>Começar Gratuitamente
                            </a>
                        @else
                            <button type="button" class="btn btn-custom-primary text-decoration-none" data-bs-toggle="modal" data-bs-target="#accessRequestModal">
                                <i class="bi bi-rocket-takeoff me-2"></i>Solicitar Teste
                            </button>
                        @endif
                        <a href="#features" class="btn btn-custom-secondary text-decoration-none">
                            Descobrir Funcionalidades
                        </a>
                    </div>
                </div>
            </div>

            <!-- Brand Logos Marquee (Moved Above Dashboard) -->
            <div class="marquee-container mt-5" style="opacity: 0.4;">
                <div class="marquee-content">
                    <img src="https://cdn.simpleicons.org/microsoft/white" class="brand-logo" alt="Microsoft">
                    <img src="https://cdn.simpleicons.org/netflix/white" class="brand-logo" alt="Netflix">
                    <img src="https://cdn.simpleicons.org/amazon/white" class="brand-logo" alt="Amazon">
                    <img src="https://cdn.simpleicons.org/spotify/white" class="brand-logo" alt="Spotify">
                    <img src="https://cdn.simpleicons.org/apple/white" class="brand-logo" alt="Apple">
                    <img src="https://cdn.simpleicons.org/disneyplus/white" class="brand-logo" alt="Disney+">
                    <img src="https://cdn.simpleicons.org/hbo/white" class="brand-logo" alt="HBO">
                    <img src="https://cdn.simpleicons.org/google/white" class="brand-logo" alt="Google">
                    <img src="https://cdn.simpleicons.org/youtube/white" class="brand-logo" alt="YouTube">
                    <img src="https://cdn.simpleicons.org/adobe/white" class="brand-logo" alt="Adobe">
                    <img src="https://cdn.simpleicons.org/openai/white" class="brand-logo" alt="OpenAI">
                    <img src="https://cdn.simpleicons.org/anthropic/white" class="brand-logo" alt="Anthropic">
                </div>
                <div class="marquee-content" aria-hidden="true">
                    <img src="https://cdn.simpleicons.org/microsoft/white" class="brand-logo" alt="Microsoft">
                    <img src="https://cdn.simpleicons.org/netflix/white" class="brand-logo" alt="Netflix">
                    <img src="https://cdn.simpleicons.org/amazon/white" class="brand-logo" alt="Amazon">
                    <img src="https://cdn.simpleicons.org/spotify/white" class="brand-logo" alt="Spotify">
                    <img src="https://cdn.simpleicons.org/apple/white" class="brand-logo" alt="Apple">
                    <img src="https://cdn.simpleicons.org/disneyplus/white" class="brand-logo" alt="Disney+">
                    <img src="https://cdn.simpleicons.org/hbo/white" class="brand-logo" alt="HBO">
                    <img src="https://cdn.simpleicons.org/google/white" class="brand-logo" alt="Google">
                    <img src="https://cdn.simpleicons.org/youtube/white" class="brand-logo" alt="YouTube">
                    <img src="https://cdn.simpleicons.org/adobe/white" class="brand-logo" alt="Adobe">
                    <img src="https://cdn.simpleicons.org/openai/white" class="brand-logo" alt="OpenAI">
                    <img src="https://cdn.simpleicons.org/anthropic/white" class="brand-logo" alt="Anthropic">
                </div>
            </div>

            <!-- Dashboard Preview -->
            <div class="mt-4 pt-2">
                <div class="p-2 p-md-3 bg-dark rounded-4 shadow-lg border border-secondary border-opacity-25 mx-auto position-relative overflow-hidden" style="max-width: 950px; height: auto; min-height: 300px; aspect-ratio: 16/9;">
                    <img src="{{ asset('assets/img/print.png') }}" id="preview-1" class="img-fluid rounded-3 preview-img active" alt="Dashboard Preview 1" style="position: absolute; top: 15px; left: 15px; width: calc(100% - 30px); transition: opacity 1s ease-in-out;">
                    <img src="{{ asset('assets/img/print2.png') }}" id="preview-2" class="img-fluid rounded-3 preview-img" alt="Dashboard Preview 2" style="position: absolute; top: 15px; left: 15px; width: calc(100% - 30px); opacity: 0; transition: opacity 1s ease-in-out;">
                </div>
            </div>
        </div>
    </section>

    <!-- Features Section (Original Restored + Spreadsheet) -->
    <section id="features" class="py-5 mt-5">
        <div class="container">
            <div class="row g-4 justify-content-center text-center">
                <div class="col-md-3">
                    <div class="feature-box">
                        <i class="bi bi-clock-history feature-icon text-warning"></i>
                        <h4 class="fw-bold fs-5 mb-3">Vencimentos e Alertas</h4>
                        <p class="text-secondary small mb-0">Sistema inteligente para projetar suas próximas faturas antes do débito.</p>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="feature-box">
                        <i class="bi bi-pie-chart feature-icon text-success"></i>
                        <h4 class="fw-bold fs-5 mb-3">Gráficos Analíticos</h4>
                        <p class="text-secondary small mb-0">Entenda seu orçamento mês a mês observando seus relatórios e previsões de gastos.</p>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="feature-box">
                        <i class="bi bi-file-earmark-spreadsheet feature-icon text-info"></i>
                        <h4 class="fw-bold fs-5 mb-3">Importação Fácil</h4>
                        <p class="text-secondary small mb-0">Importe todos os seus dados via planilha CSV em segundos, sem esforço manual.</p>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="feature-box">
                        <i class="bi bi-incognito feature-icon"></i>
                        <h4 class="fw-bold fs-5 mb-3">Privacidade Total</h4>
                        <p class="text-secondary small mb-0">Privacidade absoluta: nem mesmo os nossos administradores conseguem acessar seus dados.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Coming Soon / Roadmap Section -->
    <section id="roadmap" class="py-5 position-relative overflow-hidden" style="background: #0b0f19;">
        <!-- Dynamic Glow Background -->
        <div class="dynamic-glow" id="dynamic-glow-1"></div>
        
        <div class="container py-5 position-relative" style="z-index: 2;">
            <div class="text-center mb-5">
                <span class="badge bg-warning text-dark px-3 py-2 rounded-pill mb-3 fw-bold">EM DESENVOLVIMENTO</span>
                <h2 class="fw-bold display-5 text-white">Próximas Implementações</h2>
                <p class="text-secondary lead">O futuro do gerenciamento é inteligente. Confira o que estamos preparando para você:</p>
            </div>

            <div class="row g-4 row-cols-1 row-cols-md-2 row-cols-lg-4 justify-content-center">
                <div class="col">
                    <div class="p-4 bg-dark bg-opacity-50 rounded-4 border border-secondary border-opacity-25 h-100 position-relative overflow-hidden card-hover-effect">
                        <div class="position-absolute top-0 end-0 p-3">
                            <span class="badge bg-primary small">BREVE</span>
                        </div>
                        <i class="bi bi-envelope-paper-heart text-danger fs-2 mb-3 d-block"></i>
                        <h5 class="fw-bold text-white small">Avisos por E-mail</h5>
                        <p class="text-secondary" style="font-size: 0.75rem;">Notificações automáticas na sua caixa de entrada para você nunca perder prazos.</p>
                    </div>
                </div>
                <div class="col">
                    <div class="p-4 bg-dark bg-opacity-50 rounded-4 border border-secondary border-opacity-25 h-100 position-relative overflow-hidden card-hover-effect">
                        <div class="position-absolute top-0 end-0 p-3">
                            <span class="badge bg-primary small">BREVE</span>
                        </div>
                        <i class="bi bi-robot text-primary fs-2 mb-3 d-block"></i>
                        <h5 class="fw-bold text-white small">Chat com IA</h5>
                        <p class="text-secondary" style="font-size: 0.75rem;">Assistente pessoal 24/7 para tirar dúvidas sobre seus gastos.</p>
                    </div>
                </div>
                <div class="col">
                    <div class="p-4 bg-dark bg-opacity-50 rounded-4 border border-secondary border-opacity-25 h-100 position-relative overflow-hidden card-hover-effect">
                        <div class="position-absolute top-0 end-0 p-3">
                            <span class="badge bg-primary small">BREVE</span>
                        </div>
                        <i class="bi bi-magic text-purple fs-2 mb-3 d-block" style="color: #a855f7;"></i>
                        <h5 class="fw-bold text-white small">Análise Preditiva</h5>
                        <p class="text-secondary" style="font-size: 0.75rem;">Detecção de padrões e projeção de faturas futuras.</p>
                    </div>
                </div>
                <div class="col">
                    <div class="p-4 bg-dark bg-opacity-50 rounded-4 border border-secondary border-opacity-25 h-100 position-relative overflow-hidden card-hover-effect">
                        <div class="position-absolute top-0 end-0 p-3">
                            <span class="badge bg-primary small">BREVE</span>
                        </div>
                        <i class="bi bi-lightbulb text-warning fs-2 mb-3 d-block"></i>
                        <h5 class="fw-bold text-white small">Dicas de IA</h5>
                        <p class="text-secondary" style="font-size: 0.75rem;">Recomendações baseadas no seu perfil de consumo.</p>
                    </div>
                </div>
            </div>

            <div class="mt-5 text-center">
                <p class="text-secondary small fst-italic">...e muitas outras inovações impulsionadas por IA em fase de laboratório!</p>
            </div>
        </div>
    </section>

    <!-- About Project Section (With interactive spotlight) -->
    <section id="about" class="py-5 position-relative spotlight-area" style="background: #0b0f19; border-top: 1px solid rgba(255,255,255,0.05);">
        <div class="spotlight" id="mouse-spotlight"></div>
        
        <div class="container py-5 position-relative" style="z-index: 2;">
            <div class="row align-items-center g-5">
                <div class="col-lg-6">
                    <h2 class="fw-bold mb-4 text-white">Economize com assinaturas ativas que você não usa</h2>
                    <p class="text-secondary lead">
                        Nossa ferramenta foi desenhada para ser a solução definitiva contra as renovações automáticas esquecidas.
                    </p>
                    <p class="text-secondary">
                        Você registra suas assinaturas, define os detalhes de renovação e, em um único lugar, visualiza quanto está gastando por mês, ano ou qualquer período desejado.
                    </p>
                    <div class="mt-4 border-start border-primary border-4 ps-4 py-2">
                        <p class="mb-0 fw-semibold text-white">"Facilitamos o gerenciamento para que você tome as decisões. O controle é soberano."</p>
                    </div>
                    <div class="mt-5">
                        @if(Route::has('register'))
                            <a href="{{ route('register') }}" class="btn btn-custom-primary text-decoration-none">
                                <i class="bi bi-rocket-takeoff me-2"></i>Entrar Gratuitamente Agora
                            </a>
                        @else
                            <button type="button" class="btn btn-custom-primary text-decoration-none" data-bs-toggle="modal" data-bs-target="#accessRequestModal">
                                <i class="bi bi-rocket-takeoff me-2"></i>Solicitar Teste Agora
                            </button>
                        @endif
                    </div>
                </div>
                <div class="col-lg-6">
                    <div class="p-4 bg-dark bg-opacity-40 rounded-4 border border-secondary border-opacity-25 shadow-sm backdrop-blur">
                        <h5 class="fw-bold mb-4 d-flex align-items-center text-white"><i class="bi bi-shield-check text-primary me-2"></i>Compromisso com sua Gestão</h5>
                        <ul class="list-unstyled mb-0">
                            <li class="mb-3 d-flex align-items-start">
                                <i class="bi bi-check2-circle text-success me-3 mt-1"></i>
                                <span class="text-secondary"><strong>Privacidade por Design:</strong> Nem admins acessam seus dados financeiros.</span>
                            </li>
                            <li class="mb-3 d-flex align-items-start">
                                <i class="bi bi-check2-circle text-success me-3 mt-1"></i>
                                <span class="text-secondary"><strong>Gestão Transparente:</strong> Sem integrações ocultas ou cancelamentos indesejados.</span>
                            </li>
                            <li class="d-flex align-items-start">
                                <i class="bi bi-check2-circle text-success me-3 mt-1"></i>
                                <span class="text-secondary"><strong>Visão de 360 Graus:</strong> Projeções reais de gastos futuros baseadas nos seus ciclos.</span>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="footer text-center pb-5">
        <div class="container">
            <p class="mb-2 fw-light small">
                &copy; {{ date('Y') }} Minhas Assinaturas. Pensado por <strong>Humanos</strong>, desenvolvido por <strong>IA</strong> e ajustado novamente por <strong>Humanos</strong>.
            </p>
            <div class="mb-3">
                <a href="{{ route('terms') }}" class="text-secondary text-decoration-none small mx-2 opacity-75 hover-opacity-100">Termos de Uso</a>
                <span class="text-secondary opacity-25">|</span>
                <a href="{{ route('privacy') }}" class="text-secondary text-decoration-none small mx-2 opacity-75 hover-opacity-100">Privacidade</a>
            </div>
            <a href="https://carlossoares.dev" target="_blank" class="d-inline-block mt-2" style="color: #4b5563; text-decoration: none; font-size: 0.7rem; letter-spacing: 0.5px; transition: color 0.3s;" onmouseover="this.style.color='#94a3b8'" onmouseout="this.style.color='#4b5563'">
                carlossoares.dev
            </a>
        </div>
    </footer>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            let current = 1;
            const img1 = document.getElementById('preview-1');
            const img2 = document.getElementById('preview-2');

            setInterval(function() {
                if (current === 1) {
                    img1.style.opacity = '0';
                    img2.style.opacity = '1';
                    current = 2;
                } else {
                    img1.style.opacity = '1';
                    img2.style.opacity = '0';
                    current = 1;
                }
            }, 5000);

            // Cookie Consent Logic
            const cookieBanner = document.getElementById('cookie-consent-banner');
            const acceptBtn = document.getElementById('accept-cookies');
            
            if (!localStorage.getItem('cookies-accepted')) {
                setTimeout(() => {
                    cookieBanner.classList.remove('d-none');
                    cookieBanner.classList.add('animate-up');
                }, 2000);
            }

            acceptBtn.addEventListener('click', function() {
                localStorage.setItem('cookies-accepted', 'true');
                cookieBanner.style.transform = 'translateY(100%)';
                cookieBanner.style.opacity = '0';
                setTimeout(() => cookieBanner.remove(), 500);
            });

            // Efeito de Luz seguindo o Mouse
            const spotlight = document.getElementById('mouse-spotlight');
            const spotlightArea = document.querySelector('.spotlight-area');

            window.addEventListener('mousemove', (e) => {
                const rect = spotlightArea.getBoundingClientRect();
                const x = e.clientX;
                const y = e.clientY - rect.top;
                
                spotlight.style.left = `${x}px`;
                spotlight.style.top = `${y}px`;
            });

            // Efeito de Glow reagindo ao Scroll
            const dynamicGlow = document.getElementById('dynamic-glow-1');
            window.addEventListener('scroll', () => {
                const scrollValue = window.scrollY;
                const movement = scrollValue * 0.2;
                dynamicGlow.style.transform = `translateY(${movement}px) translateX(${movement * 0.5}px)`;
            });
        });
    </script>

    <!-- Cookie Consent Banner -->
    <div id="cookie-consent-banner" class="fixed-bottom d-none p-3" style="z-index: 9999; transition: all 0.5s ease-in-out;">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-lg-8">
                    <div class="p-4 bg-dark border border-secondary border-opacity-25 rounded-4 shadow-lg d-md-flex align-items-center justify-content-between gap-4" style="backdrop-filter: blur(15px); background: rgba(11, 15, 25, 0.9) !important;">
                        <div class="mb-3 mb-md-0">
                            <h6 class="fw-bold text-white mb-1"><i class="bi bi-cookie text-warning me-2"></i>Nós valorizamos sua privacidade</h6>
                            <p class="text-secondary small mb-0">Utilizamos cookies apenas para garantir a melhor experiência e segurança em nosso sistema. Ao continuar navegando, você concorda com nossa <a href="{{ route('privacy') }}" class="text-primary-custom text-decoration-none">Política de Privacidade</a>.</p>
                        </div>
                        <div class="flex-shrink-0">
                            <button id="accept-cookies" class="btn btn-primary px-4 py-2 fw-bold" style="border-radius: 50px;">Aceitar Cookies</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <style>
        .animate-up {
            animation: slideUp 0.8s cubic-bezier(0.16, 1, 0.3, 1) forwards;
        }
        @keyframes slideUp {
            from { transform: translateY(100%); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }
    </style>

    <!-- Access Request Modal -->
    <div class="modal fade" id="accessRequestModal" tabindex="-1" aria-labelledby="accessRequestModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content bg-dark border-secondary border-opacity-25 rounded-4 shadow-lg backdrop-blur" style="background: rgba(11, 15, 25, 0.9) !important;">
                <div class="modal-header border-0 pb-0">
                    <h5 class="modal-title text-white fw-bold" id="accessRequestModalLabel">Solicitar Acesso ao Teste</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <livewire:access-request-form />
                </div>
            </div>
        </div>
    </div>

    {{-- @livewireScripts removido: inject_assets=true já injeta automaticamente --}}
</body>
</html>

