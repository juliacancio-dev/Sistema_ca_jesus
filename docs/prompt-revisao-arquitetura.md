# Prompt — Revisão arquitetural e continuidade do Sistema CA Jesus

Você atuará como Senior Software Engineer, Code Reviewer e Software Architect deste repositório: **Sistema de Gestão de Estoque CA Jesus**.

Seu trabalho é revisar o projeto em profundidade e conduzir uma refatoração incremental que o transforme em um código profissional, coerente e fácil de manter — **sem trocar de stack** e **sem quebrar o que já funciona**.

---

## 1. CONTEXTO REAL DO PROJETO

Não assuma a stack: ela é esta.

| Camada | Tecnologia atual |
|---|---|
| Backend | PHP ≥ 7.4, sem framework, MVC caseiro |
| Banco | MySQL (PDO), com **triggers e stored procedures** em `app/database/bancoInteiro.sql` |
| Frontend | Views PHP server-side + Tailwind (CDN) + JS inline nas views + `public/assets/js/app.js` e `charts.js` (Chart.js) |
| Servidor | Apache + `.htaccess` (mod_rewrite), rodando em `/sistema_ca_jesus/public` (XAMPP local) |
| Autoload | `spl_autoload_register` manual em `public/index.php`. `composer.json` declara PSR-4 `App\\`, mas **nenhuma classe usa namespace** |
| Testes | Não existem |
| Integrações externas | ViaCEP (chamado direto do JS nas views) |

**Fora de escopo:** migrar para outra stack (FastAPI, Next.js, Laravel, PostgreSQL etc.). Se em algum momento você concluir que uma migração seria a melhor decisão, apresente isso como recomendação separada, com custo e risco — nunca execute.

### Funcionalidades existentes (pontos de entrada em `public/index.php`)

- **Autenticação**: login, logout, timeout de sessão (`AuthController`, `AuthHelper`, `AuthMiddleware`)
- **Dashboard**: estatísticas e gráficos (`DashboardController`, `/api/dashboard/estatisticas`)
- **Produtos**: CRUD e busca (`ProdutoController`, `ProdutoModel`)
- **Movimentações**: registrar entrada/saída, consultar estoque atual (`MovimentacaoController`, `MovimentacaoModel`)
- **Relatórios**: financeiro e de estoque por período (`RelatorioController`, `RelatorioModel`)
- **Usuários** (admin): CRUD, ativar/inativar; **Perfil** (qualquer usuário): editar dados, alterar senha (`UsuarioController`, `UsuarioModel`)
- **Fornecedores** (admin): CRUD, ativar/inativar (`FornecedorController`, `FornecedorModel`)

### Pontos de atenção já observados (hipóteses — confirme com evidência antes de agir)

Use esta lista como ponto de partida, não como verdade. Verifique cada item no código e acrescente o que encontrar.

**Higiene do repositório / segurança**
- Não há `.gitignore`. Cerca de 400 arquivos de log estão versionados em `armazenamento/logs/` (~2,6 MB), alguns contendo a palavra "senha". `AuthMiddleware` e `Router` gravam `$_SESSION` e `$_SERVER` inteiros no log a cada requisição.
- Credenciais de banco commitadas em `config/database.php`.
- `README.md` descreve arquivos que não existem (`config/database.example.php`, `app/Database/` com migrations) e cita Bootstrap, mas as views usam Tailwind. `raiz de diretorios.txt` descreve uma estrutura antiga (`tests/`, `populate_db.php`, `.qodo/`) que não está no repo. `index.css` na raiz parece sobra de outro projeto (`#root`).

**Configuração**
- Configuração definida em três lugares com valores divergentes: `app/core/config.php`, `config/config.php`, `config/database.php` (ex.: `BASE_URL` = `/sistema_ca_jesus` em um, URL absoluta em outro). `public/index.php` faz `require` de `config/config.php` duas vezes.
- O caminho `/sistema_ca_jesus/public` está fixo em ~50 lugares (controllers, middleware, views, JS).
- `session_start()` é chamado em vários pontos (config, Router, index, middlewares, controllers).
- O nome do banco aparece como `bd_sistema` no PHP e `bd_Sistema` no SQL (case importa em Linux).

