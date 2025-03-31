-- Exibe detalhes dos veículos disponíveis
CREATE VIEW veiculos_detalhados AS
SELECT v.id, v.veiculo_nome, v.veiculo_ano, v.veiculo_km, v.veiculo_cambio, v.veiculo_combustivel, 
       v.veiculo_portas, v.veiculo_acentos, v.veiculo_tracao, c.categoria, l.nome_local
FROM veiculo v
JOIN categoria_veiculo c ON v.categoria_veiculo_id = c.id
JOIN local l ON v.local_id = l.id;

-- Exibe informações sobre reservas feitas por usuários
CREATE VIEW usuarios_reservas AS
SELECT cu.id, cu.primeiro_nome, cu.e_mail, r.reserva_data, r.devolucao_data, r.valor_total
FROM conta_usuario cu
JOIN reserva r ON cu.id = r.conta_usuario_id;

-- Lista os donos e seus respectivos veículos
CREATE VIEW donos_veiculos AS
SELECT d.id AS dono_id, cu.primeiro_nome, v.veiculo_nome, v.veiculo_ano
FROM dono d
JOIN conta_usuario cu ON d.conta_usuario_id = cu.id
JOIN veiculo v ON d.id = v.dono_id;

-- Calcula a média de avaliações para cada veículo
CREATE VIEW media_avaliacoes AS
SELECT v.veiculo_nome, AVG(r.media_avaliacao) AS media_avaliacao
FROM veiculo v
JOIN review_usuario r ON v.id = r.veiculo_id
GROUP BY v.veiculo_nome;

-- Exibe os veículos disponíveis para reserva
CREATE VIEW veiculos_disponiveis AS
SELECT v.id, v.veiculo_nome, v.veiculo_ano, l.nome_local
FROM veiculo v
LEFT JOIN reserva r ON v.id = r.veiculo_id
JOIN local l ON v.local_id = l.id
WHERE r.veiculo_id IS NULL;

-- Lista todos os usuários cadastrados
CREATE VIEW lista_usuarios AS
SELECT id, primeiro_nome, segundo_nome, e_mail, data_de_entrada
FROM conta_usuario;