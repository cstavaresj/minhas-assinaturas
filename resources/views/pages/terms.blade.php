<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-bs-theme="dark">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Termos de Uso | Minhas Assinaturas</title>
    @vite(['resources/css/app.scss', 'resources/js/app.js'])
    <style>
        body { background-color: #0b0f19; color: #a0b0d0; line-height: 1.6; }
        .content-card { background: rgba(255, 255, 255, 0.02); border: 1px solid rgba(255, 255, 255, 0.05); border-radius: 16px; padding: 40px; margin-top: 50px; margin-bottom: 80px; }
        h1, h2, h3 { color: #fff; font-weight: 800; }
        .text-primary-custom { color: #0d6efd; }
    </style>
</head>
<body>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-9">
                <div class="content-card shadow-lg">
                    <div class="text-center mb-5">
                        <a href="/" class="text-decoration-none d-inline-block mb-4">
                            <i class="bi bi-shield-check text-primary fs-1"></i>
                        </a>
                        <h1 class="display-4">Termos de Uso</h1>
                        <p class="text-secondary">Última atualização: Abril de 2026</p>
                    </div>

                    <h2 class="h4 mt-5 mb-3 text-primary-custom">1. Aceitação dos Termos</h2>
                    <p>Ao acessar e usar o sistema <strong>Minhas Assinaturas</strong>, você concorda em cumprir e estar vinculado a estes Termos de Uso. Se você não concordar com qualquer parte destes termos, não deverá utilizar o serviço.</p>

                    <h2 class="h4 mt-5 mb-3 text-primary-custom">2. Descrição do Serviço</h2>
                    <p>O Minhas Assinaturas é uma ferramenta de gestão financeira pessoal focada no rastreamento e controle de assinaturas recorrentes. O serviço é fornecido "como está", e não garantimos a precisão de dados externos (como valores de terceiros) que você venha a inserir manualmente.</p>

                    <h2 class="h4 mt-5 mb-3 text-primary-custom">3. Privacidade e Proteção de Dados</h2>
                    <p>Nossa prioridade máxima é sua privacidade. Operamos sob o princípio de <em>Privacy by Design</em>. Seus dados de assinaturas são isolados por tokens criptográficos e não são acessíveis nem mesmo pela nossa equipe administrativa.</p>

                    <h2 class="h4 mt-5 mb-3 text-primary-custom">4. Responsabilidades do Usuário</h2>
                    <ul>
                        <li>Manter a segurança de sua senha e conta.</li>
                        <li>Inserir informações verídicas e lícitas.</li>
                        <li>Não utilizar o sistema para fins fraudulentos ou ilegais.</li>
                    </ul>

                    <h2 class="h4 mt-5 mb-3 text-primary-custom">5. Modificações no Serviço</h2>
                    <p>Reservamos o direito de modificar, suspender ou descontinuar o serviço a qualquer momento, com ou sem aviso prévio, visando melhorias técnicas ou de segurança.</p>

                    <h2 class="h4 mt-5 mb-3 text-primary-custom">6. Contato</h2>
                    <p>Se você tiver dúvidas sobre estes Termos, entre em contato através do site <a href="https://carlossoares.dev" class="text-primary-custom text-decoration-none">carlossoares.dev</a>.</p>

                    <div class="mt-5 text-center">
                        <a href="/" class="btn btn-primary px-5 py-2 fw-bold" style="border-radius: 50px;">Voltar para o Início</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>

