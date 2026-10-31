# Avaliação técnica: performance e boas práticas

Situação em 2026-10-31, no commit `7db37b0` da `main`. Este documento registra uma avaliação pontual e não é atualizado conforme os itens são resolvidos. Ele complementa o [Diagnóstico de desempenho e segurança](diagnostico-desempenho-seguranca.md): aqui estão as métricas, as notas e as medições em execução; lá está a lista completa de problemas, com os IDs `S` e `D` citados abaixo.

## Resultado

**Nota geral: 5,1 de 10**, com o critério de pronto para produção. O sistema atende ao uso local atual, mas ainda não deve ir para a internet.

| Critério | Peso | Pontos | Nota |
|---|---|---:|---:|
| Testes | 15% | 9 de 12 | 7,5 |
| Integridade dos dados | 10% | 8 de 14 | 5,7 |
| Manutenibilidade | 15% | 9 de 16 | 5,6 |
| Segurança | 20% | 12 de 22 | 5,5 |
| Performance do backend e do banco | 15% | 7 de 14 | 5,0 |
| Performance do frontend | 15% | 4 de 14 | 2,9 |
| Automação e entrega | 10% | 4 de 14 | 2,9 |

- **Mais forte:** os testes e a regra de estoque. Passaram 66 testes PHPUnit e 70 E2E em MariaDB 10.4 e em MySQL 8.0.
- **Mais fraco:** o carregamento do frontend e a falta de CI e de análise estática.
- **Maior risco de crescimento:** com 100 mil movimentações, a tela `/movimentacoes` gasta 9,5 s no servidor e gera 172 MB de HTML.
- **Primeiro passo:** limitar as tentativas de login e revalidar o usuário na sessão.

A nota mede a distância até um deploy seguro. Ela não compara o projeto com outros, porque não há base de comparação.

## Como a avaliação foi feita

| Item | Como |
|---|---|
| Código | Leitura do backend, das views, do JS, do schema e dos `.htaccess` |
| Testes | PHPUnit e `tests/e2e/rodar.sh`, nos dois bancos |
| Banco | Consultas reais do sistema em um banco temporário, apagado ao final, com 200 produtos e até 100 mil movimentações sintéticas distribuídas em 2 anos. Cada tempo é a mediana de 5 execuções |
| Telas | Chromium sem interface, via Playwright, com cache desligado e mediana de 3 carregamentos no Apache local |
| Login e rotas | `curl` no Apache local, em `bd_sistema`, com os usuários do seed |
| Dependências | `composer audit`, `npm audit` e tamanho dos arquivos baixados dos CDNs |

### Limites

- **Ambiente:** as medições rodaram em uma máquina de desenvolvimento em uso, com XAMPP, PHP 8.2.12 e MariaDB 10.4.32. O OPcache, o `mod_deflate` e o `mod_expires` estão desligados. Os números valem como ordem de grandeza e comparação, não como previsão de um servidor.
- **Dados sintéticos:** o HTML gerado é muito repetitivo, então a compressão medida tende a ser otimista.
- **Telas:** `bd_sistema` tinha 14 produtos e nenhuma movimentação. A medição mostra o custo de carregar a tela, não o de muitos dados. Não houve limitação de rede nem de CPU.
- **Momento da medição:** as medições de banco, telas e login foram feitas pouco antes do merge `7db37b0`, que moveu `config`, `database` e `storage` para `backend/`. As linhas citadas e os testes foram conferidos nesse commit.
- **O que não foi feito:** análise estática (o download do PHPStan foi bloqueado no ambiente), cobertura de testes (não há Xdebug nem PCOV), Lighthouse, teste de carga e teste de invasão.
- **Julgamento:** pesos e limites são escolhas desta avaliação, adaptadas a um sistema interno de estoque que guarda dados pessoais. Quando há referência externa, ela aparece na tabela.

**Tipos de evidência**

| Tipo | Significado |
|---|---|
| Medido | Número obtido em execução |
| Confirmado | Comportamento reproduzido em execução |
| Contagem | Obtido por busca no repositório |
| Leitura | Concluído lendo o código, sem reproduzir |

