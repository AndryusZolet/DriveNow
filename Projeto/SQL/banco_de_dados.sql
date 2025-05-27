-- --------------------------------------------------------
-- Servidor:                     127.0.0.1
-- Versão do servidor:           10.4.32-MariaDB - mariadb.org binary distribution
-- OS do Servidor:               Win64
-- HeidiSQL Versão:              12.10.0.7000
-- --------------------------------------------------------

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET NAMES utf8 */;
/*!50503 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;


-- Copiando estrutura do banco de dados para drivenow
CREATE DATABASE IF NOT EXISTS `drivenow` /*!40100 DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci */;
USE `drivenow`;

-- Copiando estrutura para tabela drivenow.atributo
CREATE TABLE IF NOT EXISTS `atributo` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nome_atributo` varchar(100) DEFAULT NULL,
  `descricao` text DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Copiando dados para a tabela drivenow.atributo: ~0 rows (aproximadamente)


-- Copiando dados para a tabela drivenow.atributos_veiculos: ~0 rows (aproximadamente)

-- Copiando estrutura para tabela drivenow.categoria_veiculo
CREATE TABLE IF NOT EXISTS `categoria_veiculo` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `categoria` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Copiando dados para a tabela drivenow.categoria_veiculo: ~8 rows (aproximadamente)
INSERT INTO `categoria_veiculo` (`id`, `categoria`) VALUES
	(1, 'Coupé'),
	(2, 'Sedan'),
	(3, 'SUV'),
	(4, 'Hatch'),
	(5, 'Picape'),
	(6, 'Conversível'),
	(7, 'Perua'),
	(8, 'Minivan');

-- Copiando estrutura para tabela drivenow.estado
CREATE TABLE IF NOT EXISTS `estado` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `estado_nome` varchar(100) NOT NULL,
  `sigla` char(2) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=28 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Copiando dados para a tabela drivenow.estado: ~27 rows (aproximadamente)
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

-- Copiando estrutura para tabela drivenow.cidade
CREATE TABLE IF NOT EXISTS `cidade` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `estado_id` int(11) NOT NULL,
  `cidade_nome` varchar(100) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `estado_id` (`estado_id`),
  CONSTRAINT `cidade_ibfk_1` FOREIGN KEY (`estado_id`) REFERENCES `estado` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=63 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Copiando dados para a tabela drivenow.cidade: ~62 rows (aproximadamente)
INSERT INTO `cidade` (id, estado_id, cidade_nome) VALUES
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

-- Copiando estrutura para tabela drivenow.conta_usuario
CREATE TABLE IF NOT EXISTS `conta_usuario` (
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
  PRIMARY KEY (`id`),
  UNIQUE KEY `e_mail` (`e_mail`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Copiando dados para a tabela drivenow.conta_usuario: ~2 rows (aproximadamente)
INSERT INTO `conta_usuario` (`id`, `is_admin`, `primeiro_nome`, `segundo_nome`, `telefone`, `e_mail`, `senha`, `data_de_entrada`, `foto_perfil`, `cpf`, `foto_cnh_frente`, `foto_cnh_verso`, `status_docs`, `data_verificacao`, `admin_verificacao`, `tem_cnh`, `cadastro_completo`, `observacoes_docs`) VALUES
	(4, 1, 'Valentin', 'Rojas', '(41) 99781-2602', 'valentin@gmail.com', '$2y$10$v2pibQ5XqxByeDZslhrh6e4MRNUWKbCOTayxbfq14o1cNpXaHRike', '2025-05-07', NULL, '594.635.580-55', 'uploads/user_4/docs/foto_cnh_frente_6823ed28d0954_1747184936.jpg', 'uploads/user_4/docs/foto_cnh_verso_6823ed28d0af8_1747184936.jpg', 'aprovado', '2025-05-13 22:30:45', 4, 1, 1, ''),
	(5, 0, 'Valentin2', 'Rojas2', '', 'valentin2@gmail.com', '$2y$10$qp07.FdiWhaQIGi3lw2dQuASiI9JAJnwKpkEJ.0/3vpHLUPi7Kg1C', '2025-05-13', NULL, '411.216.650-80', 'uploads/user_5/docs/foto_cnh_frente_68251f40120b9_1747263296.jpg', 'uploads/user_5/docs/foto_cnh_verso_68251f4012416_1747263296.jpg', 'aprovado', '2025-05-14 19:56:17', 4, 1, 1, 'Documentos validados!');

-- Copiando estrutura para tabela drivenow.dono
CREATE TABLE IF NOT EXISTS `dono` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `conta_usuario_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `conta_usuario_id` (`conta_usuario_id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Copiando dados para a tabela drivenow.dono: ~1 rows (aproximadamente)
INSERT INTO `dono` (`id`, `conta_usuario_id`) VALUES
	(1, 4);

-- Copiando estrutura para tabela drivenow.favoritos
CREATE TABLE IF NOT EXISTS `favoritos` (
  `veiculo_id` int(11) NOT NULL,
  `conta_usuario_id` int(11) NOT NULL,
  PRIMARY KEY (`veiculo_id`,`conta_usuario_id`),
  KEY `conta_usuario_id` (`conta_usuario_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Copiando dados para a tabela drivenow.favoritos: ~0 rows (aproximadamente)

-- Copiando estrutura para tabela drivenow.imagem
CREATE TABLE IF NOT EXISTS `imagem` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `veiculo_id` int(11) DEFAULT NULL,
  `imagem_url` varchar(255) DEFAULT NULL,
  `imagem_ordem` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `veiculo_id` (`veiculo_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Copiando dados para a tabela drivenow.imagem: ~0 rows (aproximadamente)

-- Copiando estrutura para tabela drivenow.local
CREATE TABLE IF NOT EXISTS `local` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `cidade_id` int(11) NOT NULL,
  `nome_local` varchar(100) NOT NULL,
  `endereco` varchar(255) DEFAULT NULL,
  `complemento` varchar(100) DEFAULT NULL,
  `cep` varchar(10) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `cidade_id` (`cidade_id`),
  CONSTRAINT `local_ibfk_1` FOREIGN KEY (`cidade_id`) REFERENCES `cidade` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=61 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Copiando dados para a tabela drivenow.local: ~60 rows (aproximadamente)
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

-- Copiando estrutura para tabela drivenow.log_verificacao_docs
CREATE TABLE IF NOT EXISTS `log_verificacao_docs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `conta_usuario_id` int(11) NOT NULL,
  `admin_id` int(11) NOT NULL,
  `status_anterior` enum('pendente','verificando','aprovado','rejeitado') DEFAULT NULL,
  `novo_status` enum('pendente','verificando','aprovado','rejeitado') DEFAULT NULL,
  `data_alteracao` datetime DEFAULT current_timestamp(),
  `observacoes` text DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `conta_usuario_id` (`conta_usuario_id`),
  KEY `admin_id` (`admin_id`),
  CONSTRAINT `log_verificacao_docs_ibfk_1` FOREIGN KEY (`conta_usuario_id`) REFERENCES `conta_usuario` (`id`) ON DELETE CASCADE,
  CONSTRAINT `log_verificacao_docs_ibfk_2` FOREIGN KEY (`admin_id`) REFERENCES `administrador` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Copiando dados para a tabela drivenow.log_verificacao_docs: ~0 rows (aproximadamente)

-- Copiando estrutura para tabela drivenow.reserva
CREATE TABLE IF NOT EXISTS `reserva` (
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
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Copiando dados para a tabela drivenow.reserva: ~1 rows (aproximadamente)
INSERT INTO `reserva` (`id`, `veiculo_id`, `conta_usuario_id`, `reserva_data`, `devolucao_data`, `diaria_valor`, `taxas_de_uso`, `taxas_de_limpeza`, `valor_total`, `status`, `observacoes`) VALUES
	(7, 6, 5, '2025-05-15', '2025-05-22', 350.00, 20.00, 30.00, 2500.00, 'rejeitada', 'Teste obs'),
	(8, 6, 5, '2025-05-16', '2025-05-19', 350.00, 20.00, 30.00, 1100.00, 'pendente', 'E para fazer uma viagem ate a praia.');

-- Copiando estrutura para tabela drivenow.review_usuario
CREATE TABLE IF NOT EXISTS `review_usuario` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `veiculo_id` int(11) DEFAULT NULL,
  `conta_usuario_id` int(11) DEFAULT NULL,
  `media_avaliacao` float DEFAULT NULL CHECK (`media_avaliacao` between 0 and 5),
  `comentario` text DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `veiculo_id` (`veiculo_id`),
  KEY `conta_usuario_id` (`conta_usuario_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Copiando dados para a tabela drivenow.review_usuario: ~0 rows (aproximadamente)

-- Copiando estrutura para tabela drivenow.veiculo
CREATE TABLE IF NOT EXISTS `veiculo` (
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
  PRIMARY KEY (`id`),
  KEY `local_id` (`local_id`),
  KEY `categoria_veiculo_id` (`categoria_veiculo_id`),
  KEY `dono_id` (`dono_id`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
-- Copiando estrutura para tabela drivenow.atributos_veiculos
CREATE TABLE IF NOT EXISTS `atributos_veiculos` (
  `veiculo_id` int(11) NOT NULL,
  `atributo_id` int(11) NOT NULL,
  PRIMARY KEY (`veiculo_id`,`atributo_id`),
  KEY `atributo_id` (`atributo_id`),
  CONSTRAINT `atributos_veiculos_ibfk_1` FOREIGN KEY (`veiculo_id`) REFERENCES `veiculo` (`id`) ON DELETE CASCADE,
  CONSTRAINT `atributos_veiculos_ibfk_2` FOREIGN KEY (`atributo_id`) REFERENCES `atributo` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Copiando dados para a tabela drivenow.veiculo: ~2 rows (aproximadamente)
INSERT INTO `veiculo` (`id`, `local_id`, `categoria_veiculo_id`, `dono_id`, `veiculo_marca`, `veiculo_modelo`, `veiculo_ano`, `veiculo_km`, `veiculo_placa`, `veiculo_cambio`, `veiculo_combustivel`, `veiculo_portas`, `veiculo_acentos`, `veiculo_tracao`, `disponivel`, `preco_diaria`, `descricao`) VALUES
	(6, 13, 2, 1, 'Chevrolet', 'Onix Plus', 2022, 2000, 'ABD2G62', 'Automático', 'Gasolina', 4, 5, 'Dianteira', 1, 350.00, 'Otimo para viagem, veiculo todo revisado!'),
	(7, 44, 1, 1, 'Fiat', 'Uno', 1998, 158900, 'ABC1234', 'Manual', 'Gasolina', 2, 4, 'Dianteira', 0, 50.00, 'Veiculo quase morrendo mas ainda funciona e esta barato para alugar!');

-- Tabela para armazenar avaliações de veículos
CREATE TABLE IF NOT EXISTS `avaliacao_veiculo` (
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
  KEY `veiculo_id` (`veiculo_id`),
  CONSTRAINT `avaliacao_veiculo_ibfk_1` FOREIGN KEY (`reserva_id`) REFERENCES `reserva` (`id`) ON DELETE CASCADE,
  CONSTRAINT `avaliacao_veiculo_ibfk_2` FOREIGN KEY (`usuario_id`) REFERENCES `conta_usuario` (`id`) ON DELETE CASCADE,
  CONSTRAINT `avaliacao_veiculo_ibfk_3` FOREIGN KEY (`veiculo_id`) REFERENCES `veiculo` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Tabela para armazenar avaliações de proprietários (donos de veículos)
CREATE TABLE IF NOT EXISTS `avaliacao_proprietario` (
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
  KEY `proprietario_id` (`proprietario_id`),
  CONSTRAINT `avaliacao_proprietario_ibfk_1` FOREIGN KEY (`reserva_id`) REFERENCES `reserva` (`id`) ON DELETE CASCADE,
  CONSTRAINT `avaliacao_proprietario_ibfk_2` FOREIGN KEY (`usuario_id`) REFERENCES `conta_usuario` (`id`) ON DELETE CASCADE,
  CONSTRAINT `avaliacao_proprietario_ibfk_3` FOREIGN KEY (`proprietario_id`) REFERENCES `conta_usuario` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Tabela para armazenar avaliações de locatários
CREATE TABLE IF NOT EXISTS `avaliacao_locatario` (
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
  KEY `locatario_id` (`locatario_id`),
  CONSTRAINT `avaliacao_locatario_ibfk_1` FOREIGN KEY (`reserva_id`) REFERENCES `reserva` (`id`) ON DELETE CASCADE,
  CONSTRAINT `avaliacao_locatario_ibfk_2` FOREIGN KEY (`proprietario_id`) REFERENCES `conta_usuario` (`id`) ON DELETE CASCADE,
  CONSTRAINT `avaliacao_locatario_ibfk_3` FOREIGN KEY (`locatario_id`) REFERENCES `conta_usuario` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Adicionar colunas para média de avaliações na tabela veiculo
ALTER TABLE `veiculo` 
ADD COLUMN IF NOT EXISTS `media_avaliacao` DECIMAL(3,1) DEFAULT NULL,
ADD COLUMN IF NOT EXISTS `total_avaliacoes` INT DEFAULT 0;

-- Adicionar colunas para média de avaliações na tabela conta_usuario
ALTER TABLE `conta_usuario` 
ADD COLUMN IF NOT EXISTS `media_avaliacao_proprietario` DECIMAL(3,1) DEFAULT NULL,
ADD COLUMN IF NOT EXISTS `total_avaliacoes_proprietario` INT DEFAULT 0,
ADD COLUMN IF NOT EXISTS `media_avaliacao_locatario` DECIMAL(3,1) DEFAULT NULL,
ADD COLUMN IF NOT EXISTS `total_avaliacoes_locatario` INT DEFAULT 0;

-- Tabela para armazenar as mensagens
CREATE TABLE IF NOT EXISTS `mensagem` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `reserva_id` int(11) NOT NULL,
  `remetente_id` int(11) NOT NULL,
  `mensagem` text NOT NULL,
  `data_envio` datetime NOT NULL DEFAULT current_timestamp(),
  `lida` tinyint(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `reserva_id` (`reserva_id`),
  KEY `remetente_id` (`remetente_id`),
  CONSTRAINT `mensagem_ibfk_1` FOREIGN KEY (`reserva_id`) REFERENCES `reserva` (`id`) ON DELETE CASCADE,
  CONSTRAINT `mensagem_ibfk_2` FOREIGN KEY (`remetente_id`) REFERENCES `conta_usuario` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Adicionar um contador de mensagens não lidas no perfil do usuário
ALTER TABLE `conta_usuario` ADD `mensagens_nao_lidas` int(11) NOT NULL DEFAULT 0;

-- Tabela para armazenar pagamentos
CREATE TABLE IF NOT EXISTS `pagamento` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `reserva_id` int(11) NOT NULL,
  `valor` decimal(10,2) NOT NULL,
  `metodo_pagamento` varchar(50) NOT NULL, -- cartao, pix, boleto
  `status` varchar(20) NOT NULL DEFAULT 'pendente', -- pendente, aprovado, recusado, estornado
  `data_pagamento` datetime DEFAULT NULL,
  `data_criacao` datetime NOT NULL DEFAULT current_timestamp(),
  `comprovante_url` varchar(255) DEFAULT NULL,
  `codigo_transacao` varchar(100) DEFAULT NULL,
  `detalhes` text DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `reserva_id` (`reserva_id`),
  CONSTRAINT `pagamento_ibfk_1` FOREIGN KEY (`reserva_id`) REFERENCES `reserva` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Histórico de pagamentos para cada reserva
CREATE TABLE IF NOT EXISTS `historico_pagamento` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `pagamento_id` int(11) NOT NULL,
  `status_anterior` varchar(20) DEFAULT NULL,
  `novo_status` varchar(20) NOT NULL,
  `observacao` text DEFAULT NULL,
  `data_alteracao` datetime NOT NULL DEFAULT current_timestamp(),
  `usuario_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `pagamento_id` (`pagamento_id`),
  KEY `usuario_id` (`usuario_id`),
  CONSTRAINT `historico_pagamento_ibfk_1` FOREIGN KEY (`pagamento_id`) REFERENCES `pagamento` (`id`) ON DELETE CASCADE,
  CONSTRAINT `historico_pagamento_ibfk_2` FOREIGN KEY (`usuario_id`) REFERENCES `conta_usuario` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


/*!40103 SET TIME_ZONE=IFNULL(@OLD_TIME_ZONE, 'system') */;
/*!40101 SET SQL_MODE=IFNULL(@OLD_SQL_MODE, '') */;
/*!40014 SET FOREIGN_KEY_CHECKS=IFNULL(@OLD_FOREIGN_KEY_CHECKS, 1) */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40111 SET SQL_NOTES=IFNULL(@OLD_SQL_NOTES, 1) */;
