# Diagnóstico de desempenho e segurança

Situação em 2026-10-31, na branch `refactor/10-views`, incluindo as alterações ainda não commitadas (`config/`, `database/` e `storage/` movidos para `backend/`). Este documento registra uma análise pontual e não é atualizado conforme os itens são resolvidos. O que falta fazer na refatoração continua em [`plano.md`](plano.md).

## Conclusão

O sistema está adequado ao uso local atual, mas ainda não está pronto para a internet.

- **Segurança:** a base é sólida (SQL, CSRF, senhas, tratamento de erros). Faltam proteções no login, na sessão e na implantação.
- **Desempenho:** não há sinal de gargalo no volume atual, que é o de um banco de desenvolvimento. O maior risco é a tela de movimentações, cujo HTML cresce sem limite junto com o histórico.

## Como a análise foi feita

| Item | Como |
|---|---|
| Código | Leitura do backend, das views e do JS, com buscas por saídas sem escape e por `innerHTML` |
| Configuração | `.htaccess`, `.gitignore`, `backend/database/schema.sql` e `C:\xampp\php\php.ini` |
| Histórico do git | Procura por credenciais em `config/config.php` e em commits que mexem com senhas ou chaves |
| Medições | Script PHP e `curl`, descritos em [Métricas](#métricas) |

**O que não foi feito:** a aplicação não foi executada. Não houve teste de carga, Lighthouse, medição de tempo de resposta (TTFB), `EXPLAIN` com dados reais nem teste de invasão. Os itens marcados como **leitura** vêm da leitura do código e não foram reproduzidos em execução.

## Critérios

**Gravidade em segurança**

| Nível | Significado |
|---|---|
| Alta | Permite tomar uma conta, manter um acesso que deveria ter sido removido, capturar senhas ou derrubar o serviço |
| Média | Exige uma condição específica, ou expõe dados a quem já tem login |
| Baixa | Dá pouca vantagem a um atacante, ou é uma proteção adicional |

"Antes de publicar" indica um item sem efeito prático no XAMPP local, mas que precisa estar resolvido antes do primeiro deploy.

**Impacto em desempenho**

| Nível | Significado |
|---|---|
| Alto | Afeta todas as páginas, ou deixa uma tela inutilizável conforme os dados crescem |
| Médio | Afeta uma tela ou consulta, ou causa problema depois de um deploy |
| Baixo | Custo pequeno; só compensa corrigir junto com outra mudança |

## Métricas

### Medidas

**Custo do hash de senha.** PHP 8.2.12 do XAMPP, nesta máquina, em linha de comando, com 11 repetições de cada operação. Foram usados os mesmos parâmetros de `UsuarioModel::gerarHashDeSenha` (`memory_cost` 65536 KiB, `time_cost` 4, `threads` 3).

| Operação | Mediana | Mínimo | Máximo |
|---|---|---|---|
| Argon2id, gerar hash | 849 ms | 204 ms | 1.288 ms |
| Argon2id, verificar senha | 600 ms | 219 ms | 1.710 ms |
| bcrypt custo 10, verificar senha (hash do seed) | 121 ms | 97 ms | 178 ms |

A variação é grande porque a máquina estava em uso normal. Os números servem como ordem de grandeza, não como referência de um servidor. A memória de 64 MB por operação vem da configuração e não foi medida.

**Tamanho do HTML de `/movimentacoes`.** A linha real da tabela foi renderizada com dados de exemplo e deu **1.776 bytes** por movimentação. O volume diário abaixo é uma hipótese, porque não há dados reais.

| Linhas | Cenário | HTML sem compressão |
|---|---|---|
| 1.000 | cerca de 20 dias a 50 por dia | 1,7 MB |
| 18.250 | 1 ano a 50 por dia | 30,9 MB |
| 91.250 | 5 anos a 50 por dia | 154,6 MB |

A compressão reduz a transferência, mas não o trabalho de gerar a página nem o de o navegador montar a tabela. O gzip não foi medido com dados variados, então não há número confiável para ele.

**Bibliotecas externas.** Bytes baixados dos CDNs em 2026-10-31.

| Biblioteca | Onde carrega | Com gzip | Sem compressão |
|---|---|---|---|
| Tailwind 3.4.17 | todas as telas | 123 KB | 407 KB |
| Chart.js 4.6.1 | todas as telas, exceto login | 71 KB | 210 KB |
| Font Awesome 6.4.0 | todas as telas | 22 KB | 102 KB |
| jsPDF 2.5.1 | relatórios | 117 KB | 364 KB |
| html2canvas 1.4.1 | relatórios | 46 KB | 199 KB |
| SheetJS (xlsx) 0.18.5 | relatórios | 315 KB | 882 KB |

| Tela | Bibliotecas externas, com gzip |
|---|---|
| Login | 145 KB, mais as fontes do Google (não medidas) |
| Telas internas | 216 KB |
| Relatórios | 694 KB |

Para comparar, todo o JS e o CSS do próprio projeto somam 106 KB sem compressão, e cada tela carrega só parte disso.

**Dependências**

| Verificação | Resultado |
|---|---|
| Pacotes de produção no `composer.lock` | 0 |
| Pacotes de desenvolvimento no `composer.lock` | 27 |
| `npm audit` em `tests/e2e` | 0 vulnerabilidades |
| `composer audit` | Não rodou: o Composer não está no PATH |
| Bibliotecas via CDN | Sem auditoria: o `npm audit` não cobre esses arquivos |

**Configuração do PHP no XAMPP**

| Diretiva | Valor | Efeito |
|---|---|---|
| OPcache (`php.ini`, linha 964) | desligado | O PHP recompila os arquivos a cada requisição |
| `session.gc_maxlifetime` | 1440 s (24 min) | Menor que os 7200 s de inatividade do sistema |
| `expose_php` | On | Informa a versão do PHP nas respostas |
| `zend.exception_ignore_args` | Off | Os traces do log podem conter argumentos das funções |

**Tamanho do projeto.** Contagens por `grep` e `wc`, para dar a dimensão da análise.

| Item | Valor |
|---|---|
| Rotas | 28 (4 públicas, 15 de login, 9 de admin) |
| Linhas de PHP no backend | 2.856 |
| Linhas de PHP nas views | 2.409 |
| Linhas de JS | 2.572 |
| Chamadas `prepare()` e `query()` no backend | 45 |
| Ocorrências de `LIMIT` em `backend/Features` | 3 |
| Índices criados à parte no schema | 7, sendo 3 redundantes |
| Testes PHPUnit | 56 |
| Testes E2E | 67 |

## Segurança

### O que já está bom

| Área | Situação | Onde |
|---|---|---|
| SQL injection | Prepared statements reais (`ATTR_EMULATE_PREPARES` desligado). O único valor interpolado é o nome da tabela, uma constante da classe | [Database.php:19](../backend/Core/Database.php#L19) |
| CSRF | Verificação central em todo POST e DELETE, com `hash_equals` | [Router.php:46](../backend/Core/Router.php#L46), [Session.php:36](../backend/Core/Session.php#L36) |
| Sessão | `strict_mode`, `httponly`, `SameSite=Lax`; ID e token CSRF trocados no login | [index.php:41](../public/index.php#L41), [Auth.php:21](../backend/Core/Auth.php#L21) |
| Senhas | Novas senhas usam Argon2id; o hash é removido antes de ir para a sessão e para a API | [UsuarioModel.php:22](../backend/Features/Usuarios/UsuarioModel.php#L22), [AuthController.php:42](../backend/Features/Autenticacao/AuthController.php#L42) |
| Erros | Mensagem genérica para o usuário e detalhe só no log, fora de `development` | [index.php:111](../public/index.php#L111) |
| XSS nas views | Texto livre passa por `htmlspecialchars`; o JSON do dashboard usa `JSON_HEX_*` | [dashboard/index.php:141](../frontend/views/dashboard/index.php#L141) |
| Admin | Usuários e fornecedores protegidos por middleware na rota | [index.php:73](../public/index.php#L73) |
| Credenciais no git | O `config.php` antigo do histórico tem usuário `root` e senha vazia; o atual está no `.gitignore` | [.gitignore](../.gitignore) |

### Problemas

| ID | Gravidade | Problema | Evidência | Correção sugerida |
|---|---|---|---|---|
| S1 | Alta | O login não limita tentativas. Permite força bruta e, como cada verificação Argon2id custa em torno de 600 ms e 64 MB, também facilita derrubar o servidor | Leitura e medição; [AuthController.php:21](../backend/Features/Autenticacao/AuthController.php#L21) | Limitar por conta e por IP, com bloqueio temporário |
| S2 | Alta | A sessão nunca é revalidada no banco. Usuário desativado, excluído ou rebaixado mantém o acesso enquanto a sessão durar, e trocar a senha não derruba outras sessões | Leitura; [AuthMiddleware.php:13](../backend/Features/Autenticacao/AuthMiddleware.php#L13), [Auth.php:47](../backend/Core/Auth.php#L47) | Conferir `ativo`, `tipo` e `deletado_em` a cada requisição autenticada |
| S3 | Alta, antes de publicar | Scripts de CDN rodam sem SRI e sem CSP, inclusive na tela de login. Um CDN comprometido poderia capturar senhas | Leitura; [login.php:11](../frontend/views/autenticacao/login.php#L11), [layout.php:62](../frontend/views/layout.php#L62) | Servir as bibliotecas localmente ou com SRI, e adicionar CSP |
| S4 | Alta, antes de publicar | O cookie de sessão não tem `Secure`, e não há HTTPS forçado nem HSTS | Leitura; [index.php:41](../public/index.php#L41) | Ativar `session.cookie_secure`, redirecionar para HTTPS e enviar HSTS |
| S5 | Média | Funcionário não vê o saldo no dashboard, mas acessa os dados financeiros por `/relatorios` e pela API. Também pode excluir produtos e alterar preços. O [`rotas.md`](rotas.md) documenta essas rotas como abertas a qualquer login | Leitura; [dashboard/index.php:54](../frontend/views/dashboard/index.php#L54), [index.php:92](../public/index.php#L92), [index.php:66](../public/index.php#L66) | Decisão de produto. Recomendação: restringir o relatório financeiro e a exclusão a admin |
| S6 | Média | Redirecionamento aberto: `//site-malicioso.com/dashboard` é aceito como rota e guardado para depois do login. Só funciona com o site na raiz do domínio, não em subpasta do XAMPP | `parse_url` medido; fluxo completo não executado; [AuthMiddleware.php:19](../backend/Features/Autenticacao/AuthMiddleware.php#L19), [AuthController.php:50](../backend/Features/Autenticacao/AuthController.php#L50) | Guardar só o caminho e recusar valores que comecem com `//` |
| S7 | Média | Os cabeçalhos de segurança e os bloqueios de pasta ficam só no `.htaccess` da raiz. Com `DocumentRoot` em `public/`, esse arquivo não é lido e os cabeçalhos somem | Leitura; [.htaccess:24](../.htaccess#L24), [public/.htaccess](../public/.htaccess) | Colocar os cabeçalhos também em `public/.htaccess` ou no VirtualHost |
| S8 | Baixa | O tempo de resposta do login revela se o e-mail existe, porque a senha só é verificada quando a conta existe. O comentário da linha 37 mostra a intenção de evitar essa descoberta | Leitura; [AuthController.php:34](../backend/Features/Autenticacao/AuthController.php#L34) | Verificar contra um hash fictício quando a conta não existir |
| S9 | Baixa | O logout funciona por GET, então um link externo consegue deslogar o usuário | Leitura; [index.php:57](../public/index.php#L57) | Usar POST com CSRF |
| S10 | Baixa | A auditoria de estoque não registra usuário nem IP: `usuario_id` é sempre `NULL`, e `ip_usuario` nunca é preenchido | Leitura; [schema.sql:131](../backend/database/schema.sql#L131) | Gravar a auditoria com esses dados, ou remover as colunas |
| S11 | Baixa, antes de publicar | O config de exemplo vem como `development`, com `root` sem senha, e o seed cria contas com a senha `123456789` | Leitura; [config.example.php:7](../backend/config/config.example.php#L7), [README.md:83](../README.md#L83) | Lista de verificação de deploy, sem seed em produção, com usuário de banco restrito |
| S12 | Baixa, antes de publicar | CPF, RG e endereço ficam sem criptografia no banco, e não há política de retenção para a LGPD | Leitura; [schema.sql:11](../backend/database/schema.sql#L11) | Definir retenção e acesso antes de cadastrar dados reais |
| S13 | Baixa | jsPDF 2.5.1 e SheetJS 0.18.5 estão desatualizadas. A falha conhecida da SheetJS (CVE-2023-30533) está na leitura de arquivos, e o projeto só usa escrita. O CVE é citado de memória e não foi consultado nesta análise | Leitura; [relatorios/index.php:86](../frontend/views/relatorios/index.php#L86), [relatorios.js:363](../public/assets/js/relatorios.js#L363) | Atualizar as versões junto com S3 |

**Já está no plano:** o escape nos modais de detalhe faz parte da [Etapa 11](plano.md#etapa-11-escape-na-entrada). Hoje eles só estão protegidos porque os dados são gravados já escapados ([usuarios.js:421](../public/assets/js/usuarios.js#L421), [fornecedores.js:348](../public/assets/js/fornecedores.js#L348)).

## Desempenho

### O que já está bom

- **Conexão:** o singleton mantém uma conexão por requisição, e só as páginas que consultam o banco a abrem.
- **Router:** testa as 28 rotas uma a uma, o que não pesa neste porte.
- **Estoque:** o `FOR UPDATE` trava só a linha do produto e dura pouco, então não deve gerar fila com poucos usuários.
- **Dashboard:** a maior parte dos indicadores já é agregada pelo banco.
- **Usuários:** a tela tem paginação.

### Problemas

| ID | Impacto | Problema | Evidência | Correção sugerida |
|---|---|---|---|---|
| D1 | Alto | `/movimentacoes` carrega e desenha o histórico inteiro, sem `LIMIT` nem paginação: 30,9 MB de HTML em um ano no cenário de 50 por dia | Tamanho medido, volume hipotético; [MovimentacaoModel.php:31](../backend/Features/Movimentacoes/MovimentacaoModel.php#L31) | Paginar como em Usuários, com índice em `(data_movimentacao, id_movimentacao)` |
| D2 | Alto | O Tailwind via CDN bloqueia o carregamento e gera o CSS no navegador a cada página; a documentação dele não o recomenda para produção. São 407 KB de JS para processar | Tamanho medido; [layout.php:62](../frontend/views/layout.php#L62) | Gerar um CSS estático com a CLI do Tailwind, sem introduzir build no restante do projeto |
| D3 | Alto | O OPcache está desligado no XAMPP | `php.ini`, linha 964 | Ativar no XAMPP e no servidor. É a correção mais simples da lista |
| D4 | Médio | O Chart.js bloqueia o carregamento e vem em todas as telas internas, embora só o dashboard e os relatórios desenhem gráficos. Os 478 KB dos relatórios também carregam sem `defer`, mesmo quando nada é exportado | Tamanho medido; [layout.php:65](../frontend/views/layout.php#L65), [relatorios/index.php:86](../frontend/views/relatorios/index.php#L86) | Carregar cada biblioteca só na tela que usa, com `defer`; as de exportação, ao clicar |
| D5 | Médio | `DATE(coluna)` impede o uso do índice de data | Leitura, sem `EXPLAIN`; [RelatorioConsultas.php:30](../backend/Features/Relatorios/RelatorioConsultas.php#L30), [DashboardConsultas.php:26](../backend/Features/Dashboard/DashboardConsultas.php#L26) | Trocar por `>= início AND < dia seguinte` |
| D6 | Médio | O relatório aceita qualquer período, sem limite nem validação, e traz todas as linhas para o PHP | Leitura; [RelatorioController.php:46](../backend/Features/Relatorios/RelatorioController.php#L46) | Validar as datas, limitar o período e agregar no banco |
| D7 | Médio | CSS e JS ficam um ano em cache, sem versão na URL. Depois de uma atualização, o navegador pode continuar usando arquivos antigos | Leitura; [public/.htaccess:33](../public/.htaccess#L33) | Versionar a URL (`?v=` com a data do arquivo) |
| D8 | Baixo | O sino de alertas busca todos os produtos a cada página carregada | Leitura; [layout.js:146](../public/assets/js/layout.js#L146) | Endpoint só com estoque baixo |
| D9 | Baixo | O dashboard percorre toda a tabela de movimentações para calcular o saldo, o que só pesa com muitos dados | Leitura; [DashboardConsultas.php:32](../backend/Features/Dashboard/DashboardConsultas.php#L32) | Reavaliar se ficar lento |
| D10 | Baixo | O gráfico busca as linhas de 7 dias e soma no PHP, embora o comentário da classe diga que o banco agrega | Leitura; [DashboardConsultas.php:64](../backend/Features/Dashboard/DashboardConsultas.php#L64), [DashboardController.php:35](../backend/Features/Dashboard/DashboardController.php#L35) | `GROUP BY` por dia e ação |
| D11 | Baixo | Três índices repetem os `UNIQUE` e só encarecem as escritas | Leitura; [schema.sql:102-104](../backend/database/schema.sql#L102-L104) | Remover os três |
| D12 | Baixo | Respostas `application/json` não entram nas regras de compressão | Leitura; [.htaccess:31](../.htaccess#L31), [public/.htaccess:42](../public/.htaccess#L42) | Adicionar o tipo ao `mod_deflate` |

**Já está no plano:** a [Etapa 12](plano.md#etapa-12-marca_produto--id_produto) troca a referência `marca_produto` por `id_produto`. O ganho dela é principalmente de integridade, e também evita comparar textos nessa relação.

## Outra inconsistência

A sessão promete 2 horas de inatividade ([Auth.php:57](../backend/Core/Auth.php#L57)), mas o `session.gc_maxlifetime` do XAMPP é de 24 minutos. Com isso, a sessão pode expirar antes do prazo. Se os 7200 s forem o valor desejado, é preciso definir o mesmo valor no `index.php`, junto dos outros `ini_set` de sessão.

## Ordem recomendada

1. **Agora:** D3, D1 e D5, que têm o melhor retorno pelo menor esforço.
2. **Junto com a Etapa 11:** S2 e S1, com S6 e S8 aproveitando a mexida no login.
3. **Antes de qualquer deploy:** S3, S4, S7, S11 e S12, junto com D2, D4 e D7. As bibliotecas locais resolvem parte de S3 e S13.
4. **Quando houver oportunidade:** os demais itens.

## Decisão pendente

**S5:** funcionário deve ver dados financeiros e poder excluir produtos? A recomendação é restringir os dois a administradores, porque esconder o saldo no dashboard hoje não protege nada.
