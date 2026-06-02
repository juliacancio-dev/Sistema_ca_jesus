CREATE DATABASE IF NOT EXISTS bd_sistema;
USE bd_sistema;

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
) ENGINE=InnoDB;

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
) ENGINE=InnoDB;


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
    UNIQUE KEY uk_marca_produto (marca_produto)
) ENGINE=InnoDB;

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
) ENGINE=InnoDB;

CREATE TABLE tb_logs_movimentacao (
    id_log BIGINT AUTO_INCREMENT PRIMARY KEY,
    session_id VARCHAR(255) NOT NULL,
    request_id VARCHAR(255) NOT NULL UNIQUE,
    marca_produto VARCHAR(100) NOT NULL,
    acao_movimentacao ENUM('entrada', 'saida') NOT NULL,
    quantidade_movimentacao INT NOT NULL,
    preco_custo_movimentacao DECIMAL(10,2) NOT NULL,
    preco_venda_movimentacao DECIMAL(10,2) NOT NULL,
    observacao_movimentacao TEXT,
    usuario_id INT,
    ip_usuario VARCHAR(45),
    user_agent TEXT,
    status_processamento ENUM('pendente', 'processado', 'erro', 'duplicado') DEFAULT 'pendente',
    id_movimentacao_gerada INT NULL,
    erro_detalhes TEXT NULL,
    data_criacao DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    data_processamento DATETIME NULL,
    tempo_processamento_ms INT NULL,
    FOREIGN KEY (usuario_id) REFERENCES tbusuarios(id_usuario) ON DELETE SET NULL,
    FOREIGN KEY (id_movimentacao_gerada) REFERENCES tbmovimentacoes(id_movimentacao) ON DELETE SET NULL
) ENGINE=InnoDB COMMENT='Log detalhado de todas as tentativas de movimentação';

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
) ENGINE=InnoDB COMMENT='Auditoria de todas as operações críticas do sistema';

USE bd_Sistema;

CREATE INDEX idx_produto_fornecedor ON tbprodutos(fornecedor_id);
CREATE INDEX idx_movimentacao_marca ON tbmovimentacoes(marca_produto);
CREATE INDEX idx_movimentacao_data ON tbmovimentacoes(data_movimentacao);
CREATE INDEX idx_movimentacao_acao ON tbmovimentacoes(acao_movimentacao);
CREATE INDEX idx_usuario_email ON tbusuarios(email_usuario);
CREATE INDEX idx_fornecedor_cnpj ON tbfornecedores(cnpj_fornecedor);
CREATE INDEX idx_produto_marca ON tbprodutos(marca_produto);

USE bd_Sistema;

DELIMITER //
CREATE TRIGGER before_movimentacao_insert
BEFORE INSERT ON tbmovimentacoes
FOR EACH ROW
BEGIN
    DECLARE estoque_atual INT;

    IF NEW.acao_movimentacao = 'saida' THEN
        SELECT estoque_produto INTO estoque_atual 
        FROM tbprodutos 
        WHERE marca_produto = NEW.marca_produto;

        IF estoque_atual < NEW.quantidade_movimentacao THEN
            SIGNAL SQLSTATE '45000' 
            SET MESSAGE_TEXT = 'Estoque insuficiente para esta operação';
        END IF;
    END IF;
END; //
DELIMITER ;

DELIMITER //
CREATE TRIGGER after_movimentacao_insert
AFTER INSERT ON tbmovimentacoes
FOR EACH ROW
BEGIN
    IF NEW.acao_movimentacao = 'entrada' THEN
        UPDATE tbprodutos 
        SET estoque_produto = estoque_produto + NEW.quantidade_movimentacao 
        WHERE marca_produto = NEW.marca_produto;
    ELSE
        UPDATE tbprodutos 
        SET estoque_produto = estoque_produto - NEW.quantidade_movimentacao 
        WHERE marca_produto = NEW.marca_produto;
    END IF;
END; //
DELIMITER ;

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

USE bd_Sistema;

