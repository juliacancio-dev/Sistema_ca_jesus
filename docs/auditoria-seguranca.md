# Auditoria de segurança, robustez e confiabilidade — Sistema CA Jesus

Situação em 2026-10-31, no commit `55e135c` da `main`. Este documento registra uma análise pontual e não é atualizado conforme os itens são resolvidos. O que falta fazer está em [`plano.md`](plano.md). Ele complementa o [diagnóstico](diagnostico-desempenho-seguranca.md) e a [avaliação técnica](avaliacao-tecnica.md); a comparação com os dois está na seção 7.

## Como ler as evidências

| Marca | Significado |
|---|---|
| **FATO (execução)** | Reproduzido nesta auditoria, em ambiente isolado |
| **FATO (código)** | Comprovado lendo o código ou a configuração, sem precisar executar |
| **INFERÊNCIA** | Conclusão sustentada por evidências, não reproduzida diretamente |
| **HIPÓTESE** | Cenário possível que ainda depende de validação |
| **PONTO A VALIDAR** | Não foi possível confirmar; alguém precisa decidir ou verificar |

**Ambiente das provas:** o Docker de `tests/e2e` (Apache 2.4 + PHP 8.2 + MariaDB 10.4 com banco em memória) e o Chromium do Playwright. O banco de desenvolvimento não foi tocado. No XAMPP local, só houve `GET`/`HEAD` em caminhos, sem login, e leitura da lista de contas do MariaDB. Os scripts das provas não foram versionados.

**Isto não foi:** teste de invasão, teste de carga nem teste a partir de outra máquina. HTTPS não foi testado. Nenhum sistema externo foi atacado; as respostas do ViaCEP foram simuladas.

---

# 1. Visão geral

**O sistema está preparado para a internet? Não.** O uso local atual é razoável, mas há falhas que precisam de correção antes de publicar.

| Pergunta | Resposta curta |
|---|---|
| Arquitetura | Confere com a documentação: monólito modular em Vertical Slice, um ponto de entrada, `public/` separado de `backend/` e `frontend/` |
| Estado geral | A base é boa: SQL, CSRF, roteamento e regra de estoque resistiram às provas. As falhas estão no **ciclo de vida do acesso**, no **login**, na **implantação** e no **tratamento de falhas** |
| Críticos hoje | **Nenhum** |
| Altos | 5, dos quais 2 só valem ao publicar e 1 está latente |
| Maior risco imediato | **S-003:** a Etapa 11, como está planejada, abre um caminho de funcionário para administrador via XSS, por um ponto que o plano não cobre |
| Melhor notícia | A regra de estoque aguentou concorrência real: 40 requisições simultâneas sobre saldo 10 resultaram em exatamente 10 saídas e saldo 0 |

**Contagem por gravidade**

| Gravidade | Qtde | IDs |
|---|---|---|
| Crítica | 0 | — |
| Alta | 5 | S-001, S-002, S-003 (latente), S-004 e S-005 (antes de publicar) |
| Média | 15 | S-006, S-007, S-008, S-009, S-010, S-011, I-001, I-002, I-003, I-004, I-005, I-006, R-001, R-002, L-001 |
| Baixa | 19 | S-012 a S-016, I-007 a I-012, R-003 a R-006, P-001 a P-003 (P-001 e P-002 são médias em desempenho, ver texto), M-001 |
| Informativa | 2 | M-002, M-003 |

---

# 2. Arquitetura reconstruída

```
Navegador → Apache (.htaccess raiz → public/.htaccess) → public/index.php
  → config.php, ini_set, session_start, autoload, tabela de rotas
  → Router::resolve: casa método + caminho → middlewares → CSRF (se não for GET) → controller
  → controller → caso de uso ou model → PDO → MySQL/MariaDB
  ← HTML (render + layout), JSON (RespostaJson) ou redirect
  ← exceções: ErroDeNegocio → 422/flash; outras → 500 genérico + log
```

| Peça | Onde | Observação |
|---|---|---|
| Ponto de entrada e rotas | `public/index.php` | 28 rotas; todas as não públicas têm middleware (conferido uma a uma) |
| Autenticação | `backend/Core/Auth.php`, `Features/Autenticacao/` | O estado fica **só em `$_SESSION`** — origem de S-001 |
| CSRF | `Router.php:46`, `Session.php:36` | Central, em todo método não GET |
| Escrita de saldo | `RegistrarMovimentacao` | Único ponto; `ProdutoModel` não toca em `estoque_produto` |
| Serviço externo | `public/assets/js/cep.js` | ViaCEP só no navegador; o servidor não depende dele e não há SSRF possível |
| Upload | — | **N/A:** não existe |
| Arquivos gerados | Navegador (PDF/XLSX) e `backend/storage/logs/` | O servidor não guarda exportações |

**A separação Vertical Slice está sendo respeitada?** Sim, com ressalvas sem impacto de segurança: views leem `$_SESSION` diretamente (`dashboard/index.php:54`, `usuarios/index.php:16`), convivem duas formas de acesso ao banco (`Database` e `PDO` injetado), e a validação de CPF/CNPJ fica no controller, o que a regra 1 da arquitetura permite. A propriedade de escrita das tabelas foi conferida e está correta. **Nenhuma mudança arquitetural é recomendada.** Nenhum achado abaixo é causado pela arquitetura.

## Matriz de permissões (implementada e medida)

Medida por HTTP com admin, funcionário e anônimo. Anônimo: 302 para login em HTML e 401 em JSON, em todas as rotas não públicas.

| Recurso | Consultar | Criar | Modificar | Excluir | Comprovado por |
|---|---|---|---|---|---|
| Produtos, com custo | Func, Admin | Func, Admin | Func, Admin | Func, Admin ⚠ | Execução |
| Movimentações | Func, Admin | Func, Admin | Ninguém (imutável ✔) | Ninguém ✔ | Execução + código |
| Relatório financeiro | Func ⚠, Admin | — | — | — | Execução |
| Dashboard | Func (sem o card de saldo, mas com valores ⚠), Admin | — | — | — | Execução |
| Usuários | Admin | Admin | Admin | Admin (lógica) | Execução: func recebe 403/redirect |
| Perfil próprio | O próprio | — | O próprio; `id` e `tipo` forjados são ignorados ✔ | — | Execução |
| Fornecedores | Admin (func vê só o nome no select) | Admin | Admin | Admin | Execução |

⚠ = decisão de produto pendente (S-008). A interface sugere que o funcionário não vê finanças, mas o servidor entrega.

## Modelo de ameaças

| Ameaça | Ponto vulnerável | Exploração | Impacto | Controle esperado | Situação |
|---|---|---|---|---|---|
| Atacante externo adivinha senha | `POST /login` | Tentativas ilimitadas | Tomada de conta; 64 MB por tentativa com Argon2id | Limite por conta e IP | **Falha** S-002 |
| Ex-funcionário com sessão aberta | `AuthMiddleware` | Continua usando a sessão | Grava e lê tudo | Revalidar a cada requisição | **Falha** S-001 |
| Funcionário malicioso → admin | Nome em `onclick` | XSS após a Etapa 11 | Administração completa | Escape por contexto | **Latente** S-003 |
| Funcionário manipula finanças | Preços vindos do formulário | POST com preço arbitrário | Relatórios falsos | Preço do cadastro ou motivo auditado | **Falha** I-001 |
| CDN comprometido | Scripts sem SRI no login | Script captura a senha | Todas as contas | Arquivos locais + CSP | **Ausente** S-004 |
| Rede insegura | Cookie sem `Secure`, sem TLS | Interceptação | Sessão e senha | HTTPS + HSTS | **Ausente** S-005 |
| Acesso direto a arquivos internos | Depende de `AllowOverride` | Servidor mal configurado | Logs, SQL, `.git` | `DocumentRoot` em `public/` | **Parcial** S-006 |
| Phishing pós-login | `redirect_after_login` | Link `//evil/dashboard` | Credenciais em site falso | Validar destino | **Falha** S-007 |
| Requisição duplicada | `/movimentacoes/registrar` | Reenvio após falha de rede | Saldo baixado duas vezes | Chave de idempotência | **Ausente** I-007 |
| Saídas simultâneas | Saldo | Corrida | Saldo negativo | Lock + `CHECK` | **OK** |
| CSRF | Rotas mutáveis | Site externo | Ação em nome da vítima | Token + SameSite | **OK**, exceto logout |
| SQL injection | Consultas | Parâmetros | Banco inteiro | Prepared statements | **OK** |
| Banco fora do ar | Todas as telas | Falha | Vazamento ou falso positivo | Erro genérico | **Parcial** R-001 |
| ViaCEP fora do ar | Formulário de endereço | Falha | Cadastro bloqueado | Preenchimento manual | **OK**, com ressalvas R-003 |

---

# 3. Achados

## Segurança

### S-001 — Acesso revogado continua funcionando
- **Gravidade:** Alta. **Categoria:** Segurança. **Confiança:** alta. **FATO (execução).**
- **Evidência:** `Features/Autenticacao/AuthMiddleware.php:13`, `AdminMiddleware.php:13`, `Core/Auth.php:47-50`. O dado vem de `$_SESSION['user']`, gravado em `Auth.php:24`.
- **Problema:** a sessão nunca é conferida com o banco.
- **Mecanismo:** `tipo` e `ativo` são copiados no login e nunca mais consultados. Por isso, a existência da sessão vale como autorização permanente.
- **Cenário reproduzido:**
  - Usuário **desativado** continuou abrindo telas, lendo o relatório financeiro e registrou uma movimentação.
  - Usuário **excluído** registrou uma movimentação (HTTP 200).
  - Admin **rebaixado** continuou em `/usuarios`, leu o CPF de outra pessoa e **se promoveu de volta a administrador** sozinho, usando `/usuarios/salvar`.
  - Trocar a senha, ou o admin redefini-la, **não derrubou** a outra sessão aberta.
