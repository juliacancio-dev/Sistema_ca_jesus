# Sistema de Gestão de Estoque CA Jesus

Sistema web para controle de estoque de uma distribuidora: cadastro de produtos e fornecedores, registro de entradas e saídas, relatórios financeiros e de estoque, e gestão de usuários.

> Este projeto está passando por uma refatoração. Arquitetura-alvo e regras: [`docs/arquitetura.md`](docs/arquitetura.md). Diagnóstico e plano: [`docs/diagnostico-fase-a.md`](docs/diagnostico-fase-a.md).

## Funcionalidades

- **Autenticação**: login e logout, expiração da sessão por inatividade (2 h), perfis Administrador e Funcionário.
- **Dashboard**: indicadores (produtos, estoque total, movimentações do dia, lucro, saldo) e gráficos.
- **Produtos**: cadastro, edição, exclusão e busca.
- **Movimentações**: registro de entrada e saída; uma saída não pode ser maior que o estoque disponível.
- **Relatórios**: financeiro e de estoque por período, com exportação para PDF e Excel.
- **Usuários** (admin): cadastro, edição, ativação e inativação, exclusão.
- **Perfil** (todos os usuários): edição dos próprios dados e troca de senha.
- **Fornecedores** (admin): cadastro, edição, ativação e inativação, exclusão.

## Tecnologias

| Camada | Tecnologia |
|---|---|
| Backend | PHP ≥ 8.2, MVC próprio (sem framework), Composer |
| Banco | MySQL / MariaDB via PDO, com triggers |
| Frontend | Views PHP, Tailwind CSS (CDN), JavaScript, Chart.js |
| Servidor | Apache com `mod_rewrite` (testado no XAMPP) |
| Integrações | ViaCEP (preenchimento de endereço) |

## Estrutura

```
sistema_ca_jesus/
├── app/
│   ├── controllers/   # um controller por tela
│   ├── core/          # Router, Controller base, Database
│   ├── database/      # bancoInteiro.sql (criação do banco, triggers e dados iniciais)
│   ├── helpers/       # autenticação, formatação, log
│   ├── middleware/    # AuthMiddleware (login) e AdminMiddleware (perfil admin)
│   ├── models/        # acesso ao banco
│   └── views/         # layout e telas
├── armazenamento/
│   └── logs/          # logs gerados pela aplicação (não versionados)
├── config/
│   ├── config.example.php  # modelo de configuração (versionado)
│   └── config.php          # sua configuração local (não versionado)
├── docs/              # documentação da refatoração
├── public/            # index.php (ponto de entrada) e assets
├── tests/             # testes PHPUnit
├── .htaccess          # redireciona para public/ e bloqueia pastas internas
├── composer.json
└── phpunit.xml
```

## Como executar (XAMPP)

1. Clone o projeto dentro de `htdocs` com o nome `sistema_ca_jesus`. O caminho `/sistema_ca_jesus/public` ainda está fixo no código:
   ```bash
   cd C:/xampp/htdocs
   git clone https://github.com/juliacancio-dev/Sistema_ca_jesus.git sistema_ca_jesus
   ```
2. Crie a configuração local e ajuste as credenciais do banco:
   ```bash
   cp config/config.example.php config/config.php
   ```
3. Instale as dependências (requer o [Composer](https://getcomposer.org/download/)):
   ```bash
   composer install
   ```
4. Importe `app/database/bancoInteiro.sql` no phpMyAdmin ou pelo cliente `mysql`. O script cria o banco `bd_sistema`, as tabelas, os triggers e os dados iniciais.
5. Garanta que o `mod_rewrite` do Apache está ativo.
6. Acesse `http://localhost/sistema_ca_jesus/public`.

Em produção, defina `ENVIRONMENT` com um valor diferente de `'development'` em `config/config.php`, para que os erros não apareçam na tela.

## Testes

```bash
composer test
```

Os testes usam o banco `bd_sistema_teste`, separado do banco de desenvolvimento. Os testes que precisam de banco entram na etapa 8 da refatoração.

## Autora

**Julia Cancio** — [GitHub](https://github.com/juliacancio-dev)