DELIMITER //
CREATE PROCEDURE registrar_movimentacao(
    IN p_marca_produto VARCHAR(100),
    IN p_acao VARCHAR(10),
    IN p_quantidade INT,
    IN p_preco_custo DECIMAL(10,2),
    IN p_preco_venda DECIMAL(10,2),
    IN p_observacao TEXT,
    IN p_usuario_id INT
)
BEGIN
    DECLARE estoque_atual INT;
    DECLARE produto_existe INT DEFAULT 0;
    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        ROLLBACK;
        RESIGNAL;
    END;

    START TRANSACTION;

    SELECT COUNT(*) INTO produto_existe 
    FROM tbprodutos 
    WHERE marca_produto = p_marca_produto;

    IF produto_existe = 0 THEN
        SIGNAL SQLSTATE '45000' 
        SET MESSAGE_TEXT = 'Produto não encontrado';
    END IF;

    IF p_acao = 'saida' THEN
        SELECT estoque_produto INTO estoque_atual 
        FROM tbprodutos 
        WHERE marca_produto = p_marca_produto FOR UPDATE;

        IF estoque_atual < p_quantidade THEN
            SIGNAL SQLSTATE '45000' 
            SET MESSAGE_TEXT = 'Estoque insuficiente para esta operação';
        END IF;

        UPDATE tbprodutos 
        SET estoque_produto = estoque_produto - p_quantidade 
        WHERE marca_produto = p_marca_produto;
    ELSE
        UPDATE tbprodutos 
        SET estoque_produto = estoque_produto + p_quantidade 
        WHERE marca_produto = p_marca_produto;
    END IF;

    INSERT INTO tbmovimentacoes (
        marca_produto,
        acao_movimentacao, 
        quantidade_movimentacao, 
        preco_custo_movimentacao, 
        preco_venda_movimentacao,
        observacao_movimentacao,
        usuario_id
    ) VALUES (
        p_marca_produto,
        p_acao,
        p_quantidade,
        p_preco_custo,
        p_preco_venda,
        p_observacao,
        p_usuario_id
    );

    COMMIT;
END; //
DELIMITER ;

DELIMITER //
CREATE PROCEDURE cadastrar_produto_simples(
    IN p_marca_produto VARCHAR(100)
)
BEGIN
    DECLARE produto_existe INT DEFAULT 0;
    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        ROLLBACK;
        RESIGNAL;
    END;

    START TRANSACTION;

    SELECT COUNT(*) INTO produto_existe 
    FROM tbprodutos 
    WHERE marca_produto = p_marca_produto;

    IF produto_existe > 0 THEN
        SIGNAL SQLSTATE '45000' 
        SET MESSAGE_TEXT = 'Produto já cadastrado com esta marca';
    END IF;

    INSERT INTO tbprodutos (marca_produto, fornecedor_id) VALUES (p_marca_produto, 1);

    COMMIT;
    
    SELECT CONCAT('Produto "', p_marca_produto, '" cadastrado com sucesso!') AS resultado;
END; //
DELIMITER ;

DELIMITER //
CREATE PROCEDURE verificar_duplicacao_movimentacao(
    IN p_session_id VARCHAR(255),
    IN p_request_id VARCHAR(255),
    IN p_marca_produto VARCHAR(100),
    IN p_acao VARCHAR(10),
    IN p_quantidade INT,
    IN p_usuario_id INT,
    OUT p_resultado VARCHAR(50),
    OUT p_mensagem TEXT
)
BEGIN
    DECLARE v_count_request INT DEFAULT 0;
    DECLARE v_count_similar INT DEFAULT 0;
    DECLARE v_time_limit DATETIME;
    
    SELECT COUNT(*) INTO v_count_request
    FROM tb_logs_movimentacao 
    WHERE request_id = p_request_id 
    AND status_processamento IN ('processado', 'duplicado');
    
    IF v_count_request > 0 THEN
        SET p_resultado = 'DUPLICADO_REQUEST';
        SET p_mensagem = 'Request ID já foi processado anteriormente';
    ELSE
        SET v_time_limit = DATE_SUB(NOW(), INTERVAL 30 SECOND);
        SELECT COUNT(*) INTO v_count_similar
        FROM tb_logs_movimentacao 
        WHERE session_id = p_session_id
        AND marca_produto = p_marca_produto
        AND acao_movimentacao = p_acao
        AND quantidade_movimentacao = p_quantidade
        AND usuario_id = p_usuario_id
        AND data_criacao >= v_time_limit
        AND status_processamento = 'processado';
        
        IF v_count_similar > 0 THEN
            SET p_resultado = 'DUPLICADO_SIMILAR';
            SET p_mensagem = 'Movimentação similar detectada nos últimos 30 segundos';
        ELSE
            SET p_resultado = 'OK';
            SET p_mensagem = 'Movimentação pode ser processada';
        END IF;
    END IF;