- **Impacto:** demitir alguém não encerra o acesso, e a sessão continua ativa por até 2 h desde a última requisição, sem limite absoluto: basta continuar usando. Um atacante com sessão roubada não é expulso nem com a troca da senha.
- **Correção:** no `AuthMiddleware`, buscar `tipo_usuario, ativo_usuario, deletado_em, senha_atualizada_em` por chave primária; se inativo ou excluído, encerrar a sessão; atualizar `tipo` na sessão antes do `AdminMiddleware`. Para derrubar sessões na troca de senha, guardar na sessão uma versão (`versao_sessao`) e comparar com o banco. Custo: uma consulta por chave primária por requisição. Regenerar o ID ao trocar a senha. Adicionar expiração absoluta (ex.: 12 h).
- **Teste:** E2E com dois contextos do navegador: o admin desativa/rebaixa/exclui, e a próxima requisição da vítima recebe 401/redirect. Repetir para a troca de senha.

### S-002 — Login sem limite de tentativas
- **Gravidade:** Alta. **Categoria:** Segurança. **Confiança:** alta. **FATO (execução).**
- **Evidência:** `AuthController.php:21-54`. Em 30 senhas erradas seguidas para o admin, todas receberam o mesmo tratamento, e a senha certa entrou em seguida.
- **Problema:** não há limite, atraso nem bloqueio. A política de senha também só exige 8 caracteres.
- **Mecanismo:** cada tentativa é independente; o token CSRF não atrapalha, pois um script o obtém com um GET.
- **Cenário:** força bruta com lista de senhas. Como cada verificação Argon2id usa 64 MB fora do `memory_limit` do PHP, tentativas paralelas também pressionam a memória do servidor (**INFERÊNCIA**: não houve teste de carga).
- **Impacto:** tomada de contas, incluindo admin, e risco de indisponibilidade.
- **Correção:** tabela `tentativas_login` (hash de e-mail + IP, contagem, `bloqueado_ate`), com limite por conta+IP e outro maior só por IP. Bloqueio temporário, nunca permanente, para não permitir que alguém tranque a conta de outra pessoa. Não precisa de Redis.
- **Teste:** N+1 tentativas seguidas recebem recusa mesmo com a senha certa; passa ao fim da janela; outra conta não é afetada.

### S-003 — A Etapa 11 abre XSS por um ponto que o plano não cobre
- **Gravidade:** Alta, **latente** (não é explorável hoje). **Categoria:** Segurança. **Confiança:** alta. **FATO (execução)** para o XSS com o dado sem escape; a alteração da Etapa 11 em si não foi aplicada, só simulada no banco.
- **Evidência:** `frontend/views/usuarios/index.php:161` e `frontend/views/fornecedores/index.php:148`:
  `onclick="deleteUsuario(<id>, '<?= htmlspecialchars($usuario['nome']) ?>')"`
- **Problema:** `htmlspecialchars` protege HTML, mas aqui o dado está numa **string JavaScript dentro de um atributo**. O navegador decodifica `&#039;` para `'` **antes** de o JavaScript ser interpretado.
- **Mecanismo:** hoje o escape duplo salva por acidente (entrada + saída). A Etapa 11 remove o escape de entrada, e sobra um único nível, que o navegador desfaz.
- **Cenário:** o `docs/plano.md` prevê corrigir só os modais. Com o nome `');window.__x='js';//` gravado sem escape, foi simulado o estado pós-Etapa 11:

  | Ponto | Hoje | Depois da Etapa 11 | No plano? |
  |---|---|---|---|
  | Botão excluir de usuário | não executa | **executa** | **Não** |
  | Botão excluir de fornecedor | não executa | **executa** | **Não** |
  | Modal de usuário | não executa | executa | Sim |
  | Modal de fornecedor | não executa | executa | Sim |

  O **funcionário pode alterar o próprio nome** pelo perfil. Basta o admin clicar em excluir esse usuário. O limite de 100 caracteres ainda permite carregar um script externo com `import()` (**INFERÊNCIA** sobre o tamanho do payload).
- **Impacto:** escalada de funcionário para administrador: o script age na sessão do admin, com o token CSRF da página.
- **Correção:** antes de remover o escape de entrada, trocar os dois `onclick` por `data-id` e `data-nome` lidos num `addEventListener`, ou no mínimo `<?= htmlspecialchars(json_encode($nome), ENT_QUOTES) ?>` sem as aspas simples ao redor. Atualizar `plano.md`. Revisar também `fornecedores/index.php:96`, que imprime o primeiro byte do nome sem escape e corta caracteres multibyte.
- **Teste:** E2E criando o nome com `');window.__x=1;//` e também `<img src=x onerror=...>` pelo perfil; o admin clica em excluir e abre o modal; `window.__x` continua indefinido. O teste atual cobre HTML em produtos, mas **não** este contexto.

### S-004 — Scripts de CDN sem SRI e sem CSP, inclusive no login
- **Gravidade:** Alta antes de publicar. **Categoria:** Segurança. **Confiança:** alta. **FATO (código + execução).**
- **Evidência:** `login.php:11-13`, `layout.php:62-65`, `relatorios/index.php:86-88`.
- **Problema:** um arquivo alterado no CDN roda com acesso à senha digitada e à sessão.
- **Mecanismo:** sem `integrity`, o navegador confia em qualquer byte que vier. **Fato novo:** `cdn.tailwindcss.com` não enviou `Access-Control-Allow-Origin`, então **SRI não é viável nele**. Os outros (jsDelivr e cdnjs) enviam e devolveram bytes estáveis em duas leituras.
- **Cenário:** comprometimento do CDN (hipótese de cadeia de suprimentos, sem caso conhecido aqui).
- **Impacto:** captura de credenciais de todos os usuários.
- **Correção, nesta ordem:** (1) gerar o CSS do Tailwind com a CLI e servir local; (2) servir localmente as outras bibliotecas, ou SRI com `crossorigin`; (3) **CSP em Report-Only** e depois em bloqueio.
- **Viabilidade da CSP (medida em report-only):** sem violações de `eval`. Violações por tela, com uma política sem `unsafe-inline`: `script-src-attr` 51 em produtos, 40 em usuários, 20 em fornecedores, 6 no perfil; 1 `<script>` inline por tela (`APP_BASE_URL`, trocável por `data-` no `<body>`); `style-src` inline de 3 a 6 por tela. O dashboard não foi medido, por uma limitação do script da prova. Conclusão: é preciso remover os 23 `onclick`/`onsubmit` (incluindo os dos modais gerados por JS) antes de bloquear; `style-src` pode começar com `'unsafe-inline'`.
- **Teste:** nenhum host externo na aba de rede do login; relatório de CSP sem violações no E2E.

### S-005 — Sem HTTPS forçado, HSTS nem cookie `Secure`
- **Gravidade:** Alta antes de publicar; sem efeito no XAMPP local. **Categoria:** Segurança. **Confiança:** alta. **FATO (execução)** para o cabeçalho; HTTPS **não testado**.
- **Evidência:** `public/index.php:41-43`; cookie observado: `HttpOnly; SameSite=Lax`, sem `Secure`.
- **Problema:** em HTTP, senha e sessão trafegam em claro.
- **Correção:** no bloco de sessão, `session.cookie_secure` condicionado ao ambiente; redirecionar para HTTPS no VirtualHost; HSTS só depois de o HTTPS estar validado.
- **Cenário e impacto:** alguém na mesma rede captura o cookie e assume a sessão.
- **Teste:** `curl -I` no site publicado: `Set-Cookie` com `Secure`, `http://` responde 301, e `Strict-Transport-Security` presente.

### S-006 — A proteção de arquivos e os cabeçalhos dependem de qual `.htaccess` o servidor lê
- **Gravidade:** Média (exige configuração específica). **Categoria:** Segurança. **Confiança:** alta. **FATO (execução).**
- **Evidência:** `.htaccess` da raiz e `public/.htaccess`. O mesmo código foi testado em quatro implantações:

  | Implantação | Internos (`backend/`, `tests/`, `vendor/`, `.git`, SQL, logs) | Cabeçalhos | Listagem de `assets/` |
  |---|---|---|---|
  | A. Subpasta, como no XAMPP e no E2E | Bloqueados (403/404) | Sim | Não |
  | C. Raiz do projeto, `AllowOverride All` | Bloqueados | Sim | Não |
  | B2. `DocumentRoot` em `public/`, pais sem `AllowOverride` | Inacessíveis, fora da raiz web | **Nenhum** | **Sim** |
  | D. Raiz do projeto, `AllowOverride None` | **Baixados: log, `seed.sql`, `schema.sql`, `composer.lock`, `.git/config`, e views geram erro com caminho** | Nenhum | — |

- **Mecanismo:** o `.htaccess` é lido pelo caminho no disco. Por isso, a afirmação S7 do diagnóstico anterior vale **só** se os diretórios pais tiverem `AllowOverride None`: na imagem de E2E, o `sed` do Dockerfile liga `AllowOverride All` em tudo e mascara o problema. `public/.htaccess` não tem cabeçalhos nem `Options -Indexes`.
- **Cenário:** no servidor padrão recomendado (B2), o site perde silenciosamente `nosniff` e `X-Frame-Options`, ficando sujeito a clickjacking. No cenário D, qualquer pessoa baixa o log, que contém CPF (S-009).
- **Impacto:** a configuração mais segura perde a proteção contra framing; a mais errada expõe tudo. O teste E2E só cobre o cenário A.
- **Correção:** adotar `DocumentRoot` em `public/` como único modo de produção, documentar no README e mover para `public/.htaccess` ou VirtualHost: cabeçalhos e `Options -Indexes`. Adicionar `Content-Security-Policy: frame-ancestors`, `Referrer-Policy`, e retirar o obsoleto `X-XSS-Protection`. Mover logs para fora da árvore do projeto ajudaria no cenário D.
- **Teste:** script de verificação pós-deploy que confere os cabeçalhos e os status acima.

