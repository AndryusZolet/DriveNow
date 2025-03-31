-- Criando o banco de dados
DROP DATABASE DriveNow;
CREATE DATABASE IF NOT EXISTS DriveNow;
USE DriveNow;

-- Tabela de usuários
CREATE TABLE conta_usuario (
    id INT PRIMARY KEY AUTO_INCREMENT,
    primeiro_nome VARCHAR(50),
    segundo_nome VARCHAR(50),
    e_mail VARCHAR(100) UNIQUE,
    senha VARCHAR(255),
    data_de_entrada DATE
);

-- Tabela de donos de veículos
CREATE TABLE dono (
    id INT PRIMARY KEY AUTO_INCREMENT,
    conta_usuario_id INT UNIQUE,
    FOREIGN KEY (conta_usuario_id) REFERENCES conta_usuario(id) ON DELETE CASCADE
);

-- Tabela de cidades
CREATE TABLE cidade (
    id INT PRIMARY KEY AUTO_INCREMENT,
    cidade_nome VARCHAR(100)
);

-- Tabela de estados (referencia cidade)
CREATE TABLE estado (
    id INT PRIMARY KEY AUTO_INCREMENT,
    cidade_id INT,
    estado_nome VARCHAR(100),
    FOREIGN KEY (cidade_id) REFERENCES cidade(id) ON DELETE CASCADE
);

-- Tabela de locais (referencia estado)
CREATE TABLE local (
    id INT PRIMARY KEY AUTO_INCREMENT,
    estado_id INT,
    nome_local VARCHAR(100),
    FOREIGN KEY (estado_id) REFERENCES estado(id) ON DELETE CASCADE
);

-- Tabela de categorias de veículos
CREATE TABLE categoria_veiculo (
    id INT PRIMARY KEY AUTO_INCREMENT,
    categoria VARCHAR(100)
);

-- Tabela de veículos
CREATE TABLE veiculo (
    id INT PRIMARY KEY AUTO_INCREMENT,
    local_id INT,
    categoria_veiculo_id INT,
    dono_id INT,
    veiculo_nome VARCHAR(100),
    veiculo_ano INT,
    veiculo_km INT,
    veiculo_cambio VARCHAR(50),
    veiculo_combustivel VARCHAR(50),
    veiculo_portas INT,
    veiculo_acentos INT,
    veiculo_tracao VARCHAR(50),
    FOREIGN KEY (local_id) REFERENCES local(id) ON DELETE SET NULL,
    FOREIGN KEY (categoria_veiculo_id) REFERENCES categoria_veiculo(id) ON DELETE SET NULL,
    FOREIGN KEY (dono_id) REFERENCES dono(id) ON DELETE CASCADE
);

-- Tabela de atributos dos veículos
CREATE TABLE atributo (
    id INT PRIMARY KEY AUTO_INCREMENT,
    nome_atributo VARCHAR(100),
    descricao TEXT
);

CREATE TABLE atributos_veiculos (
    veiculo_id INT,
    atributo_id INT,
    PRIMARY KEY (veiculo_id, atributo_id),
    FOREIGN KEY (veiculo_id) REFERENCES veiculo(id) ON DELETE CASCADE,
    FOREIGN KEY (atributo_id) REFERENCES atributo(id) ON DELETE CASCADE
);

-- Tabela de imagens dos veículos
CREATE TABLE imagem (
    id INT PRIMARY KEY AUTO_INCREMENT,
    veiculo_id INT,
    imagem_url VARCHAR(255),
    imagem_ordem INT,
    FOREIGN KEY (veiculo_id) REFERENCES veiculo(id) ON DELETE CASCADE
);

-- Tabela de reviews dos usuários sobre os veículos
CREATE TABLE review_usuario (
    id INT PRIMARY KEY AUTO_INCREMENT,
    veiculo_id INT,
    conta_usuario_id INT,
    media_avaliacao FLOAT CHECK (media_avaliacao BETWEEN 0 AND 5),
    comentario TEXT,
    FOREIGN KEY (veiculo_id) REFERENCES veiculo(id) ON DELETE CASCADE,
    FOREIGN KEY (conta_usuario_id) REFERENCES conta_usuario(id) ON DELETE CASCADE
);

-- Tabela de reservas de veículos
CREATE TABLE reserva (
    id INT PRIMARY KEY AUTO_INCREMENT,
    veiculo_id INT,
    conta_usuario_id INT,
    reserva_data DATE,
    devolucao_data DATE,
    diaria_valor DECIMAL(10,2),
    taxas_de_uso DECIMAL(10,2),
    taxas_de_limpeza DECIMAL(10,2),
    valor_total DECIMAL(10,2),
    FOREIGN KEY (veiculo_id) REFERENCES veiculo(id) ON DELETE CASCADE,
    FOREIGN KEY (conta_usuario_id) REFERENCES conta_usuario(id) ON DELETE CASCADE
);

-- Tabela de favoritos (veículos marcados como favoritos pelos usuários)
CREATE TABLE favoritos (
    veiculo_id INT,
    conta_usuario_id INT,
    PRIMARY KEY (veiculo_id, conta_usuario_id),
    FOREIGN KEY (veiculo_id) REFERENCES veiculo(id) ON DELETE CASCADE,
    FOREIGN KEY (conta_usuario_id) REFERENCES conta_usuario(id) ON DELETE CASCADE
);