**Regra de negócio espalhada — o caso mais importante**
- A regra "saída não pode exceder o estoque" existe em pelo menos quatro lugares: `MovimentacaoController::registrar`, `MovimentacaoModel::registrar`, o trigger `before_movimentacao_insert` e a procedure `registrar_movimentacao_segura`. A atualização do saldo é feita pelo trigger `after_movimentacao_insert`, mas `MovimentacaoModel` também tem `atualizarEstoqueProduto`.
- O cálculo de lucro está no model PHP; a coluna `lucro_movimentacao` também pode ser derivada no banco — verifique.
- Movimentações referenciam produto por `marca_produto` (texto), não por `id_produto`. O controller percorre `listarTodos()` em PHP para achar o produto pela marca.
- Decida **onde mora a regra de estoque** (PHP ou banco) e elimine as demais cópias. Apresente as opções com prós/contras antes de decidir (ver seção 6).

**Código possivelmente morto**
- `EstoqueModel` e `LogModel` não são referenciados fora de si mesmos.
- `DiagnosticoHelper` é usado só pela view do dashboard e gerou centenas de arquivos `diagnostico_dashboard_*.log`.
- `main.php` chama `/public/api/search/simple.php`, que não existe.
- `csrf_field()` nunca é usado; verifique se o token CSRF é de fato validado em algum POST.
- `verificarEstrutura()` em `UsuarioModel` e `FornecedorModel` (checagem de schema em runtime).
- Classes de `app/core/Exceptions.php`: verifique quais são lançadas e capturadas de verdade.
- Os `.htaccess` dentro de cada subpasta de `app/` são redundantes se o `.htaccess` raiz já bloqueia `app/` — confirme antes de remover.

**Duplicação**
- Formatação: funções globais em `helpers/functions.php` (`formatCpf`, `formatCurrency`…) **e** `FormatHelper` com a mesma responsabilidade.
- Validação de CNPJ: `FornecedorController::validarCNPJ` e `validateCnpj()` em `functions.php`.
- Rotas-alias para a mesma ação: `/usuarios/salvar`, `/store`, `/update/{id}`, `/atualizar/{id}` → todas `UsuarioController::salvar` (idem fornecedores). Descubra quais o frontend realmente usa.
- Chamada ao ViaCEP copiada em `fornecedor/index.php`, `usuario/index.php` e `usuario/perfil.php`.
- Três mecanismos de mensagem: `$_SESSION['error']`/`['success']`, `$_SESSION['flash']` e toast. Verifique se `flash` chega a ser exibido.
- Chamadas `fetch` com URL montada de formas diferentes (`BASE_URL`, caminho fixo, `app.js`).

**Responsabilidades no lugar errado**
- `RelatorioController` agrega e monta dados financeiros e de estoque (lógica de domínio no controller).
- `DashboardController` calcula estatísticas em métodos privados.
- `Controller::renderView` decide layout pelo prefixo do caminho, injeta usuário e flash, e renderiza HTML de erro.
- `RelatorioModel::exportarCSV` mistura persistência com formato de saída.
- Arquivos muito grandes misturando HTML, JS de comportamento, chamadas de API e validação: `usuario/index.php` (~1100 linhas), `relatorio/index.php` e `fornecedor/index.php` (~850), `main.php` (~660), `UsuarioModel` e `UsuarioController` (~750).

**Erros e logging**
- `error_log` de debug em quase todo arquivo, com mensagens informais ("tamo junto", "deu ruim").
- `Database` em erro de conexão chama `ErrorHelper::displayToast()` (camada de infraestrutura renderizando UI).
- `ErrorHelper`, `LogHelper` e `error_log` coexistem sem uma estratégia única.

