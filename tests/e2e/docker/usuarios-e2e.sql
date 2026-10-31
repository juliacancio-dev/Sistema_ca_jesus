-- Usuários só dos testes E2E, todos com a senha Senha@E2E123.
SET NAMES utf8mb4;

INSERT INTO tbusuarios (tipo_usuario, nome_usuario, email_usuario, senha_usuario, cpf_usuario, rg_usuario, telefone_usuario, sexo_usuario, cep_usuario, endereco_usuario, cidade_usuario, estado_usuario, numero_usuario, bairro_usuario, ativo_usuario) VALUES
    (1, 'Admin E2E', 'e2e-admin@teste.com', '$2y$10$qdxMurvCgPmNxm9owWcwlOpYWTdiMfdIvZbsc8.QFvUXbyZa77g3C', '11144477735', 'E2E-1', '17999990001', 'Outro', '15130067', 'Rua do Admin', 'Mirassol', 'SP', '1', 'Centro', 1),
    (2, 'Funcionario E2E', 'e2e-func@teste.com', '$2y$10$qdxMurvCgPmNxm9owWcwlOpYWTdiMfdIvZbsc8.QFvUXbyZa77g3C', '52998224725', 'E2E-2', '17999990002', 'Outro', '15130067', 'Rua do Funcionario', 'Mirassol', 'SP', '2', 'Centro', 1),
    (2, 'Inativo E2E', 'e2e-inativo@teste.com', '$2y$10$qdxMurvCgPmNxm9owWcwlOpYWTdiMfdIvZbsc8.QFvUXbyZa77g3C', '39053344705', 'E2E-3', '17999990003', 'Outro', '15130067', 'Rua Inativa', 'Mirassol', 'SP', '3', 'Centro', 0),
    (2, 'Perfil E2E', 'e2e-perfil@teste.com', '$2y$10$qdxMurvCgPmNxm9owWcwlOpYWTdiMfdIvZbsc8.QFvUXbyZa77g3C', '86288366757', 'E2E-4', '17999990004', 'Outro', '15130067', 'Rua Original', 'Mirassol', 'SP', '44', 'Bairro Original', 1),
    (2, 'Senha E2E', 'e2e-senha@teste.com', '$2y$10$qdxMurvCgPmNxm9owWcwlOpYWTdiMfdIvZbsc8.QFvUXbyZa77g3C', '71460238405', 'E2E-5', '17999990005', 'Outro', '15130067', 'Rua da Senha', 'Mirassol', 'SP', '5', 'Centro', 1);