### S-007 — Redirecionamento aberto depois do login
- **Gravidade:** Média. **Categoria:** Segurança. **Confiança:** alta. **FATO (execução),** ponta a ponta (o diagnóstico anterior não tinha executado o fluxo completo).
- **Evidência:** `AuthMiddleware.php:19` guarda `REQUEST_URI`; `AuthController.php:50-52` redireciona para ele.
- **Mecanismo:** `parse_url('//evil.example/dashboard', PHP_URL_PATH)` devolve `/dashboard`, então a rota casa; mas o valor guardado é a URI crua, e `//host` no `Location` é uma URL absoluta para outro site.
- **Cenário reproduzido:** a vítima abre `https://site//evil.example/dashboard`, vê a tela de login legítima, entra, e o servidor responde `Location: //evil.example/dashboard`. Confirmado nas implantações na raiz do domínio (B e C); **não** ocorre em subpasta (A).
- **Impacto:** phishing convincente, logo depois de um login real.
- **Correção:** guardar só em GET e validar: começa com `/`, não com `//` nem `/\`, sem `://`. Hoje um POST expirado também guarda o destino e leva a um 404.
- **Teste:** repetir o fluxo: o `Location` final é local. Incluir em PHPUnit a função de validação, com variações de `//`, `/\`, `/%2f`.

### S-008 — Funcionário acessa finanças, altera preços e exclui produtos
- **Gravidade:** Média. **Categoria:** Segurança/Decisão de produto. **Confiança:** alta. **FATO (execução)**; **PONTO A VALIDAR** se é o desejado.
- **Evidência:** `public/index.php:64-66,71,92`; `dashboard/index.php:54` esconde só o card; `dashboard/index.php:141` envia `preco_custo` no JSON da página.
- **Problema:** o funcionário não vê o card de saldo, mas recebe `saldo_final`, `lucro`, receitas e despesas pela API. Alterou um preço de 10,00 para 0,01 e excluiu um produto, sem nenhum registro em `tb_auditoria`.
- **Mecanismo:** a restrição está na interface; a rota só exige login. A documentação (`rotas.md`) confirma as rotas como abertas, então não há divergência: a decisão é que não está tomada.
- **Impacto:** o dado financeiro não está protegido, e a mudança de preço não deixa rastro.
- **Correção:** decidir. A recomendação é: relatório financeiro e exclusão de produto só para admin; retirar `preco_custo` do JSON do dashboard (o gráfico não o usa); auditar alteração de preço.
- **Teste:** matriz de permissões no E2E, incluindo a chamada direta à API com a sessão do funcionário.

### S-009 — CPF e RG em claro nos logs
- **Gravidade:** Média. **Categoria:** Segurança/Privacidade. **Confiança:** alta. **FATO (execução).**
- **Evidência:** `UsuarioModel.php:251,313` gravam `$e->getMessage()`, e `UsuarioController.php:275` grava e-mails. O PHP do XAMPP tem `zend.exception_ignore_args=Off`, então os traces incluem argumentos, como `executar('marca', ...)`.
- **Problema:** a mensagem do MySQL em violação de unicidade traz o valor: `Duplicate entry '<CPF>' for key ...`. Reproduzido com CPF e com RG. O log não tem rotação nem retenção.
- **O que não vazou (também testado):** senha digitada, e-mail digitado no login com o banco fora, senha do banco. O PHP 8.2 protege este argumento: o trace mostra `Object(SensitiveParameterValue)`. Host e usuário do banco aparecem.
- **Impacto:** quem lê o log (ou o baixa, no cenário D de S-006) tem documentos pessoais. Um recadastro comum já gera a linha (I-011).
- **Correção:** logar `errorInfo[1]` e o nome da chave, nunca a mensagem inteira, ao menos nos models de Usuários; `zend.exception_ignore_args=On` em produção; rotação com retenção definida.
- **Teste:** provocar duplicidade e verificar com `grep` que o valor não está no log.

### S-010 — Repositório público com logs, dump e dados de seed no histórico
- **Gravidade:** Média até validar. **Categoria:** Segurança/Privacidade. **Confiança:** alta sobre os fatos; **PONTO A VALIDAR** sobre os dados serem reais.
- **Evidência:** `gh repo view` informa `PUBLIC`. O histórico contém `armazenamento/logs/` (423 arquivos, 2,6 MB) e `app/database/bancoInteiro.sql`. O `seed.sql` atual traz nomes, telefones e endereços, e os 4 CPFs do seed têm dígitos verificadores válidos, com CNPJs de fornecedores.
- **O que verifiquei no histórico, contando sem imprimir valores:**
  - `DB_PASS` antigo: **vazio** em todas as versões; não há senha de banco para trocar.
  - Hash no dump: 1 distinto, equivalente à senha pública do README.
  - Procura por chaves de API/tokens: nenhuma, só a palavra `htpasswd` no `.htaccess`.
  - Logs: dados de fornecedores salvos, alguns e-mails, e um `session_id`/cookie de desenvolvimento, agora sem valor.
- **Problema:** o código não mostra se aquelas pessoas e empresas existem. A origem dos dados precisa ser confirmada pela responsável pelo projeto.
- **Impacto:** se forem reais, são dados pessoais publicados, e a remoção do código atual não os retira do histórico.
- **Correção:** confirmar a origem. Se forem reais: reescrever o histórico, pedir a limpeza de cache ao GitHub e avaliar as obrigações de privacidade. Se forem fictícios: registrar essa conclusão em `docs/` e trocar os domínios por `@example.com`. A decisão anterior de não reescrever o histórico foi sobre logs, e vale revê-la só nesse primeiro caso.
- **Teste:** `git log --all -S` com cada valor retorna vazio depois da limpeza.

### S-011 — Ambiente de desenvolvimento acessível pela rede
- **Gravidade:** Média. **Categoria:** Segurança/Ambiente. **Confiança:** média. **FATO (configuração)**; o acesso de outra máquina é **INFERÊNCIA**, porque não foi testado de fora.
- **Evidência:** o XAMPP vem escutando em todas as interfaces, e o firewall pode liberar o Apache na rede. O ambiente usa `ENVIRONMENT=development`, e a senha do seed está no README público. Arquivos deixados em `htdocs`, como cópias antigas do projeto, ficam disponíveis pela mesma porta.
- **Mitigantes confirmados:** contas `root` do MariaDB restritas à própria máquina; phpMyAdmin com `Require local`.
- **Cenário:** alguém na mesma rede abre o IP da máquina e entra como admin com a senha do README, vendo trace de erro.
- **Impacto:** limitado enquanto o banco tiver só dados de seed. Passa a ser grave se houver dado real.
- **Correção:** `Listen 127.0.0.1:80` no XAMPP, revisar as regras de firewall e não guardar cópias do projeto em `htdocs`.
- **Teste:** de outro dispositivo na rede, a porta 80 não responde.

### S-012 — O tempo de resposta do login revela se o e-mail existe
- **Gravidade:** Baixa. **Confiança:** alta. **FATO (execução):** mediana de 15: 73 ms com conta existente (bcrypt) e 6 ms sem conta, no Docker. A diferença tende a crescer com Argon2id. As mensagens são iguais ✔.
- **Evidência:** `AuthController.php:34`. **Mecanismo:** `password_verify` só roda se a conta existir. **Cenário:** cronometrar para listar e-mails válidos e focar a força bruta. **Impacto:** facilita S-002.
- **Correção:** verificar contra um hash fictício Argon2id quando não houver conta. **Teste:** medianas equivalentes em N tentativas.

### S-013 — Logout por GET
- **Gravidade:** Baixa. **Confiança:** alta. **FATO (execução):** GET com origem externa encerrou a sessão.
- **Evidência:** `public/index.php:57`, `layout.php:261`. **Mecanismo:** GET não passa por CSRF. Com `SameSite=Lax`, só navegação de topo leva o cookie: `<img>` em outro site não deve funcionar (**INFERÊNCIA**). **Cenário:** um link externo desconecta o usuário. **Impacto:** incômodo; pode perder formulário em andamento.
- **Correção:** `POST /logout` com token. **Teste:** GET responde 404, e o POST sem token responde 403.

### S-014 — Ciclo de vida da senha incompleto
- **Gravidade:** Baixa. **Confiança:** alta. **FATO (código).**
- **Evidência:** nenhum `password_needs_rehash` no projeto; `AuthController.php:34`. Mínimo de 8 bytes sem lista de senhas comuns (`UsuarioController.php:60`, `PerfilController.php:104`), sem máximo. Não há recuperação de conta (N/A: admin redefine), e a senha definida pelo admin não exige troca no primeiro acesso.
- **Mecanismo e cenário:** o bcrypt do seed nunca migra para Argon2id; senhas muito comuns com 8+ caracteres, como a do seed, são aceitas. **Impacto:** agrava S-002.
- **Correção:** rehash no login, lista curta de senhas proibidas, máximo de 4096 bytes, troca obrigatória após redefinição. **Teste:** login com hash bcrypt regrava Argon2id.

### S-015 — Bibliotecas de CDN com alertas conhecidos, sem caminho de exploração atual
- **Gravidade:** Baixa. **Confiança:** média. **FATO (execução)** no `npm audit` sobre um lockfile com as mesmas versões, sem instalar; a análise de alcance é **INFERÊNCIA** por leitura.
- **Evidência:** `jspdf 2.5.1`: alertas até **crítico** (corrigido em 4.2.1), com `dompurify` opcional; `xlsx 0.18.5`: alto, sem correção no npm; `html2canvas 1.4.1`: última versão publicada; Font Awesome e Tailwind desatualizados, sem alerta. `composer audit` e `npm audit` do projeto retornam 0, mas **não cobrem CDN**.
- **Mecanismo de baixo risco:** `relatorios.js` só usa `addImage` com PNG gerado localmente, `text` fixo e `XLSX.writeFile`; não lê arquivo de terceiros nem monta PDF com dado externo. **Cenário:** um novo uso, como importar planilha, ativaria as falhas da leitura. **Impacto hoje:** baixo.
- **Correção:** atualizar ao trazer as bibliotecas para o projeto (S-004), lembrando que SheetJS corrigida vem do CDN dela, não do npm. **Teste:** versões novas + roteiro de exportação. Fórmula no Excel: **testado, não é injetável** (`=HYPERLINK(...)` vira célula de texto).

### S-016 — Padrões inseguros na implantação
- **Gravidade:** Baixa antes de publicar. **Confiança:** alta. **FATO (código).**
- **Evidência:** `config.example.php:7,11-12` (development, root, sem senha); `README.md` ensina `composer install` sem `--no-dev`, enquanto o Dockerfile faz certo; sem usuário de banco de privilégio mínimo; `expose_php=On` e cabeçalho `Server` completo; mensagens de `index.php:20,48` citando caminhos.
- **Ponto positivo:** sem a constante, `ENVIRONMENT` assume `production` (`index.php:24`), e em produção confirmei erro genérico e HTTP 500 sem detalhes.
- **Cenário:** copiar o exemplo para o servidor liga `display_errors` e mostra traces. **Impacto:** vazamento de detalhes internos.
- **Correção:** lista de implantação no README; exemplo vindo como `production`; usuário MySQL só com `SELECT, INSERT, UPDATE, DELETE`. **Teste:** script de verificação pós-deploy.

## Integridade

### I-001 — Preços da movimentação vêm do formulário, sem conferência
- **Gravidade:** Média. **Confiança:** alta. **FATO (execução).**
- **Evidência:** `MovimentacaoController.php:31-32`, `RegistrarMovimentacao.php:28,43`.
- **Problema:** o funcionário informa o valor que quiser. Saída com custo 0 e venda 99.999.999,99, qtd 1000, foi aceita sem confirmação, e o relatório anual passou a mostrar lucro de cerca de **R$ 100 bilhões**. A confirmação só existe se custo > venda.
- **Mecanismo:** o cadastro só preenche o formulário no navegador; o servidor não compara. Editar é um recurso legítimo (a compra pode variar), mas não há limite nem justificativa.
- **Impacto:** relatórios financeiros manipuláveis. Fica o `usuario_id`, então há como saber quem foi. **Dependente de decisão de produto.**
- **Correção:** exigir `observacao` e confirmação quando o preço divergir do cadastro além de uma margem, ou só admin altera. Registrar o preço do cadastro junto.
- **Teste:** PHPUnit no caso de uso, com preço divergente sem confirmação.

### I-002 — "Sucesso" quando nada foi gravado
- **Gravidade:** Média. **Confiança:** alta. **FATO (execução).**
- **Evidência:** `ProdutoModel.php:99`, `FornecedorModel.php:160,196`, `UsuarioModel.php:308`.
- **Mecanismo:** `execute()` devolve `true` com zero linhas afetadas; com `ERRMODE_EXCEPTION`, os `else` de "Erro ao salvar" são código morto.
- **Cenário reproduzido:** atualizar o produto 999999 → "Produto atualizado com sucesso!"; excluir o fornecedor 999999 → "excluído com sucesso"; editar fornecedor/usuário inexistentes → sucesso. **Editar um usuário excluído** foi aceito, inclusive `tipo=1` e `ativo=1`.
- **Impacto:** duas abas ou exclusão concorrente dão falso positivo; o registro excluído continua sendo alterado.
- **Correção:** verificar existência (o `rowCount()` do MySQL é 0 também quando nada muda, então conferir antes) e `AND deletado_em IS NULL`; responder 404. **Teste:** E2E/HTTP com id inexistente espera 404.

### I-003 — A proteção contra autodesativação é contornável e o último admin pode sumir
- **Gravidade:** Média. **Confiança:** alta. **FATO (execução).**
- **Evidência:** `UsuarioController.php:94,139` protegem; `salvar()` (linhas 41-87) não. `ativo` ausente vira 0 (`:180`), e o botão de editar aparece para o próprio usuário (`usuarios/index.php:148`).
- **Cenário reproduzido:** toggle e excluir em si mesmo → 422; `/usuarios/salvar` em si mesmo, sem `ativo` e `tipo=2` → gravado (tipo 2, ativo 0). Também: `status=2` é gravado (bloqueia o login, mas a tela o mostra como ativo), e um corpo inválido **desativa** o usuário (`:137`).
- **Impacto:** um único admin pode se trancar para fora, sem recuperação pela aplicação, e o sistema fica sem administrador até intervenção no banco.
- **Correção:** regra única: ninguém altera o próprio tipo/status, e nunca remover o último admin ativo, com contagem dentro de transação. Validar `status` em {0,1} e recusar corpo inválido com 422. **Teste:** cada caminho, incluindo dois admins rebaixando um ao outro em paralelo.

### I-004 — O escape na entrada corrompe o dado a cada edição
- **Gravidade:** Média. **Confiança:** alta. **FATO (execução).**
- **Evidência:** `UsuarioController.php:268-271`, `PerfilController.php:130-133`, `FornecedorController.php:126-136`.
- **Cenário reproduzido:** salvar `D'Ávila & Filhos` três vezes: `D&#039;Ávila &amp; Filhos` → `D&amp;#039;Ávila &amp;amp; Filhos` → `D&amp;amp;#039;Ávila &amp;amp;amp; Filhos`. O usuário vê as entidades na tela.
- **Mecanismo:** cada edição reaplica o escape ao valor já escapado, e `strlen` mede o tamanho já inflado, então nomes válidos podem chegar a ser recusados.
- **Impacto:** nome e endereço degradam a cada edição. Por isso a Etapa 11 é necessária, com a ordem indicada em S-003.
- **Correção:** Etapa 11. Usar `mb_strlen`. **Teste:** salvar 3 vezes e comparar byte a byte.