END; //
DELIMITER ;

DELIMITER //
CREATE PROCEDURE registrar_movimentacao_segura(
    IN p_session_id VARCHAR(255),
    IN p_request_id VARCHAR(255),
    IN p_marca_produto VARCHAR(100),
    IN p_acao VARCHAR(10),
    IN p_quantidade INT,
    IN p_preco_custo DECIMAL(10,2),
    IN p_preco_venda DECIMAL(10,2),
    IN p_observacao TEXT,
    IN p_usuario_id INT,
    IN p_ip_usuario VARCHAR(45),
    IN p_user_agent TEXT
)
BEGIN
    DECLARE v_resultado VARCHAR(50);
    DECLARE v_mensagem TEXT;
    DECLARE v_id_movimentacao INT DEFAULT NULL;
    DECLARE v_tempo_inicio BIGINT;
    DECLARE v_tempo_fim BIGINT;
    DECLARE v_produto_existe INT DEFAULT 0;
    DEClARE v_estoque_atual INT DEFAULT 0;
    DECLARE v_error_message TEXT;
    
    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        GET DIAGNOSTICS CONDITION 1 v_error_message = MESSAGE_TEXT;
        SET v_tempo_fim = UNIX_TIMESTAMP(NOW(6)) * 1000 + MICROSECOND(NOW(6)) / 1000;
        
        UPDATE tb_logs_movimentacao 
        SET status_processamento = 'erro',
            erro_detalhes = CONCAT('Erro SQL: ', v_error_message),
            data_processamento = NOW(),
            tempo_processamento_ms = v_tempo_fim - v_tempo_inicio
        WHERE request_id = p_request_id;
        
        ROLLBACK;
        RESIGNAL;
    END;
    
    START TRANSACTION;
    
    SET v_tempo_inicio = UNIX_TIMESTAMP(NOW(6)) * 1000 + MICROSECOND(NOW(6)) / 1000;
    
    INSERT INTO tb_logs_movimentacao (
        session_id, request_id, marca_produto, acao_movimentacao,
        quantidade_movimentacao, preco_custo_movimentacao, 
        preco_venda_movimentacao, observacao_movimentacao,
        usuario_id, ip_usuario, user_agent, status_processamento
    ) VALUES (
        p_session_id, p_request_id, p_marca_produto, p_acao,
        p_quantidade, p_preco_custo, p_preco_venda, p_observacao,
        p_usuario_id, p_ip_usuario, p_user_agent, 'pendente'
    );
    
    CALL verificar_duplicacao_movimentacao(
        p_session_id, p_request_id, p_marca_produto, 
        p_acao, p_quantidade, p_usuario_id, v_resultado, v_mensagem
    );
    
    IF v_resultado != 'OK' THEN
        UPDATE tb_logs_movimentacao 
        SET status_processamento = 'duplicado',
            erro_detalhes = v_mensagem,
            data_processamento = NOW()
        WHERE request_id = p_request_id;
        
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = v_mensagem;
    END IF;
    
    SELECT COUNT(*), COALESCE(estoque_produto, 0) 
    INTO v_produto_existe, v_estoque_atual
    FROM tbprodutos 
    WHERE marca_produto = p_marca_produto;
    
    IF v_produto_existe = 0 THEN
        UPDATE tb_logs_movimentacao 
        SET status_processamento = 'erro',
            erro_detalhes = 'Produto não encontrado'
        WHERE request_id = p_request_id;
        
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Produto não encontrado';
    END IF;
    
    IF p_acao = 'saida' AND v_estoque_atual < p_quantidade THEN
        UPDATE tb_logs_movimentacao 
        SET status_processamento = 'erro',
            erro_detalhes = CONCAT('Estoque insuficiente. Disponível: ', v_estoque_atual)
        WHERE request_id = p_request_id;
        
        SIGNAL SQLSTATE '45000' 
        SET MESSAGE_TEXT = 'Estoque insuficiente para esta operação';
    END IF;
    
    INSERT INTO tbmovimentacoes (
        marca_produto, acao_movimentacao, quantidade_movimentacao,
        preco_custo_movimentacao, preco_venda_movimentacao,
        observacao_movimentacao, usuario_id
    ) VALUES (
        p_marca_produto, p_acao, p_quantidade,
        p_preco_custo, p_preco_venda, p_observacao, p_usuario_id
    );
    
    SET v_id_movimentacao = LAST_INSERT_ID();
    
    IF p_acao = 'entrada' THEN
        UPDATE tbprodutos 
        SET estoque_produto = estoque_produto + p_quantidade,
            data_atualizacao_produto = NOW()
        WHERE marca_produto = p_marca_produto;
    ELSE
        UPDATE tbprodutos 
        SET estoque_produto = estoque_produto - p_quantidade,
            data_atualizacao_produto = NOW()
        WHERE marca_produto = p_marca_produto;
    END IF;
    
    SET v_tempo_fim = UNIX_TIMESTAMP(NOW(6)) * 1000 + MICROSECOND(NOW(6)) / 1000;
    
    UPDATE tb_logs_movimentacao 
    SET status_processamento = 'processado',
        id_movimentacao_gerada = v_id_movimentacao,
        data_processamento = NOW(),
        tempo_processamento_ms = v_tempo_fim - v_tempo_inicio
    WHERE request_id = p_request_id;
    
    COMMIT;
    
    SELECT 'SUCCESS' as status, 
           'Movimentação registrada com sucesso' as mensagem,
           v_id_movimentacao as id_movimentacao;
