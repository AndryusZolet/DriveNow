-- phpMyAdmin SQL Dump
-- version 5.2.0
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3307
-- Tempo de geração: 03-Jun-2025 às 16:12
-- Versão do servidor: 10.4.24-MariaDB
-- versão do PHP: 8.1.6

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Banco de dados: `drivenow`
--

-- --------------------------------------------------------

--
-- Estrutura da tabela `administrador`
--

CREATE DATABASE IF NOT EXISTS `drivenow` /*!40100 DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci */;

USE `drivenow`;

CREATE TABLE `administrador` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `conta_usuario_id` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `conta_usuario_id` (`conta_usuario_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Estrutura da tabela `atributo`
--

CREATE TABLE `atributo` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nome_atributo` varchar(100) DEFAULT NULL,
  `descricao` text DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Estrutura da tabela `atributos_veiculos`
--

CREATE TABLE `atributos_veiculos` (
  `veiculo_id` int(11) NOT NULL,
  `atributo_id` int(11) NOT NULL,
  PRIMARY KEY (`veiculo_id`,`atributo_id`),
  KEY `atributo_id` (`atributo_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Estrutura da tabela `avaliacao_locatario`
--

CREATE TABLE `avaliacao_locatario` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `reserva_id` int(11) NOT NULL,
  `proprietario_id` int(11) NOT NULL COMMENT 'ID do proprietário que faz a avaliação',
  `locatario_id` int(11) NOT NULL COMMENT 'ID do locatário que está sendo avaliado',
  `nota` int(1) NOT NULL CHECK (`nota` between 1 and 5),
  `comentario` text DEFAULT NULL,
  `data_avaliacao` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `reserva_id` (`reserva_id`),
  KEY `proprietario_id` (`proprietario_id`),
  KEY `locatario_id` (`locatario_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Estrutura da tabela `avaliacao_proprietario`
--

CREATE TABLE `avaliacao_proprietario` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `reserva_id` int(11) NOT NULL,
  `usuario_id` int(11) NOT NULL COMMENT 'ID do locatário que faz a avaliação',
  `proprietario_id` int(11) NOT NULL COMMENT 'ID do proprietário que está sendo avaliado',
  `nota` int(1) NOT NULL CHECK (`nota` between 1 and 5),
  `comentario` text DEFAULT NULL,
  `data_avaliacao` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `reserva_id` (`reserva_id`),
  KEY `usuario_id` (`usuario_id`),
  KEY `proprietario_id` (`proprietario_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Estrutura da tabela `avaliacao_veiculo`
--

CREATE TABLE `avaliacao_veiculo` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `reserva_id` int(11) NOT NULL,
  `usuario_id` int(11) NOT NULL,
  `veiculo_id` int(11) NOT NULL,
  `nota` int(1) NOT NULL CHECK (`nota` between 1 and 5),
  `comentario` text DEFAULT NULL,
  `data_avaliacao` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `reserva_id` (`reserva_id`),
  KEY `usuario_id` (`usuario_id`),
  KEY `veiculo_id` (`veiculo_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Estrutura da tabela `categoria_veiculo`
--

CREATE TABLE `categoria_veiculo` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `categoria` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO `categoria_veiculo` (`id`, `categoria`) VALUES
(1, 'Coupé'),
(2, 'Sedan'),
(3, 'SUV'),
(4, 'Hatch'),
(5, 'Picape'),
(6, 'Conversível'),
(7, 'Perua'),
(8, 'Minivan');

-- --------------------------------------------------------

--
-- Estrutura da tabela `cidade`
--

CREATE TABLE `cidade` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `estado_id` int(11) NOT NULL,
  `cidade_nome` varchar(100) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `estado_id` (`estado_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO `cidade` (`id`, `estado_id`, `cidade_nome`) VALUES
(7, 4, 'Manaus'),
(9, 5, 'Salvador'),
(12, 6, 'Fortaleza'),
(14, 7, 'Brasília'),
(25, 13, 'Belo Horizonte'),
(32, 16, 'Curitiba'),
(34, 16, 'Foz do Iguaçu'),
(36, 17, 'Recife'),
(40, 19, 'Rio de Janeiro'),
(45, 21, 'Porto Alegre'),
(52, 24, 'Florianópolis'),
(55, 25, 'São Paulo');

-- --------------------------------------------------------

--
-- Estrutura da tabela `conta_usuario`
--

CREATE TABLE `conta_usuario` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `is_admin` tinyint(1) NOT NULL DEFAULT 0,
  `primeiro_nome` varchar(50) DEFAULT NULL,
  `segundo_nome` varchar(50) DEFAULT NULL,
  `telefone` varchar(20) DEFAULT NULL,
  `e_mail` varchar(100) DEFAULT NULL,
  `senha` varchar(255) DEFAULT NULL,
  `data_de_entrada` date DEFAULT NULL,
  `foto_perfil` varchar(255) DEFAULT NULL,
  `cpf` varchar(14) DEFAULT NULL COMMENT 'CPF do usuário',
  `foto_cnh_frente` varchar(255) DEFAULT NULL COMMENT 'Caminho para foto da CNH (frente)',
  `foto_cnh_verso` varchar(255) DEFAULT NULL COMMENT 'Caminho para foto da CNH (verso)',
  `status_docs` enum('pendente','verificando','aprovado','rejeitado') NOT NULL DEFAULT 'pendente' COMMENT 'Status da verificação dos documentos',
  `data_verificacao` datetime DEFAULT NULL COMMENT 'Data da última verificação',
  `admin_verificacao` int(11) DEFAULT NULL COMMENT 'ID do admin que verificou os documentos',
  `tem_cnh` tinyint(1) NOT NULL DEFAULT 0 COMMENT 'Se o usuário possui CNH',
  `cadastro_completo` tinyint(1) NOT NULL DEFAULT 0 COMMENT 'Se o usuário completou o cadastro com documentos',
  `observacoes_docs` text DEFAULT NULL COMMENT 'Observações da verificação',
  `mensagens_nao_lidas` int(11) NOT NULL DEFAULT 0,
  `media_avaliacao_proprietario` decimal(3,1) DEFAULT NULL,
  `total_avaliacoes_proprietario` int(11) DEFAULT 0,
  `media_avaliacao_locatario` decimal(3,1) DEFAULT NULL,
  `total_avaliacoes_locatario` int(11) DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `e_mail` (`e_mail`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO `conta_usuario` (`id`, `is_admin`, `primeiro_nome`, `segundo_nome`, `telefone`, `e_mail`, `senha`, `data_de_entrada`, `foto_perfil`, `cpf`, `foto_cnh_frente`, `foto_cnh_verso`, `status_docs`, `data_verificacao`, `admin_verificacao`, `tem_cnh`, `cadastro_completo`, `observacoes_docs`, `mensagens_nao_lidas`, `media_avaliacao_proprietario`, `total_avaliacoes_proprietario`, `media_avaliacao_locatario`, `total_avaliacoes_locatario`) VALUES
(1, 1, 'Andryus', 'Zolet', '(41) 99796-3268', 'zoletandryus@gmail.com', '$2y$10$q7m/RxOCrcZvSi.1SKShC.u3.5DKHyyVQR7Nk3oFgX/9F0.zCm2wa', '2025-05-10', NULL, '138.477.099-25', 'uploads/user_1/docs/foto_cnh_frente_68484ff76d484_1749569527.jpg', 'uploads/user_1/docs/foto_cnh_verso_68484ff76dde1_1749569527.jpg', 'aprovado', '2025-05-10 12:32:16', 1, 1, 1, '', 0, NULL, 0, NULL, 0),
(2, 0, 'Stuart', 'Capivas', '', 'capivara@gmail.com', '$2y$10$U.zVCHqT.YRRxWpPezMUhO1zLcze44j/aX9xz9mA34uZ15DG8d8ri', '2025-06-10', NULL, '567.372.568-05', 'uploads/user_2/docs/foto_cnh_frente_684851b9eb915_1749569977.jpg', 'uploads/user_2/docs/foto_cnh_verso_684851b9ec0b6_1749569977.jpg', 'aprovado', '2025-06-10 12:40:11', 1, 1, 1, 'Bob Esponja', 0, NULL, 0, NULL, 0),
(4, 0, 'Scoobert', 'Doo', '', 'scoobydoo@gmail.com', '$2y$10$9OMuyhMBeF4XL60ExWUCEOI3bCy8ZmdrRiS7R.BSYaKckLSsfv9H.', '2025-06-10', NULL, '309.088.147-04', 'uploads/user_4/docs/foto_cnh_frente_684897b3d2961_1749587891.jpg', 'uploads/user_4/docs/foto_cnh_verso_684897b3d327b_1749587891.jpg', 'aprovado', '2025-06-10 17:39:21', 1, 1, 1, 'Biscoito Scooby', 0, NULL, 0, 5.0, 2),
(5, 0, 'Valentin', 'Rojas', '', 'valentin@gmail.com', '$2y$10$2Enp.jb4mT1ARyMNzGsRLeIW.kpzN65UQn6Va1AA8kjSW3SFqP4ca', '2025-06-10', NULL, '594.635.580-55', 'uploads/user_5/docs/foto_cnh_frente_685021829ace5_1750081922.png', 'uploads/user_5/docs/foto_cnh_verso_685021829b58d_1750081922.png', 'aprovado', '2025-06-16 10:52:21', 1, 1, 1, 'Validado', 0, NULL, 0, NULL, 0),
(6, 0, 'Norville', 'Rogers', '', 'salsicha@gmail.com', '$2y$10$LBFFy7x.IshlyyJaSEV5R.hi1TYILIDURBmUi6ztFiP1ZxIlPTXS6', '2025-06-11', NULL, '853.243.670-60', 'uploads/user_6/docs/foto_cnh_frente_6849640f26be4_1749640207.jpg', 'uploads/user_6/docs/foto_cnh_verso_6849640f2a79e_1749640207.jpg', 'aprovado', '2025-06-11 08:10:41', 1, 1, 1, 'salsicha', 0, NULL, 0, NULL, 0),
(7, 0, 'Anakin', 'Skywalker', '', 'darthvader@gmail.com', '$2y$10$8959IB2rAJsNuhePmJvsf.PkTxnUTKShyrYejgutF.CXULbHgYERG', '2025-06-13', NULL, '540.120.230-04', 'uploads/user_7/docs/foto_cnh_frente_685023298c75d_1750082345.jpg', 'uploads/user_7/docs/foto_cnh_verso_685023298d0bd_1750082345.jpg', 'aprovado', '2025-06-16 10:59:41', 1, 1, 1, 'may the 4th be with you', 0, NULL, 0, NULL, 0);

-- --------------------------------------------------------

--
-- Estrutura da tabela `dono`
--

CREATE TABLE `dono` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `conta_usuario_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `conta_usuario_id` (`conta_usuario_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO `dono` (`id`, `conta_usuario_id`) VALUES
(1, 1),
(2, 5);

-- --------------------------------------------------------

--
-- Estrutura da tabela `estado`
--

CREATE TABLE `estado` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `estado_nome` varchar(100) NOT NULL,
  `sigla` char(2) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO `estado` (`id`, `estado_nome`, `sigla`) VALUES
(1, 'Acre', 'AC'),
(2, 'Alagoas', 'AL'),
(3, 'Amapá', 'AP'),
(4, 'Amazonas', 'AM'),
(5, 'Bahia', 'BA'),
(6, 'Ceará', 'CE'),
(7, 'Distrito Federal', 'DF'),
(8, 'Espírito Santo', 'ES'),
(9, 'Goiás', 'GO'),
(10, 'Maranhão', 'MA'),
(11, 'Mato Grosso', 'MT'),
(12, 'Mato Grosso do Sul', 'MS'),
(13, 'Minas Gerais', 'MG'),
(14, 'Pará', 'PA'),
(15, 'Paraíba', 'PB'),
(16, 'Paraná', 'PR'),
(17, 'Pernambuco', 'PE'),
(18, 'Piauí', 'PI'),
(19, 'Rio de Janeiro', 'RJ'),
(20, 'Rio Grande do Norte', 'RN'),
(21, 'Rio Grande do Sul', 'RS'),
(22, 'Rondônia', 'RO'),
(23, 'Roraima', 'RR'),
(24, 'Santa Catarina', 'SC'),
(25, 'São Paulo', 'SP'),
(26, 'Sergipe', 'SE'),
(27, 'Tocantins', 'TO');

-- --------------------------------------------------------

--
-- Estrutura da tabela `favoritos`
--

CREATE TABLE `favoritos` (
  `veiculo_id` int(11) NOT NULL,
  `conta_usuario_id` int(11) NOT NULL,
  PRIMARY KEY (`veiculo_id`,`conta_usuario_id`),
  KEY `conta_usuario_id` (`conta_usuario_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Estrutura da tabela `historico_pagamento`
--

CREATE TABLE `historico_pagamento` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `pagamento_id` int(11) NOT NULL,
  `status_anterior` varchar(20) DEFAULT NULL,
  `novo_status` varchar(20) NOT NULL,
  `observacao` text DEFAULT NULL,
  `data_alteracao` datetime NOT NULL DEFAULT current_timestamp(),
  `usuario_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `pagamento_id` (`pagamento_id`),
  KEY `usuario_id` (`usuario_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Estrutura da tabela `imagem`
--

CREATE TABLE `imagem` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `veiculo_id` int(11) DEFAULT NULL,
  `imagem_url` varchar(255) DEFAULT NULL,
  `imagem_ordem` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `veiculo_id` (`veiculo_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Estrutura da tabela `local`
--

CREATE TABLE `local` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `cidade_id` int(11) NOT NULL,
  `nome_local` varchar(100) NOT NULL,
  `endereco` varchar(255) DEFAULT NULL,
  `complemento` varchar(100) DEFAULT NULL,
  `cep` varchar(10) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `cidade_id` (`cidade_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO `local` (`id`, `cidade_id`, `nome_local`, `endereco`, `complemento`, `cep`) VALUES
(1, 55, 'Parque Ibirapuera', 'Av. Pedro Álvares Cabral', 'Portão 10', '04094-050'),
(2, 55, 'Avenida Paulista', 'Avenida Paulista', 'Próximo ao MASP', '01310-100'),
(3, 55, 'Mercado Municipal', 'Rua da Cantareira, 306', 'Centro', '01024-000'),
(4, 55, 'Shopping Ibirapuera', 'Av. Ibirapuera, 3103', 'Indianópolis', '04028-000'),
(5, 55, 'Aeroporto de Congonhas', 'Av. Washington Luís', 'Campo Belo', '04626-911'),
(6, 40, 'Cristo Redentor', 'Parque Nacional da Tijuca', 'Alto da Boa Vista', '22241-125'),
(7, 40, 'Praia de Copacabana', 'Av. Atlântica', 'Copacabana', '22070-000'),
(8, 40, 'Pão de Açúcar', 'Av. Pasteur, 520', 'Urca', '22290-240'),
(9, 40, 'Maracanã', 'Av. Pres. Castelo Branco', 'Maracanã', '20271-130'),
(10, 40, 'Aeroporto Santos Dumont', 'Praça Sen. Salgado Filho', 'Centro', '20021-340'),
(11, 32, 'Jardim Botânico', 'Rua Engenheiro Ostoja Roguski', 'Jardim Botânico', '80210-390'),
(12, 32, 'Museu Oscar Niemeyer', 'R. Mal. Hermes, 999', 'Centro Cívico', '80530-230'),
(13, 32, 'Parque Barigui', 'Av. Cândido Hartmann', 'Santo Inácio', '82025-000'),
(14, 32, 'Aeroporto Afonso Pena', 'Av. Rocha Pombo, s/n', 'São José dos Pinhais', '83010-900'),
(15, 32, 'Shopping Estação', 'Av. Sete de Setembro, 2775', 'Rebouças', '80230-010'),
(16, 14, 'Congresso Nacional', 'Praça dos Três Poderes', 'Zona Cívico-Administrativa', '70160-900'),
(17, 14, 'Catedral Metropolitana', 'Esplanada dos Ministérios', 'Lote 12', '70050-000'),
(18, 14, 'Palácio do Planalto', 'Praça dos Três Poderes', 'Zona Cívico-Administrativa', '70150-900'),
(19, 14, 'Aeroporto Internacional de Brasília', 'Lago Sul', '', '71608-900'),
(20, 14, 'Parque da Cidade', 'Eixo Monumental', 'Sudoeste', '70070-350'),
(21, 9, 'Pelourinho', 'Centro Histórico', '', '40026-280'),
(22, 9, 'Farol da Barra', 'Av. Oceânica', 'Barra', '40140-130'),
(23, 9, 'Elevador Lacerda', 'Praça Municipal', 'Centro', '40020-010'),
(24, 9, 'Mercado Modelo', 'Praça Visconde do Cairu', 'Comércio', '40015-970'),
(25, 9, 'Aeroporto Internacional de Salvador', 'Praça Gago Coutinho', 'São Cristóvão', '41520-970'),
(26, 36, 'Marco Zero', 'Av. Alfredo Lisboa', 'Recife Antigo', '50030-150'),
(27, 36, 'Praia de Boa Viagem', 'Av. Boa Viagem', 'Boa Viagem', '51011-000'),
(28, 36, 'Instituto Ricardo Brennand', 'R. Mário Campelo, 700', 'Várzea', '50741-540'),
(29, 36, 'Shopping Recife', 'R. Padre Carapuceiro, 777', 'Boa Viagem', '51020-900'),
(30, 36, 'Aeroporto Internacional do Recife', 'Av. Mascarenhas de Morais', 'Imbiribeira', '51210-000'),
(31, 25, 'Praça da Liberdade', 'Praça da Liberdade', 'Funcionários', '30140-010'),
(32, 25, 'Mercado Central', 'Av. Augusto de Lima, 744', 'Centro', '30190-922'),
(33, 25, 'Mineirão', 'Av. Antônio Abrahão Caram, 1001', 'São José', '31275-000'),
(34, 25, 'Parque Municipal', 'Av. Afonso Pena, 1377', 'Centro', '30130-002'),
(35, 25, 'Aeroporto de Confins', 'MG-010', 'Confins', '33500-900'),
(36, 45, 'Mercado Público', 'Largo Jornalista Glênio Peres', 'Centro Histórico', '90010-120'),
(37, 45, 'Parque Farroupilha (Redenção)', 'Av. João Pessoa', 'Farroupilha', '90040-000'),
(38, 45, 'Casa de Cultura Mario Quintana', 'R. dos Andradas, 736', 'Centro Histórico', '90020-004'),
(39, 45, 'Aeroporto Salgado Filho', 'Av. Severo Dullius, 90010', 'São João', '90200-310'),
(40, 45, 'Shopping Iguatemi', 'Av. João Wallig, 1800', 'Passo dAreia', '91340-000'),
(41, 34, 'Cataratas do Iguaçu', 'Rodovia das Cataratas, km 18', 'Parque Nacional do Iguaçu', '85855-750'),
(42, 34, 'Usina Hidrelétrica de Itaipu', 'Av. Tancredo Neves, 6731', 'Jardim Itaipu', '85856-970'),
(43, 34, 'Marco das Três Fronteiras', 'Av. General Meira', 'Jardim Jupira', '85853-110'),
(44, 34, 'Parque das Aves', 'Av. das Cataratas, 12450', 'Vila Yolanda', '85853-000'),
(45, 34, 'Aeroporto Internacional de Foz do Iguaçu', 'Rod. das Cataratas, km 17', 'Aeroporto', '85863-900'),
(46, 52, 'Praia de Jurerê', 'Av. dos Búzios', 'Jurerê Internacional', '88053-300'),
(47, 52, 'Ponte Hercílio Luz', 'Centro', 'Centro', '88010-970'),
(48, 52, 'Mercado Público', 'R. Jerônimo Coelho', 'Centro', '88010-030'),
(49, 52, 'Praia do Campeche', 'Av. Pequeno Príncipe', 'Campeche', '88063-000'),
(50, 52, 'Aeroporto Hercílio Luz', 'Rod. Ac. ao Aeroporto, 6200', 'Carianos', '88047-902'),
(51, 7, 'Teatro Amazonas', 'Av. Eduardo Ribeiro', 'Centro', '69025-140'),
(52, 7, 'Encontro das Águas', 'Rio Negro', 'Zona Rural', '69000-000'),
(53, 7, 'Mercado Municipal Adolpho Lisboa', 'R. dos Barés', 'Centro', '69005-020'),
(54, 7, 'Aeroporto Internacional de Manaus', 'Av. Santos Dumont', 'Tarumã', '69041-000'),
(55, 7, 'MUSA - Museu da Amazônia', 'Av. Margarita', 'Cidade de Deus', '69099-415'),
(56, 12, 'Praia do Futuro', 'Av. Zezé Diogo', 'Praia do Futuro', '60182-025'),
(57, 12, 'Beach Park', 'Rua Porto das Dunas, 2734', 'Aquiraz', '61700-000'),
(58, 12, 'Mercado Central', 'R. Gen. Bezerril, 115', 'Centro', '60055-100'),
(59, 12, 'Catedral Metropolitana', 'Av. Dom Manuel', 'Centro', '60060-090'),
(60, 12, 'Aeroporto Pinto Martins', 'Av. Senador Carlos Jereissati', 'Serrinha', '60741-000');

-- --------------------------------------------------------

--
-- Estrutura da tabela `log_verificacao_docs`
--

CREATE TABLE `log_verificacao_docs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `conta_usuario_id` int(11) NOT NULL,
  `admin_id` int(11) NOT NULL,
  `status_anterior` enum('pendente','verificando','aprovado','rejeitado') DEFAULT NULL,
  `novo_status` enum('pendente','verificando','aprovado','rejeitado') DEFAULT NULL,
  `data_alteracao` datetime DEFAULT current_timestamp(),
  `observacoes` text DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `conta_usuario_id` (`conta_usuario_id`),
  KEY `admin_id` (`admin_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Estrutura da tabela `mensagem`
--

CREATE TABLE `mensagem` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `reserva_id` int(11) NOT NULL,
  `remetente_id` int(11) NOT NULL,
  `mensagem` text NOT NULL,
  `data_envio` datetime NOT NULL DEFAULT current_timestamp(),
  `lida` tinyint(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `reserva_id` (`reserva_id`),
  KEY `remetente_id` (`remetente_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Estrutura da tabela `pagamento`
--

CREATE TABLE `pagamento` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `reserva_id` int(11) NOT NULL,
  `valor` decimal(10,2) NOT NULL,
  `metodo_pagamento` varchar(50) NOT NULL,
  `status` varchar(20) NOT NULL DEFAULT 'pendente',
  `data_pagamento` datetime DEFAULT NULL,
  `data_criacao` datetime NOT NULL DEFAULT current_timestamp(),
  `comprovante_url` varchar(255) DEFAULT NULL,
  `codigo_transacao` varchar(100) DEFAULT NULL,
  `detalhes` text DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `reserva_id` (`reserva_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Estrutura da tabela `reserva`
--

CREATE TABLE `reserva` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `veiculo_id` int(11) DEFAULT NULL,
  `conta_usuario_id` int(11) DEFAULT NULL,
  `reserva_data` date DEFAULT NULL,
  `devolucao_data` date DEFAULT NULL,
  `diaria_valor` decimal(10,2) DEFAULT NULL,
  `taxas_de_uso` decimal(10,2) DEFAULT NULL,
  `taxas_de_limpeza` decimal(10,2) DEFAULT NULL,
  `valor_total` decimal(10,2) DEFAULT NULL,
  `status` varchar(20) DEFAULT 'pendente',
  `observacoes` text DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `veiculo_id` (`veiculo_id`),
  KEY `conta_usuario_id` (`conta_usuario_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Estrutura da tabela `veiculo`
--

CREATE TABLE `veiculo` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `local_id` int(11) DEFAULT NULL,
  `categoria_veiculo_id` int(11) DEFAULT NULL,
  `dono_id` int(11) DEFAULT NULL,
  `veiculo_marca` varchar(50) DEFAULT NULL,
  `veiculo_modelo` varchar(100) DEFAULT NULL,
  `veiculo_ano` int(11) DEFAULT NULL,
  `veiculo_km` int(11) DEFAULT NULL,
  `veiculo_placa` varchar(50) DEFAULT NULL,
  `veiculo_cambio` varchar(50) DEFAULT NULL,
  `veiculo_combustivel` varchar(50) DEFAULT NULL,
  `veiculo_portas` int(11) DEFAULT NULL,
  `veiculo_acentos` int(11) DEFAULT NULL,
  `veiculo_tracao` varchar(50) DEFAULT NULL,
  `disponivel` tinyint(1) DEFAULT 1 COMMENT '0=Indisponível, 1=Disponível',
  `preco_diaria` decimal(10,2) NOT NULL DEFAULT 150.00,
  `descricao` text DEFAULT NULL,
  `media_avaliacao` decimal(3,1) DEFAULT NULL,
  `total_avaliacoes` int(11) DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `local_id` (`local_id`),
  KEY `categoria_veiculo_id` (`categoria_veiculo_id`),
  KEY `dono_id` (`dono_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO `veiculo` (`id`, `local_id`, `categoria_veiculo_id`, `dono_id`, `veiculo_marca`, `veiculo_modelo`, `veiculo_ano`, `veiculo_km`, `veiculo_placa`, `veiculo_cambio`, `veiculo_combustivel`, `veiculo_portas`, `veiculo_acentos`, `veiculo_tracao`, `disponivel`, `preco_diaria`, `descricao`, `media_avaliacao`, `total_avaliacoes`) VALUES
(1, 11, 1, 1, 'Toyota', 'Supra', 2000, 35897, 'ZOL3T11', 'Manual', 'Gasolina', 2, 4, 'Traseira', 1, 450.00, 'Toyota Supra MK4 3.0i Turbo', NULL, 0),
(2, 1, 8, 1, 'Mercedes-Benz', 'Sprinter 515', 2010, 89032, 'SCO0B17', 'Automático', 'Gasolina', 3, 3, 'Dianteira', 1, 150.00, 'prefeita para uma maquina de mistério', NULL, 0),
(3, 6, 4, 1, 'Honda', 'Civic Type R', 2001, 94034, 'CAP1V45', 'Manual', 'Gasolina', 2, 5, 'Dianteira', 1, 200.00, 'O Honda Civic Type R é uma série de modelos hot hatchback esportivo', NULL, 0),
(4, 44, 3, 2, 'Audi', 'Q5', 2018, 54879, 'VAL3T14', 'Automático', 'Gasolina', 4, 5, 'Dianteira', 1, 389.00, '', NULL, 0);


-- --------------------------------------------------------
--
-- VIEWS DO SISTEMA
--
-- --------------------------------------------------------
--
-- View para veículos disponíveis com informações completas
--
CREATE VIEW vw_veiculos_disponiveis AS
SELECT 
    v.id,
    v.veiculo_marca,
    v.veiculo_modelo,
    v.veiculo_ano,
    v.preco_diaria,
    v.media_avaliacao,
    v.total_avaliacoes,
    v.descricao,
    cv.categoria,
    l.nome_local,
    l.endereco,
    c.cidade_nome,
    e.estado_nome,
    e.sigla as estado_sigla,
    CONCAT(cu.primeiro_nome, ' ', cu.segundo_nome) as proprietario_nome,
    cu.media_avaliacao_proprietario
FROM veiculo v
JOIN categoria_veiculo cv ON v.categoria_veiculo_id = cv.id
JOIN local l ON v.local_id = l.id
JOIN cidade c ON l.cidade_id = c.id
JOIN estado e ON c.estado_id = e.id
JOIN dono d ON v.dono_id = d.id
JOIN conta_usuario cu ON d.conta_usuario_id = cu.id
WHERE v.disponivel = 1;
--
-- View para resumo de reservas
--
CREATE VIEW vw_reservas_completas AS
SELECT 
    r.id,
    r.status,
    r.reserva_data,
    r.devolucao_data,
    r.valor_total,
    CONCAT(cu.primeiro_nome, ' ', cu.segundo_nome) as locatario_nome,
    cu.e_mail as locatario_email,
    CONCAT(v.veiculo_marca, ' ', v.veiculo_modelo, ' ', v.veiculo_ano) as veiculo_completo,
    l.nome_local,
    c.cidade_nome,
    CONCAT(prop.primeiro_nome, ' ', prop.segundo_nome) as proprietario_nome
FROM reserva r
JOIN conta_usuario cu ON r.conta_usuario_id = cu.id
JOIN veiculo v ON r.veiculo_id = v.id
JOIN local l ON v.local_id = l.id
JOIN cidade c ON l.cidade_id = c.id
JOIN dono d ON v.dono_id = d.id
JOIN conta_usuario prop ON d.conta_usuario_id = prop.id;
--
-- View para estatísticas de usuários
--
CREATE VIEW vw_stats_usuarios AS
SELECT 
    cu.id,
    CONCAT(cu.primeiro_nome, ' ', cu.segundo_nome) as nome_completo,
    cu.e_mail,
    cu.status_docs,
    cu.tem_cnh,
    COUNT(DISTINCT r.id) as total_reservas,
    COUNT(DISTINCT v.id) as total_veiculos_cadastrados,
    COALESCE(cu.media_avaliacao_locatario, 0) as media_locatario,
    COALESCE(cu.media_avaliacao_proprietario, 0) as media_proprietario
FROM conta_usuario cu
LEFT JOIN reserva r ON cu.id = r.conta_usuario_id
LEFT JOIN dono d ON cu.id = d.conta_usuario_id
LEFT JOIN veiculo v ON d.id = v.dono_id
WHERE cu.is_admin = 0
GROUP BY cu.id;
--
-- View para veículos por categoria e localização
--
CREATE VIEW vw_veiculos_por_categoria AS
SELECT 
    cv.categoria,
    e.estado_nome,
    c.cidade_nome,
    COUNT(v.id) as total_veiculos,
    AVG(v.preco_diaria) as preco_medio,
    COUNT(CASE WHEN v.disponivel = 1 THEN 1 END) as disponiveis
FROM veiculo v
JOIN categoria_veiculo cv ON v.categoria_veiculo_id = cv.id
JOIN local l ON v.local_id = l.id
JOIN cidade c ON l.cidade_id = c.id
JOIN estado e ON c.estado_id = e.id
GROUP BY cv.categoria, e.estado_nome, c.cidade_nome;
--
-- View para dashboard administrativo
--
CREATE VIEW vw_dashboard_admin AS
SELECT 
    (SELECT COUNT(*) FROM conta_usuario WHERE is_admin = 0) as total_usuarios,
    (SELECT COUNT(*) FROM veiculo) as total_veiculos,
    (SELECT COUNT(*) FROM reserva) as total_reservas,
    (SELECT COUNT(*) FROM conta_usuario WHERE status_docs = 'pendente') as docs_pendentes,
    (SELECT SUM(valor_total) FROM reserva WHERE status = 'confirmada') as receita_total,
    (SELECT COUNT(*) FROM reserva WHERE status = 'pendente') as reservas_pendentes;

-- --------------------------------------------------------
--
-- Restrições para despejos de tabelas
--

--
-- Limitadores para a tabela `administrador`
--
ALTER TABLE `administrador`
  ADD CONSTRAINT `fk_admin_usuario` FOREIGN KEY (`conta_usuario_id`) REFERENCES `conta_usuario` (`id`) ON DELETE CASCADE;

--
-- Limitadores para a tabela `atributos_veiculos`
--
ALTER TABLE `atributos_veiculos`
  ADD CONSTRAINT `atributos_veiculos_ibfk_1` FOREIGN KEY (`veiculo_id`) REFERENCES `veiculo` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `atributos_veiculos_ibfk_2` FOREIGN KEY (`atributo_id`) REFERENCES `atributo` (`id`) ON DELETE CASCADE;

--
-- Limitadores para a tabela `avaliacao_locatario`
--
ALTER TABLE `avaliacao_locatario`
  ADD CONSTRAINT `avaliacao_locatario_ibfk_1` FOREIGN KEY (`reserva_id`) REFERENCES `reserva` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `avaliacao_locatario_ibfk_2` FOREIGN KEY (`proprietario_id`) REFERENCES `conta_usuario` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `avaliacao_locatario_ibfk_3` FOREIGN KEY (`locatario_id`) REFERENCES `conta_usuario` (`id`) ON DELETE CASCADE;

--
-- Limitadores para a tabela `avaliacao_proprietario`
--
ALTER TABLE `avaliacao_proprietario`
  ADD CONSTRAINT `avaliacao_proprietario_ibfk_1` FOREIGN KEY (`reserva_id`) REFERENCES `reserva` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `avaliacao_proprietario_ibfk_2` FOREIGN KEY (`usuario_id`) REFERENCES `conta_usuario` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `avaliacao_proprietario_ibfk_3` FOREIGN KEY (`proprietario_id`) REFERENCES `conta_usuario` (`id`) ON DELETE CASCADE;

--
-- Limitadores para a tabela `avaliacao_veiculo`
--
ALTER TABLE `avaliacao_veiculo`
  ADD CONSTRAINT `avaliacao_veiculo_ibfk_1` FOREIGN KEY (`reserva_id`) REFERENCES `reserva` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `avaliacao_veiculo_ibfk_2` FOREIGN KEY (`usuario_id`) REFERENCES `conta_usuario` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `avaliacao_veiculo_ibfk_3` FOREIGN KEY (`veiculo_id`) REFERENCES `veiculo` (`id`) ON DELETE CASCADE;

--
-- Limitadores para a tabela `cidade`
--
ALTER TABLE `cidade`
  ADD CONSTRAINT `cidade_ibfk_1` FOREIGN KEY (`estado_id`) REFERENCES `estado` (`id`);

--
-- Limitadores para a tabela `dono`
--
ALTER TABLE `dono`
  ADD CONSTRAINT `fk_dono_conta_usuario` FOREIGN KEY (`conta_usuario_id`) REFERENCES `conta_usuario` (`id`) ON DELETE CASCADE;

--
-- Limitadores para a tabela `historico_pagamento`
--
ALTER TABLE `historico_pagamento`
  ADD CONSTRAINT `historico_pagamento_ibfk_1` FOREIGN KEY (`pagamento_id`) REFERENCES `pagamento` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `historico_pagamento_ibfk_2` FOREIGN KEY (`usuario_id`) REFERENCES `conta_usuario` (`id`) ON DELETE SET NULL;

--
-- Limitadores para a tabela `imagem`
--
ALTER TABLE `imagem`
  ADD CONSTRAINT `imagem_ibfk_1` FOREIGN KEY (`veiculo_id`) REFERENCES `veiculo` (`id`) ON DELETE CASCADE;

--
-- Limitadores para a tabela `local`
--
ALTER TABLE `local`
  ADD CONSTRAINT `local_ibfk_1` FOREIGN KEY (`cidade_id`) REFERENCES `cidade` (`id`);

--
-- Limitadores para a tabela `log_verificacao_docs`
--
ALTER TABLE `log_verificacao_docs`
  ADD CONSTRAINT `log_verificacao_docs_ibfk_1` FOREIGN KEY (`conta_usuario_id`) REFERENCES `conta_usuario` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `log_verificacao_docs_ibfk_2` FOREIGN KEY (`admin_id`) REFERENCES `administrador` (`id`) ON DELETE CASCADE;

--
-- Limitadores para a tabela `mensagem`
--
ALTER TABLE `mensagem`
  ADD CONSTRAINT `mensagem_ibfk_1` FOREIGN KEY (`reserva_id`) REFERENCES `reserva` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `mensagem_ibfk_2` FOREIGN KEY (`remetente_id`) REFERENCES `conta_usuario` (`id`) ON DELETE CASCADE;

--
-- Limitadores para a tabela `pagamento`
--
ALTER TABLE `pagamento`
  ADD CONSTRAINT `pagamento_ibfk_1` FOREIGN KEY (`reserva_id`) REFERENCES `reserva` (`id`) ON DELETE CASCADE;

--
-- Limitadores para a tabela `reserva`
--
ALTER TABLE `reserva`
  ADD CONSTRAINT `reserva_ibfk_1` FOREIGN KEY (`veiculo_id`) REFERENCES `veiculo` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `reserva_ibfk_2` FOREIGN KEY (`conta_usuario_id`) REFERENCES `conta_usuario` (`id`) ON DELETE SET NULL;

--
-- Limitadores para a tabela `veiculo`
--
ALTER TABLE `veiculo`
  ADD CONSTRAINT `veiculo_ibfk_1` FOREIGN KEY (`local_id`) REFERENCES `local` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `veiculo_ibfk_2` FOREIGN KEY (`categoria_veiculo_id`) REFERENCES `categoria_veiculo` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `veiculo_ibfk_3` FOREIGN KEY (`dono_id`) REFERENCES `dono` (`id`) ON DELETE SET NULL;

--
-- Limitadores para a tabela `favoritos`
--
ALTER TABLE `favoritos`
  ADD CONSTRAINT `favoritos_ibfk_1` FOREIGN KEY (`veiculo_id`) REFERENCES `veiculo` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `favoritos_ibfk_2` FOREIGN KEY (`conta_usuario_id`) REFERENCES `conta_usuario` (`id`) ON DELETE CASCADE;

COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;