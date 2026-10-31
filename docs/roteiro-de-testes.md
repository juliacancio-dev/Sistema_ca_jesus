# Roteiro de testes manual

Rode antes de cada merge na `main`, junto com os testes automatizados (`composer test` e `tests/e2e/rodar.sh`). Numa mudança pequena, basta testar as telas afetadas.

Os usuários e a senha estão no [README](../README.md#usuários-de-desenvolvimento). O roteiro altera dados, então use o banco de desenvolvimento.

## Login
- [ ] Senha errada: aparece uma mensagem.
- [ ] Usuário inativo: aparece uma mensagem específica.
- [ ] Login correto: abre o dashboard.

## Dashboard
- [ ] Os cards e os 2 gráficos carregam.
- [ ] O sino mostra os produtos com estoque baixo.

## Produtos
- [ ] Cadastrar um produto.
- [ ] Cadastrar com venda menor que custo: recusado.
- [ ] Editar um produto.
- [ ] Renomear um produto com movimentações: salva, e o nome novo aparece no histórico e nos relatórios.
- [ ] Excluir um produto sem movimentação: excluído.
- [ ] Excluir um produto com movimentação: recusado, com mensagem clara.

## Movimentações
- [ ] Entrada de 10: o saldo sobe 10.
- [ ] Saída de 5: o saldo desce 5.
- [ ] **Saída acima do saldo: recusada com mensagem, e o saldo não muda.**
- [ ] Custo maior que venda: o sistema pede confirmação. Cancelar não registra nada.
- [ ] Custo maior que venda, confirmando: registra.

## Relatórios
- [ ] Financeiro e estoque no período mensal.
- [ ] Financeiro e estoque num intervalo personalizado.
- [ ] Exportar para PDF e para Excel.

## Usuários (admin)
- [ ] Criar, editar e excluir um usuário.
- [ ] Inativar e reativar um usuário.
- [ ] Tentar inativar a si mesmo: recusado.
- [ ] Como funcionário, abrir `/usuarios`: redireciona com mensagem.
- [ ] Mudar o próprio nome para `');window.__x=1;//` e, como admin, clicar em excluir esse usuário: nada executa.

## Perfil
- [ ] Salvar os dados pessoais: o endereço não muda.
- [ ] Salvar o endereço: os dados pessoais não mudam. O CEP preenche o endereço.
- [ ] Trocar a senha com a senha atual errada: recusado.
- [ ] Trocar a senha com a senha atual correta.

## Fornecedores (admin)
- [ ] Criar um fornecedor.
- [ ] Editar um fornecedor: **ele continua ativo**.
- [ ] Inativar um fornecedor com produtos vinculados: recusado.
- [ ] Excluir um fornecedor com produtos: recusado, com mensagem clara.
- [ ] Salvar "D'Ávila & Filhos" três vezes: o nome continua igual, na lista e nos detalhes.

## Saída
- [ ] Fazer logout e voltar: abre a tela de login.

## Depois das correções da auditoria

Estes itens falham hoje. Passe a exigi-los quando o achado correspondente da [auditoria](auditoria-seguranca.md) for corrigido.

- [ ] Com o funcionário logado em outra janela, desativar, excluir ou rebaixar esse usuário: a próxima ação dele leva ao login (S-001).
- [ ] Trocar a senha: a outra sessão aberta é encerrada (S-001).
- [ ] Errar a senha várias vezes seguidas: o login é bloqueado por um tempo, e outra conta não é afetada (S-002).
- [ ] Editar o próprio usuário sem marcar "ativo" ou mudando o tipo: recusado (I-003).
- [ ] No relatório, escolher "Anual" e aplicar: vêm as movimentações do ano todo (I-006).
- [ ] Com a API fora do ar, o sino e o relatório mostram erro, não "Tudo ok" nem "R$ 0,00" (R-001).
- [ ] Buscar na tela um usuário que está na página 2: ele aparece (R-005).
