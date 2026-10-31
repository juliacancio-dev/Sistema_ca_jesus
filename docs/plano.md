# Plano da refatoração

Situação em 2026-10-31. As regras que este plano segue estão em [`arquitetura.md`](arquitetura.md); os motivos das decisões, no [diagnóstico](historico/diagnostico-fase-a.md).

## Situação

| Etapa | Situação |
|---|---|
| 1 a 9 | Concluídas |
| 10. Views | **Em andamento** (`refactor/10-views`) |
| Mover `config/` e `database/` | Pendente, depois da etapa 10 |
| 11. Escape na entrada | Pendente |
| 12. `marca_produto` → `id_produto` | Pendente |

## O que falta

### Etapa 10: Views

Tirar o JS das telas e levá-lo para `public/assets/js/`, com `cep.js` e `formatacao.js` compartilhados. Tailwind, FontAwesome e Chart.js ficam só no layout, com versão fixa.

- **Risco:** médio (visual).
- **Como validar:** comparar cada tela; console do navegador sem erro; testes E2E.

### Mover `config/` e `database/` para dentro de `backend/`

Decisão de 2026-10-31, em branch própria. `public/` continua na raiz.

- **Precisa atualizar:** os caminhos em `public/index.php`, `tests/` e `tests/e2e/`, o `.htaccess` e o `.gitignore`, além do README e da arquitetura.
- **Risco:** baixo.
- **Como validar:** `composer test`, testes E2E, e recriar o banco seguindo o README.

### Etapa 11: Escape na entrada

Parar de gravar `htmlspecialchars()` no banco em Usuários, Perfil e Fornecedores. Não precisa migrar dados: o banco é recriado.

**Precisa ir junto:** o escape com `escapeHtml` nos modais de detalhe (`showUsuarioDetails` em `usuarios.js` e `showFornecedorDetails` em `fornecedores.js`). Hoje eles só estão protegidos porque os dados são gravados já escapados; sem isso, a etapa abre uma falha de XSS.

- **Risco:** baixo.
- **Como validar:** cadastrar "D'Ávila & Filhos" e ver o nome correto na lista e no modal; cadastrar um nome com `<b>` e ver o texto literal no modal.

### Etapa 12: `marca_produto` → `id_produto`

Movimentações passam a referenciar o produto pelo id, editando `database/schema.sql`. Resolve o erro ao renomear um produto que já tem movimentações. **É preciso recriar o banco.**

- **Afeta:** Movimentações, Relatórios e Dashboard.
- **Risco:** médio.
- **Como validar:** `composer test`; renomear um produto com histórico; os relatórios mostram o nome novo.

## O que já foi feito

| Etapa | Entregou | Commit |
|---|---|---|
| 1. Higiene | `.gitignore`, logs fora do git, `config.example.php`, README real | `8cc663c` |
| 2. Configuração | `BASE_URL` calculado, um único `session_start()`, tratamento único de erros | `5aace58` |
| 3. Código morto | Cerca de 2.800 linhas removidas | `b9d5716` |
| 4. Correções | Mensagens ao usuário, fornecedor inativo, escape de saída | `61ba78c` |
| 5. Composer e PHPUnit | Autoload PSR-4 e testes | `983582f` |
| 6. Contrato JSON | Formato único de resposta | `15bf577` |
| 7. CSRF | Token em todos os POST e DELETE | `2974e7c` |
| 8. Regra de estoque | Caso de uso em PHP com lock e `CHECK` no banco | `9fa7185` |
| 9. Slices | `backend/Features/` e `frontend/views/`; `app/` removida | `0221226` |
| Testes E2E | Playwright com Docker, em MariaDB e MySQL | `119c7aa` |
| Perfil | Cada formulário grava só os próprios campos | `70a5d7d` |

## Como cada etapa é entregue

1. Branch própria a partir da `main`.
2. Testes automatizados e [roteiro manual](roteiro-de-testes.md) passando.
3. Merge na `main` com `--no-ff`.
4. Este plano atualizado.
