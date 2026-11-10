# Plano do projeto

Situação em 2026-10-31. As regras que este plano segue estão em [`arquitetura.md`](arquitetura.md); os motivos das decisões, no [diagnóstico](historico/diagnostico-fase-a.md).

## Situação

| Etapa | Situação |
|---|---|
| 1 a 10 | Concluídas |
| `config/`, `database/` e `storage/` em `backend/` | Concluída |
| 11. Escape na entrada | Concluída |
| 12. `marca_produto` → `produto_id` | Concluída |
| Correções da [auditoria de segurança](auditoria-seguranca.md) | Pendentes |

## O que falta

### Correções da auditoria de segurança

Detalhes, evidências e testes de cada ID estão na [auditoria](auditoria-seguranca.md). Cada linha pode ser uma branch própria.

**Decisões pendentes, antes de implementar os itens ligados a elas:**

- **S-008 e I-001:** o que o funcionário pode ver e fazer (financeiro, preços, exclusão de produtos, preço livre na movimentação).

**Obrigatórias antes de produção, nesta ordem:**

| # | Item | IDs |
|---|---|---|
| 1 | Uma só fonte de data entre PHP e banco | R-002 |
| 2 | Logs sem CPF, RG e e-mail | S-009 |
| 3 | Implementar as decisões sobre o funcionário | S-008, I-001 |
| 4 | CSS gerado e bibliotecas servidas localmente | S-004, P-002, S-015 |
| 5 | Pacote de implantação: `DocumentRoot` em `public/`, HTTPS, cabeçalhos, usuário de banco restrito, retenção e backup | S-005, S-006, S-016, L-001 |

**Recomendadas:** CSP em bloqueio, depois de remover os `onclick` (S-004); testes dos cenários sem cobertura (M-001); chave de idempotência nas movimentações (I-007); auditoria de operações administrativas (I-012); ciclo de vida da senha (S-014); logout por POST (S-013); paginar movimentações (P-001); fornecedor inativo (I-009); CPF e RG de excluídos (I-011); ViaCEP (R-003); parâmetros malformados (R-004); busca de usuários (R-005); restringir o Apache local (S-011), que leva minutos.

**Futuras:** CI e PHPStan (M-002), `strict_types` gradual, coluna de lucro (I-010), custos menores de desempenho (P-003), divergências da documentação (M-003).

## O que já foi feito

| Etapa | Entregou | Commit |
|---|---|---|
| 1. Higiene | `.gitignore`, logs fora do git, `config.example.php`, README real | `5e92e87` |
| 2. Configuração | `BASE_URL` calculado, um único `session_start()`, tratamento único de erros | `5e54c66` |
| 3. Código morto | Cerca de 2.800 linhas removidas | `e43bca4` |
| 4. Correções | Mensagens ao usuário, fornecedor inativo, escape de saída | `0b8ccf1` |
| 5. Composer e PHPUnit | Autoload PSR-4 e testes | `bf3f3a2` |
| 6. Contrato JSON | Formato único de resposta | `ef2da16` |
| 7. CSRF | Token em todos os POST e DELETE | `c9add92` |
| 8. Regra de estoque | Caso de uso em PHP com lock e `CHECK` no banco | `bfc2ca8` |
| 9. Slices | `backend/Features/` e `frontend/views/`; `app/` removida | `c96984f` |
| Testes E2E | Playwright com Docker, em MariaDB e MySQL | `c5ea4ba` |
| Perfil | Cada formulário grava só os próprios campos | `bf0588c` |
| 10. Views | JS em `public/assets/js/`; bibliotecas com versão fixa só no layout; XSS do dashboard corrigido | `5ba9824` |
| 11. Escape na entrada | O banco guarda o dado como foi digitado; escape só na saída, modais e botões de excluir incluídos (S-003, I-004) | `f1dee92` |
| 12. Movimentação por id | `tbmovimentacoes.produto_id`; renomear produto com histórico funciona e o nome novo aparece em todo lugar | `d57a49c` |
| Pastas | `config/`, `database/` e `storage/` (antiga `armazenamento/`) dentro de `backend/`; `public/` continua na raiz | `5629c5b` |
| S-001 | Sessão conferida no banco a cada requisição; troca de senha encerra as outras sessões; expiração absoluta de 12 h | `04e4e0c` |
| S-002, S-012 | Limite de tentativas de login por conta e IP; tempo de resposta igual com ou sem conta | `4e8056c` |
| S-007 | Depois do login, só volta a destino local e pedido por GET | `b288f4c` |
| S-010 | Usuários do seed sempre foram fictícios; os fornecedores eram reais e passaram a ser fictícios, com CNPJs que passam na validação. O histórico do git foi reescrito para apagar os dados antigos, e todos os hashes mudaram | `9889d45` |
| I-003, I-002, I-005 | O sistema não fica sem admin ativo; id inexistente não responde sucesso; validação estrita de números, preços, marca, perfil e `{id}` | `28d2531` |
| I-006, R-001, R-006 | O período escolhido define o intervalo, calculado no servidor; datas só no personalizado, validadas e até 10 anos; falha da API aparece como falha no relatório e no sino | `fix/i006-r001-relatorio-periodo` |

## Como cada etapa é entregue

1. Branch própria a partir da `main`.
2. Testes automatizados e [roteiro manual](roteiro-de-testes.md) passando.
3. Merge na `main` com `--no-ff`.
4. Este plano atualizado.