## Métricas de avaliação

### Pontuação

Cada indicador vale **2** (atende), **1** (atende em parte) ou **0** (não atende). A nota do critério é `pontos ÷ máximo × 10`, e a nota geral é a média ponderada pelos pesos. Assim, qualquer pessoa pode refazer a conta e contestar um indicador específico.

### Por que esses pesos

Os critérios seguem características da ISO/IEC 25010 (eficiência de desempenho, segurança, confiabilidade e manutenibilidade). A norma foi usada só para organizar a avaliação, e isto não é uma certificação.

| Peso | Critérios | Motivo |
|---|---|---|
| 20% | Segurança | O sistema guarda CPF, RG, endereço e dados financeiros |
| 15% cada | Frontend, backend, testes, manutenibilidade | São o foco do pedido e o que sustenta a evolução do projeto |
| 10% cada | Integridade, automação | A integridade já tem boa base, e a automação ajuda mas não afeta o usuário diretamente |

### Sensibilidade aos pesos

| Perfil de pesos | Nota |
|---|---:|
| Foco em segurança: Segurança 35, Integridade 15, Backend 15, demais 5 a 10 | 5,3 |
| Atual | 5,1 |
| Pesos iguais | 5,0 |
| Foco em performance: Frontend 30, Backend 30, demais 5 a 10 | 4,6 |

A nota fica entre 4,6 e 5,3, então a conclusão não depende da escolha exata dos pesos.

## Notas por critério

### Performance do frontend: 2,9

| ID | Indicador | Regra para 2 / 1 / 0 | Medição | Tipo | Pontos |
|---|---|---|---|---|---:|
| FE-1 | Transferência da tela mais pesada | ≤ 500 KB / ≤ 1 MB / maior | 1.091 KB em `/relatorios` | Medido | 0 |
| FE-2 | First Contentful Paint (Lighthouse, desktop) | ≤ 0,9 s / ≤ 1,6 s / maior | De 0,30 s a 0,48 s, em ambiente local | Medido | 2 |
| FE-3 | Recursos que bloqueiam a renderização | ≤ 1 / 2 a 3 / 4 ou mais | 4 nas telas internas | Medido | 0 |
| FE-4 | CSS gerado antes do deploy | Sim / parcial / no navegador | Tailwind Play CDN | Leitura | 0 |
| FE-5 | Bibliotecas só onde são usadas | Sempre / parcial / nunca | O JS é por tela, mas o Chart.js vem em todas | Leitura | 1 |
| FE-6 | Imagem proporcional ao uso | Sim / até 2× / maior | 938 px exibida com 75 px (334 KB) | Medido | 0 |
| FE-7 | Compressão e cache | Ativos e versionados / só configurados / ausentes | Regras no `.htaccess`, módulos desligados, sem versão na URL | Confirmado | 1 |

### Performance do backend e do banco: 5,0

| ID | Indicador | Regra para 2 / 1 / 0 | Medição | Tipo | Pontos |
|---|---|---|---|---|---:|
| BE-1 | Sem consultas N+1 | Nenhuma / rara / frequente | Nenhum laço com consulta | Contagem | 2 |
| BE-2 | Listagens paginadas | Todas / as principais / poucas | 1 de 4 (só Usuários) | Leitura | 0 |
| BE-3 | Filtros usam índice | Todos / a maioria / poucos | 2 filtros com `DATE()` fazem varredura | Medido | 1 |
| BE-4 | Agregação no banco | Sempre / parcial / nunca | Dashboard em parte; relatório no PHP | Leitura | 1 |
| BE-5 | Tela mais pesada com 100 mil linhas | ≤ 1 s / ≤ 3 s / maior | 9,5 s no servidor | Medido | 0 |
| BE-6 | Índices sem redundância | 0 repetidos / até 3 / mais | 3 repetidos | Confirmado | 1 |
| BE-7 | Dashboard com 100 mil linhas | ≤ 1 s / ≤ 3 s / maior | 0,56 s | Medido | 2 |

### Segurança: 5,5

