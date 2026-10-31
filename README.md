# Sistema de Gestão de Estoque CA Jesus

Sistema web para controle de estoque de uma distribuidora: cadastro de produtos e fornecedores, registro de entradas e saídas, relatórios financeiros e de estoque, e gestão de usuários.

> Este projeto está passando por uma refatoração. Veja a [documentação](docs/README.md): arquitetura, rotas, plano e roteiro de testes.

## Funcionalidades

- **Autenticação**: login e logout, expiração da sessão por inatividade (2 h), perfis Administrador e Funcionário.
- **Dashboard**: indicadores (produtos, estoque total, movimentações do dia, lucro, saldo) e gráficos.
- **Produtos**: cadastro, edição, exclusão e busca.
- **Movimentações**: registro de entrada e saída; uma saída não pode ser maior que o estoque disponível, e custo maior que venda pede confirmação.
- **Relatórios**: financeiro e de estoque por período, com exportação para PDF e Excel.
- **Usuários** (admin): cadastro, edição, ativação e inativação, exclusão.
- **Perfil** (todos os usuários): edição dos próprios dados e troca de senha.
- **Fornecedores** (admin): cadastro, edição, ativação e inativação, exclusão.

## Tecnologias

| Camada | Tecnologia |
|---|---|
| Backend | PHP ≥ 8.2, sem framework, organizado por funcionalidade (Vertical Slice), Composer |
| Banco | MySQL / MariaDB via PDO |
| Frontend | Views PHP, Tailwind CSS (CDN), JavaScript, Chart.js |
| Servidor | Apache com `mod_rewrite` (testado no XAMPP) |
| Integrações | ViaCEP (preenchimento de endereço) |

## Estrutura

```
sistema_ca_jesus/
├── armazenamento/
│   └── logs/          # logs gerados pela aplicação (não versionados)
├── backend/
│   ├── config/
│   │   ├── config.example.php  # modelo de configuração (versionado)
│   │   └── config.php          # sua configuração local (não versionado)
│   ├── Core/          # Router, Controller base, Database, sessão, autenticação
│   ├── database/      # schema.sql (estrutura) e seed.sql (dados iniciais)
│   ├── Features/      # uma pasta por funcionalidade
│   └── Shared/        # formatação
├── frontend/views/    # layout e telas
├── docs/              # documentação (comece por docs/README.md)
├── public/            # index.php (ponto de entrada) e assets
├── tests/             # testes PHPUnit e, em tests/e2e/, testes no navegador
├── .htaccess          # redireciona para public/ e bloqueia pastas internas
├── composer.json
└── phpunit.xml
```

## Como executar (XAMPP)

Os comandos abaixo são para o Git Bash. Se `php`, `composer` ou `mysql` não forem encontrados, coloque as pastas do XAMPP no PATH:

```bash
export PATH="$PATH:/c/xampp/php:/c/xampp/mysql/bin"
```

1. Clone o projeto dentro de `htdocs` e entre na pasta:
   ```bash
   cd C:/xampp/htdocs
   git clone https://github.com/juliacancio-dev/Sistema_ca_jesus.git sistema_ca_jesus
   cd sistema_ca_jesus
   ```
2. Crie a configuração local e ajuste as credenciais do banco:
   ```bash
   cp backend/config/config.example.php backend/config/config.php
   ```
3. Instale as dependências (requer o [Composer](https://getcomposer.org/download/)):
   ```bash
   composer install
   ```
4. Crie o banco e carregue a estrutura e os dados iniciais. Os mesmos comandos servem para recriar o banco do zero (**isso apaga todos os dados dele**):
   ```bash
   mysql -uroot -e "DROP DATABASE IF EXISTS bd_sistema; CREATE DATABASE bd_sistema CHARACTER SET utf8mb4"
   mysql -uroot bd_sistema < backend/database/schema.sql
   mysql -uroot bd_sistema < backend/database/seed.sql
   ```
5. Garanta que o `mod_rewrite` do Apache está ativo.
6. Acesse `http://localhost/sistema_ca_jesus/public`.

### Usuários de desenvolvimento

O `backend/database/seed.sql` cria estes usuários, todos com a senha `123456789`:

| E-mail | Perfil |
|---|---|
| `ana.souza@gmail.com` | Administrador |
| `jonathan@gmail.com` | Funcionário |
| `maria@gmail.com` | Funcionário |
| `ana@gmail.com` | Funcionário |

Essa senha é só para desenvolvimento: não carregue o `seed.sql` em produção.

### Produção

Em produção, defina `ENVIRONMENT` com um valor diferente de `'development'` em `backend/config/config.php`, para que os erros não apareçam na tela.

## Testes

```bash
composer test
```

Os testes usam o banco `bd_sistema_teste`, separado do banco de desenvolvimento. Ele é apagado e recriado a cada execução a partir de `backend/database/schema.sql`; o usuário do banco precisa de permissão para isso.

### Testes no navegador (E2E)

Requer Docker e Node. Sobem Apache e PHP em Linux, com o banco em memória, e usam o Chromium pelo Playwright. O banco de desenvolvimento não é tocado.

```bash
tests/e2e/rodar.sh                # MariaDB 10.4 e MySQL 8.0
tests/e2e/rodar.sh mariadb:10.4   # só um banco
```

## Autora

**Julia Cancio** — [GitHub](https://github.com/juliacancio-dev)
