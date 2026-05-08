# Checklist de Testes Manuais - Minhas Assinaturas

Este documento serve como guia para validacao manual das funcionalidades cobertas pelos testes automatizados, em ordem logica de execucao.

## Regras Atuais do Ambiente
- Cadastro publico desativado temporariamente.
- Login com Google desativado temporariamente.
- Apenas administradores podem criar e editar usuarios.

---

## 1) Preparacao do Ambiente
- [x] A01: Aplicacao sobe sem erro
  - Instrucao: Suba o projeto (`composer dev`) e confirme telas principais carregando.
- [x] A02: Banco migrado
  - Instrucao: Execute `php artisan migrate` e confirme sem erro.
- [x] A03: Sessao limpa para teste
  - Instrucao: Use aba anonima ou limpe cookies/sessao antes de iniciar.

---

## 2) Autenticacao Basica (Sem Cadastro/Google)
- [x] 004: Usuario pode ver a tela de login
- [x] 005: Erro ao logar com credenciais invalidas
- [x] 013: Tela de login renderiza
- [x] 014: Login com credenciais validas
- [x] 015: Login falha com senha errada
- [x] 017: Logout encerra sessao
- [x] 029: Confirmacao de senha para areas sensiveis

Itens desativados no contexto atual:
- [ ] 001: Usuario pode ver tela de registro (desativado)
- [ ] 002: Erro ao registrar com e-mail existente (desativado)
- [ ] 003: Registro bem-sucedido com dados validos (desativado)
- [ ] 006: Tela de registro renderiza corretamente (desativado)
- [ ] 007: Registro com consentimento LGPD funciona (desativado)
- [ ] 008: Registro sem consentimento LGPD bloqueado (desativado)
- [ ] 024: Botao Login com Google redireciona (desativado)
- [ ] 025: Erro no Socialite retorna para login (desativado)
- [ ] 026: Callback Google sem e-mail falha (desativado)
- [ ] 027: Cadastro automatico via Google (desativado)
- [ ] 028: Login Google para usuario existente (desativado)

---

## 3) Recuperacao de Conta e Verificacao
- [ ] 009: Solicitacao de reset de senha renderiza (nao implementado)
- [ ] 010: Link de reset de senha pode ser solicitado (nao implementado)
- [ ] 011: Tela de reset de senha renderiza com token (nao implementado)
- [ ] 012: Senha pode ser redefinida com token valido (nao implementado)
- [ ] 018: Tela de verificacao de e-mail aparece (nao implementado)
- [ ] 019: Verificacao de e-mail via link (nao implementado)
- [ ] 020: Verificacao falha com hash invalido (nao implementado)
- [ ] 021: Usuario ja verificado e redirecionado adequadamente (nao implementado)

---

## 4) Admin - Usuarios e Controle de Acesso
- [x] 039: Tela visual de gerenciamento de usuarios
- [x] 045: Lista completa de usuarios (Admin)
- [x] 048: Criar novo usuario via Admin
- [x] 050: Desativar usuario (Soft Delete)
- [x] 054: Usuario comum nao acessa assinaturas admin
- [x] 073: Usuario normal nao acessa gerenciamento de categorias
- [x] 030: Registro de sessao e User-Agent
- [x] 031: Acesso aos logs de atividade
- [x] 032: Filtrar logs por tipo de evento
- [x] 033: Ver detalhes de log especifico
- [x] 034: Paginacao de logs funciona
- [x] 035: Busca por nome de usuario nos logs
- [x] 036: Varredura de alertas manuais

---