As referências são o OWASP ASVS e os guias de autenticação e de senhas da OWASP.

| ID | Indicador | Situação | Tipo | Pontos |
|---|---|---|---|---:|
| SEG-1 | SQL parametrizado | Prepared statements reais em todas as consultas | Leitura | 2 |
| SEG-2 | Proteção contra CSRF | Central em POST e DELETE, mas o logout funciona por GET | Confirmado | 1 |
| SEG-3 | Armazenamento de senhas | Argon2id com 64 MiB, `t=4`, `p=3`, acima do mínimo da OWASP (19 MiB, `t=2`, `p=1`) | Leitura | 2 |
| SEG-4 | Limite de tentativas de login | Nenhum: 21 senhas erradas seguidas, sem bloqueio | Confirmado | 0 |
| SEG-5 | Login não revela contas | Conta existente leva 157 ms; inexistente, 29 ms | Medido | 0 |
| SEG-6 | Sessão | Boas flags e troca de ID, mas sem revalidação do usuário e sem `Secure` | Leitura | 1 |
| SEG-7 | Proteção contra XSS | Views escapam na saída; os modais dependem do escape na entrada | Leitura | 1 |
| SEG-8 | Cabeçalhos de segurança | Sem CSP e sem HSTS; `X-XSS-Protection` está obsoleto | Confirmado | 1 |
| SEG-9 | Dependências | 0 alertas no Composer e no npm, mas CDNs sem SRI e sem auditoria | Medido | 1 |
| SEG-10 | Erros não vazam detalhes | Tratamento central e detalhe só no log | Leitura | 2 |
| SEG-11 | Autorização | Middleware em todas as rotas, mas funcionário acessa dados financeiros (S5) | Leitura | 1 |

### Integridade dos dados: 5,7

| ID | Indicador | Situação | Tipo | Pontos |
|---|---|---|---|---:|
| DAD-1 | Transação e lock no estoque | `FOR UPDATE` e transação, com teste do lock | Confirmado | 2 |
| DAD-2 | Restrições no banco | FKs, `UNIQUE` e `CHECK` no saldo | Leitura | 2 |
| DAD-3 | Erros de unicidade tratados | Produtos sim; usuários caem em erro genérico | Confirmado | 1 |
| DAD-4 | Chave estrangeira estável | Movimentações usam `marca_produto`, um texto editável (Etapa 12) | Leitura | 0 |
| DAD-5 | Validação no servidor | Desigual: o relatório aceita `abc` como data | Confirmado | 1 |
| DAD-6 | Auditoria | Registra o estoque, mas sem usuário nem IP | Leitura | 1 |
| DAD-7 | Valores monetários | `DECIMAL` no banco, `float` no PHP | Leitura | 1 |

### Testes: 7,5

| ID | Indicador | Situação | Tipo | Pontos |
|---|---|---|---|---:|
| TES-1 | Suíte unitária passa | 66 testes e 152 asserções, em 2,9 s | Medido | 2 |
| TES-2 | Regra crítica coberta | Saldo, confirmação, rollback, lock, auditoria e `CHECK` | Contagem | 2 |
| TES-3 | Features com PHPUnit | 3 de 7 (faltam Autenticação, Fornecedores, Produtos e Usuários) | Contagem | 1 |
| TES-4 | E2E em bancos reais | 70 testes em MariaDB 10.4 e MySQL 8.0, cerca de 4 min por banco | Medido | 2 |
| TES-5 | Cobertura medida | Não há driver de cobertura instalado | Confirmado | 0 |
| TES-6 | Isolamento do banco de teste | Recusa rodar fora de `bd_sistema_teste` | Leitura | 2 |

### Manutenibilidade: 5,6

