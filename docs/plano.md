# Plano da refatoração

Situação em 2026-10-31. As regras que este plano segue estão em [`arquitetura.md`](arquitetura.md); os motivos das decisões, no [diagnóstico](historico/diagnostico-fase-a.md).

## Situação

| Etapa | Situação |
|---|---|
| 1 a 10 | Concluídas |
| `config/`, `database/` e `storage/` em `backend/` | Concluída |
| 11. Escape na entrada | Concluída |
| 12. `marca_produto` → `id_produto` | Pendente |
| Correções da [auditoria de segurança](auditoria-seguranca.md) | Pendentes |

## O que falta

### Etapa 12: `marca_produto` → `id_produto`

Movimentações passam a referenciar o produto pelo id, editando `backend/database/schema.sql`. Resolve o erro ao renomear um produto que já tem movimentações. **É preciso recriar o banco.**

- **Afeta:** Movimentações, Relatórios e Dashboard.
- **Risco:** médio.
- **Como validar:** `composer test`; renomear um produto com histórico; os relatórios mostram o nome novo.

### Correções da auditoria de segurança

Detalhes, evidências e testes de cada ID estão na [auditoria](auditoria-seguranca.md). Cada linha pode ser uma branch própria.

**Decisões pendentes, antes de implementar os itens ligados a elas:**

- **S-010:** os dados do seed e do histórico do git são de pessoas ou empresas reais? O repositório é público.
- **S-008 e I-001:** o que o funcionário pode ver e fazer (financeiro, preços, exclusão de produtos, preço livre na movimentação).

**Obrigatórias antes de produção, nesta ordem:**

| # | Item | IDs |
|---|---|---|
| 1 | Revalidar o usuário a cada requisição, derrubar sessões na troca de senha, expiração absoluta | S-001 |
| 2 | Limite de tentativas no login e hash fictício para conta inexistente | S-002, S-012 |
| 3 | Validar o destino do redirecionamento pós-login | S-007 |
| 4 | Regra de último admin, 404 para id inexistente, validação estrita no servidor | I-003, I-002, I-005 |
| 5 | Relatório respeita o período escolhido, e falha aparece como falha na tela | I-006, R-001 |
| 6 | Uma só fonte de data entre PHP e banco | R-002 |
| 7 | Logs sem CPF, RG e e-mail | S-009 |
| 8 | Implementar as decisões sobre o funcionário | S-008, I-001 |
| 9 | CSS gerado e bibliotecas servidas localmente | S-004, P-002, S-015 |
| 10 | Pacote de implantação: `DocumentRoot` em `public/`, HTTPS, cabeçalhos, usuário de banco restrito, retenção e backup | S-005, S-006, S-016, L-001 |

**Recomendadas:** CSP em bloqueio, depois de remover os `onclick` (S-004); testes dos cenários sem cobertura (M-001); chave de idempotência nas movimentações (I-007); auditoria de operações administrativas (I-012); ciclo de vida da senha (S-014); logout por POST (S-013); paginar movimentações (P-001); fornecedor inativo (I-009); CPF e RG de excluídos (I-011); ViaCEP (R-003); parâmetros malformados (R-004); busca de usuários (R-005); limite do período do relatório (R-006); restringir o Apache local (S-011), que leva minutos.

**Futuras:** CI e PHPStan (M-002), `strict_types` gradual, coluna de lucro (I-010), custos menores de desempenho (P-003), divergências da documentação (M-003).

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
| 10. Views | JS em `public/assets/js/`; bibliotecas com versão fixa só no layout; XSS do dashboard corrigido | `15d2888` |
| 11. Escape na entrada | O banco guarda o dado como foi digitado; escape só na saída, modais e botões de excluir incluídos (S-003, I-004) | `fix/11-escape-na-entrada` |
| Pastas | `config/`, `database/` e `storage/` (antiga `armazenamento/`) dentro de `backend/`; `public/` continua na raiz | `refactor/config-database-no-backend` |

## Como cada etapa é entregue

1. Branch própria a partir da `main`.
2. Testes automatizados e [roteiro manual](roteiro-de-testes.md) passando.
3. Merge na `main` com `--no-ff`.
4. Este plano atualizado.