END; //
DELIMITER ;

USE bd_Sistema;

CREATE OR REPLACE VIEW vw_produtos_lista AS
SELECT 
    marca_produto,
    estoque_produto,
    data_cadastro_produto
FROM tbprodutos
ORDER BY marca_produto;

CREATE OR REPLACE VIEW vw_movimentacoes_detalhadas AS
SELECT 
    m.id_movimentacao,
    m.marca_produto,
    m.data_movimentacao,
    m.acao_movimentacao,
    m.quantidade_movimentacao,
    m.preco_custo_movimentacao,
    m.preco_venda_movimentacao,
    m.lucro_movimentacao,
    m.observacao_movimentacao,
    u.nome_usuario,
    p.estoque_produto AS estoque_atual
FROM tbmovimentacoes m
LEFT JOIN tbusuarios u ON m.usuario_id = u.id_usuario
INNER JOIN tbprodutos p ON m.marca_produto = p.marca_produto
ORDER BY m.data_movimentacao DESC;

CREATE OR REPLACE VIEW vw_relatorio_logs AS
SELECT 
    l.id_log,
    l.session_id,
    l.request_id,
    l.marca_produto,
    l.acao_movimentacao,
    l.quantidade_movimentacao,
    l.status_processamento,
    l.data_criacao,
    l.data_processamento,
    l.tempo_processamento_ms,
    l.erro_detalhes,
    u.nome_usuario,
    l.ip_usuario,
    CASE 
        WHEN l.status_processamento = 'duplicado' THEN 'Tentativa Duplicada'
        WHEN l.status_processamento = 'erro' THEN 'Erro no Processamento'
        WHEN l.status_processamento = 'processado' THEN 'Processado com Sucesso'
        ELSE 'Pendente'
    END as status_descricao