| ID | Indicador | Regra para 2 / 1 / 0, ou situação | Medição | Tipo | Pontos |
|---|---|---|---|---|---:|
| MAN-1 | Organização | Por feature e sem camadas misturadas | `backend/Features`, `frontend/views` | Leitura | 2 |
| MAN-2 | Documentação | Atual e fácil de navegar | README, arquitetura, rotas, plano e roteiro | Leitura | 2 |
| MAN-3 | Tamanho dos arquivos | Nenhum acima de 400 linhas / até 5 / mais | 5 (o maior tem 499) | Contagem | 1 |
| MAN-4 | Tipagem | Retorno tipado em ≥ 80% / ≥ 50% / menos | Cerca de 43%; `strict_types` em 0 de 32 arquivos | Contagem | 0 |
| MAN-5 | Pouca duplicação | Listas de `SELECT` e sanitização repetidas | 3 controllers repetem a sanitização | Contagem | 1 |
| MAN-6 | Padrões consistentes | `try/catch` e acesso ao banco variam | 39 `catch`; 2 formas de acesso ao banco | Contagem | 1 |
| MAN-7 | JS e HTML separados | JS em arquivos próprios, com resíduos | 23 `onclick`; 5 `style` inline | Contagem | 1 |
| MAN-8 | Sem código morto | Restos pequenos | `layout.php:12-23`; campos antigos em `layout.js` | Leitura | 1 |

A contagem de tipagem usa uma busca por texto, que ignora assinaturas em várias linhas e construtores. O valor real pode ser um pouco maior, mas não muda a faixa enquanto `strict_types` estiver em zero.

### Automação e entrega: 2,9

| ID | Indicador | Situação | Tipo | Pontos |
|---|---|---|---|---:|
| AUT-1 | CI | Não existe | Contagem | 0 |
| AUT-2 | Análise estática | Não configurada | Contagem | 0 |
| AUT-3 | Formatador e linter | Não configurados | Contagem | 0 |
| AUT-4 | Dependências travadas e auditadas | Com lockfiles, mas auditoria manual | Medido | 1 |
| AUT-5 | Ambiente de teste reproduzível | E2E em Docker, com script único | Confirmado | 2 |
| AUT-6 | Migrações de banco | Ainda não; decisão consciente até o primeiro deploy | Leitura | 1 |
| AUT-7 | Build do frontend | Não existe | Leitura | 0 |

## Medições

### Crescimento das movimentações

| Medida | 1 mil | 10 mil | 100 mil |
|---|---:|---:|---:|
| `listarTodas()` | 146 ms | 721 ms | 3.020 ms |
| Renderização da view | 16 ms | 221 ms | 6.496 ms |
| **Tela no servidor** | **162 ms** | **941 ms** | **9.516 ms** |
| Memória da consulta | 1 MB | 10 MB | 99 MB |
| HTML da view | 1,9 MB | 17,3 MB | 172 MB |
| HTML com gzip | 32 KB | 257 KB | 2,4 MB |
| Relatório anual: consulta | 63 ms | 220 ms | 1.423 ms |
| Relatório anual: JSON | 49 KB | 304 KB | 2,7 MB |
| Dashboard: todas as consultas | 198 ms | 415 ms | 558 ms |

Na hipótese de 50 movimentações por dia, 10 mil linhas equivalem a cerca de 7 meses e 100 mil a cerca de 5 anos e meio. Esse volume diário é uma suposição, porque não há dados reais. O tempo acima não inclui rede nem o navegador montando a tabela.

### Índice de data

Contagem do dia com 100 mil linhas:

| Consulta | `EXPLAIN` | Tempo |
|---|---|---:|
| `WHERE DATE(data_movimentacao) = ?` | `type=index`, cerca de 99.644 linhas | 58,3 ms |
| `WHERE data_movimentacao >= ? AND data_movimentacao < ?` | `type=range`, cerca de 8 linhas | 0,4 ms |

A forma com faixa é cerca de 145 vezes mais rápida e examina muito menos linhas. Em tempo absoluto, o ganho ainda é pequeno, por isso o item ficou com prioridade baixa. Com 1 mil linhas, os tempos ficaram dominados por ruído e não servem de comparação.

### Telas

