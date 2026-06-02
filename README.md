
# Sistema de Gestão de Estoque CA Jesus

[![PHP Version](https://img.shields.io/badge/PHP-%3E%3D7.4-777BB4.svg)](https://php.net)

## 📋 Sobre o Projeto

Este é um sistema completo para gestão de estoque, desenvolvido para otimizar o controle de produtos e movimentações. O projeto foi concebido com uma arquitetura MVC robusta, garantindo organização e facilidade de manutenção.

### Funcionalidades Principais

O sistema oferece uma variedade de funcionalidades, incluindo:

- **Autenticação e Controle de Acesso**:
  - Login/Logout de usuários.
  - Sistema de níveis de acesso (Middleware de Autenticação e Administrador).

- **Gestão de Produtos**:
  - CRUD completo (Criar, Listar, Editar e Excluir) de produtos.
  - Busca e listagem de produtos.

- **Gestão de Estoque**:
  - Registro de movimentações de entrada e saída.
  - Consulta de estoque atual em tempo real via API.

- **Relatórios**:
  - Geração de relatórios personalizados.
  - API para obtenção de dados para relatórios.

- **Gestão de Usuários (Acesso Restrito a Administradores)**:
  - Cadastro, edição e exclusão de usuários.
  - Controle de status do usuário (ativo/inativo).
  - Gerenciamento de perfil e alteração de senha.

- **Gestão de Fornecedores (Acesso Restrito a Administradores)**:
  - CRUD completo de fornecedores.
  - Controle de status do fornecedor (ativo/inativo).

- **API para Operações Dinâmicas**:
  - Endpoints para dashboard, produtos, fornecedores e movimentações, facilitando a integração com outras ferramentas.

## 🚀 Tecnologias Utilizadas

- **Backend**: PHP 7.4 ou superior.
- **Frontend**: HTML, CSS, JavaScript e Bootstrap para a interface.
- **Banco de Dados**: MySQL.
- **Arquitetura**: MVC com PSR-4 para autoloading.
- **Servidor**: Apache com suporte a .htaccess para URL amigáveis.

## 📁 Estrutura do Projeto

A organização do projeto segue o padrão MVC, garantindo clareza e facilidade de navegação.

```
sistema_ca_jesus/
├── app/               # Núcleo da aplicação (MVC)
│   ├── Controllers/   # Controladores da aplicação
│   ├── Core/          # Componentes centrais (rotas, conexão com BD)
│   ├── Database/      # Migrações e seeds do banco de dados
│   ├── Helpers/       # Funções auxiliares
│   ├── Middleware/    # Camada de autenticação e permissões
│   ├── Models/        # Modelos para interação com o banco de dados
│   └── Views/         # Templates da interface (HTML, CSS, JS)
├── armazenamento/     # Uploads e arquivos gerados pelo sistema
├── config/            # Arquivos de configuração (config.php, database.php)
├── public/            # Ponto de entrada da aplicação (assets, index.php)
├── .htaccess          # Configuração do servidor Apache na raiz
├── composer.json      # Dependências e autoload PSR-4
└── raiz de diretorios.txt # Documentação adicional da estrutura
```

## ⚙️ Como Executar o Projeto

Para rodar o sistema em seu ambiente local, siga os passos abaixo.

### Pré-requisitos

- PHP >= 7.4.
- MySQL.
- Servidor Web com suporte a `.htaccess` (Apache recomendado).
- Composer para gerenciar as dependências (caso utilize alguma).

### Passo a Passo

1.  **Clone o repositório**:
    ```bash
    git clone https://github.com/juliacancio-dev/sistema_ca_jesus.git
    cd sistema_ca_jesus
    ```

2.  **Instale as dependências (se aplicável)**:
    ```bash
    composer install
    ```

3.  **Configure o Banco de Dados**:
    - Crie um banco de dados MySQL.
    - Renomeie o arquivo `config/database.example.php` para `database.php` e preencha com as credenciais do seu banco.
    - Execute o script SQL de criação das tabelas disponível na pasta `app/Database/`.

4.  **Configure o Servidor Web**:
    - Aponte o documento raiz do seu servidor para a pasta `public/`.
    - Certifique-se de que a extensão `mod_rewrite` do Apache está ativada para o funcionamento correto das rotas amigáveis.

5.  **Acesse o Sistema**:
    - Abra o navegador e acesse o endereço configurado (ex: `http://localhost/sistema_ca_jesus/public`).
    - Utilize as credenciais padrão de acesso (caso existam) ou crie um novo usuário diretamente no banco de dados para começar.

## 🤝 Contribuição

Sinta-se à vontade para contribuir com o projeto! Para isso, você pode:

1. Fazer um **Fork** do projeto.
2. Criar uma **Branch** para sua funcionalidade (`git checkout -b feature/nova-feature`).
3. **Commit** suas mudanças (`git commit -m 'feat: Adiciona nova funcionalidade'`).
4. **Push** para a Branch (`git push origin feature/nova-feature`).
5. Abrir um **Pull Request**.

## 👩‍💻 Autor

**Julia Cancio** - Desenvolvedora Backend - [GitHub](https://github.com/juliacancio-dev)

---

Se tiver alguma dúvida ou sugestão, fique à vontade para abrir uma *issue* ou entrar em contato.
```

Estou à disposição para quaisquer ajustes ou dúvidas!
