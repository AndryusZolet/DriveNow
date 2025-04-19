-- Criando Procedures
DELIMITER //
-- Adiciona uma nova reserva e calcula o valor total automaticamente
CREATE PROCEDURE AdicionarReserva (
    IN p_veiculo_id INT,
    IN p_conta_usuario_id INT,
    IN p_reserva_data DATE,
    IN p_devolucao_data DATE,
    IN p_diaria_valor DECIMAL(10,2),
    IN p_taxas_de_uso DECIMAL(10,2),
    IN p_taxas_de_limpeza DECIMAL(10,2)
)
BEGIN
    DECLARE v_valor_total DECIMAL(10,2);
    SET v_valor_total = (DATEDIFF(p_devolucao_data, p_reserva_data) * p_diaria_valor) + p_taxas_de_uso + p_taxas_de_limpeza;
    INSERT INTO reserva (veiculo_id, conta_usuario_id, reserva_data, devolucao_data, diaria_valor, taxas_de_uso, taxas_de_limpeza, valor_total)
    VALUES (p_veiculo_id, p_conta_usuario_id, p_reserva_data, p_devolucao_data, p_diaria_valor, p_taxas_de_uso, p_taxas_de_limpeza, v_valor_total);
END //

-- Remove uma reserva específica pelo ID
CREATE PROCEDURE RemoverReserva (IN p_reserva_id INT)
BEGIN
    DELETE FROM reserva WHERE id = p_reserva_id;
END //

-- Adiciona um veículo à lista de favoritos de um usuário
CREATE PROCEDURE AdicionarFavorito (IN p_veiculo_id INT, IN p_conta_usuario_id INT)
BEGIN
    INSERT INTO favoritos (veiculo_id, conta_usuario_id) VALUES (p_veiculo_id, p_conta_usuario_id);
END //

-- Remove um veículo da lista de favoritos de um usuário
CREATE PROCEDURE RemoverFavorito (IN p_veiculo_id INT, IN p_conta_usuario_id INT)
BEGIN
    DELETE FROM favoritos WHERE veiculo_id = p_veiculo_id AND conta_usuario_id = p_conta_usuario_id;
END //

-- Adiciona um novo usuário ao sistema
CREATE PROCEDURE AdicionarUsuario (IN p_primeiro_nome VARCHAR(50), IN p_segundo_nome VARCHAR(50), IN p_e_mail VARCHAR(100), IN p_senha VARCHAR(255))
BEGIN
    INSERT INTO conta_usuario (primeiro_nome, segundo_nome, e_mail, senha, data_de_entrada)
    VALUES (p_primeiro_nome, p_segundo_nome, p_e_mail, p_senha, CURDATE());
END //

-- Atualiza o e-mail de um usuário
CREATE PROCEDURE AtualizarEmailUsuario (IN p_usuario_id INT, IN p_novo_email VARCHAR(100))
BEGIN
    UPDATE conta_usuario SET e_mail = p_novo_email WHERE id = p_usuario_id;
END //
DELIMITER ;