| Tela | Primeira pintura | Evento `load` | Requisições | Transferido | Bloqueantes | Recursos externos |
|---|---:|---:|---:|---:|---:|---:|
| `/login` | 476 ms | 505 ms | 7 | 635 KB | 3 | 3 |
| `/dashboard` | 388 ms | 413 ms | 14 | 740 KB | 4 | 3 |
| `/produtos` | 416 ms | 425 ms | 14 | 801 KB | 4 | 3 |
| `/movimentacoes` | 420 ms | 411 ms | 14 | 749 KB | 4 | 3 |
| `/relatorios` | 300 ms | 419 ms | 19 | 1.091 KB | 4 | 6 |

Os tempos são bons porque o servidor é local e a rede não foi limitada. Os indicadores mais úteis aqui são o volume transferido e os recursos bloqueantes, que pesam mais em uma conexão real. Em `/login`, a logo sozinha representa cerca de metade do que é transferido.

### Bibliotecas externas

| Biblioteca | Onde carrega | Transferido | Sem compressão |
|---|---|---:|---:|
| Tailwind 3.4.17 | todas as telas | 126 KB | 407 KB |
| Chart.js 4.6.1 | telas internas | 61 KB | 210 KB |
| Font Awesome 6.4.0 | todas as telas | 23 KB | 102 KB |
| jsPDF 2.5.1 | relatórios | 95 KB | 364 KB |
| html2canvas 1.4.1 | relatórios | 38 KB | 199 KB |
| SheetJS (xlsx) 0.18.5 | relatórios | 201 KB | 882 KB |

Os valores transferidos diferem dos do diagnóstico porque cada CDN escolhe entre gzip e Brotli conforme o pedido. Em `/relatorios`, as bibliotecas somam 544 KB transferidos, dos quais 334 KB só são necessários ao exportar. Todo o JS do projeto soma 105 KB, ou 20 KB com gzip.

### Login e rotas

| Verificação | Resultado |
|---|---|
| Conta existente, senha errada (bcrypt do seed) | Mediana de 157 ms, 21 tentativas |
| Conta inexistente | Mediana de 29 ms, 63 tentativas |
| Verificar senha com o Argon2id do sistema | 291 ms nesta medição (o diagnóstico registrou 600 ms), o que aumentaria a diferença |
| 21 senhas erradas seguidas | Todas com resposta 302, sem bloqueio |
| `?data_inicio[]=x` | Resposta 500 |
| `?data_inicio=abc&data_fim=def` | Resposta 200 com `sucesso: true` e relatório vazio |
| `composer audit` e `npm audit` | Nenhum alerta (só cobrem dependências de desenvolvimento) |

**Resultado não explicado:** na primeira rodada, 3 de 21 tentativas com conta inexistente responderam 200 em vez de 302. Isso não se repetiu em outras 63 tentativas, e a causa não foi identificada. A mediana da tabela usa só as 63 respostas 302.

## Achados priorizados

