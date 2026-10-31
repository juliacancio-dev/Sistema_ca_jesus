# Rotas

Fonte: `public/index.php`. As respostas JSON seguem o contrato de [`arquitetura.md`](arquitetura.md#contrato-das-respostas-json); a coluna **dados** diz o que vem nesse campo.

Permissão: **pública**, **login** (`AuthMiddleware`) ou **admin** (`AuthMiddleware` + `AdminMiddleware`). Sem login, uma rota JSON responde 401; sem ser admin, 403. Uma rota HTML redireciona com mensagem.

Rota não cadastrada: 404 em JSON (se a requisição espera JSON) ou página HTML.

Todo POST e DELETE exige o token CSRF (ver [`arquitetura.md`](arquitetura.md#csrf)). Sem token válido, uma rota JSON responde 403; um formulário volta para a página anterior com mensagem.

## Telas e formulários (HTML / redirect)

| Método | Rota | Permissão | Faz |
|---|---|---|---|
| GET | `/`, `/login` | pública | Tela de login |
| POST | `/login` | pública | Autentica; redireciona ao dashboard ou de volta com mensagem |
| GET | `/logout` | pública | Encerra a sessão |
| GET | `/dashboard` | login | Indicadores e gráficos |
| GET | `/produtos` | login | Lista e modal de cadastro |
| POST | `/produtos/salvar` | login | Cadastra; redirect + flash |
| POST | `/produtos/atualizar/{id}` | login | Atualiza; redirect + flash |
| GET | `/movimentacoes` | login | Lista e formulário |
| GET | `/relatorios` | login | Tela de relatórios (dados via `/api/relatorios/dados`) |
| GET | `/usuarios` | admin | Lista e modal |
| POST | `/usuarios/salvar` | admin | Cria ou atualiza; redirect + flash |
| GET | `/usuarios/perfil` | login | Perfil do próprio usuário |
| POST | `/usuarios/atualizarPerfil` | login | Atualiza o próprio perfil; redirect + flash |
| POST | `/usuarios/alterarSenha` | login | Troca a própria senha; redirect + flash |
| GET | `/fornecedores` | admin | Lista e modal (`?search=` filtra) |
| POST | `/fornecedores/salvar` | admin | Cria ou atualiza; redirect + flash |

## JSON

| Método | Rota | Permissão | Entrada | dados | Erros |
|---|---|---|---|---|---|
| GET | `/api/produtos/buscar` | login | `?id=` | produto (`id`, `marca`, `preco_custo`, `preco_venda`, `estoque`, `fornecedor_id`) | 404, 500 |
| DELETE | `/produtos/excluir/{id}` | login | — | `null` | 404; 409 com movimentações; 500 |
| POST | `/movimentacoes/registrar` | login | form: `produto`, `tipo`, `quantidade`, `preco_custo`, `preco_venda`, `observacao`, `confirmar_custo_maior` | `null` | `requer_confirmacao` se custo > venda sem confirmar; 404 produto; 422 regra de estoque ou campos; 500 |
| GET | `/api/estoque/atual` | login | — | lista de produtos com `estoque` | 500 |
| GET | `/api/relatorios/dados` | login | `?tipo=financeiro\|estoque&periodo=diario\|semanal\|mensal\|anual` ou `data_inicio`/`data_fim` | relatório (ver `RelatorioController`) | 422 tipo inválido; 500 |
| GET | `/usuarios/buscar/{id}` | admin | — | usuário, sem a senha | 404, 500 |
| POST | `/usuarios/toggle-status/{id}` | admin | JSON `{ "status": 0\|1 }` | `null` | 422 o próprio usuário; 404; 500 |
| POST | `/usuarios/excluir/{id}` | admin | — | `null` | 422 o próprio usuário; 404; 500 |
| POST | `/fornecedores/toggle-status/{id}` | admin | JSON `{ "status": 0\|1 }` | `null` | 404; 409 desativar com produtos; 500 |
| POST | `/fornecedores/excluir/{id}` | admin | — | `null` | 409 com produtos; 500 |

As ações de alterar status e excluir também gravam um flash de sucesso, mostrado depois que a tela recarrega.