### I-005 — Validação no servidor desigual e coerção silenciosa
- **Gravidade:** Média. **Confiança:** alta. **FATO (execução).**
- **Evidência:** casts em `MovimentacaoController.php:30-32`, `ProdutoController.php:39-41,129`, `PerfilController.php:37,64-70`.
- **Cenário reproduzido:**

  | Entrada | Resultado |
  |---|---|
  | `quantidade=1e3` | 1000 unidades |
  | `quantidade=2abc` | 2 |
  | `quantidade=1.9` | 1 |
  | `quantidade[]=50` | 1 |
  | `preco_custo=12,50` | 12,00 |
  | `preco_custo=abc` | 0,00 |
  | `preco_custo[]=1` em produto | aceito como 1 |
  | `marca=   ` | "Produto cadastrado com sucesso" |
  | `marca=' Espaco'` | gravada; depois não movimenta ("Produto não encontrado") |
  | preços vazios | 0,00 |
  | Perfil com telefone, CEP, endereço e UF vazios | "Perfil atualizado", sem validação |
  | `DELETE /produtos/excluir/1abc` | o banco converte para **id 1** (bloqueado por haver movimentações) |

- **Mecanismo:** `(int)`/`(float)` nunca falham; sem `strict_types`; rotas com `{id}` aceitam qualquer texto; o formulário faz a validação real.
- **Impacto:** quantidade errada baixa saldo real, e `/produtos/excluir/2abc` apagaria o produto 2 se não houver movimentação (**INFERÊNCIA** pelo caso do id 1).
- **Correção:** `filter_var(FILTER_VALIDATE_INT|FLOAT)` com faixas, recusando com 422; `trim` e limite na marca; `{id}` só com dígitos no Router (`([0-9]+)`, opção por rota); usar no perfil a mesma validação do cadastro. **Teste:** `DataProvider` com a tabela acima.

### I-006 — O período escolhido no relatório é ignorado
- **Gravidade:** Média. **Confiança:** alta. **FATO (execução em navegador).**
- **Evidência:** `relatorios.js:26-29,492-498`, `RelatorioController.php:46`.
- **Cenário reproduzido:** ao abrir a tela, as datas já vêm preenchidas com o mês atual. Selecionar "Anual" + Aplicar envia `?periodo=anual&data_inicio=2026-10-01&data_fim=2026-10-31`, e o servidor devolve só outubro. O PDF sai com o rótulo "… (anual)".
- **Mecanismo:** datas prevalecem, como o aviso da tela diz, mas como sempre estão preenchidas o seletor nunca tem efeito.
- **Impacto:** um relatório "anual" exportado traz os valores de um mês, sem nenhum aviso. Usa a tela como foi feita para ser usada, por isso conta mais que outros bugs.
- **Correção:** ao mudar o período, preencher ou limpar as datas de forma coerente; o rótulo usa o intervalo devolvido pelo servidor. Também validar `Y-m-d` e `inicio ≤ fim` no servidor (hoje `abc` responde `sucesso: true`). **Teste:** E2E com movimentos de meses diferentes: "Anual" inclui todos.