| # | Prioridade | Achado | Evidência | Correção | ID no diagnóstico |
|---|---|---|---|---|---|
| 1 | Alta | Login sem limite de tentativas | Confirmado; [AuthController.php:21](../backend/Features/Autenticacao/AuthController.php#L21) | Limitar por conta e por IP | S1 |
| 2 | Alta | Usuário desativado, excluído ou rebaixado mantém o acesso | Leitura; [AuthMiddleware.php:13](../backend/Features/Autenticacao/AuthMiddleware.php#L13) | Conferir o usuário no banco a cada requisição | S2 |
| 3 | Alta | Histórico de movimentações sem paginação | Medido; [MovimentacaoModel.php:31](../backend/Features/Movimentacoes/MovimentacaoModel.php#L31) | Paginar como em Usuários | D1 |
| 4 | Alta | Tailwind compilado no navegador; a documentação indica o Play CDN só para desenvolvimento | Medido; [layout.php:62](../frontend/views/layout.php#L62) | Gerar CSS estático | D2 |
| 5 | Média | Usuário excluído bloqueia o próprio CPF: a verificação diz que está livre, e o `UNIQUE` recusa com erro genérico | Confirmado; [UsuarioModel.php:436](../backend/Features/Usuarios/UsuarioModel.php#L436) | Decidir entre reativar o cadastro ou mostrar o motivo | Novo |
| 6 | Média | Escape na entrada grava texto alterado, e os modais dependem dele | Leitura; [UsuarioController.php:270](../backend/Features/Usuarios/UsuarioController.php#L270), [usuarios.js:421](../public/assets/js/usuarios.js#L421) | Etapa 11, junto com os modais | Plano |
| 7 | Média | Relatório sem validar datas e trazendo todas as linhas para o PHP | Confirmado; [RelatorioController.php:46](../backend/Features/Relatorios/RelatorioController.php#L46) | Validar, limitar o período e agregar no banco | D6 |
| 8 | Média | Bibliotecas bloqueantes fora de onde são usadas | Medido; [layout.php:65](../frontend/views/layout.php#L65), [relatorios/index.php:86](../frontend/views/relatorios/index.php#L86) | `defer`; exportação sob demanda | D4 |
| 9 | Média | Logo de 334 KB exibida com 75 px em todas as telas | Medido; [layout.php:163](../frontend/views/layout.php#L163) | Reduzir para cerca de 150 px | Novo |
| 10 | Média | Sem CI nem análise estática | Contagem | CI com PHPUnit, PHPStan e formatador | Novo |
| 11 | Média | Cobertura de testes não é medida | Confirmado | Instalar PCOV ou Xdebug | Novo |
| 12 | Baixa | `DATE()` impede o uso eficiente do índice | Medido; [RelatorioConsultas.php:30](../backend/Features/Relatorios/RelatorioConsultas.php#L30), [DashboardConsultas.php:26](../backend/Features/Dashboard/DashboardConsultas.php#L26) | Usar faixa de datas | D5 |
| 13 | Baixa | Tempo de resposta do login revela se a conta existe | Medido; [AuthController.php:34](../backend/Features/Autenticacao/AuthController.php#L34) | Verificar contra um hash fictício | S8 |
| 14 | Baixa | RG vazio passa pelo servidor e falha no `UNIQUE` no segundo cadastro. O formulário exige o campo, então só ocorre fora da tela | Confirmado no model; [UsuarioController.php:199](../backend/Features/Usuarios/UsuarioController.php#L199), [schema.sql:12](../backend/database/schema.sql#L12) | Tornar o RG obrigatório também no servidor | Novo |

Duas prioridades diferem do diagnóstico:

- **`DATE()` (D5):** passou de médio para baixo, porque a medição mostrou 58 ms com 100 mil linhas. A correção continua simples.
- **Tempo do login (S8):** continua baixo, mas agora tem medição.

Os itens específicos de deploy (S3, S4, S7, S11 e S12) e a decisão de permissões (S5) estão descritos no [diagnóstico](diagnostico-desempenho-seguranca.md#problemas). Eles também precisam ser resolvidos antes de publicar. O OPcache (D3) não entra na nota por ser configuração do ambiente, mas é uma correção barata.

**SheetJS:** a versão 0.18.5 é afetada por CVE-2023-30533 e CVE-2024-22363. Hoje o risco prático é baixo, porque o sistema só grava planilhas, com [`XLSX.writeFile`](../public/assets/js/relatorios.js#L466), e não tem upload de arquivos. Vale atualizar ao mover as bibliotecas para o projeto. O registro npm não recebe versões corrigidas, que são distribuídas pelo CDN da própria SheetJS.

## O que preservar

- **Regra de estoque:** caso de uso único, com transação, lock e testes, inclusive de rollback e de espera pelo lock.
- **SQL:** prepared statements reais, sem `SELECT *` e sem N+1.
- **Senhas e CSRF:** Argon2id acima do mínimo da OWASP e verificação central de CSRF.
- **Testes E2E:** rodam em dois bancos e cobrem casos difíceis, como HTML em nomes de produto e token adulterado.
- **Organização:** estrutura por feature e documentação atualizada.
- **Dashboard:** continua em 0,56 s com 100 mil linhas, porque a maior parte do cálculo fica no banco.

## Plano e metas

A nota acumulada considera que cada indicador da fase chegue à meta. É um teto de referência, não uma promessa.

| Fase | O que entra | Indicadores | Nota acumulada |
|---|---|---|---:|
| 1. Sessão, login e cadastro | Achados 1, 2, 5, 13 e 14 | SEG-4, SEG-5, SEG-6, DAD-3 | 5,6 |
| 2. Escala e etapas planejadas | Achados 3, 6, 7 e 12, Etapas 11 e 12 | BE-2 a BE-6, SEG-7, DAD-4, DAD-5 | 6,7 |
| 3. Frontend | Achados 4, 8 e 9, versão na URL | FE-1, FE-3 a FE-7, AUT-7 | 7,9 |
| 4. Qualidade contínua | Achados 10 e 11, `strict_types` | AUT-1 a AUT-3, TES-3, TES-5, MAN-4 | 8,9 |

As metas abaixo também servem de roteiro para a reavaliação:

| Métrica | Hoje | Meta |
|---|---:|---:|
| Tela de movimentações, 100 mil linhas, no servidor | 9.516 ms | ≤ 1.000 ms |
| HTML da tela de movimentações | 172 MB | ≤ 200 KB |
| Consulta do dia com 100 mil linhas | 58 ms | ≤ 5 ms |
| Tela mais pesada, transferência | 1.091 KB | ≤ 500 KB |
| Recursos bloqueantes nas telas internas | 4 | ≤ 1 |
| Logo | 334 KB | ≤ 30 KB |
| Tentativas de login sem bloqueio | Sem limite | ≤ 5 por conta na janela definida |
| Diferença de tempo no login | 128 ms | Sem diferença consistente |
| Features com PHPUnit | 3 de 7 | 7 de 7 |
| Cobertura de linhas | Não medida | Medir primeiro e definir a meta depois |
| Funções com retorno tipado | Cerca de 43% | ≥ 80% |
| Erros do PHPStan | Não medido | Definir o nível inicial e chegar a zero |

**Como reavaliar:** repita as medições depois de cada fase, com o mesmo volume de dados, e atualize os pontos pela regra de cada indicador. Os scripts usados nesta avaliação não foram versionados, então a primeira reavaliação precisa recriá-los a partir da descrição em [Como a avaliação foi feita](#como-a-avaliação-foi-feita). Para comparações mais confiáveis, ligue o OPcache e meça sempre no mesmo ambiente.

## Referências

Páginas consultadas em 2026-10-31, exceto onde indicado:

- [Tailwind CSS: Play CDN](https://v3.tailwindcss.com/docs/installation/play-cdn): uso indicado só para desenvolvimento.
- [Lighthouse: First Contentful Paint](https://developer.chrome.com/docs/lighthouse/performance/first-contentful-paint): limites de FCP para desktop e mobile.
- [MariaDB: como montar o melhor índice](https://mariadb.com/kb/en/building-the-best-index-for-a-given-select/): função sobre a coluna e filtros por faixa de datas.
- [OWASP: Authentication Cheat Sheet](https://cheatsheetseries.owasp.org/cheatsheets/Authentication_Cheat_Sheet.html): limite de tentativas e enumeração de contas por tempo de resposta.
- [OWASP: Password Storage Cheat Sheet](https://cheatsheetseries.owasp.org/cheatsheets/Password_Storage_Cheat_Sheet.html): parâmetros mínimos do Argon2id.
- [MDN: X-XSS-Protection](https://developer.mozilla.org/en-US/docs/Web/HTTP/Reference/Headers/X-XSS-Protection): cabeçalho obsoleto e recomendação de CSP.
- [CVE-2023-30533](https://cvefeed.io/vuln/detail/CVE-2023-30533) e [CVE-2024-22363](https://cvefeed.io/vuln/detail/CVE-2024-22363): falhas da SheetJS.
- [SheetJS: pedido de publicação da correção no npm](https://git.sheetjs.com/sheetjs/sheetjs/issues/2961).

Fontes secundárias, porque o texto oficial não foi consultado:

- [Resumo do OWASP ASVS](https://quality.arc42.org/standards/owasp-asvs).
- [Guia da ISO/IEC 25010 da Sonar](https://www.sonarsource.com/resources/library//iso-iec-25010-explained/).