---

## 2. PRINCÍPIOS

```
COERÊNCIA                      > COMPLEXIDADE
CLAREZA                        > SOFISTICAÇÃO
RESPONSABILIDADE BEM DEFINIDA  > QUANTIDADE DE CAMADAS
MANUTENIBILIDADE               > ABSTRAÇÕES PREMATURAS
CONSISTÊNCIA                   > SOLUÇÕES ISOLADAS
```

- É um sistema pequeno, mantido por uma pessoa. A estrutura deve ser proporcional a isso.
- Não introduza framework, container de DI, ORM, interfaces, factories ou repositories sem um problema concreto que justifique.
- Não preserve estrutura ruim só porque existe; não crie estrutura nova só para parecer profissional.
- Toda alteração precisa responder: **"Qual problema essa mudança resolve?"**

---

## 3. ARQUITETURA-ALVO: VERTICAL SLICES EM PHP SIMPLES

Hoje o projeto é organizado por tipo técnico (`controllers/`, `models/`, `views/`, `helpers/`). A arquitetura desejada é **Vertical Slice**: agrupar por funcionalidade o que muda junto.

Esboço conceitual (não copie mecanicamente; o tamanho de cada slice deve seguir a complexidade real):

```
app/
  Core/                 # Router, Database, View/Response, Session, Config — infraestrutura sem regra de negócio
  Features/
    Autenticacao/       # login, logout, middleware de auth/admin
    Dashboard/
    Produtos/
    Movimentacoes/      # registrar entrada/saída, estoque atual — onde mora a regra de estoque
    Relatorios/
    Usuarios/           # gestão (admin)
    Perfil/             # editar perfil, alterar senha — avalie se é slice próprio ou parte de Usuarios
    Fornecedores/
  Shared/               # só o que é realmente compartilhado: formatação, validação CPF/CNPJ, layout, partials
public/
  index.php             # bootstrap + rotas
  assets/
```

Para cada slice, defina: caso de uso, entrada, saída, regras de negócio, tabelas acessadas, dependências de outros slices.

Decisões que você deve tomar e justificar:
- Adotar namespaces + autoload do Composer (já declarado em `composer.json`) ou manter o autoloader manual?
- Views ficam dentro do slice (`Features/Produtos/views/`) ou em `app/views/<feature>/`?
- JS de cada tela: continua inline, ou vai para `public/assets/js/<feature>.js`?
- Tabelas acessadas por mais de um slice (`tbprodutos` é lida por Produtos, Movimentações, Dashboard e Relatórios): quem é dono, e como os demais leem?

`Shared/` não pode virar depósito. Se algo é usado por um único slice, fica no slice.

---

## 4. COMO TRABALHAR

### Fase A — Diagnóstico (antes de alterar qualquer código)

1. Leia `public/index.php`, `app/core/*`, todos os controllers, models, o SQL e as views principais. Monte o mapa: rota → controller → model → tabela/trigger → view → JS → endpoint de API chamado.
2. Confirme ou refute cada hipótese da seção 1, com evidência (arquivo:linha, resultado de busca).
3. Entregue o relatório no formato da seção 7. **Pare e aguarde minha aprovação.**

### Fase B — Execução incremental (quando eu autorizar)

Ordem sugerida — ajuste se encontrar motivo, explicando:

1. **Higiene e segurança**: `.gitignore` (logs, sessões, `vendor/`, `config/database.php`), remover logs versionados, criar `config/database.example.php`, parar de logar sessão/`$_SERVER`, corrigir README e remover `raiz de diretorios.txt` desatualizado.
2. **Configuração única**: um só ponto de configuração; `BASE_URL` derivado uma vez e usado em todos os lugares (PHP e JS); um só `session_start()`.
3. **Código morto**: remover o que foi comprovado sem uso.
4. **Regra de estoque**: escolher uma localização, eliminar cópias, avaliar troca de `marca_produto` por `id_produto` (é **mudança de schema** — tratar separadamente e com script de migração).
5. **Duplicações**: formatação, validação CPF/CNPJ, rotas-alias, ViaCEP, mensagens de feedback.
6. **Reorganização em slices**, uma feature por vez, começando pela mais simples (ex.: Fornecedores) para validar o padrão antes de aplicá-lo às demais.
7. **Views grandes**: separar JS de comportamento do HTML; extrair partials apenas quando realmente reutilizados.
8. **Erros e logging**: uma estratégia única; nada de infraestrutura renderizando HTML.

Para cada etapa: objetivo, arquivos afetados, motivo, risco, como validar.

### Validação após cada conjunto de mudanças

Não há testes nem linter configurados. No mínimo:
- `php -l` em todos os arquivos `.php` alterados;
- busca por referências quebradas (nomes de classe, rotas, caminhos de view, URLs em `fetch`);
- me fornecer um **roteiro de teste manual** das telas afetadas (login, cadastrar produto, registrar entrada e saída, saída acima do estoque, relatório por período, ativar/inativar usuário e fornecedor).

Se propor introduzir PHPUnit, comece pelo que tem mais regra e mais risco (registro de movimentação, cálculos de relatório) e justifique o custo.

---

## 5. O QUE REVISAR EM CADA ARQUIVO

Aplique estas perguntas onde fizer sentido, sem transformar em checklist mecânico:

- **Arquivo**: por que existe? tem uma responsabilidade? o nome diz isso? pertence a outro slice? ainda é usado?
- **Função**: faz uma coisa? o nome descreve o comportamento? mistura regra com HTTP, sessão ou SQL? dá para testar isoladamente?
- **Controller**: deve só ler a requisição, validar entrada, chamar o caso de uso e responder (view, JSON ou redirect). Loops de agregação, montagem de relatório e consultas de domínio saem daqui.
- **Model/SQL**: queries duplicadas; N+1; `listarTodos()` usado para buscar um item; transações mal delimitadas; regra de negócio duplicada entre PHP e trigger; checagem de schema em runtime.
- **View**: lógica de negócio no template; JS gigante inline; `fetch` com URL fixa; validação que só existe no frontend (precisa existir também no backend).
- **Duplicação**: separe duplicação real (mesmo conceito, muda junto) de semelhança acidental. Só extraia quando for o mesmo conceito.
- **Abstrações**: `Database` é um singleton que só repassa métodos do PDO — isso agrega valor? `Exceptions.php` define classes que alguém captura? Um wrapper que não reduz complexidade deve sair.
- **Nomes**: português e inglês misturados (`buscar`/`getDados`/`listar`/`usuariosJson`); nomes de tabela e coluna (`tbprodutos`, `estoque_produto`). Padronize o código PHP; não renomeie tabelas sem tratar como mudança de schema.
- **Erros**: `catch (Exception)` genérico, erro convertido em falso sucesso, mensagens diferentes para a mesma falha, `exit` no meio de helpers.
- **Comentários**: remova os que repetem o código; mantenha os que explicam decisão ou restrição.

---

## 6. REGRAS DE DECISÃO E DE SEGURANÇA DA MUDANÇA

**Classifique toda alteração:**
- **REFATORAÇÃO** — muda estrutura, preserva comportamento.
- **CORREÇÃO** — muda comportamento porque o atual está errado (ex.: chamada para endpoint inexistente).
- **MELHORIA** — adiciona capacidade (ex.: CSRF de verdade, testes).
- **MUDANÇA DE SCHEMA** — altera tabela, trigger ou procedure; exige script de migração e aviso explícito.

Nunca misture correção de bug com refatoração no mesmo passo sem avisar.

**Quando houver alternativas reais**, apresente:

```
OPÇÃO A — vantagens / desvantagens / impacto
OPÇÃO B — vantagens / desvantagens / impacto
RECOMENDAÇÃO — e por quê, para este projeto
```

Casos em que isso é obrigatório: onde mora a regra de estoque (PHP vs. trigger), namespaces/Composer vs. autoload manual, onde ficam as views, separar ou não Perfil de Usuários.

**Não deixe o código antigo coexistindo com o novo.** Ao mover algo, atualize todas as referências e remova a origem.

**Não altere o banco de produção.** Mudanças de schema vão como arquivo SQL de migração para eu aplicar.

---

## 7. FORMATO DO RELATÓRIO INICIAL (Fase A)

1. **Mapa do sistema** — tabela por funcionalidade: rotas, controller, model, tabelas/triggers, view, endpoints JS.
2. **Diagnóstico da arquitetura atual** — como está organizado de fato e onde é incoerente.
3. **Hipóteses da seção 1** — CONFIRMADA / REFUTADA / PARCIAL, com evidência.
4. **Problemas** classificados em CRÍTICO / ALTO / MÉDIO / BAIXO.
5. **Código morto** — com evidência de não uso.
6. **Duplicações reais** — e como consolidar.
7. **Responsabilidades fora do lugar.**
8. **Overengineering e abstrações sem função.**
9. **Estrutura proposta** — árvore de diretórios, e para cada mudança relevante:
   ```
   ANTES:  caminho atual
   DEPOIS: novo caminho
   AÇÃO:   MANTER | MOVER | RENOMEAR | DIVIDIR | JUNTAR | REFATORAR | EXCLUIR | CRIAR
   MOTIVO: justificativa técnica
   ```
10. **Plano de execução** — etapas em ordem, com risco e forma de validação.

## 8. AO FINAL DA REFATORAÇÃO

**Revisão pós-refatoração**: ainda há duplicação, código morto, regra espalhada, caminho fixo, nome vago, inconsistência de padrão? Um desenvolvedor novo encontra onde se registra uma saída de estoque em menos de um minuto?

**Avaliação** — classifique como EXCELENTE / BOM / PRECISA MELHORAR / PROBLEMÁTICO, com justificativa (sem EXCELENTE por cortesia): clareza, coesão, acoplamento, consistência, manutenibilidade, testabilidade, simplicidade, ausência de duplicação, ausência de código morto, aderência a vertical slices, segurança básica (credenciais, logs, CSRF, sessão).

**Seção obrigatória — O QUE EU PRECISO APRENDER A ENXERGAR NO MEU PRÓPRIO CÓDIGO**

Com base no que apareceu *neste* projeto, identifique meus padrões de erro. Para cada um:

```
PROBLEMA OBSERVADO:     (com exemplo concreto deste repo)
POR QUE É UM PROBLEMA:
COMO IDENTIFICAR SOZINHA:
PERGUNTA QUE EU DEVERIA ME FAZER:
```

Exemplos do tipo de padrão esperado:
- *Regra de estoque em quatro lugares* → "Se a regra de saída mudar amanhã, quantos arquivos eu preciso alterar — e quantos eu lembraria?"
- *Caminho `/sistema_ca_jesus/public` fixo em 50 lugares* → "Se o sistema for publicado em outra pasta, o que quebra?"
- *Logs de debug commitados* → "Este arquivo foi gerado pela aplicação ou escrito por mim? Se foi gerado, por que está no git?"
- *Rotas-alias para a mesma ação* → "Quem chama esta rota? Se eu não sei, por que ela existe?"

---

## REGRA FINAL

Se algo está bom, mantenha. Se está desnecessário, remova. Se está duplicado, consolide. Se está no lugar errado, mova. Se uma abstração não tem propósito, simplifique.

O resultado deve parecer um sistema pequeno planejado conscientemente — não um conjunto de arquivos acumulados por tentativa e erro, e também não uma arquitetura corporativa aplicada a um problema que não pede isso.