### I-007 — Requisições duplicadas registram duas movimentações
- **Gravidade:** Baixa a média. **Confiança:** alta. **FATO (execução).**
- **Evidência:** `RegistrarMovimentacao.php` não tem chave de idempotência. **Cenário reproduzido:** duas requisições iguais: saldo 100 → 90, 2 saídas. O botão fica desabilitado (`movimentacoes.js:8`) e cobre o duplo clique, mas não o reenvio: se a resposta se perde, `requisicaoJson` diz "não foi possível falar com o servidor" e a pessoa reenvia, embora o servidor possa já ter gravado.
- **Impacto:** saldo errado, sem erro aparente. **Correção:** gerar um UUID ao abrir o formulário, gravar em coluna `UNIQUE` em `tbmovimentacoes`, tratar a repetição como sucesso. **Teste:** dois POST com a mesma chave geram uma movimentação.

### I-008 — Movimentação liga-se ao produto por texto (Etapa 12)
- **Gravidade:** Baixa. **Confiança:** alta. **FATO (execução).** **Evidência:** `schema.sql:65,80`. A colação trata `gas de cozinha` e `Gás de cozinha` como iguais para FK e lock, mas o PHP os separa. **Cenário reproduzido:** POST direto com as variantes dividiu o ranking "mais vendidos" em duas linhas do mesmo produto. **Impacto:** ranking e auditoria errados; renomear produto com histórico é impossível. **Correção:** Etapa 12; até lá, gravar a marca lida do banco, não a enviada. **Teste:** o da Etapa 12 mais o POST com variante de grafia.

### I-009 — Fornecedor inativo: regra incompleta e tela enganosa
- **Gravidade:** Baixa. **Confiança:** alta. **FATO (execução).** **Evidência:** `ProdutoModel.php:34-44` não filtra ativos, nem `exigirDadosValidos` verifica; `fornecedores.js:267,354`. **Cenário reproduzido:** funcionário criou produto ligado a fornecedor inativo. O modal de detalhes de um inativo diz **"Ativo"** (`'0'` é verdadeiro em JS), com nome e e-mail em minúsculas. **Impacto:** a regra "não desativar com produtos" é contornável na ordem inversa. **Correção:** filtrar e validar `ativo_fornecedor`; usar `Number(status)` e `dataset.nomeOriginal`. **Teste:** E2E dos dois casos.

### I-010 — Coluna de lucro trunca valores altos sem erro
- **Gravidade:** Baixa. **Confiança:** alta. **FATO (execução).** **Evidência:** `schema.sql:71`: `DECIMAL(10,2)` virtual. **Cenário:** `lucro_movimentacao` leu 99.999.999,99 onde o valor real era 99.999.999.990,00. O INSERT passa, porque a coluna virtual é calculada na leitura; os relatórios recalculam em PHP e não usam a coluna, então os números divergem. **Impacto:** hoje pequeno, porque o caso depende de I-001. **Correção:** ampliar para `DECIMAL(14,2)` ou remover a coluna, e limitar quantidade × preço. **Teste:** caso-limite no PHPUnit.

### I-011 — Excluído bloqueia CPF/RG com erro genérico
- **Gravidade:** Baixa. **Confiança:** alta. **FATO (execução);** já registrado na avaliação técnica. **Evidência:** `UsuarioModel.php:436`, `schema.sql:11-12`. RG duplicado também não é verificado. **Cenário:** recadastrar um ex-funcionário → "Erro ao salvar usuário", gerando a linha de S-009. **Impacto:** cadastro impossível sem saber o motivo. **Correção:** decidir entre reativar ou anonimizar na exclusão (L-001), e checar RG como o e-mail. **Teste:** E2E de recadastro.

### I-012 — Auditoria incompleta
- **Gravidade:** Baixa. **Confiança:** alta. **FATO (execução + código).** **Evidência:** `schema.sql:131` grava `usuario_id` nulo e nunca grava o IP; só o saldo é auditado. Preço, exclusão de produto, papéis e redefinição de senha só aparecem como texto no log, ou nem isso. **Cenário:** "quem mudou o preço do produto X?" não tem resposta. **Impacto:** investigação inviável, agravando S-008 e I-001. **Correção:** gravar auditoria no PHP, dentro da mesma transação, para operações administrativas e de preço. **Teste:** cada operação gera uma linha com usuário e IP.

## Confiabilidade

### R-001 — Falha do servidor apresentada como resultado normal
- **Gravidade:** Média. **Confiança:** alta. **FATO (execução em navegador, com API simulada em 500).**
- **Evidência:** `layout.js:121-125`, `relatorios.js:74,87`, `app.js:70-91`. `requisicaoJson` nunca rejeita, e os `catch` chamadores são inalcançáveis.
- **Cenário reproduzido:**

  | Situação | O que a pessoa vê |
  |---|---|
  | Sino com a API em 500 | **"Tudo ok no estoque."** |
  | Relatório já carregado, reaplicado com a API em 500 | Saldo anterior, sem nenhum aviso |
  | Primeira carga do relatório com a API em 500 | **"R$ 0,00"**, sem nenhum aviso |

- **Impacto:** estoque baixo não é notado, e um relatório vazio parece real. Decisões erradas com aparência de dado correto.
- **Correção:** tratar `sucesso === false` nos chamadores: "Não foi possível carregar", limpando os valores antigos. **Teste:** E2E com `page.route` em 500, como o acima.
- **Comparação:** a parte do servidor está certa: com o banco fora, respostas 500 genéricas e nada vazado.

### R-002 — Datas do banco e do PHP em fusos diferentes
- **Gravidade:** Média, em servidor com fuso diferente. **Confiança:** alta. **FATO (execução).**
- **Evidência:** `index.php:29` fixa `America/Sao_Paulo`; `schema.sql:66` usa `CURRENT_TIMESTAMP` do banco; a conexão não define `time_zone` (`Database.php:15-28`).
- **Cenário reproduzido:** PHP 13:28, banco 16:28 (UTC, padrão de servidores). A movimentação ficou com hora **3 h adiantada**. **INFERÊNCIA:** depois das 21h, ela cai no dia seguinte, some de "Movimentações Hoje" e entra na data errada dos relatórios.
- **Mecanismo:** duas fontes de relógio. No XAMPP não aparece, porque banco e PHP estão na mesma máquina e fuso; os E2E não checam a hora.
- **Correção:** `SET time_zone = '-03:00'` ao conectar, ou a data gerada no PHP e enviada na query. **Teste:** E2E já roda com o banco em UTC; acrescentar a comparação de hora da movimentação.

### R-003 — ViaCEP: sem timeout, sem aviso e podendo sobrescrever
- **Gravidade:** Baixa. **Confiança:** alta. **FATO (execução, com respostas simuladas).** **Evidência:** `cep.js:3-15`.
- **Cenário reproduzido:** JSON sem campos → o endereço vira o texto **"undefined"**; resposta lenta → **sobrescreve** o que a pessoa corrigiu à mão; JSON inválido, HTTP 500 ou rede caída → nada acontece, sem aviso.
- **Ponto positivo:** usa HTTPS, `.value` (sem risco de XSS), o CEP é só dígitos, e o formulário continua utilizável. A indisponibilidade não bloqueia nada.
- **Impacto:** um cadastro pode gravar "undefined". **Correção:** `AbortController` com 5 s; `dados.logradouro ?? ''`; só preencher campo vazio; aviso discreto. **Teste:** os mocks acima.

### R-004 — Parâmetros malformados viram erro 500
- **Gravidade:** Baixa. **Confiança:** alta. **FATO (execução).** **Evidência:** `email[]=x` no login (sem autenticação), `?search[]=x`, `?data_inicio[]=x`, `produto[]=x`, `/usuarios/buscar/abc`: 500, contra o 422/404 do contrato. Método errado dá 404, não o 405 documentado. **Mecanismo:** `TypeError` não é `Exception`, e sobe ao tratamento geral. **Impacto:** ruído de log sem autenticação (a resposta não vaza nada em produção); dificulta perceber erro real. **Correção:** helper de leitura de texto (`is_string` ou 422). **Teste:** `DataProvider` por rota.

### R-005 — A busca de usuários só olha a página atual
- **Gravidade:** Baixa. **Confiança:** alta. **FATO (execução).** **Evidência:** `usuarios.js:53-63,299`. **Cenário reproduzido:** usuário existente na página 2 não aparece na busca da tela (0 linhas), embora `?search=` na URL o encontre. E filtrar gera erro JS (`stats-total` não existe na view). **Impacto:** o admin pode concluir que alguém não está cadastrado, e deixar de desativar um acesso. **Correção:** a busca envia `?search=` ao servidor; remover `updateStats`. **Teste:** E2E com mais de 10 usuários.

### R-006 — Relatório sem limite de período
- **Gravidade:** Baixa. **Confiança:** alta. **FATO (execução).** **Evidência:** `RelatorioFinanceiro.php:58-65`, `RelatorioController.php:46`. **Cenário reproduzido:** 100 anos: 150 ms e 2,2 MB; `0001-01-01..9999-12-31`: erro fatal de memória (HTTP 500 em produção, corpo vazio). Exige login. **Impacto:** uma requisição consome todo o `memory_limit` (512 MB no XAMPP), e um fatal não passa pelo contrato JSON. **Correção:** validar e limitar a 2 anos, por exemplo. **Teste:** período acima do limite responde 422.

## Performance

### P-001 — `/movimentacoes` carrega o histórico inteiro
- **Gravidade:** Média em desempenho, baixa em segurança. **Confiança:** alta. **FATO (código)** nesta auditoria; os números são da avaliação técnica, em **dados sintéticos** (não os refiz): 941 ms e 17 MB com 10 mil linhas; 9,5 s e 172 MB com 100 mil. **Evidência:** `MovimentacaoModel.php:31`; a ordenação só por `data` também não é estável. **Impacto:** a tela principal degrada com o uso. **Correção:** paginar como em Usuários, com índice `(data_movimentacao, id_movimentacao)`. **Teste:** tempo e tamanho nas metas da avaliação.