FROM tb_logs_movimentacao l
LEFT JOIN tbusuarios u ON l.usuario_id = u.id_usuario;

CREATE OR REPLACE VIEW vw_auditoria_estoque AS
SELECT 
    a.id_auditoria,
    a.data_operacao,
    a.operacao,
    JSON_UNQUOTE(JSON_EXTRACT(a.dados_anteriores, '$.marca_produto')) as marca_produto,
    JSON_UNQUOTE(JSON_EXTRACT(a.dados_anteriores, '$.estoque_produto')) as estoque_anterior,
    JSON_UNQUOTE(JSON_EXTRACT(a.dados_novos, '$.estoque_produto')) as estoque_novo,
    (JSON_UNQUOTE(JSON_EXTRACT(a.dados_novos, '$.estoque_produto')) - 
     JSON_UNQUOTE(JSON_EXTRACT(a.dados_anteriores, '$.estoque_produto'))) as variacao_estoque,
    u.nome_usuario,
    a.ip_usuario
FROM tb_auditoria a
LEFT JOIN tbusuarios u ON a.usuario_id = u.id_usuario
WHERE a.tabela_afetada = 'tbprodutos' 
AND a.operacao = 'UPDATE'
AND JSON_EXTRACT(a.dados_anteriores, '$.estoque_produto') != JSON_EXTRACT(a.dados_novos, '$.estoque_produto');

USE bd_Sistema;

SET @sql = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
     WHERE TABLE_SCHEMA = 'bd_Sistema' 
     AND TABLE_NAME = 'tbusuarios' 
     AND COLUMN_NAME = 'ativo_usuario') = 0,
    'ALTER TABLE tbusuarios ADD COLUMN ativo_usuario TINYINT(1) DEFAULT 1',
    'SELECT "Coluna ativo_usuario já existe"'
));
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @sql = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
     WHERE TABLE_SCHEMA = 'bd_Sistema' 
     AND TABLE_NAME = 'tbusuarios' 
     AND COLUMN_NAME = 'data_criacao') = 0,
    'ALTER TABLE tbusuarios ADD COLUMN data_criacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP',
    'SELECT "Coluna data_criacao já existe"'
));
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @sql = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
     WHERE TABLE_SCHEMA = 'bd_Sistema' 
     AND TABLE_NAME = 'tbusuarios' 
     AND COLUMN_NAME = 'data_atualizacao') = 0,
    'ALTER TABLE tbusuarios ADD COLUMN data_atualizacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP',
    'SELECT "Coluna data_atualizacao já existe"'
));
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @sql = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
     WHERE TABLE_SCHEMA = 'bd_Sistema' 
     AND TABLE_NAME = 'tbusuarios' 
     AND COLUMN_NAME = 'ultimo_login') = 0,
    'ALTER TABLE tbusuarios ADD COLUMN ultimo_login TIMESTAMP NULL',
    'SELECT "Coluna ultimo_login já existe"'
));
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @sql = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
     WHERE TABLE_SCHEMA = 'bd_Sistema' 
     AND TABLE_NAME = 'tbusuarios' 
     AND COLUMN_NAME = 'deletado_em') = 0,
    'ALTER TABLE tbusuarios ADD COLUMN deletado_em TIMESTAMP NULL',
    'SELECT "Coluna deletado_em já existe"'
));
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

UPDATE tbusuarios SET ativo_usuario = 1 WHERE ativo_usuario IS NULL;
UPDATE tbusuarios SET data_criacao = NOW() WHERE data_criacao IS NULL;
UPDATE tbusuarios SET data_atualizacao = NOW() WHERE data_atualizacao IS NULL;

SELECT 'Estrutura da tabela tbusuarios atualizada com sucesso!' as resultado;