## 5) Admin - Categorias
- [x] 072: Gerenciar categorias globais
- [x] 074: Criar categoria com nome valido
- [x] 075: Criar categoria com nome muito curto
- [x] 076: Criar categoria com nome muito longo (limite maximo: 80 caracteres)
- [x] 077: Criar categoria com payload XSS (exemplos: <script>alert(1)</script> | \"><img src=x onerror=alert(1)>)
- [x] 078: Nome XSS de categoria escapado na tela (exemplos: <img src=x onerror=alert(1)> | <svg onload=alert(1)>)
- [x] 079: Criar categoria com nome duplicado
- [x] 080: Criar categoria sem icone
- [x] 081: Criar categoria sem cor
- [x] 082: Icone com SQL Injection salvo como string
- [x] 083: Cor com valor invalido
- [x] 084: Editar categoria existente
- [x] 085: Editar categoria com nome duplicado
- [x] 086: Editar categoria mantendo o mesmo nome
- [x] 087: Deletar categoria sem assinaturas
- [x] 088: Deletar categoria com assinaturas (bloqueio)
- [x] 089: Cancelar exclusao limpa o modal
- [x] 090: Busca por categoria
- [x] 091: Busca por categoria com payload XSS
- [x] 092: Paginacao de categorias - proxima pagina
- [x] 093: Paginacao de categorias - anterior na pagina 1
- [x] 094: Resetar campos limpa entradas
- [x] 095: Slug gerado automaticamente
- [x] 096: Nome de categoria com acentos e especiais
- [x] 097: Icone com payload XSS renderizado com seguranca

---

## 6) Assinaturas e Dashboard
- [x] 118: Listagem de assinaturas carrega
- [x] 119: Criar nova assinatura via modal/form
- [x] 120: Exportar CSV de assinaturas
- [x] 121: Neutralizacao de formulas no CSV
- [x] 122: Importar CSV com deteccao de duplicatas
- [x] 123: Protecao contra inputs gigantes
- [x] 124: Busca por texto literal (SQL Injection)
- [x] 125: HTML/XSS na listagem (texto puro)
- [x] 126: Paginacao - proxima pagina na listagem
- [x] 127: Paginacao - pagina anterior
- [x] 128: Paginacao - saltar para pagina especifica
- [x] 129: Ordenacao por nome
- [x] 130: Ordenacao por valor
- [x] 131: Ordenacao por proximo vencimento
- [x] 132: Soma no dashboard ignora canceladas
- [x] 133: Soma no dashboard converte centavos corretamente
- [x] 134: Soma no dashboard lida com virgula e ponto
- [x] 136: Filtro de categoria mantem estado apos paginacao
- [x] 137: Selecao multipla para exclusao
- [x] 138: Confirmacao ao excluir assinatura unica
- [x] 139: Nome de categoria truncado quando muito longo
- [x] 053: Admin ve todas as assinaturas do sistema
- [x] 071: Admin ve assinaturas de todos os usuarios
- [x] 114: Dashboard isolado por Privacy Token
- [x] 055: Busca com SQL Injection tratada como string
- [x] 056: Busca com payload XSS escapada
- [x] 057: Ordenacao por campo invalido usa padrao
- [x] 058: Filtro de categoria com ID invalido
- [x] 059: Filtro de status invalido retorna vazio
- [x] 060: Paginacao acima do total nao quebra
- [x] 061: Busca vazia retorna todos
- [x] 062: Busca com match parcial
- [x] 063: Busca case-insensitive
- [x] 064: Busca com caracteres especiais
- [x] 065: Ordenacao alterna direcao ao clicar no mesmo campo
- [x] 066: Notas com XSS escapadas
- [x] 067: URL do servico com protocolo javascript neutralizada
- [x] 068: Combinacao de multiplos filtros
- [x] 069: Paginacao - anterior na primeira pagina
- [x] 070: Busca com string muito longa
- [x] 135: Indicador de vencido para datas passadas (reavaliar regra de negocio)

---

## 7) Perfil, Senhas, 2FA e LGPD
- [x] 104: Pagina de perfil renderiza
- [x] 102: Alterar/criar senha
- [x] 098: Pagina de gerenciamento de 2FA renderiza
- [x] 016: Redirecionamento para 2FA se ativo
- [x] 022: 2FA exige autenticacao previa
- [x] 023: Desafio 2FA aparece apos login correto
- [x] 105: Alterar e-mail para um ja existente falha
- [x] 106: Logout em outros dispositivos ao mudar senha
- [ ] 107: Verificacao de e-mail exigida apos mudar e-mail (nao implementado)
- [x] 108: Termos de uso renderiza corretamente
- [x] 109: Exclusao de conta com confirmacao de senha
- [x] 110: Download de dados em JSON (LGPD)
- [x] 111: Exportacao completa LGPD
- [x] 112: Revogacao de consentimento LGPD
- [x] 113: Cache/sessao invalidado apos exclusao
- [x] 116: Metricas do painel admin

---

## 8) Casos Extremos e Jobs
- [x] 140: CSV com caracteres especiais
- [x] 141: CSV com caracteres multibyte (UTF-8)
- [x] 142: Importar CSV com colunas fora de ordem
- [x] 143: CSV com formulas Excel protegido
- [x] 144: Bloqueio de URLs inseguras
- [x] 145: Job de limpeza de usuarios excluidos apos 30 dias