### P-002 — Tailwind compilado no navegador e bibliotecas em toda tela
- **Gravidade:** Média em desempenho. **Confiança:** alta. **FATO (medido na avaliação técnica):** 1.091 KB em `/relatorios`. **Evidência:** `layout.php:62,65`, `relatorios/index.php:86-88`. **Impacto:** carregamento lento em rede real; a correção coincide com S-004. **Correção:** CSS gerado na CLI; bibliotecas sob demanda. **Teste:** as metas da avaliação.

### P-003 — Outros custos menores, sem gargalo comprovado
- **Gravidade:** Baixa. **Evidência:** `DATE()` em filtros (`RelatorioConsultas.php:30`, `DashboardConsultas.php:26`; 58 ms com 100 mil linhas); sino baixa todos os produtos (`layout.js:148`); 3 índices redundantes (`schema.sql:102-104`). N+1: não encontrado ✔. **Recomendação:** corrigir só junto com outras mudanças; **não precisa de cache**.

## Manutenção e testes

### M-001 — Os testes não cobrem os cenários em que os achados vivem
- **Gravidade:** Baixa em si, mas relevante. **Confiança:** alta. **FATO:** PHPUnit: 66 testes passando; E2E: ver seção 7.
- **Lacunas:**

  | Cenário | Testado? |
  |---|---|
  | Regra de estoque, lock, rollback, `CHECK` | Sim, e bem |
  | Saídas realmente em paralelo | Não: só espera de lock (a prova por HTTP foi manual, nesta auditoria) |
  | Sessão de usuário desativado, excluído ou rebaixado | **Não** |
  | Limite de login | **Não** (não existe) |
  | Token CSRF inválido | Sim |
  | Funcionário em rota admin | Sim, por tela; a API por chamada direta não |
  | XSS em `onclick`, com dado sem escape | **Não** |
  | Falha da API refletida na tela | **Não** |
  | Id inexistente, entrada malformada, duplicidade | **Não** |
  | Arquivos internos | Só na implantação A, e o Dockerfile mascara os outros cenários |
  | Hora da movimentação em fuso diferente | **Não** |

- **Ponto positivo:** a suíte falha com qualquer erro JS, aviso PHP ou 500; roda em dois bancos; `BancoDeTeste` recusa outro banco que não o de teste.

### M-002 — Sem CI nem análise estática
- **Gravidade:** Informativa. **Evidência:** sem `.github/` nem config de PHPStan. **Benefício concreto:** CI impediria o merge com testes quebrados; PHPStan apontaria casos como `$usuario` possivelmente nulo em `PerfilController.php:111`. **Recomendação:** GitHub Actions com PHPUnit e `composer audit`; PHPStan em nível baixo depois. Não é pré-requisito de segurança.

### M-003 — Divergências entre documentação e código, e código morto
- **Gravidade:** Informativa. **Evidência:**
  - `arquitetura.md`: lista 405, mas método errado responde 404.
  - `arquitetura.md`: "`backend/`, `tests/` e `vendor/` são bloqueados pelo `.htaccess`": só vale com o `.htaccess` lido (S-006).
  - `dashboard/index.php:95`: "Últimas 24h", mas a consulta traz as 5 últimas de qualquer data.
  - `layout.php:12-23`: bloco que imprime trace sem checar ambiente, hoje inalcançável; `fornecedores/index.php:162`: paginação sem dados.
  - O "resultado não explicado" da avaliação (3 respostas 200 em 21 tentativas) **não se repetiu** nas minhas 45 tentativas.
  - **Correção:** ajustar os documentos e remover o bloco de `layout.php`, que é perigoso se um dia voltar a ser alcançável.

## Privacidade

### L-001 — Dados pessoais sem retenção definida
- **Gravidade:** Média antes de haver dado real. **Categoria:** Privacidade. **Confiança:** alta sobre os fatos técnicos. **Não é parecer jurídico:** as obrigações da LGPD precisam de avaliação por quem responde por isso.
- **Fatos:**
  - **Dados:** nome, e-mail, CPF, RG, telefone, sexo, endereço completo de funcionários; dados de fornecedores; quem fez cada movimentação.
  - **Minimização:** CPF, RG e sexo são obrigatórios para qualquer funcionário; **PONTO A VALIDAR** se todos são necessários.
  - **Exclusão:** é lógica, e **mantém todos os dados para sempre**; não há anonimização.
  - **Acesso:** só admin (✔ por middleware, com a falha S-001). CPF completo aparece na tabela e em `data-cpf`, sem necessidade.
  - **Armazenamento:** sem criptografia em repouso; a sessão guarda nome e e-mail em arquivo; logs com CPF/RG (S-009).
  - **Exportações:** PDF/XLSX **não** trazem dados pessoais de usuários ✔, mas ficam na máquina de quem baixa, inclusive as de funcionário (S-008).
  - **Backups:** não há política documentada.
  - **Compartilhamento:** só o CEP vai ao ViaCEP.
- **Correção:** definir finalidade e retenção; anonimizar na exclusão, preservando o `id` nas movimentações; mascarar CPF na lista; política de backup com restauração testada; avaliar criptografia de disco ou de coluna depois.
- **Teste:** excluir usuário e verificar a anonimização, com o histórico de movimentações intacto.

---

# 4. Pontos positivos (comprovados)

| Mecanismo | Evidência | Por que importa |
|---|---|---|
| **Regra de estoque** | 40 saídas paralelas em 8 sessões sobre saldo 10: exatamente 10 aceitas, 30 recusadas (422), saldo 0, `soma(movimentos) = saldo`, auditoria coerente | É o coração do sistema, e foi resolvido com lock, transação e `CHECK`, sem triggers de regra |
| **Rollback** | Estouro de INT no `UPDATE`, **depois** do `INSERT`: 500, saldo intacto, nenhum movimento | Transação cobre a falha entre as duas escritas |
| **SQL injection** | `ATTR_EMULATE_PREPARES=false`; a única interpolação é a constante `tableName`; não há `ORDER BY` dinâmico nem concatenação de entrada | Tira a classe inteira de vulnerabilidade |
| **CSRF** | Sem token, errado, em array, na query e de outra sessão: todos 403, nada gravado. Trocado no login | Central, por isso nenhuma rota nova fica de fora |
| **Roteamento** | Todas as rotas conferidas: nenhuma administrativa sem middleware; PUT/HEAD/`X-HTTP-Method-Override` não contornam | A autorização está no servidor |
| **Sessão** | ID forjado rejeitado (`strict_mode`), ID trocado no login e o antigo invalidado, `HttpOnly`, `SameSite=Lax`, `Cache-Control: no-store`, expiração de 2 h funciona (401/redirect) | Bloqueia fixação; mas falta S-001 |
| **Hash de senha** | Argon2id acima do mínimo OWASP; hash removido da sessão e da API; mensagens de login iguais | Vazamento do banco não entrega senhas fáceis |
| **Perfil** | `id` e `tipo` forjados ignorados: o id vem da sessão | Sem acesso horizontal nem atribuição em massa |
| **Escape na saída** | Payloads HTML, de atributo e JS em marca, observação, endereço e busca: **nenhuma execução hoje** em 12 pontos testados | `textContent`, `escapeHtml` e `JSON_HEX_*` estão corretos |
| **Erros** | Modo produção: genérico, sem trace, banco fora → 500; a senha do banco não vai para o log | O usuário nunca vê detalhe técnico |
| **Exportação Excel** | Texto com `=` não vira fórmula | Evita injeção de fórmula |
| **Dependência externa** | ViaCEP só no navegador, via HTTPS | Sem SSRF, e a indisponibilidade não trava cadastro |
| **Isolamento dos testes** | `BancoDeTeste` recusa outro banco; E2E em tmpfs | Rodar teste nunca apaga dados reais |
| **Exclusões** | Movimentações imutáveis; produto com histórico e fornecedor com produtos bloqueados; usuário em exclusão lógica mantém o nome no histórico | O histórico financeiro não desaparece |
| **Segredos** | `config.php` fora do git; histórico só tem senha vazia | Nada a revogar |

---

# 5. Plano de correção

**Decidir primeiro (decisão da responsável pelo projeto):** se o seed e o histórico têm dados de pessoas reais (S-010) e o que o funcionário pode ver e fazer (S-008, I-001).

## Obrigatórias antes de produção

| # | Item | Depende de | Esforço |
|---|---|---|---|
| 1 | **S-003**, e só depois **Etapa 11** (resolve I-004): trocar os `onclick`, escapar os modais, atualizar o plano | — | Pequeno |
| 2 | **S-001**: revalidar usuário a cada requisição, versão de sessão, expiração absoluta | — | Pequeno |
| 3 | **S-002** e **S-012**: limite de tentativas e hash fictício | — | Médio |
| 4 | **S-007**: validar destino pós-login | — | Pequeno |
| 5 | **I-003, I-002, I-005**: regra de último admin, 404 em id inexistente, validação estrita | — | Médio |
| 6 | **I-006, R-001**: relatório respeita o período; falha aparece como falha | — | Pequeno |
| 7 | **R-002**: um só relógio | — | Pequeno |
| 8 | **S-009**: logs sem dado pessoal | — | Pequeno |
| 9 | **Decisões S-008 e I-001** implementadas | Decisão | Médio |
| 10 | **S-004 (1ª fase)**: CSS gerado e bibliotecas locais; também reduz P-002 e S-015 | — | Médio |
| 11 | **S-005, S-006, S-016, L-001**: pacote de implantação com `DocumentRoot=public`, HTTPS, cabeçalhos, usuário de banco, política de retenção e backup, verificação pós-deploy | — | Médio |

## Recomendadas

