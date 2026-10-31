-- Estrutura do banco. Não cria nem seleciona o banco: quem roda escolhe qual
-- (bd_sistema no desenvolvimento, bd_sistema_teste nos testes). Ver README.
SET NAMES utf8mb4;

CREATE TABLE tbusuarios (
    id_usuario INT AUTO_INCREMENT PRIMARY KEY,
    tipo_usuario INT NOT NULL,
    nome_usuario VARCHAR(255) NOT NULL,
    email_usuario VARCHAR(255) UNIQUE NOT NULL,
    senha_usuario VARCHAR(255) NOT NULL,
    cpf_usuario VARCHAR(14) UNIQUE NOT NULL,
    rg_usuario VARCHAR(20) UNIQUE NOT NULL,
    telefone_usuario VARCHAR(15) NOT NULL,
    sexo_usuario ENUM('Masculino', 'Feminino', 'Outro') NOT NULL,
    cep_usuario VARCHAR(9) NOT NULL,
    endereco_usuario VARCHAR(255),
    cidade_usuario VARCHAR(100),
    estado_usuario CHAR(2),
    numero_usuario VARCHAR(10),
    complemento_usuario VARCHAR(255),
    bairro_usuario VARCHAR(100),
    ativo_usuario TINYINT(1) DEFAULT 1,
    data_criacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    data_atualizacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    ultimo_login TIMESTAMP NULL,
    deletado_em TIMESTAMP NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE tbfornecedores (
    id_fornecedor INT AUTO_INCREMENT PRIMARY KEY,
    nome_fornecedor VARCHAR(255) NOT NULL,
    telefone_fornecedor VARCHAR(15) NOT NULL,
    cnpj_fornecedor VARCHAR(18) UNIQUE NOT NULL,
    email_fornecedor VARCHAR(255) UNIQUE NOT NULL,
    cep_fornecedor VARCHAR(9) NOT NULL,
    endereco_fornecedor VARCHAR(255),
    numero_fornecedor VARCHAR(10),
    complemento_fornecedor VARCHAR(255),
    bairro_fornecedor VARCHAR(100),
    cidade_fornecedor VARCHAR(100),
    estado_fornecedor CHAR(2),
    ativo_fornecedor TINYINT(1) NOT NULL DEFAULT 1,
    data_criacao TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    data_atualizacao TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- O saldo é mantido por App\Features\Movimentacoes\RegistrarMovimentacao.
-- O CHECK é só a rede de segurança: a regra e a mensagem ficam no PHP.
CREATE TABLE tbprodutos (
    id_produto INT AUTO_INCREMENT PRIMARY KEY,
    marca_produto VARCHAR(100) NOT NULL,
    preco_custo_produto DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    preco_venda_produto DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    estoque_produto INT NOT NULL DEFAULT 0,
    fornecedor_id INT NOT NULL,
    data_cadastro_produto DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    data_atualizacao_produto DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (fornecedor_id) REFERENCES tbfornecedores(id_fornecedor) ON DELETE RESTRICT,
    UNIQUE KEY uk_marca_produto (marca_produto),
    CONSTRAINT chk_estoque_nao_negativo CHECK (estoque_produto >= 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE tbmovimentacoes (
    id_movimentacao INT AUTO_INCREMENT PRIMARY KEY,
    marca_produto VARCHAR(100) NOT NULL,
    data_movimentacao DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    acao_movimentacao ENUM('entrada', 'saida') NOT NULL,
    quantidade_movimentacao INT NOT NULL,
    preco_custo_movimentacao DECIMAL(10,2) NOT NULL,
    preco_venda_movimentacao DECIMAL(10,2) NOT NULL,
    lucro_movimentacao DECIMAL(10,2) AS (
        CASE
            WHEN acao_movimentacao = 'saida' THEN
                (preco_venda_movimentacao - preco_custo_movimentacao) * quantidade_movimentacao
            ELSE 0
        END
    ) VIRTUAL,
    observacao_movimentacao TEXT,
    usuario_id INT,
    FOREIGN KEY (marca_produto) REFERENCES tbprodutos(marca_produto) ON DELETE RESTRICT,
    FOREIGN KEY (usuario_id) REFERENCES tbusuarios(id_usuario) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE tb_auditoria (
    id_auditoria BIGINT AUTO_INCREMENT PRIMARY KEY,
    tabela_afetada VARCHAR(100) NOT NULL,
    operacao ENUM('INSERT', 'UPDATE', 'DELETE') NOT NULL,
    id_registro INT NOT NULL,
    dados_anteriores JSON NULL,
    dados_novos JSON NULL,
    usuario_id INT NULL,
    ip_usuario VARCHAR(45),
    data_operacao DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    observacoes TEXT,
    FOREIGN KEY (usuario_id) REFERENCES tbusuarios(id_usuario) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='Auditoria de todas as operações críticas do sistema';

CREATE INDEX idx_produto_fornecedor ON tbprodutos(fornecedor_id);
CREATE INDEX idx_movimentacao_marca ON tbmovimentacoes(marca_produto);
CREATE INDEX idx_movimentacao_data ON tbmovimentacoes(data_movimentacao);
CREATE INDEX idx_movimentacao_acao ON tbmovimentacoes(acao_movimentacao);
CREATE INDEX idx_usuario_email ON tbusuarios(email_usuario);
CREATE INDEX idx_fornecedor_cnpj ON tbfornecedores(cnpj_fornecedor);
CREATE INDEX idx_produto_marca ON tbprodutos(marca_produto);

DELIMITER //
CREATE TRIGGER tr_auditoria_estoque
AFTER UPDATE ON tbprodutos
FOR EACH ROW
BEGIN
    IF OLD.estoque_produto <> NEW.estoque_produto THEN
        INSERT INTO tb_auditoria (
            tabela_afetada,
            operacao,
            id_registro,
            dados_anteriores,
            dados_novos,
            usuario_id
        ) VALUES (
            'tbprodutos',
            'UPDATE',
            NEW.id_produto,
            JSON_OBJECT(
                'marca_produto', OLD.marca_produto,
                'estoque_produto', OLD.estoque_produto
            ),
            JSON_OBJECT(
                'marca_produto', NEW.marca_produto,
                'estoque_produto', NEW.estoque_produto
            ),
            NULL
        );
    END IF;
END; //
DELIMITER ;