SET @sql = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
     WHERE TABLE_SCHEMA = DATABASE() 
     AND TABLE_NAME = 'tbfornecedores' 
     AND COLUMN_NAME = 'ativo_fornecedor') = 0,
    'ALTER TABLE tbfornecedores ADD COLUMN ativo_fornecedor TINYINT(1) NOT NULL DEFAULT 1',
    'SELECT "Coluna ativo_fornecedor já existe"'
));
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @sql = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
     WHERE TABLE_SCHEMA = DATABASE() 
     AND TABLE_NAME = 'tbfornecedores' 
     AND COLUMN_NAME = 'data_criacao') = 0,
    'ALTER TABLE tbfornecedores ADD COLUMN data_criacao TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP',
    'SELECT "Coluna data_criacao já existe"'
));
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @sql = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
     WHERE TABLE_SCHEMA = DATABASE() 
     AND TABLE_NAME = 'tbfornecedores' 
     AND COLUMN_NAME = 'data_atualizacao') = 0,
    'ALTER TABLE tbfornecedores ADD COLUMN data_atualizacao TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP',
    'SELECT "Coluna data_atualizacao já existe"'
));
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @sql = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
     WHERE TABLE_SCHEMA = DATABASE() 
     AND TABLE_NAME = 'tbfornecedores' 
     AND COLUMN_NAME = 'endereco_fornecedor') = 0,
    'ALTER TABLE tbfornecedores ADD COLUMN endereco_fornecedor VARCHAR(255) NULL',
    'SELECT "Coluna endereco_fornecedor já existe"'
));
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
UPDATE tbfornecedores SET ativo_fornecedor = 1 WHERE ativo_fornecedor IS NULL;

USE bd_Sistema;

INSERT INTO tbusuarios (
    tipo_usuario, nome_usuario, email_usuario, senha_usuario, cpf_usuario, rg_usuario, 
    telefone_usuario, sexo_usuario, cep_usuario, endereco_usuario, cidade_usuario, 
    estado_usuario, numero_usuario, complemento_usuario, bairro_usuario, ativo_usuario
) VALUES
    (1, 'Ana Souza', 'ana.souza@gmail.com', '$2y$10$etxro1P45E8fNuoGDueQxuwvD5aOscba9ZwnjzFI/UkqG2Cpzpd42',
    '94405576807', '245485922', '17999862135', 'Feminino',
    '15130-067 ', 'Rua 9 de Julho', 'Mirassol', 'SP', '32', 'Apto 201', 'Centro', 1),
    (2, 'Jonathan', 'jonathan@gmail.com', '$2y$10$etxro1P45E8fNuoGDueQxuwvD5aOscba9ZwnjzFI/UkqG2Cpzpd42',
    '04742679890', '143291907', '15993845466', 'Masculino',
    '15130-254', 'Rua Piratininga', 'Mirassol', 'SP', '765', '-', 'Jardim São José', 1),
    (2, 'Maria', 'maria@gmail.com', '$$2y$10$etxro1P45E8fNuoGDueQxuwvD5aOscba9ZwnjzFI/UkqG2Cpzpd42',
    '52555336885', '509845496', '15983059185', 'Feminino',
    '15138-358', 'Rua 1', 'Mirassol', 'SP', '64', '-', 'Bela Vista', 1),
    (2, 'Ana', 'ana@gmail.com', '$2y$10$etxro1P45E8fNuoGDueQxuwvD5aOscba9ZwnjzFI/UkqG2Cpzpd42',
    '52840966816', '285635694', '18984646224', 'Feminino',
    '15135-462 ', 'Rua 17', 'Mirassol', 'SP', '93', '-', 'Village Mirassol IV', 1);

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
    '26',
    'Galpão 1',
    'Centro',
    'Mirassol',
    'SP'
), (
    'Distribuidora Beta',
    '11990000002',
    '12.123.123/0002-80',
    'contato@beta.example',
    '01002-000',
    'Avenida Exemplo',
    '820',
    '-',
    'Centro',
    'José Bonifácio',
    'SP'
), (
    'Comercial Gama',
    '11990000003',
    '12.123.123/0003-60',
    'contato@gama.example',
    '01003-000',
    'Travessa Exemplo',
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