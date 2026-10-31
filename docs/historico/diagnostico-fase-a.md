# Diagnóstico — Fase A (Sistema CA Jesus)

Data: 2026-10-23 · Base: `main` @ `fff3403`.

> **Este documento é um registro histórico e não é atualizado.** Ele descreve o projeto como estava em 2026-10-23 e cita caminhos que já não existem (`app/`, `bancoInteiro.sql`...). O estado atual está em [`arquitetura.md`](../arquitetura.md), e o que falta fazer, em [`plano.md`](../plano.md). As decisões que mudaram depois do diagnóstico estão em [Revisão de arquitetura](#revisão-de-arquitetura-2026-10-24), no fim deste documento.

Ambiente na data do diagnóstico: PHP 8.2.12 e **MariaDB 10.4.32** (XAMPP), sem Composer (instalado depois, na etapa 5). `php -l` passava em todos os 47 arquivos `.php` versionados.

Legenda: ✗ = rota quebrada · (morto) = sem nenhum chamador encontrado por busca no repositório.

---

## 1. Mapa do sistema

| Funcionalidade | Rotas (`public/index.php`) | Controller | Model / métodos | Banco | View | JS / endpoints chamados |
|---|---|---|---|---|---|---|
| **Autenticação** | `GET /`, `GET /login`, `POST /login`, `GET /logout` | `AuthController` + `AuthHelper`, `AuthMiddleware`, `AdminMiddleware` | `UsuarioModel::buscarPorEmail`, `autenticar`, `atualizarUltimoLogin` | `tbusuarios` | `layouts/auth/login.php` (sem layout) | form `POST /sistema_ca_jesus/public/login` |
| **Layout comum** | — | — | — | — | `layouts/main.php` | `GET /api/estoque/atual` (sino de estoque baixo); `/public/api/search/simple.php` ✗ (não existe) |
| **Dashboard** | `GET /dashboard`; `GET /api/dashboard/estatisticas` (morto) | `DashboardController` (estatísticas em métodos privados) | `ProdutoModel::contarTotal`, `listarTodos`; `MovimentacaoModel::getMovimentacoesHoje`, `getLucroTotal`, `getSaldoFinanceiro`, `listarTodas`, `getMovimentacoesUltimos7Dias` | `tbprodutos`, `tbmovimentacoes` (lucro virtual), `tbusuarios` | `layouts/dashboard/index.php` + `DiagnosticoHelper` + `partials/toast.php` | `charts.js` (`ChartManager`), Chart.js CDN sem versão |
| **Produtos** | `GET /produtos`; `GET /produtos/novo` ✗; `GET /produtos/editar/{id}` ✗; `POST /produtos/salvar`; `POST /produtos/atualizar/{id}`; `DELETE` e `POST /produtos/excluir/{id}`; `GET /produtos/listar` (morto); `GET /api/produtos/buscar` | `ProdutoController` | `ProdutoModel`; `FornecedorModel::listarTodos` | `tbprodutos`, `tbfornecedores` | `layouts/produto/index.php` + `toast.php` | `GET api/produtos/buscar?id=`, `DELETE produtos/excluir/{id}`, form → `salvar` / `atualizar/{id}` |
| **Movimentações** | `GET /movimentacoes`; `POST /movimentacoes/registrar`; `GET /api/movimentacoes/listar` (morto); `GET /api/estoque/atual` | `MovimentacaoController` | `MovimentacaoModel::registrar`, `listarTodas`; `ProdutoModel::listarTodos` | `INSERT tbmovimentacoes` → trigger `before_movimentacao_insert` (valida) → trigger `after_movimentacao_insert` (atualiza `tbprodutos.estoque_produto`) → trigger `tr_auditoria_estoque` (grava `tb_auditoria`) | `layouts/movimentacao/index.php` | `fetch(form.action)` seguido de `location.reload()` (resposta ignorada) |
| **Relatórios** | `GET /relatorios`; `POST /relatorios/gerar` (morto e falso: só loga “sucesso”); `GET /api/relatorios/dados` | `RelatorioController` (agrega tudo) | `MovimentacaoModel::buscarPorPeriodo`; `ProdutoModel::listarTodos` (`RelatorioModel` **não é usado**) | `tbmovimentacoes`, `tbprodutos`, `tbusuarios` | `layouts/relatorio/index.php` | `GET api/relatorios/dados` (2×); jsPDF, html2canvas, xlsx no navegador |
| **Usuários (admin)** | `GET /usuarios`, `/usuarios/novo`, `/usuarios/editar/{id}`, `/usuarios/buscar/{id}`; `POST /usuarios/salvar` + aliases `store`, `update/{id}`, `atualizar/{id}`; `POST /usuarios/toggle-status/{id}`; `POST` e `DELETE /usuarios/excluir/{id}`; `GET /api/usuarios/listar` (morto) | `UsuarioController` | `UsuarioModel` | `tbusuarios` (soft delete via `deletado_em`) | `layouts/usuario/index.php` (modal) e `layouts/usuario/form.php` (página avulsa, sem link no front) | `fetch usuarios/buscar/{id}`, `toggle-status`, `excluir`; ViaCEP |
| **Perfil** | `GET /usuarios/perfil`; `POST /usuarios/atualizarPerfil`; `POST /usuarios/alterarSenha` | `UsuarioController` | `UsuarioModel::buscarPorId`, `atualizarPerfil`, `alterarSenha` | `tbusuarios` | `layouts/usuario/perfil.php` | ViaCEP |
| **Fornecedores (admin)** | `GET /fornecedores`; `GET /fornecedores/novo` ✗; `GET /fornecedores/editar/{id}` ✗; `POST /fornecedores/salvar` + 3 aliases; `toggle-status`; `POST` e `DELETE excluir`; `GET /api/fornecedores/listar` (morto) | `FornecedorController` | `FornecedorModel` | `tbfornecedores`; contagem em `tbprodutos` | `layouts/fornecedor/index.php` | `fetch toggle-status`, `excluir`; ViaCEP |

Objetos de banco **sem uso pela aplicação**: procedures `registrar_movimentacao`, `registrar_movimentacao_segura`, `verificar_duplicacao_movimentacao`, `cadastrar_produto_simples`; tabela `tb_logs_movimentacao`; views `vw_produtos_lista`, `vw_movimentacoes_detalhadas`, `vw_relatorio_logs`, `vw_auditoria_estoque`. Só são referenciados pelos models mortos `EstoqueModel` e `LogModel`. `tb_auditoria` é gravada pelo trigger, mas só é lida pelo `LogModel` (morto).

---

## 2. Diagnóstico da arquitetura atual

A estrutura é um MVC organizado por tipo técnico, e na prática cada tela faz tudo sozinha:

- **O controller é o caso de uso.** Validação, regra de negócio, agregação de relatório, escolha de mensagem e formato de resposta ficam todos no mesmo método. Não existe camada intermediária, e para um sistema deste tamanho isso seria aceitável. O problema é que a mesma regra também foi escrita no model, no banco e no JS.
- **A view é uma segunda aplicação.** As cinco views principais têm entre 270 e 1.100 linhas, com JS de comportamento, validação, formatação (`formatCpf`, `formatCnpj`, `validateEmail` redefinidos em JS), chamadas de API com URL fixa e até o carregamento do próprio Tailwind e FontAwesome.
- **Não existe uma camada de infraestrutura confiável.** Há três arquivos de configuração, mas só um é carregado. São 30 chamadas a `session_start()` e quatro mecanismos de mensagem, dos quais só um funciona de verdade. Erros chegam ao usuário de três jeitos diferentes, e o `Database` renderiza `<script>`.
- **Há duas implementações de autenticação.** `AuthController` + `AuthHelper` + middlewares formam o fluxo ativo. `UsuarioController` tem um segundo fluxo completo (`login`, `autenticar`, `logout`, `verificarAutenticacao`, `verificarPermissao`, timeout próprio) sem rota que o chame. Ainda assim, `verificarAutenticacao`/`verificarPermissao` rodam em todos os métodos do controller, por cima do middleware.
- **Há código de “tentativa” acumulado:** procedures “seguras” com deduplicação de requisição, tabela de logs de movimentação, auditoria, `DiagnosticoHelper`, `verificarEstrutura()` com `ALTER TABLE` em runtime, 18 classes de exceção e 20 funções globais. Quase nada disso está ligado ao fluxo real.

Incoerências centrais:
1. A regra de estoque não tem dono (seção 3, hipótese 8).
2. A URL base não tem dono: são 53 linhas com caminho fixo e 33 com `BASE_URL . '/public'`, e o próprio `BASE_URL` não aponta para `/public`.
3. A mensagem para o usuário não tem dono, e por isso a maioria delas nunca aparece (problema C1).

---

## 3. Hipóteses da seção 1

| # | Hipótese | Veredito | Evidência |
|---|---|---|---|
| 1 | Sem `.gitignore`; ~400 logs versionados (~2,6 MB) | **CONFIRMADA** | `git ls-files armazenamento/logs` = 423 arquivos, 2,6 MB: 385 `diagnostico_dashboard_*`, 33 `sistema_*`, 2 `error_*`, 1 `diagnostico_permissoes_*`. |
| 1a | Alguns logs contêm “senha” | **PARCIAL** | 3 arquivos. Não há senha em texto puro: são URLs de rotas antigas (`/esqueci-senha`, `/reset-senha`), e uma delas leva um **token de redefinição** na query string. Os logs também têm IP, user-agent, nome de usuário e dados de movimentação. O remoto é `github.com/juliacancio-dev/Sistema_ca_jesus`, então isso já está no histórico publicado. |
| 1b | `AuthMiddleware` e `Router` gravam `$_SESSION` e `$_SERVER` inteiros a cada requisição | **PARCIAL** | `$_SESSION` é gravada a cada requisição em `Router.php:51`, `AuthMiddleware.php:13` e `index.php:63`, via `error_log` (vai para o log do PHP/Apache, não para `armazenamento/`). A sessão contém `user` (id, nome, email, tipo) e `csrf_token`. `$_SERVER` inteiro só aparece em `AuthMiddleware.php:26-41`, que também o **imprime na tela**; esse ramo hoje é inalcançável porque `/login` não passa pelo middleware. |
| 2 | Credenciais commitadas em `config/database.php` | **PARCIAL** | O arquivo está versionado (`root`, sem senha) mas **nunca é carregado**. As credenciais efetivas vêm de `config/config.php:10-14`, também versionado. |
| 3 | README e `raiz de diretorios.txt` desatualizados; `index.css` é sobra | **CONFIRMADA** | `README.md:45` cita Bootstrap; `:59` cita `app/Database/` com migrations; `:98-99` cita `config/database.example.php`; pastas aparecem com maiúscula (`Controllers/`). O `.txt` lista `.qodo/`, `migrations/`, `TesteDiagnostico.php`, `form.php` de produto e movimentação: nada disso existe. `index.css` tem `#root` e não é referenciado. |
| 4 | Configuração em 3 lugares com valores divergentes; `config/config.php` incluído 2× | **PARCIAL** | Existem 3 arquivos, mas **só `config/config.php` é carregado** (`index.php:6` e `:22`). `app/core/config.php` (save_path de sessão, `Config::SESSION_TIMEOUT`, `BASE_URL='/sistema_ca_jesus'`) e `config/database.php` são código morto: o diretório de sessões, o timeout e `Config::` nunca valem. **`BASE_URL` efetivo = `http://HOST/sistema_ca_jesus`, sem `/public`.** |
| 5 | `/sistema_ca_jesus/public` fixo em ~50 lugares | **CONFIRMADA** | 53 linhas em 18 arquivos, incluindo `.htaccess:11` e `public/.htaccess:3`. Mais 33 usos de `BASE_URL`, quase todos concatenando `'/public'` à mão. |
| 6 | `session_start()` em vários pontos | **CONFIRMADA** | 30 chamadas em 13 arquivos. `FornecedorController:43,57,67,106` chama sem checar o status (gera *Notice* “session already active”). |
| 7 | `bd_sistema` × `bd_Sistema` | **CONFIRMADA** | `bancoInteiro.sql:1-2` cria/usa `bd_sistema`; `:118,128,198,483,553,670` fazem `USE bd_Sistema`; `:557-607` filtram `TABLE_SCHEMA = 'bd_Sistema'`. Num MySQL/MariaDB em Linux o script falha na linha 118. Os logs têm 9 ocorrências de `Unknown database 'bd_sistema'`. |
| 8 | Regra “saída ≤ estoque” em ≥4 lugares; saldo atualizado pelo trigger e por `atualizarEstoqueProduto` | **CONFIRMADA, e são 6 lugares** | (1) JS `movimentacao/index.php:203-213`; (2) `MovimentacaoController:49-70`; (3) `MovimentacaoModel:19-21`; (4) trigger `before_movimentacao_insert` (`sql:131-148`); (5) procedure `registrar_movimentacao` (`sql:230-238`); (6) procedure `registrar_movimentacao_segura` (`sql:433-441`). As duas procedures não são usadas. O saldo é atualizado **só** pelo trigger `after_movimentacao_insert` (`sql:151-165`); `atualizarEstoqueProduto` (`Model:70-77`) é privado e nunca chamado. Nenhuma das verificações trava a linha (`FOR UPDATE`), então duas saídas simultâneas podem deixar o saldo negativo (risco teórico com um operador só). |
| 9 | Lucro calculado no PHP e derivável no banco | **CONFIRMADA** | `lucro_movimentacao` é coluna **gerada VIRTUAL** (`sql:68-74`). O PHP calcula (`Model:23-26`) e **envia um valor** para ela (`:35,47`). O MariaDB ignora com aviso, por isso funciona hoje (os logs mostram registros com sucesso); o MySQL 5.7+/8 rejeita com erro 3105. O cálculo em PHP é morto e quebra a portabilidade. |
| 10 | Movimentação referencia produto por `marca_produto`; controller percorre `listarTodos()` | **CONFIRMADA** | FK por texto (`sql:62,77`), sem `ON UPDATE`: **renomear um produto que já tem movimentações falha** (RESTRICT). O controller varre todos os produtos para achar um (`Controller:52-57`) e o model faz a mesma busca de novo (`Model:14,60-68`). |
| 11 | `EstoqueModel` e `LogModel` sem referência | **CONFIRMADA** | Zero referências fora de si mesmos. `LogModel` ainda chama `limpar_logs_antigos` e `vw_relatorio_duplicacoes`, que **não existem** no SQL. Achado novo: `RelatorioModel` também não é usado por ninguém. |
| 12 | `DiagnosticoHelper` usado só pelo dashboard; gerou centenas de logs | **CONFIRMADA** | Chamado em `dashboard/index.php:72-93,313`. Grava **um arquivo novo a cada acesso** ao dashboard (`DiagnosticoHelper.php:309-323`), daí os 385 arquivos. |
| 13 | `main.php` chama `/public/api/search/simple.php`, que não existe | **CONFIRMADA** | `main.php:428`. Não existe `public/api/`, e `public/.htaccess:22-25` bloquearia qualquer `.php` direto. |
| 14 | `csrf_field()` nunca usado; CSRF validado? | **PARCIAL** | `csrf_field()` e `csrf_token()` estão mortos. O CSRF **é** validado em `UsuarioController::salvar`, `atualizarPerfil` e `alterarSenha` (`:94,412,451` → `:623-630`). O formulário de fornecedor envia o token (`fornecedor/index.php:209`), mas o controller não valida. Produtos, Movimentações, `toggle-status` e `excluir` (todos) não têm CSRF. |
| 15 | `verificarEstrutura()` em Usuario/FornecedorModel | **CONFIRMADA** | Nenhum chamador. O SQL repete a mesma lógica (`sql:555-668`). |
| 16 | Classes de `Exceptions.php` | **PARCIAL** | São 18 classes. Só 4 são lançadas (`DatabaseException`, `SecurityException`, `MethodNotAllowedException`, `ForbiddenException`) e só `DatabaseException` é capturada pelo tipo (`AuthController:76`), num `catch` que dá exatamente a mesma resposta do `catch (Exception)` seguinte. As outras 14 nunca são usadas. |
| 17 | `.htaccess` das subpastas de `app/` redundantes | **CONFIRMADA** | `app/.htaccess` (`Deny from all`) já vale recursivamente, e `.htaccess:5` já dá 403 em `^app/`. Os 6 das subpastas não acrescentam nada. Recomendo manter `app/.htaccess` (defesa se o `mod_rewrite` estiver desligado). |
| 18 | Formatação duplicada: `functions.php` × `FormatHelper` | **PARCIAL** | `functions.php` só **delega** para `FormatHelper`; não reimplementa nada. De ~25 funções globais, **só `formatCpf` é usada** (`perfil.php:97`); o resto está morto. A duplicação real é PHP × JS: `formatCpf`, `formatCnpj` e `validateEmail` são redefinidos inline nas views, e `formatCnpj` aparece **duas vezes no mesmo arquivo** (`fornecedor/index.php:658` e `:780`). |
| 19 | CNPJ: `FornecedorController::validarCNPJ` × `validateCnpj()` | **CONFIRMADA** | Mesmo caso para CPF (`UsuarioController::validarCpf` × `validateCpf`). As cópias de `functions.php` estão mortas, então consolidar é só apagá-las. |
| 20 | Rotas-alias; quais o front usa | **CONFIRMADA** | O front usa só `/usuarios/salvar` (`usuario/index.php:239`, `form.php:3`) e `/fornecedores/salvar` (`fornecedor/index.php:207`). `store`, `update/{id}` e `atualizar/{id}` não têm chamador. Para excluir, o front usa `POST` em usuários/fornecedores e `DELETE` em produtos; a outra variante de cada um não tem chamador. |
| 21 | ViaCEP copiado em 3 views | **CONFIRMADA** | `fornecedor/index.php:569`, `usuario/index.php:780`, `usuario/perfil.php:323`. |
| 22 | Três mecanismos de mensagem; `flash` é exibido? | **CONFIRMADA, e é pior** | São quatro: `$_SESSION['error'/'success']`, `$_SESSION['flash']`, `$_SESSION['toast_error']` e toasts JS. **`flash` é gravado em 12 pontos e lido em nenhum.** `error`/`success` são movidos para `$error`/`$success` por `Controller::renderView:10-18`, mas **nenhuma view imprime essas variáveis**. Ver problema C1. |
| 23 | `fetch` com URL montada de formas diferentes | **CONFIRMADA** | Literal `/sistema_ca_jesus/public/...` (fornecedor, usuário, perfil); `<?= BASE_URL ?>/public/...` (produto, relatório, main); `form.action` (movimentação). `app.js:makeRequest` existe e não é usado. |
| 24 | `RelatorioController` agrega dados | **CONFIRMADA** | `:63-223`: intervalo de datas, totais, séries diárias, ranking de mais vendidos. |
| 25 | `DashboardController` calcula estatísticas | **CONFIRMADA** | E carrega **todas** as movimentações para exibir 5 (`:67-71`) e todos os produtos para somar o estoque (`:45-49`). Uma query resolveria cada caso. |
| 26 | `Controller::renderView` faz demais | **CONFIRMADA** | Injeta `usuarioAtual` (que nenhuma view usa: `main.php` busca o usuário de novo), move mensagens, decide o layout pelo prefixo do caminho (`:34`) e imprime HTML de erro com trace (`:67-81`). |
| 27 | `RelatorioModel::exportarCSV` mistura persistência e formato | **CONFIRMADA, mas é código morto** | `RelatorioModel` não é usado; a exportação é feita no navegador (xlsx/jsPDF). |
| 28 | Arquivos muito grandes | **CONFIRMADA** | `usuario/index.php` 1.114 linhas (65 KB); `relatorio/index.php` 854; `fornecedor/index.php` 839; `UsuarioModel` 762; `UsuarioController` 726; `main.php` 661. |
| 29 | `error_log` informal em quase todo arquivo | **CONFIRMADA** | 90 chamadas em 12 arquivos. “tamo junto”, “deu ruim”: `index.php:51,53,131`; “Tenta de novo mais tarde?” é exibido ao usuário (`:136`). |
| 30 | `Database` chama `ErrorHelper::displayToast()` | **CONFIRMADA** | `Database.php:24-27`: imprime `<script>` antes de qualquer HTML. |
| 31 | `ErrorHelper`, `LogHelper` e `error_log` sem estratégia única | **CONFIRMADA** | `ErrorHelper::logSuccess` grava “sucesso” com a categoria `'Teste de Sistema'` em produção. |

---

## 4. Problemas classificados

### CRÍTICO

| ID | Problema | Tipo de correção | Evidência |
|---|---|---|---|
| C1 | **Quase nenhuma mensagem de erro ou sucesso chega ao usuário.** `flash` nunca é lido; `$error`/`$success` nunca são impressos. No login, `renderView` apaga `$_SESSION['error']` (`Controller.php:10-13`) **antes** de a view lê-lo (`login.php:76`), então “Email ou senha incorretos” e “usuário inativo” nunca aparecem. Em Movimentações, o JS faz `fetch` e `reload` sem ler a resposta (`movimentacao/index.php:225-229`): uma saída recusada pelo servidor some em silêncio. | CORREÇÃO | ver hipótese 22 |
| C2 | **Todo fornecedor salvo pelo formulário fica inativo.** `processarDados` usa `'ativo' => isset($_POST['ativo']) ? 1 : 0` (`FornecedorController:196`), mas o formulário não tem campo `ativo` (`fornecedor/index.php:207-…`). Criar ou editar grava `ativo_fornecedor = 0`. | CORREÇÃO | |

### ALTO

| ID | Problema | Tipo | Evidência |
|---|---|---|---|
| A1 | Logs com IP, user-agent, nome de usuário, dados de negócio e um token de redefinição de senha estão versionados **e publicados no GitHub**. Remover do HEAD não limpa o histórico. | Higiene / segurança | hipótese 1a |
| A2 | `ENVIRONMENT` fixo em `'development'` (`index.php:2-4`): `display_errors=1` e `renderView` mostra arquivo, linha e trace na tela (`Controller.php:71-75`). | Segurança | |
| A3 | CSRF só existe em 3 ações de Usuários; Produtos, Movimentações, Fornecedores, ativar/inativar e excluir não têm. | MELHORIA | hipótese 14 |
| A4 | Regra de estoque em 6 lugares, sem lock (ver seção 6). | REFATORAÇÃO + SCHEMA | hipótese 8 |
| A5 | Rotas que quebram: `/produtos/novo` e `/produtos/editar/{id}` chamam métodos que não existem. É um `Error`, não `Exception`, e `index.php:129` só captura `Exception`, então vira *fatal error*. `/fornecedores/novo` e `/editar/{id}` renderizam `fornecedor/form`, que não existe. | CORREÇÃO (provavelmente remoção) | `index.php:76-77,109-110`; `FornecedorController:25,33` |
| A6 | Redirecionamentos para `BASE_URL . '/dashboard'` (`AdminMiddleware:13`) e `BASE_URL . '/login'` (`main.php:12`) vão para `/sistema_ca_jesus/dashboard`, **sem `/public`**. O `.htaccess` raiz reescreve para `public/`, mas o `Router` só remove o prefixo `/sistema_ca_jesus/public` (`Router.php:40-45`), então o resultado provável é **404 JSON** quando um funcionário abre `/usuarios`. Confirmar no teste manual. | CORREÇÃO | |
| A7 | `bancoInteiro.sql` não roda em servidor case-sensitive (hipótese 7). O usuário seed “Maria” tem hash inválido (`'$$2y$...'`, `sql:683`) e não consegue logar. | CORREÇÃO (script) | |
| A8 | Coluna virtual `lucro_movimentacao` recebe valor do PHP: funciona no MariaDB, quebra no MySQL. | CORREÇÃO | hipótese 9 |
| A9 | FK por `marca_produto` sem `ON UPDATE`: renomear um produto com histórico falha, e o usuário vê `SQLSTATE[23000]...` cru. | MUDANÇA DE SCHEMA | `sql:77`; `ProdutoController:181` |

### MÉDIO

- **M1** Escape na entrada, e não na saída. `UsuarioController::sanitizarInput` (`:605-608`) e `FornecedorController::processarDados` (`:185-195`) gravam `htmlspecialchars()` no banco. “D'Ávila” vira `D&#039;Ávila`, e os limites de tamanho são medidos no texto já escapado. *(CORREÇÃO, exige cuidado com dados já gravados)*
- **M2** XSS armazenado. O nome do produto entra em `innerHTML` sem escape (sino em `main.php:613`, tabelas de `relatorio/index.php`), e `login.php:78` imprime a mensagem sem escape. *(CORREÇÃO)*
- **M3** `FornecedorController::excluir` nunca devolve o 409 “há produtos vinculados”: o model converte `PDOException` em `DatabaseException` (`FornecedorModel:237-240`) e o controller espera `PDOException` (`:91`). Cai em “Erro inesperado”. *(CORREÇÃO)*
- **M4** Autorização em dobro com regras diferentes. `UsuarioController::index` aceita `'funcionario'` (`:20`), mas a rota exige admin. Há um timeout próprio de 2 h em `UsuarioController:642` e outro em `AuthHelper:109`. *(REFATORAÇÃO)*
- **M5** Configuração: só um arquivo é carregado, e duas vezes; `BASE_URL` não aponta para `public`; 30 `session_start()`. *(REFATORAÇÃO)*
- **M6** Frontend carrega Tailwind **duas vezes em versões diferentes**: 2.2.19 CSS no layout e o Play CDN v3 (feito para desenvolvimento) no topo de cada view. FontAwesome é carregado três vezes; Chart.js vem sem versão fixa. `showToast` é definido em `app.js:16` e redefinido em `main.php:651`. *(REFATORAÇÃO)*
- **M7** O dashboard grava um arquivo de diagnóstico por acesso e carrega tabelas inteiras para mostrar 5 linhas e uma soma. *(REFATORAÇÃO)*
- **M8** Mensagem técnica exposta ao usuário: `"Erro ao cadastrar produto: " . $e->getMessage()` (`ProdutoController:111,118,181`), além de `MovimentacaoController:93`. *(CORREÇÃO)*
- **M9** Validação de usuário pelo modal: em caso de erro, `salvar` redireciona para `/usuarios/novo` ou `/editar/{id}` (`:101-123`), uma página diferente da que o usuário estava. *(CORREÇÃO)*

### BAIXO

- **B1** 90 `error_log` de debug com tom informal; mistura de português e inglês nos nomes (`getDados`, `usuariosJson`, `getMovimentacoesHoje`, `listarTodas`/`listarTodos`).
- **B2** O `Router` responde 404 em JSON também para páginas HTML (`Router.php:83-84`).
- **B3** Limiar de “estoque baixo” espalhado: 5 no sino (`main.php:603`), 10/5 no badge (`movimentacao/index.php`), `Config::ESTOQUE_MINIMO_PADRAO = 10` (morto), `getBaixoEstoque($limite = 10)` (morto).
- **B4** Regra “custo ≤ venda” aplicada também a **entradas** (`MovimentacaoController:44`). Confirmar se é intencional: numa compra, o preço de venda não participa.
- **B5** `DATE(m.data_movimentacao) BETWEEN ? AND ?` impede o uso do índice `idx_movimentacao_data`. Irrelevante no volume atual.
- **B6** `.htaccess` com sintaxe do Apache 2.2 (`Order/Deny`); funciona via `mod_access_compat`.
- **B7** README, `raiz de diretorios.txt` e `index.css` desatualizados ou sobrando.

---

## 5. Código morto (com evidência)

Critério: busca textual por nome de classe, método, função, rota ou arquivo em `app/` e `public/` (PHP e JS) sem nenhum chamador fora da própria definição.

| Item | Evidência |
|---|---|
| `app/core/config.php` | Nenhum `require`; `Config::` nunca usado. |
| `config/database.php` | Nenhum `require`. |
| `app/models/EstoqueModel.php`, `LogModel.php`, `RelatorioModel.php` | Zero referências. |
| `app/helpers/DiagnosticoHelper.php` | Só a view do dashboard; é ferramenta de debug. |
| `app/helpers/functions.php` | Só `formatCpf` é usada (`perfil.php:97`); as outras ~24 funções não têm chamador. |
| `app/core/Exceptions.php` | 14 de 18 classes nunca usadas. |
| `app/views/partials/header.php`, `sidebar.php`, `loading.php` | Nenhum `include` (`main.php` tem sua própria versão). |
| `UsuarioController::login`, `autenticar`, `logout`, `logarTentativaLogin` | Sem rota. `autenticar` ainda redireciona para `/dashboard/admin`, que não existe. |
| `UsuarioController::verificarAutenticacao`, `verificarPermissao`, `iniciarSessao` | Duplicam o middleware; ficam redundantes depois da consolidação. |
| `UsuarioModel::verificarEstrutura`, `verificarEmail`, `buscarRecentes`, `obterEstatisticas`, `limparUsuariosDeletados`, `contarPorTipo`, `contarAtivos`, `contarTotal`, constante `COLUMN_MAP` | Sem chamador. |
| `FornecedorModel::verificarEstrutura`, `contarTotal`, constante `COLUMN_MAP` | Sem chamador. |
| `ProdutoModel::getBaixoEstoque`, `atualizarSomenteNome`, `contarPorFornecedor` | Sem chamador; o último duplica `FornecedorModel::contarProdutosVinculados`. |
| `MovimentacaoModel::atualizarEstoqueProduto`, `getTotalEntradas`, `getTotalSaidas` | Sem chamador. |
| `Controller::isAjax`, `getRequestData`, `renderJson`; `AuthController::$db` | Sem chamador. |
| `Router::put`; `Database::query`, `exec`, `executeTransaction` | `query`/`exec` só são usados pelos models mortos e por `verificarEstrutura`. |
| `AuthMiddleware` ramo “LOOP/REDIRECT” (`:24-45`) | `/` e `/login` não usam o middleware. |
| Rotas sem chamador no front | `/produtos/listar`, `POST /produtos/excluir`, `/api/dashboard/estatisticas`, `/api/movimentacoes/listar`, `/api/usuarios/listar`, `/api/fornecedores/listar`, `/relatorios/gerar`, aliases `store`/`update`/`atualizar` (usuários e fornecedores), `DELETE` de usuários e fornecedores. **Decisão sua:** `/usuarios/novo`, `/usuarios/editar/{id}` e `form.php` funcionam, mas o front não tem link para eles. |
| Rotas quebradas | `/produtos/novo`, `/produtos/editar/{id}`, `/fornecedores/novo`, `/fornecedores/editar/{id}`. |
| JS | Busca global de `main.php` (endpoint inexistente). `app.js`: `makeRequest`, `formatNumber`, `formatCurrency`, `formatDate`, `debounce`, `showLoading`/`hideLoading`, `SistemaCAJesus`. `charts.js`: `initializeDashboardCharts`, `createVendasChart`, `createLucroChart`. |
| `.htaccess` em `app/controllers`, `core`, `helpers`, `middleware`, `models`, `views` | Redundantes (hipótese 17). |
| Banco | 4 procedures, `tb_logs_movimentacao`, 4 views (seção 1). Remover é **mudança de schema** e vai para migração. `tb_auditoria` + `tr_auditoria_estoque`: **decisão sua**, porque gravam um histórico de saldo que nenhuma tela mostra. |
| Raiz | `index.css`, `raiz de diretorios.txt`. |

---

## 6. Duplicações reais e como consolidar

| Conceito | Cópias | Consolidação |
|---|---|---|
| **Saída ≤ estoque** | 6 (JS, controller, model, trigger, 2 procedures) | Ver decisão D1 abaixo: um caso de uso em PHP. O JS pode manter um *aviso* de usabilidade, lendo o estoque do `data-estoque`, sem ser a regra. |
| **Atualizar saldo** | trigger `after_insert` + `atualizarEstoqueProduto` (morto) + 2 procedures | O mesmo caso de uso da D1. |
| **Lucro** | PHP (`Model:23-26`) + coluna virtual | Mantém a coluna virtual (é derivação pura) e para de enviar o valor pelo PHP. |
| **Buscar produto por marca** | `Controller:52-57` (loop) + `Model::buscarProdutoPorMarca` | Uma query por `id_produto` (o `<select>` passa a enviar o id). |
| **Validação CPF / CNPJ** | controller + `functions.php` (morto) | Apaga a cópia morta. CPF fica em Usuários e CNPJ em Fornecedores, cada um usado por um slice só. |
| **Formatação** | `FormatHelper` + wrappers globais + JS inline | `Shared/Formatacao.php`, usado por 2 slices (telefone, por exemplo). Em JS, um `public/assets/js/formatacao.js`. |
| **ViaCEP** | 3 views | `public/assets/js/cep.js`, com uma função `preencherEndereco(cep, campos)`. |
| **Mensagens** | 4 mecanismos | **Um:** `Session::flash('success'\|'error', msg)`, exibido pelo layout e pela tela de login. Respostas AJAX em JSON `{success, message}` exibidas por um único `showToast` (`app.js`). Sai `toast_error`/`displayToast`. |
| **URL base** | 53 fixos + 33 `BASE_URL.'/public'` + 3 estilos de `fetch` | `BASE_URL` derivado uma vez de `SCRIPT_NAME`, já apontando para `public`. Uma função `url('/produtos')` no PHP; no JS, `window.APP_BASE_URL` definido uma vez no layout e lido por um `api(path, opts)` em `app.js`. Os `.htaccess` passam a usar caminho relativo (sem `RewriteBase` fixo). |
| **Autenticação e autorização** | middleware + `UsuarioController` (ativo e morto) + 2 timeouts | Só os middlewares. O timeout fica numa constante da configuração. |
| **Rotas** | aliases + `POST`/`DELETE` duplicados | Uma rota por ação: a que o front usa. |
| **Contar produtos por fornecedor** | `ProdutoModel::contarPorFornecedor` + `FornecedorModel::contarProdutosVinculados` | Fica a de Fornecedores. |
| **Listagens com colunas iguais** | `FornecedorModel::listarTodos`, `buscarPorId`, `buscar` repetem o mesmo `SELECT ... AS ...` de 15 colunas (idem em `UsuarioModel`, 6 vezes) | Uma constante `SELECT_COLUNAS` privada por model. É o mesmo conceito e muda junto. |

Semelhança acidental, que **não** deve ser unificada: `validarDados` de Usuário × Fornecedor (as regras de endereço parecem iguais, mas são entidades diferentes e podem divergir); os modais de Usuário × Fornecedor.

### D1 — Onde mora a regra de estoque *(decisão obrigatória)*

**OPÇÃO A — Banco (triggers como fonte única)**
- *Vantagens:* já existe; garante a regra mesmo para `INSERT` feito fora da aplicação (phpMyAdmin); a atualização do saldo é atômica no mesmo statement.
- *Desvantagens:* a regra fica invisível para quem lê o PHP (o “onde se registra uma saída?” exige abrir o SQL); a mensagem vem como exceção SQLSTATE 45000, que precisa ser traduzida; não dá para testar com PHPUnit sem banco; o trigger lê o saldo sem lock, então continua com a condição de corrida; cada mudança na regra exige reaplicar o trigger no banco.
- *Impacto:* remover as checagens de controller/model/JS e as procedures; tratar 45000 no controller.

**OPÇÃO B — PHP como fonte única (caso de uso `RegistrarMovimentacao`)**
- *Vantagens:* a regra fica num arquivo PHP só, legível e testável; mensagens controladas; resolve a corrida com `SELECT ... FOR UPDATE` ou com `UPDATE tbprodutos SET estoque = estoque - ? WHERE id = ? AND estoque >= ?` (verificando `rowCount()`), dentro de uma transação.
- *Desvantagens:* um `INSERT` manual no banco não é validado; exige **mudança de schema** (remover os triggers `before`/`after_movimentacao_insert`).
- *Impacto:* migração SQL que derruba os triggers + caso de uso novo + ajuste do controller. Opcionalmente, `CHECK (estoque_produto >= 0)` (o MariaDB 10.4 aplica). Não é cópia da regra: é a garantia de que o saldo nunca fica negativo, qualquer que seja a origem do `UPDATE`.

**RECOMENDAÇÃO: B + `CHECK (estoque_produto >= 0)`.** Num sistema mantido por uma pessoa que trabalha em PHP, encontrar a regra conta mais do que proteger contra `INSERT` manual. A regra passa a ter um dono e um teste, e o `CHECK` fica como rede de segurança barata sem duplicar a lógica de negócio.

---

## 7. Responsabilidades fora do lugar

| Onde está | O que faz | Para onde vai |
|---|---|---|
| `MovimentacaoController::registrar` | Validação de domínio, busca de produto, regra de estoque | Caso de uso `Movimentacoes/RegistrarMovimentacao.php`; o controller só lê `$_POST`, chama e responde. |
| `RelatorioController::montarDadosFinanceiros` / `montarDadosEstoque` / `determinarIntervaloDatas` | Agregação de relatório | `Relatorios/RelatorioFinanceiro.php` e `RelatorioEstoque.php`: funções puras, ótimas para PHPUnit. |
| `DashboardController` (privados) | Estatísticas via `array_sum`/`array_slice` sobre tabelas inteiras | `Dashboard/DashboardConsultas.php` com queries agregadas (`SUM`, `LIMIT 5`). |
| `Controller::renderView` | Layout por prefixo, injeção de usuário e mensagens, HTML de erro | `render($view, $data, $layout = 'layout')` explícito; mensagens lidas pelo layout via `Session`; erro tratado num lugar só, no `index.php`. |
| `Database::__construct` | Renderiza toast | Só lança exceção. |
| `main.php:1-30` | Verifica login e imprime trace | O middleware já garante o login; o layout só desenha. |
| Views (`dashboard/index.php:72-102`) | Logging e diagnóstico | Remover. |
| Views (todas) | Carregam Tailwind e FontAwesome | Só o layout. |
| JS das views | Regra de estoque, validação de CPF e e-mail | Fica só como aviso de UX; a regra real fica no backend. |
| `AuthHelper::requireAuth` / `requireAdmin` | Terceira implementação de checagem de acesso (morta) | Remover. |
| `UsuarioController` | Autenticação, sessão, logging e CSRF próprios | `Core/Session` (CSRF, flash) + middlewares. |

---

## 8. Overengineering e abstrações sem função

- **`Database`**: singleton que só repassa 9 métodos do PDO, com `__clone`/`__wakeup`. → `Database::connection(): PDO` (conexão preguiçosa) e os models usam o PDO direto. Sai o wrapper e fica a única coisa útil: um ponto que abre a conexão.
- **`Exceptions.php`, 18 classes**: → apagar. Usar `\DomainException` nativa para violação de regra de negócio (mensagem segura para mostrar) e tratar o resto como erro genérico logado. Zero classes próprias.
- **`ErrorHelper` + `LogHelper` + `DiagnosticoHelper` + 90 `error_log`**: → um `ini_set('error_log', .../armazenamento/logs/php-erros.log)` no bootstrap e `error_log()` **só para erros**. Não é preciso uma classe de log.
- **Procedures “seguras” com deduplicação por `request_id` + `tb_logs_movimentacao` + 4 views SQL**: infraestrutura de auditoria que nunca foi ligada. → remover por migração.
- **`verificarEstrutura()` com `ALTER TABLE` em runtime e blocos `INFORMATION_SCHEMA` no SQL**: o schema é responsabilidade do script e das migrações, não do model.
- **`COLUMN_MAP` não usado, `private $tableName` interpolado em SQL**: indireção sem ganho. → nome da tabela literal na query.
- **Hash Argon2id com parâmetros manuais** (`UsuarioController:611-615`): `password_hash($s, PASSWORD_DEFAULT)` basta e fica coerente com os seeds em bcrypt.
- **Pontos que estão bons e ficam**: `Router` (simples e suficiente, só precisa de 404 HTML e de capturar `Throwable`); `Controller` base enxuto; PDO com prepared statements em todo lugar (nenhum SQL injection encontrado); soft delete de usuários; coluna virtual de lucro; `session_regenerate_id` no login.

---

## 9. Estrutura proposta

### Decisões *(obrigatórias)*

**D2 — Namespaces: Composer × autoload manual**
- *A — Composer PSR-4:* padrão e já declarado. Mas o Composer **não está instalado** nesta máquina, `vendor/` precisa ser gerado em cada ambiente, e não há nenhuma dependência para justificar isso hoje.
- *B — manter o autoloader manual por pastas:* zero esforço, mas a lista de caminhos cresce com cada slice e nomes de classe podem colidir.
- *C — namespaces `App\...` + um autoloader PSR-4 de ~10 linhas em `public/index.php`:* mesmo mapeamento do `composer.json` (`App\` → `app/`), sem instalar nada. Se um dia entrar o PHPUnit, `composer dump-autoload` passa a funcionar sem mudar nenhuma classe.
- **RECOMENDAÇÃO: C.** Tem a organização do A sem a dependência operacional. Exige renomear pastas para PascalCase (`app/Core`, `app/Features`), que no Windows precisa de `git mv` em dois passos.
- *Revisado em 2026-10-24: passa a ser **A**. Ver [Revisão de arquitetura](#revisão-de-arquitetura-2026-10-24).*

**D3 — Onde ficam as views**
- *A — dentro do slice (`Features/Produtos/views/index.php`):* tudo o que muda junto fica junto; é o objetivo do slice.
- *B — `app/views/<feature>/`:* familiar, mas mantém a organização por tipo técnico.
- **RECOMENDAÇÃO: A.** O layout e os partials compartilhados vão para `Shared/views/`.

**D4 — JS das telas**
- *A — continuar inline:* permite interpolar PHP, e é exatamente isso que espalhou URL fixa e duplicou funções.
- *B — `public/assets/js/<feature>.js`:* cacheável, sem PHP no meio. Os dados chegam por `data-*` ou por um `<script type="application/json">`, e a URL base por `window.APP_BASE_URL`.
- **RECOMENDAÇÃO: B**, feito na etapa de views, uma tela por vez.

**D5 — Perfil separado de Usuários?**
- *A — slice `Perfil/` próprio:* casos de uso e permissões diferentes (qualquer usuário, só os próprios dados). Mas precisaria ler e gravar `tbusuarios` fora do dono, ou levar o model para `Shared`.
- *B — dentro de `Usuarios/`, com um `PerfilController` separado:* mesma tabela, mesmo model, mesmas validações de endereço; a diferença de permissão fica na rota (`AuthMiddleware` × `AdminMiddleware`).
- **RECOMENDAÇÃO: B.** A separação que importa (controller e permissão) existe, sem forçar um `Shared` artificial.

**D6 — Tabelas compartilhadas.** Regra proposta: **um slice dono escreve; os outros podem ter as próprias queries de leitura.**
- `tbprodutos`: **Produtos** é dono do cadastro (marca, preços, fornecedor). **Movimentações** é o único que escreve `estoque_produto`. Dashboard e Relatórios só leem, com SQL próprio.
- `tbusuarios`: dono **Usuarios**; **Autenticacao** tem a própria leitura por e-mail e o `UPDATE ultimo_login`.
- `tbfornecedores`: dono **Fornecedores**; Produtos lê a lista para o `<select>`.
- `tbmovimentacoes`: dono **Movimentacoes**; Dashboard e Relatórios só leem.

### Árvore-alvo

```
app/
  Core/
    Config.php            # lê config/config.php; deriva BASE_URL de SCRIPT_NAME (já com /public)
    Database.php          # connection(): PDO
    Router.php
    Controller.php        # render(view, data, layout), json(), redirect()
    Session.php           # start único, flash(), csrf (gerar/validar), timeout
    Auth.php              # usuário logado na sessão: user(), isLoggedIn(), isAdmin(), login(), logout()
  Features/
    Autenticacao/  AuthController.php  AuthMiddleware.php  AdminMiddleware.php  views/login.php
    Dashboard/     DashboardController.php  DashboardConsultas.php  views/index.php
    Produtos/      ProdutoController.php  ProdutoModel.php  views/index.php
    Movimentacoes/ MovimentacaoController.php  RegistrarMovimentacao.php  MovimentacaoModel.php  views/index.php
    Relatorios/    RelatorioController.php  RelatorioFinanceiro.php  RelatorioEstoque.php  RelatorioConsultas.php  views/index.php
    Usuarios/      UsuarioController.php  PerfilController.php  UsuarioModel.php  views/index.php  views/perfil.php
    Fornecedores/  FornecedorController.php  FornecedorModel.php  views/index.php
  Shared/
    Formatacao.php
    views/layout.php
config/
  config.example.php      # versionado
  config.php              # local, no .gitignore
database/
  schema.sql              # bancoInteiro.sql corrigido
  migrations/             # 001_..., aplicados por você
public/
  index.php               # bootstrap + autoloader + rotas + tratamento único de erro
  .htaccess
  assets/css/app.css
  assets/js/app.js        # toast, modal, api(path) com APP_BASE_URL
  assets/js/cep.js  formatacao.js
  assets/js/dashboard.js  produtos.js  movimentacoes.js  relatorios.js  usuarios.js  perfil.js  fornecedores.js
armazenamento/logs/.gitkeep
.gitignore
```

### Contrato de cada slice

| Slice | Casos de uso | Entrada | Saída | Regras de negócio | Tabelas | Depende de |
|---|---|---|---|---|---|---|
| Autenticacao | entrar, sair, exigir login e admin | e-mail, senha | redirect + flash | usuário inativo não entra; timeout de inatividade | `tbusuarios` (R + `ultimo_login`) | Core |
| Dashboard | ver indicadores | — | HTML + dados dos gráficos | — (só leitura) | `tbprodutos`, `tbmovimentacoes` (R) | Core |
| Produtos | listar, cadastrar, editar, excluir, buscar | marca, preços, fornecedor | HTML / JSON | venda ≥ custo; marca única; não excluir produto com histórico | `tbprodutos` (W cadastro), `tbfornecedores` (R) | Core |
| Movimentacoes | registrar entrada/saída, ver estoque atual | produto (id), tipo, qtd, preços, obs. | redirect + flash | **saída ≤ saldo**; qtd > 0; atualiza o saldo na mesma transação | `tbmovimentacoes` (W), `tbprodutos.estoque_produto` (W) | Core |
| Relatorios | financeiro e estoque por período | tipo, período ou datas | JSON | receita = Σ venda×qtd das saídas; despesa = Σ custo×qtd das entradas; lucro = receita − custo vendido | R em `tbmovimentacoes`, `tbprodutos` | Core |
| Usuarios | CRUD admin, ativar/inativar; perfil e senha | dados pessoais e endereço | HTML / JSON / redirect | CPF válido e único; e-mail único; não inativar/excluir a si mesmo; senha ≥ 8 | `tbusuarios` (W) | Core, Shared/Formatacao |
| Fornecedores | CRUD, ativar/inativar | dados e endereço | HTML / JSON / redirect | CNPJ válido e único; não inativar/excluir com produtos vinculados | `tbfornecedores` (W), `tbprodutos` (R contagem) | Core, Shared/Formatacao |

### Movimentos relevantes

```
ANTES:  app/controllers/*Controller.php, app/models/*Model.php, app/views/layouts/<feature>/
DEPOIS: app/Features/<Feature>/...
AÇÃO:   MOVER (+ namespace)
MOTIVO: agrupar o que muda junto; um dev novo acha "registrar saída" em Features/Movimentacoes/RegistrarMovimentacao.php

ANTES:  MovimentacaoController::registrar + MovimentacaoModel::registrar + triggers before/after
DEPOIS: Features/Movimentacoes/RegistrarMovimentacao.php + database/migrations/00X_remove_triggers_estoque.sql
AÇÃO:   JUNTAR + MUDANÇA DE SCHEMA
MOTIVO: regra de estoque com um dono só (D1)

ANTES:  RelatorioController (privados)
DEPOIS: Features/Relatorios/RelatorioFinanceiro.php, RelatorioEstoque.php
AÇÃO:   DIVIDIR
MOTIVO: tirar o domínio do controller; deixar testável

ANTES:  UsuarioController (726 linhas, inclui perfil e auth duplicada)
DEPOIS: Features/Usuarios/UsuarioController.php + PerfilController.php
AÇÃO:   DIVIDIR + EXCLUIR (auth duplicada, login morto)
MOTIVO: D5; remover a segunda implementação de autenticação

ANTES:  app/helpers/AuthHelper.php + app/core/config.php (sessão) + session_start() espalhados
DEPOIS: app/Core/Auth.php + app/Core/Session.php
AÇÃO:   JUNTAR
MOTIVO: um start de sessão, um timeout, um flash, um CSRF

ANTES:  config/config.php + config/database.php + app/core/config.php
DEPOIS: config/config.php (gitignored) + config/config.example.php + app/Core/Config.php
AÇÃO:   JUNTAR + CRIAR
MOTIVO: uma fonte de configuração; credenciais fora do git

ANTES:  app/helpers/FormatHelper.php + app/helpers/functions.php
DEPOIS: app/Shared/Formatacao.php
AÇÃO:   RENOMEAR + EXCLUIR (functions.php)
MOTIVO: usada por 2 slices; wrappers globais sem uso

ANTES:  app/helpers/ErrorHelper.php, LogHelper.php, DiagnosticoHelper.php; app/core/Exceptions.php
DEPOIS: —
AÇÃO:   EXCLUIR
MOTIVO: substituídos por error_log configurado + DomainException nativa

ANTES:  app/views/layouts/main.php ; app/views/partials/toast.php
DEPOIS: app/Shared/views/layout.php (com o bloco de flash)
AÇÃO:   MOVER + JUNTAR
MOTIVO: um ponto de exibição de mensagens

ANTES:  app/views/partials/header.php, sidebar.php, loading.php ; app/models/EstoqueModel.php, LogModel.php, RelatorioModel.php
DEPOIS: —
AÇÃO:   EXCLUIR
MOTIVO: sem uso

ANTES:  JS inline nas views (até ~600 linhas por view)
DEPOIS: public/assets/js/<feature>.js + cep.js + formatacao.js
AÇÃO:   DIVIDIR
MOTIVO: D4; fim de URL fixa e de funções duplicadas

ANTES:  app/database/bancoInteiro.sql
DEPOIS: database/schema.sql + database/migrations/
AÇÃO:   MOVER + REFATORAR
MOTIVO: script que roda em qualquer SO; lugar definido para mudanças de schema (a raiz ganha regra de bloqueio no .htaccess)

ANTES:  app/*/.htaccess (6 subpastas)
DEPOIS: só app/.htaccess
AÇÃO:   EXCLUIR
MOTIVO: redundantes

ANTES:  index.css, raiz de diretorios.txt, armazenamento/logs/*
DEPOIS: —  (logs ignorados pelo .gitignore)
AÇÃO:   EXCLUIR
MOTIVO: sobra / arquivo gerado
```

---

## 10. Plano de execução (Fase B)

> Plano original. As etapas 1–4 foram concluídas como descritas abaixo; a partir da etapa 5, ele foi substituído pelo [plano revisado](#plano-revisado-a-partir-da-etapa-5). A situação atual está em [`plano.md`](../plano.md).

Mudei duas coisas na ordem sugerida, ambas para que cada etapa possa ser validada:
- As **correções de feedback ao usuário (C1)** vêm cedo (etapa 4). Sem elas, o roteiro de teste manual das etapas seguintes não consegue ver erros como “saída acima do estoque”.
- A **limpeza de logging** acontece junto com a mudança de cada slice (etapa 7). Assim cada arquivo é mexido uma vez só; a estratégia de erro é definida antes, na etapa 2.

| # | Etapa | Tipo | Arquivos principais | Risco | Validação |
|---|---|---|---|---|---|
| 1 | **Higiene:** `.gitignore` (`armazenamento/logs/*`, sessões, `vendor/`, `config/config.php`); `git rm --cached` dos logs; `config.example.php`; README real; remover `raiz de diretorios.txt` e `index.css`; parar de logar `$_SESSION`/`$_SERVER`; `ENVIRONMENT` lido da config. | REFATORAÇÃO + segurança | raiz, `config/`, `public/index.php`, `Router`, `AuthMiddleware` | Baixo | `git status`; abrir login e dashboard. **Decisão sua:** reescrever o histórico do GitHub (`git filter-repo`) para apagar os logs antigos. É destrutivo e muda os hashes; não vou fazer sem ordem explícita. |
| 2 | **Core único:** `Config` (BASE_URL derivado), `Session` (um `session_start`, flash, CSRF, timeout), `Database::connection()`, tratamento único de erro em `index.php` (`Throwable`, página HTML genérica, log em arquivo), `url()` no PHP e `APP_BASE_URL` no JS; `.htaccess` sem caminho fixo. | REFATORAÇÃO (+ CORREÇÃO A6) | `app/core/*`, `public/index.php`, `.htaccess`, todos os redirects | **Médio** (toca quase todos os arquivos) | `php -l`; `grep -rn "/sistema_ca_jesus"` deve dar zero; navegar por todas as telas; logar como funcionário e abrir `/usuarios` (antes: provável 404; depois: dashboard com mensagem). |
| 3 | **Código morto:** tudo da seção 5, exceto o banco e as decisões pendentes. | REFATORAÇÃO | ~20 arquivos removidos ou enxugados | Baixo | `php -l`; grep por cada nome removido; roteiro completo. |
| 4 | **Correções de comportamento** (passo separado e anunciado): C1 (mensagens: um mecanismo; movimentação lê a resposta), C2 (fornecedor `ativo`), A5 (remover rotas quebradas), M3 (409 do fornecedor), M8 (mensagens técnicas), M2 (escape de saída), A8 (parar de enviar `lucro`). | CORREÇÃO | controllers, views, `layout` | Médio | Roteiro: login errado mostra mensagem; saída acima do estoque mostra mensagem; editar fornecedor mantém ativo; excluir fornecedor com produto mostra o motivo. |
| 5 | **CSRF em todos os POST** (forms e `fetch`, via header). | MELHORIA | `Session`, todas as views com form/fetch | Médio | Cada ação POST funciona; um POST sem token devolve 403. |
| 6 | **Regra de estoque (D1)**: caso de uso em PHP com lock + `database/migrations/001_estoque_no_php.sql` (DROP dos triggers `before`/`after`, `CHECK estoque >= 0`, DROP das procedures, views e `tb_logs_movimentacao` mortas). **Você aplica a migração**; o código e a migração vão juntos no mesmo commit, com aviso. | REFATORAÇÃO + **MUDANÇA DE SCHEMA** | `Movimentacoes/*`, `database/` | **Alto** (se o código for publicado sem a migração, o estoque é baixado duas vezes) | Entrada soma; saída subtrai; saída > saldo é recusada com mensagem; conferir o saldo no banco antes e depois. |
| 7 | **Slices**, um por commit: Fornecedores → Produtos → Movimentações → Usuários (+Perfil) → Relatórios → Dashboard → Autenticação. Inclui namespaces + autoloader (D2), views no slice (D3), remoção de `error_log` de debug e consolidações da seção 6 (aliases, CPF/CNPJ, `SELECT` repetido). | REFATORAÇÃO | `app/Features/*` | Médio (por slice) | `php -l`; grep pelo nome antigo da classe e da view; roteiro da tela movida. |
| 8 | **Views:** JS para `public/assets/js/`, `cep.js`, `formatacao.js`; Tailwind/FontAwesome/Chart.js só no layout, com versão fixa (escolher **uma** versão do Tailwind e conferir o visual). | REFATORAÇÃO | views + `assets/js` | Médio (visual) | Comparar cada tela visualmente; console do navegador sem erro. |
| 9 | **Escape na entrada (M1)**: parar de gravar `htmlspecialchars`; migração opcional para desfazer o escape dos registros já gravados (`&#039;`, `&amp;`…). | CORREÇÃO + dados | Usuários, Fornecedores | Médio | Cadastrar “D'Ávila & Filhos” e ver o nome correto na listagem. |
| 10 | **`marca_produto` → `id_produto` em movimentações** (opcional, separado): migração que adiciona `produto_id`, preenche pela marca, cria FK e remove a FK por texto. Resolve A9. | **MUDANÇA DE SCHEMA** | `database/migrations/002_...sql`, Movimentações, Relatórios, Dashboard | Alto | Contagem de linhas antes = depois; renomear produto com histórico; relatórios mostram o nome novo. |
| 11 | **PHPUnit** (opcional): exige instalar o Composer. Começar por `RegistrarMovimentacao` (com banco de teste) e `RelatorioFinanceiro`/`RelatorioEstoque` (funções puras, custo baixo). | MELHORIA | `tests/`, `composer.json` | Baixo | `vendor/bin/phpunit` verde. |

### Roteiro de teste manual

Movido para [`roteiro-de-testes.md`](../roteiro-de-testes.md).

---

## Decisões tomadas (2026-10-23)

| # | Tema | Decisão | Efeito no plano |
|---|---|---|---|
| 1 | D1 — regra de estoque | **PHP + `CHECK (estoque_produto >= 0)`** | Etapa 6 como descrita. |
| 2 | D2–D5 | **Aprovadas**, com uma mudança: **duas árvores no topo, `backend/` e `frontend/`** | Substitui a árvore-alvo (ver abaixo). |
| 3 | Logs no histórico do GitHub | **Só parar de versionar** (`git rm --cached` + `.gitignore`); o histórico não é reescrito | Etapa 1 sem `filter-repo`. |
| 4 | `tb_auditoria` + `tr_auditoria_estoque` | **Manter** | A migração da etapa 6 não toca nesses dois. |
| 5 | `/usuarios/novo`, `/usuarios/editar/{id}`, `form.php` | **Remover**; em erro de validação, voltar para `/usuarios` com a mensagem | Etapa 3 (código morto) + correção M9 na etapa 4. |
| 6 | Custo > venda em movimentações (B4) | **Não bloquear: pedir confirmação ao usuário.** O backend recusa custo > venda só quando o pedido vem **sem** `confirmar_custo_maior=1`; nesse caso responde pedindo confirmação, e o front mostra “Tem certeza?” e reenvia com o campo. Vale para entrada e saída. | Entra na etapa 6, junto com o caso de uso `RegistrarMovimentacao`. O cadastro de Produtos continua bloqueando venda < custo, salvo decisão contrária. |
| 7 | `marca_produto` → `id_produto` | **Entra no escopo** (etapa 10) | Etapa 10 deixa de ser opcional. |
| — | Fluxo git | **Toda mudança nova em branch própria** | Uma branch por etapa (ex.: `refactor/01-higiene`, `refactor/02-core`, `fix/04-feedback`, `schema/06-estoque-php`, `schema/10-movimentacao-id-produto`). Merge em `main` só depois da validação manual da etapa. |

### Árvore-alvo revisada (backend × frontend)

Restrição técnica: sem etapa de build, o navegador só alcança arquivos sob a pasta pública. Por isso o JS/CSS/imagens ficam em `public/assets/`, organizados por feature, enquanto os templates PHP (que não podem ser públicos) vão para `frontend/views/`.

```
backend/                          # namespace App\ → backend/
  Core/        Config.php  Database.php  Router.php  Controller.php  Session.php  Auth.php
  Features/
    Autenticacao/  AuthController.php  AuthMiddleware.php  AdminMiddleware.php
    Dashboard/     DashboardController.php  DashboardConsultas.php
    Produtos/      ProdutoController.php  ProdutoModel.php
    Movimentacoes/ MovimentacaoController.php  RegistrarMovimentacao.php  MovimentacaoModel.php
    Relatorios/    RelatorioController.php  RelatorioFinanceiro.php  RelatorioEstoque.php  RelatorioConsultas.php
    Usuarios/      UsuarioController.php  PerfilController.php  UsuarioModel.php
    Fornecedores/  FornecedorController.php  FornecedorModel.php
  Shared/      Formatacao.php
frontend/                         # templates PHP, não públicos
  views/
    layout.php
    autenticacao/login.php
    dashboard/index.php
    produtos/index.php
    movimentacoes/index.php
    relatorios/index.php
    usuarios/index.php  usuarios/perfil.php
    fornecedores/index.php
public/                           # única pasta servida pelo Apache
  index.php  .htaccess
  assets/
    css/app.css
    img/...
    js/app.js  cep.js  formatacao.js
    js/dashboard.js  produtos.js  movimentacoes.js  relatorios.js  usuarios.js  perfil.js  fornecedores.js
config/    config.php (gitignored)  config.example.php
database/  schema.sql  migrations/
armazenamento/logs/.gitkeep
```

Consequências:
- `Controller::render('produtos/index')` resolve em `frontend/views/`. O nome da view é igual ao nome da pasta da feature, para que backend e frontend de cada slice fiquem fáceis de achar.
- O `.htaccess` raiz passa a bloquear também `backend/`, `frontend/` e `database/` (no lugar de `app/`). Um `.htaccess` com `Deny from all` fica em `backend/` e em `frontend/`.
- O `composer.json` muda o mapeamento PSR-4 para `"App\\": "backend/"`.

---

## Revisão de arquitetura (2026-10-24)

Feita depois das etapas 1–4 (`main` @ `61ba78c`). A divisão entre `backend/` e `frontend/` continua valendo; a árvore detalhada em vigor é a de [`arquitetura.md`](../arquitetura.md#estrutura). O que mudou foram as premissas: **o sistema ainda não tem produção**, ou seja, não há servidor de deploy nem dados reais a preservar. A arquitetura completa, com as regras, o contrato JSON e a estratégia de testes, está em [`arquitetura.md`](../arquitetura.md).

### Decisões revisadas

| # | Tema | Antes | Agora | Motivo |
|---|---|---|---|---|
| R1 | D2 — autoload | C: autoloader próprio, sem Composer | **A: Composer (PSR-4 `App\` → `backend/`)** | O argumento contra era a dependência no servidor, e não há servidor. O Composer também traz o PHPUnit. |
| R2 | Versão do PHP | ≥ 7.4 | **≥ 8.2** | É a versão do ambiente (XAMPP, 8.2.12) e a exigida pelo PHPUnit 11. O PHP 8.1 já não recebe correções de segurança. |
| R3 | Testes | PHPUnit opcional, etapa 11 | **Obrigatório, instalado na etapa 5**, antes de mexer na regra de estoque | O ponto mais frágil do plano era mover tudo sem rede de proteção. |
| R4 | Mudanças de schema | Migrações em `database/migrations/` | **Editar `database/schema.sql` e recriar o banco local** até o primeiro deploy | Não há dados a preservar. As migrações começam depois do primeiro deploy. |
| R5 | Respostas JSON | Sem formato definido (`success`/`message`, `error`, `dados`/`erro` convivem) | **Contrato único** `{ sucesso, mensagem, dados, requer_confirmacao }` com status HTTP definido | O front precisa diferenciar "ok", "erro" e "precisa confirmar" (fluxo `confirmar_custo_maior`). |
| R6 | Casos de uso | `RegistrarMovimentacao` com lock | O mesmo, **sem depender de HTTP** (não lê `$_POST` nem sessão) | Permite testar chamando a classe direto. |

Continuam valendo: D1 (estoque em PHP + `CHECK`), D3 revisada (`frontend/views/`), D4, D5, D6 e as decisões 3 a 7 da tabela de 2026-10-23.

### Plano revisado (a partir da etapa 5)

Como foi escrito em 2026-10-24. A situação atual está em [`plano.md`](../plano.md).

| # | Etapa | Tipo | Arquivos principais | Risco | Validação |
|---|---|---|---|---|---|
| 5 | **Composer + PHPUnit:** `composer.json` (`php >= 8.2`, PSR-4 `App\` → `backend/`, `phpunit/phpunit ^11` em `require-dev`), `phpunit.xml`, `tests/bootstrap.php`, um teste inicial de `FormatHelper`; `public/index.php` carrega `vendor/autoload.php` (o autoloader manual continua para as classes antigas até a etapa 9); README com os requisitos novos; `.htaccess` bloqueia `vendor/` e `tests/`. **Você instala o Composer.** | MELHORIA | raiz, `tests/`, `public/index.php` | Baixo | `composer install`; `vendor/bin/phpunit` verde; todas as telas abrem. |
| 6 | **Contrato JSON:** `Controller::json()`, `ErroDeNegocio` no Core, tratamento único em `index.php` (422/500 no formato novo), todas as respostas JSON e os `fetch` das telas convertidos de uma vez; `docs/rotas.md`. | REFATORAÇÃO | `app/core/*`, controllers com JSON, views com `fetch`, `public/index.php` | Médio | Roteiro dos itens que usam `fetch`: buscar e excluir produto, status e exclusão de usuário e fornecedor, relatórios, sino de estoque. Console do navegador sem erro. |
| 7 | **CSRF em todos os POST** (antiga etapa 5), com 403 no formato do contrato. | MELHORIA | `Session`, views com form/fetch | Médio | Cada ação POST funciona; um `fetch` sem token recebe 403, e um formulário sem token volta com mensagem. |
| 8 | **Regra de estoque (D1).** Primeiro o teste: `RegistrarMovimentacaoTest` contra `bd_sistema_teste`. Depois o caso de uso `App\Features\Movimentacoes\RegistrarMovimentacao` (com lock, sem HTTP, com `confirmar_custo_maior`). No schema: `app/database/bancoInteiro.sql` → `database/schema.sql`, com a correção de A7, o DROP dos triggers `before`/`after`, o `CHECK (estoque_produto >= 0)` e a remoção das procedures, views e `tb_logs_movimentacao` mortas. **Você recria o banco.** | REFATORAÇÃO + **SCHEMA** | `backend/Features/Movimentacoes/`, `MovimentacaoController`, `database/schema.sql`, `tests/` | **Alto** (código novo com banco antigo baixa o estoque duas vezes) | `vendor/bin/phpunit` verde; roteiro item 4; conferir o saldo no banco antes e depois. |
| 9 | **Slices** (antiga etapa 7), um por commit, na mesma ordem. Move para `backend/Features/*` e `frontend/views/*`, aplica namespaces e, no último slice, remove o autoloader manual e os `require_once`. Relatórios ganha `RelatorioFinanceiro`/`RelatorioEstoque` com testes. | REFATORAÇÃO | `backend/`, `frontend/` | Médio (por slice) | `php -l`; `vendor/bin/phpunit`; grep pelo nome antigo; roteiro da tela movida. |
| 10 | **Views** (antiga etapa 8). | REFATORAÇÃO | views + `public/assets/js` | Médio (visual) | Comparar cada tela; console sem erro. |
| 11 | **Escape na entrada (M1)** (antiga etapa 9). Não precisa de migração de dados: o banco é recriado. **Precisa ir junto:** o escape com `escapeHtml` nos modais de detalhe (`showUsuarioDetails` em `usuarios.js` e o equivalente em `fornecedores.js`), adiado na etapa 4. Hoje eles só estão protegidos porque os dados são gravados já escapados; sem isso, a etapa abre uma falha de XSS. | CORREÇÃO | Usuários, Fornecedores | Baixo | Cadastrar "D'Ávila & Filhos" e ver o nome correto na lista e no modal; cadastrar um nome com `<b>` e ver o texto literal no modal. |
| 12 | **`marca_produto` → `id_produto`** (antiga etapa 10), editando `database/schema.sql`. **Você recria o banco.** | **SCHEMA** | `database/schema.sql`, Movimentações, Relatórios, Dashboard | Médio | `vendor/bin/phpunit`; renomear produto com histórico; relatórios mostram o nome novo. |

A antiga etapa 11 (PHPUnit) foi absorvida pela etapa 5.
