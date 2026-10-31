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
    'Talismã Distribuidora',
    '1737814486',
    '80.883.391/0001-79',
    'talisma@gmail.com',
    '15130-971',
    'Praça Doutor Anísio José Moreira',
    '26',
    'Quadra 20',
    'Centro',
    'Mirassol',
    'SP'
), (
    'Ibirá Distribuidora',
    '1774626192',
    '62.579.820/0001-37',
    'ibira@gmail.com',
    '15200-970',
    'Avenida Antonio Gonçalves da Silva',
    '820',
    '-',
    'Centro',
    'José Bonifácio',
    'SP'
), (
    'Denis',
    '17995215833',
    '48.887.123/0001-12',
    'denis@gmail.com',
    '15130-972',
    'Rua Quintino Bocaiuva 2280',
    '2280',
    '-',
    'Centro',
    'Mirassol',
    'SP'
);

INSERT INTO tbprodutos (marca_produto, preco_custo_produto, preco_venda_produto, fornecedor_id) VALUES
('Gás de cozinha', 100, 130, 1),
('Galão 20 L - Ibira', 12, 19, 1),
('Galão 20 L - Talismã', 6, 14, 1),
('Galão 20 L - Levity', 11, 19, 1),
('Galão 20 L - Minajens', 9, 14, 1),
('Galão 10 L - Ibirá', 10, 16, 1),
('Galão 10 L - Talismã', 5, 12, 1),
('Fardo 1,5 L - Talismã', 10, 17, 1),
('Fardo 1,5 L - Ibirá', 19, 27, 1),
('Fardo 510 ml - Talismã', 10, 15, 1),
('Fardo 510 ml - Ibirá', 19, 27, 1),
('Fardo 510 ml - Levity', 20, 26, 1),
('Fardo 510 ml com gás - Talismã', 13, 20, 1),
('Caixa de Copo 200 ml - Talismã', 24, 40, 1);