- **S-004 (2ª fase):** remover os inline e ligar a CSP em bloqueio.
- **M-001:** um teste para cada correção acima, de preferência escrito antes dela.
- **I-007**, **I-012**, **S-014**, **S-013**.
- **Etapa 12** (I-008), **I-009**, **I-011**, **R-003 a R-006**.
- **P-001:** paginar movimentações.
- **S-011:** vale agora, leva minutos e não depende de nada: restringir o Apache local à própria máquina.

## Melhorias futuras

- **M-002:** CI, depois PHPStan.
- `strict_types` gradual, I-010, P-003, M-003.
- Criptografia de campos, se a avaliação de privacidade pedir.

**Não são recomendados:** reescrita, framework, troca de arquitetura, Redis, WAF, cache ou ORM. Todos os problemas aqui têm correção localizada.

---

# 6. Estado do checklist

## PHP / Backend
| Controle | Estado | Base |
|---|---|---|
| Configurações e secrets | PARCIAL | Fora do git ✔; padrões de dev (S-016) |
| Autenticação | PARCIAL | Fluxo correto; S-002, S-012 |
| Hashing de senhas | OK | Argon2id; falta rehash (S-014), que não invalida o controle |
| Segurança de sessão | PARCIAL | Boas flags; sem `Secure` |
| Regeneração de sessão | PARCIAL | No login ✔; não na troca de senha |
| Expiração | PARCIAL | Inatividade ✔; sem absoluta; `gc_maxlifetime` local de 24 min menor que os 2 h prometidos |
| Rate limiting | AUSENTE | S-002 |
| Autorização | FALHA | Rotas ✔, mas estado obsoleto S-001 |
| Proteção das rotas | OK | Conferidas |
| PDO e SQL Injection | OK | Inspeção + execução |
| Validação de entradas | FALHA | I-005 |
| Tratamento de erros | PARCIAL | Central ✔; R-004 |
| Logging seguro | FALHA | S-009 |
| Timeouts | PARCIAL | Lock do banco ✔; `cep.js` sem timeout |
| Integrações externas | PARCIAL | R-003 |
| Configuração de produção | PARCIAL | Modo produção correto; sem guia (S-016) |

## Frontend / Browser
| Controle | Estado | Base |
|---|---|---|
| XSS | PARCIAL | Sem execução hoje; S-003 |
| Escape contextual | FALHA | `onclick` com escape de HTML |
| Manipulação de DOM | PARCIAL | Modais dependem da entrada |
| CSRF | OK | Execução; só o logout fora |
| Segurança de Fetch | PARCIAL | Token ✔; R-001 |
| Validação de entradas | PARCIAL | Cliente ✔, servidor desigual |
| Redirects | FALHA | S-007 |
| CSP | AUSENTE | S-004 |
| Security Headers | PARCIAL | Só via `.htaccess` da raiz (S-006) |
| Bibliotecas externas | PARCIAL | S-015 |
| CDN e SRI | AUSENTE | S-004 |
| Exposição de dados | PARCIAL | Custo e finanças ao funcionário (S-008) |

## Apache / Infraestrutura
| Controle | Estado | Base |
|---|---|---|
| DocumentRoot | NÃO VERIFICADO | Sem servidor; modo recomendado precisa ser definido |
| `.htaccess` | PARCIAL | Bom onde é lido (S-006) |
| mod_rewrite | OK | Desvios de caminho testados: nenhum passou |
| Proteção de diretórios | PARCIAL | Cenários A/C ok; D e listagem em B2 |
| Proteção de arquivos internos | PARCIAL | Idem |
| Logs inacessíveis | PARCIAL | Idem |
| Configurações inacessíveis | PARCIAL | Executadas e sem saída mesmo em D, mas `.git` e exemplo ficam expostos |
| HTTPS | NÃO VERIFICADO | S-005 |
| Permissões de arquivos | NÃO VERIFICADO | Sem servidor; o Dockerfile só libera `storage` |
| Configurações de produção | AUSENTE | Sem guia |

## MySQL / MariaDB
| Controle | Estado | Base |
|---|---|---|
| Credenciais | PARCIAL | `root` sem senha é aceitável só no local |
| Privilégios | AUSENTE | Sem usuário restrito definido |
| SQL Injection | OK | |
| Prepared Statements | OK | |
| Constraints | PARCIAL | `CHECK`, `UNIQUE` ✔; `ativo` e `tipo` sem domínio; `RG`/`CPF` sobre excluídos |
| Foreign Keys | PARCIAL | Corretas; FK por texto (I-008) |
| Integridade | PARCIAL | I-001, I-002 |
| Transações | OK | Rollback provado |
| Concorrência | OK | Prova por HTTP; só MariaDB. O `testEsperaOLockDoProduto` roda também no local |
| Índices | PARCIAL | P-001, P-003 |
| Backups | NÃO VERIFICADO | Sem política documentada |
| Auditoria | PARCIAL | I-012 |
| Retenção | AUSENTE | L-001 |
| Exclusões e cascatas | OK | Provado por testes |

## Testes e confiabilidade
| Controle | Estado | Base |
|---|---|---|
| Testes de autenticação | PARCIAL | Fluxo ✔; revogação não |
| Testes de autorização | PARCIAL | Telas ✔; API direta e revogação não |
| Testes de regras de negócio | OK | Estoque e relatórios |
| Testes de concorrência | PARCIAL | Espera de lock, sem paralelismo real |
| Testes de falha | PARCIAL | Rollback ✔; banco e API não |
| Testes de integração | PARCIAL | Só Movimentações/Relatórios/Dashboard no PHPUnit |
| Testes E2E | OK | Dois bancos, navegador real |
| Isolamento do banco de testes | OK | Proteção no código |
| Análise estática | AUSENTE | M-002 |
| Verificação de dependências | PARCIAL | Manual; sem CDN |
| Automação de CI | AUSENTE | M-002 |

---

# 7. Testes executados e limitações

| Execução | Resultado |
|---|---|
| PHPUnit (usa só `bd_sistema_teste`, recriado por design) | 66 testes, 152 asserções: **todos passam** |
| E2E oficial (`tests/e2e/rodar.sh`) Saída 0 em MariaDB 10.4 e MySQL 8.0; 70 testes no último banco (a contagem do primeiro foi cortada na saída, mas o script só sai com 0 se os dois passarem) |
| `composer audit` / `npm audit` | 0 alertas (só cobrem desenvolvimento) |
| `npm audit` com as versões de CDN, só lockfile | 1 crítico, 2 altos (ver S-015) |
| Provas manuais | Scripts não versionados, descritos em cada achado |

**O que não foi possível verificar:**
- HTTPS, cookie `Secure` em uso real, permissões de arquivo e backups: não há servidor.
- Acesso à máquina vindo de outra rede (S-011): não testado de fora.
- Se os dados do seed são de pessoas reais (S-010).
- Concorrência em MySQL 8.0: a prova manual rodou só em MariaDB 10.4.
- Desempenho: as medições não foram refeitas; os números de P-001 e P-002 vêm da avaliação técnica, com dados sintéticos.
- A CSP do dashboard não foi medida, e a Etapa 11 foi simulada no banco, sem mudar código.

**Três resultados descartados ou refeitos durante a auditoria:** o primeiro lote de `curl` era inválido (cookies não persistiam, e as recusas de CSRF pareciam logins); o "DocumentRoot em public" foi contaminado pelo `.htaccess` do diretório pai; e um "erro visível em produção" era OPcache segurando o config antigo. O que está acima é o resultado refeito.

**Comparação com os diagnósticos anteriores:**

| Item anterior | Agora |
|---|---|
| S1, S2 | Confirmados; S2 ampliado: admin rebaixado se re-promove |
| S3 | Confirmado, com o fato novo de que SRI é inviável no Tailwind CDN |
| S5 | Confirmado em execução |
| S6 (fluxo não executado) | **Confirmado ponta a ponta** |
| S7 | **Parcialmente corrigido:** vale só sem `AllowOverride` nos pais |
| S8, S9, S10, S11, S12 | Continuam |
| S13 | Atualizado: jsPDF e outros, com alcance avaliado |
| Plano da Etapa 11 | **Incompleto:** faltam os `onclick` (S-003) |
| Novos | S-009, S-011, I-001 a I-003, I-005 a I-007, R-001, R-002, R-003, R-005, entre outros |

---

# 8. QUAIS ERROS EU PRECISO APRENDER A ENXERGAR?

Não são 40 erros: são poucos raciocínios que se repetem. Cada um abaixo vale para qualquer linguagem. Os raciocínios estão na primeira pessoa de quem auditou.

## 1. Uma cópia de um fato envelhece (S-001)
1. **Raciocínio:** procurei onde o sistema decide "pode entrar". A resposta veio de `$_SESSION`. Perguntei: quem atualiza isso quando o admin desativa alguém? Ninguém.
2. **Sinal no código:** dado de permissão lido da sessão, de um token ou de um cache, e nunca comparado à origem. Um `UPDATE` de status que não mexe em nada além da tabela.
3. **Pergunta que faltou:** "Se eu mudar isso agora no banco, o que acontece com quem já está logado?"
4. **Em outro projeto:** JWT com `role` dentro, permissões em cache, Redis com perfil. Sempre que há duas cópias, pergunte qual manda e quando elas se reconciliam.
5. **Desde o desenho:** ao criar "desativar", escreva o teste com duas sessões primeiro: a vítima já logada e o admin agindo.

## 2. Escape depende de onde o dado cai (S-003)
1. **Raciocínio:** em vez de procurar `htmlspecialchars` faltando, olhei cada saída e perguntei em que linguagem ela aterrissa. Em `onclick="f('...')"` há duas: HTML e, dentro dele, JavaScript. Depois, testei com o `'` bruto, porque o escape duplo escondia o problema.
2. **Sinal no código:** `<?=` dentro de `onclick`, `<script>`, `href`, `style`. Aspas de uma linguagem dentro de aspas de outra.
3. **Pergunta que faltou:** "Quem vai interpretar este texto, e em quantas camadas?"
4. **Em outro projeto:** `dangerouslySetInnerHTML`, templates dentro de `<script>`, `href` com `javascript:`.
5. **Desde o desenho:** dado vai para `data-*` e o comportamento fica no JS. Assim, só existe um contexto, HTML, e a CSP passa a ser possível.

