-- Dados iniciais de desenvolvimento. Rode depois de schema.sql.
SET NAMES utf8mb4;

INSERT INTO tbusuarios (
    tipo_usuario, nome_usuario, email_usuario, senha_usuario, cpf_usuario, rg_usuario, 
    telefone_usuario, sexo_usuario, cep_usuario, endereco_usuario, cidade_usuario, 
    estado_usuario, numero_usuario, complemento_usuario, bairro_usuario, ativo_usuario
) VALUES
    (1, 'Ana Souza', 'ana.souza@gmail.com', '$2y$10$etxro1P45E8fNuoGDueQxuwvD5aOscba9ZwnjzFI/UkqG2Cpzpd42',
    '94405576807', '245485922', '17999862135', 'Feminino',
    '15130-067', 'Rua 9 de Julho', 'Mirassol', 'SP', '32', 'Apto 201', 'Centro', 1),
    (2, 'Jonathan', 'jonathan@gmail.com', '$2y$10$etxro1P45E8fNuoGDueQxuwvD5aOscba9ZwnjzFI/UkqG2Cpzpd42',
    '04742679890', '143291907', '15993845466', 'Masculino',
    '15130-254', 'Rua Piratininga', 'Mirassol', 'SP', '765', '-', 'Jardim São José', 1),
    (2, 'Maria', 'maria@gmail.com', '$2y$10$etxro1P45E8fNuoGDueQxuwvD5aOscba9ZwnjzFI/UkqG2Cpzpd42',
    '52555336885', '509845496', '15983059185', 'Feminino',
    '15138-358', 'Rua 1', 'Mirassol', 'SP', '64', '-', 'Bela Vista', 1),
    (2, 'Ana', 'ana@gmail.com', '$2y$10$etxro1P45E8fNuoGDueQxuwvD5aOscba9ZwnjzFI/UkqG2Cpzpd42',
    '52840966816', '285635694', '18984646224', 'Feminino',
    '15135-462', 'Rua 17', 'Mirassol', 'SP', '93', '-', 'Village Mirassol IV', 1);

INSERT INTO tbfornecedores (
    nome_fornecedor,
    telefone_fornecedor,
    cnpj_fornecedor,
    email_fornecedor,
    cep_fornecedor,
    endereco_fornecedor,
    numero_fornecedor,
    complemento_fornecedor,
    bairro_fornecedor,
    cidade_fornecedor,
    estado_fornecedor
) VALUES (
    'Distribuidora Alfa',
    '11990000001',
    '12.123.123/0001-07',
    'contato@alfa.example',
    '01001-000',
    'Rua Exemplo',
    '100',
    'Galpão 1',
    'Centro',
    'São Paulo',
    'SP'
), (
    'Distribuidora Beta',
    '11990000002',
    '12.123.123/0002-80',
    'contato@beta.example',
    '01002-000',
    'Avenida Exemplo',
    '200',
    '-',
    'Centro',
    'São Paulo',
    'SP'
), (
    'Comercial Gama',
    '11990000003',
    '12.123.123/0003-60',
    'contato@gama.example',
    '01003-000',
    'Travessa Exemplo',
    '300',
    '-',
    'Centro',
    'São Paulo',
    'SP'
);

INSERT INTO tbprodutos (marca_produto, preco_custo_produto, preco_venda_produto, fornecedor_id) VALUES
('Gás de cozinha', 100, 130, 1),
('Galão 20 L - Beta', 12, 19, 1),
('Galão 20 L - Alfa', 6, 14, 1),
('Galão 20 L - Delta', 11, 19, 1),
('Galão 20 L - Sigma', 9, 14, 1),
('Galão 10 L - Beta', 10, 16, 1),
('Galão 10 L - Alfa', 5, 12, 1),
('Fardo 1,5 L - Alfa', 10, 17, 1),
('Fardo 1,5 L - Beta', 19, 27, 1),
('Fardo 510 ml - Alfa', 10, 15, 1),
('Fardo 510 ml - Beta', 19, 27, 1),
('Fardo 510 ml - Delta', 20, 26, 1),
('Fardo 510 ml com gás - Alfa', 13, 20, 1),
('Caixa de Copo 200 ml - Alfa', 24, 40, 1);
