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
| Banco | MySQL / MariaDB via PDO |
| Frontend | Views PHP, Tailwind CSS (CDN), JavaScript, Chart.js |
| Servidor | Apache com `mod_rewrite` (testado no XAMPP) |
| Integrações | ViaCEP (preenchimento de endereço) |

## Estrutura

```
sistema_ca_jesus/
├── app/
│   ├── controllers/   # um controller por tela
│   ├── core/          # Router, Controller base, Database
│   ├── helpers/       # autenticação, formatação, log
│   ├── middleware/    # AuthMiddleware (login) e AdminMiddleware (perfil admin)
│   ├── models/        # acesso ao banco
│   └── views/         # layout e telas
├── armazenamento/
│   └── logs/          # logs gerados pela aplicação (não versionados)
├── backend/           # código novo, organizado por funcionalidade
├── config/
│   ├── config.example.php  # modelo de configuração (versionado)
│   └── config.php          # sua configuração local (não versionado)
├── database/          # schema.sql (estrutura) e seed.sql (dados iniciais)
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
4. Crie o banco e carregue a estrutura e os dados iniciais. Os mesmos comandos servem para recriar o banco do zero (**isso apaga todos os dados dele**):
   ```bash
   mysql -uroot -e "DROP DATABASE IF EXISTS bd_sistema; CREATE DATABASE bd_sistema CHARACTER SET utf8mb4"
   mysql -uroot bd_sistema < database/schema.sql
   mysql -uroot bd_sistema < database/seed.sql
   ```
5. Garanta que o `mod_rewrite` do Apache está ativo.
6. Acesse `http://localhost/sistema_ca_jesus/public`.

Em produção, defina `ENVIRONMENT` com um valor diferente de `'development'` em `config/config.php`, para que os erros não apareçam na tela.

## Testes

```bash
composer test
```

Os testes usam o banco `bd_sistema_teste`, separado do banco de desenvolvimento. Ele é apagado e recriado a cada execução a partir de `database/schema.sql`; o usuário do banco precisa de permissão para isso.

## Autora

**Julia Cancio** — [GitHub](https://github.com/juliacancio-dev)