**Lição extra:** uma proteção acidental é uma dívida. Antes de remover uma camada, pergunte "o que ela vinha protegendo sem ninguém saber?" e teste sem ela.

## 3. Interface não é autorização (S-008, I-001)
1. **Raciocínio:** vi um `if` escondendo o card de saldo e fui chamar a API direto, com a sessão do funcionário.
2. **Sinal no código:** `if ($tipo == 1)` numa view, sem o equivalente na rota. Valor sensível vindo de campo de formulário, mesmo que pré-preenchido.
3. **Pergunta que faltou:** "E se a pessoa usar `curl` em vez da tela?" e "Esse valor deveria mesmo vir do cliente?"
4. **Em outro projeto:** preço no carrinho, `user_id` no corpo, botão desabilitado como regra.
5. **Desde o desenho:** escreva a matriz papel × operação antes das telas, e transforme cada célula em teste.

## 4. Toda exceção a uma regra precisa passar pela regra (I-003)
1. **Raciocínio:** encontrei "não pode desativar a si mesmo" em `toggleStatus` e procurei **todas as outras formas** de escrever na mesma coluna. Havia `salvar()`.
2. **Sinal no código:** a regra está num controller, e o `UPDATE` da mesma coluna aparece em outro lugar.
3. **Pergunta que faltou:** "Quantos caminhos alteram este campo?" Um `grep` no nome da coluna responde.
4. **Em outro projeto:** vale para saldo, status de pedido, papel. Regra na rota é contornável por outra rota.
5. **Desde o desenho:** é o que o projeto já faz certo em `RegistrarMovimentacao`: um único lugar que escreve. Use o mesmo raciocínio para tudo que tem invariante.

## 5. Sucesso de um comando não é sucesso da operação (I-002, R-001)
1. **Raciocínio:** li `return $stmt->execute()` e pensei: e se o `WHERE` não achar nada? Testei com id 999999. Depois, no JS, li "nunca rejeita" e fui ver quem ainda usava `catch`.
2. **Sinal no código:** `if ($resultado)` depois de `execute()`; `(data || [])`; `catch` que nunca dispara; "se não tem dados, retorna" sem avisar.
3. **Pergunta que faltou:** "O que a pessoa vê se isto falhar?" Se a resposta é igual ao caso vazio, há um bug.
4. **Em outro projeto:** `|| []`, `?? 0`, `try/catch` vazio, dashboards em zero durante uma queda.
5. **Desde o desenho:** distinga três estados em toda tela: carregando, vazio e erro.

## 6. Conversão silenciosa não é validação (I-005)
1. **Raciocínio:** vi `(int)$_POST[...]` e perguntei o que acontece com `1e3`, `2abc` e `[]`. Enviei os três.
2. **Sinal no código:** casts na entrada; `floatval`; ausência de resposta 422 para tipo errado; a mesma regra no JS, mas não no PHP.
3. **Pergunta que faltou:** "Qual é a lista de entradas que devo aceitar? Todo o resto é recusado?"
4. **Em outro projeto:** `parseInt('2abc')` é 2 em JS; `int()` de Python falha, mas `float('inf')` passa.
5. **Desde o desenho:** "parseie, não sanitize": ou o dado vira um tipo válido, ou a requisição é recusada. Um helper resolve o projeto todo.

## 7. O ambiente de teste pode esconder o bug (S-006, R-002)
1. **Raciocínio:** o teste de arquivos internos passava. Perguntei: em quantas configurações isso roda? Testei quatro. Vi também que o `sed` do Dockerfile muda justamente o que importa, e que meu primeiro teste caiu nessa mesma armadilha.
2. **Sinal no código:** um Dockerfile alterando permissões globais; proteção que depende de um arquivo opcional; "funciona na minha máquina" com banco e PHP no mesmo fuso.
3. **Pergunta que faltou:** "O que é diferente no servidor de verdade?" (raiz, fuso, usuário, módulos, HTTPS).
4. **Em outro projeto:** variáveis de ambiente padrão, `localhost` como exceção de CORS, relógio igual.
5. **Desde o desenho:** escreva um script de verificação pós-deploy, e faça a configuração segura ser a padrão.

## 8. Mensagens de erro carregam dados (S-009)
1. **Raciocínio:** procurei o que acontece no `catch`: `getMessage()` de um erro de banco. Sabia que `Duplicate entry` repete o valor, e forcei o caso.
2. **Sinal no código:** `error_log($e)`, `getMessage()` de exceção de banco ou HTTP, `json_encode($dados)` num log de ação.
3. **Pergunta que faltou:** "Se este log vazar, quem é prejudicado?"
4. **Em outro projeto:** Sentry com corpo da requisição, logs de ORM com parâmetros, URLs com token.
5. **Desde o desenho:** logue identificadores e códigos, não valores. Também repare no que deu certo: o PHP 8.2 protegeu a senha do banco porque marca aquele parâmetro como sensível, e eu só soube disso testando em vez de presumir.

## 9. Valor do usuário só é seguro depois de validado para aquele uso (S-007)
1. **Raciocínio:** `REQUEST_URI` vai para `Location`. Quem controla a URI? O atacante, no link. O Router olha uma versão dela (`parse_url`) e o redirect usa outra, a crua.
2. **Sinal no código:** `header('Location: ' . $algo)`; duas funções interpretando o mesmo texto de jeitos diferentes.
3. **Pergunta que faltou:** "Quem escreve este valor, e o que acontece se começar com `//`?"
4. **Em outro projeto:** `?next=`, `returnUrl`, callbacks de OAuth.
5. **Desde o desenho:** guarde um caminho validado ou um nome de rota, nunca uma URL.

## 10. Ausência de limite é uma decisão (S-002, R-006, P-001)
1. **Raciocínio:** para cada entrada que dispara trabalho caro, perguntei "o que impede repetir ou pedir o máximo?". Enviei de `0001` até `9999`.
2. **Sinal no código:** consulta sem `LIMIT`, laço controlado por parâmetro, operação cara (hash, PDF) sem contador.
3. **Pergunta que faltou:** "E se pedirem um milhão, ou um milhão de vezes?"
4. **Em outro projeto:** paginação sem máximo, upload sem teto, reenvio de e-mail.
5. **Desde o desenho:** toda lista tem página, todo período tem teto, e toda operação cara tem limite.

## 11. Rede e duas fontes de verdade (I-007, R-002)
1. **Raciocínio:** "o botão fica desabilitado" resolve o clique, mas o que ocorre se a resposta se perder? O servidor gravou, e a pessoa tenta de novo. Da mesma forma, perguntei quem gera a data: o PHP filtra e o banco grava.
2. **Sinal no código:** POST que soma ou subtrai, sem chave; `date()` no PHP e `CURRENT_TIMESTAMP` no SQL.
3. **Pergunta que faltou:** "E se isto rodar duas vezes?" e "Quem é o relógio?"
4. **Em outro projeto:** webhooks, filas, pagamentos.
5. **Desde o desenho:** chave de idempotência em operações que movem valor, e uma única fonte para cada fato.

## Como auditar sem IA: um roteiro de 6 passos

1. **Desenhe o caminho do dado:** de onde ele vem, onde é guardado, onde é exibido.
2. **Liste os estados:** "desativado" muda o quê, e para quem já está logado?
3. **Para cada campo, use o `grep`:** quantos lugares o escrevem? Onde aparece na saída, e em qual contexto?
4. **Teste em execução, com `curl`,** o que a interface impede: outro usuário, outro id, id inexistente, `[]`, `1e3`, `'`, `//`.
5. **Provoque a falha:** derrube o banco, retorne 500, atrase 2 s, envie duas vezes. Olhe a tela e o log.
6. **Desconfie dos seus resultados:** como testar que o teste testou? Um "302 → dashboard" só vale como login se a próxima página responder 200. Foi assim que descobri que meu primeiro lote estava errado.

---

# 9. As cinco perguntas

**1. Quais riscos reais existem hoje?**
Acesso que não termina (S-001); força bruta no login (S-002); XSS de funcionário para admin ao concluir a Etapa 11 como planejada (S-003); scripts de terceiros no login e tráfego sem proteção, ao publicar (S-004, S-005); relatórios que mentem sem avisar (I-001, I-006, R-001); CPF em log (S-009); e, no local, uma máquina de desenvolvimento alcançável pela rede com senha pública (S-011). Em estoque e SQL, as provas não encontraram falha.

**2. Por que existem?**
Quase todos têm uma de quatro causas: fato copiado sem reconciliação; regra posta num caminho e não no dado; proteção acidental ou só na interface; e falha tratada como caso vazio. Não é falta de ferramentas nem de arquitetura.

**3. Como poderiam ter sido evitados?**
Com as perguntas da seção 8 feitas ao desenhar cada função, e testes do caso adversário, no estilo do excelente teste de estoque. O projeto já sabe fazer isso: `RegistrarMovimentacao` foi construído assim, e por isso resistiu. A receita é repetir aquele cuidado em usuários e sessão.

**4. O que corrigir antes de produção?**
A tabela "Obrigatórias" da seção 5, a começar por S-003, S-001 e S-002.

**5. Como reconhecer em projetos futuros, sem IA?**
O roteiro de 6 passos da seção 8. A pergunta que resume todos: **"O que acontece se isto for chamado por alguém que não usa a minha tela, num momento que eu não previ, e a parte seguinte falhar?"**
