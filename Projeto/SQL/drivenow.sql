-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Tempo de geração: 23/04/2025 às 22:04
-- Versão do servidor: 10.4.32-MariaDB
-- Versão do PHP: 8.2.12

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

DELIMITER $$
--
-- Procedimentos
--
CREATE DEFINER=`root`@`localhost` PROCEDURE `AdicionarFavorito` (IN `p_veiculo_id` INT, IN `p_conta_usuario_id` INT)   BEGIN
    INSERT INTO favoritos (veiculo_id, conta_usuario_id) VALUES (p_veiculo_id, p_conta_usuario_id);
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `AdicionarReserva` (IN `p_veiculo_id` INT, IN `p_conta_usuario_id` INT, IN `p_reserva_data` DATE, IN `p_devolucao_data` DATE, IN `p_diaria_valor` DECIMAL(10,2), IN `p_taxas_de_uso` DECIMAL(10,2), IN `p_taxas_de_limpeza` DECIMAL(10,2))   BEGIN
    DECLARE v_valor_total DECIMAL(10,2);
    SET v_valor_total = (DATEDIFF(p_devolucao_data, p_reserva_data) * p_diaria_valor) + p_taxas_de_uso + p_taxas_de_limpeza;
    INSERT INTO reserva (veiculo_id, conta_usuario_id, reserva_data, devolucao_data, diaria_valor, taxas_de_uso, taxas_de_limpeza, valor_total)
    VALUES (p_veiculo_id, p_conta_usuario_id, p_reserva_data, p_devolucao_data, p_diaria_valor, p_taxas_de_uso, p_taxas_de_limpeza, v_valor_total);
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `AdicionarUsuario` (IN `p_primeiro_nome` VARCHAR(50), IN `p_segundo_nome` VARCHAR(50), IN `p_e_mail` VARCHAR(100), IN `p_senha` VARCHAR(255))   BEGIN
    INSERT INTO conta_usuario (primeiro_nome, segundo_nome, e_mail, senha, data_de_entrada)
    VALUES (p_primeiro_nome, p_segundo_nome, p_e_mail, p_senha, CURDATE());
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `AtualizarEmailUsuario` (IN `p_usuario_id` INT, IN `p_novo_email` VARCHAR(100))   BEGIN
    UPDATE conta_usuario SET e_mail = p_novo_email WHERE id = p_usuario_id;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `RemoverFavorito` (IN `p_veiculo_id` INT, IN `p_conta_usuario_id` INT)   BEGIN
    DELETE FROM favoritos WHERE veiculo_id = p_veiculo_id AND conta_usuario_id = p_conta_usuario_id;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `RemoverReserva` (IN `p_reserva_id` INT)   BEGIN
    DELETE FROM reserva WHERE id = p_reserva_id;
END$$

DELIMITER ;

-- --------------------------------------------------------

--
-- Estrutura para tabela `atributo`
--

CREATE TABLE `atributo` (
  `id` int(11) NOT NULL,
  `nome_atributo` varchar(100) DEFAULT NULL,
  `descricao` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `atributos_veiculos`
--

CREATE TABLE `atributos_veiculos` (
  `veiculo_id` int(11) NOT NULL,
  `atributo_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `categoria_veiculo`
--

CREATE TABLE `categoria_veiculo` (
  `id` int(11) NOT NULL,
  `categoria` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `categoria_veiculo`
--

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
-- Estrutura para tabela `cidade`
--

CREATE TABLE `cidade` (
  `id` int(11) NOT NULL,
  `cidade_nome` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `cidade`
--

INSERT INTO `cidade` (`id`, `cidade_nome`) VALUES
(1, 'Porto Alegre'),
(2, 'Florianópolis'),
(3, 'Curitiba'),
(4, 'São Paulo'),
(5, 'Rio de Janeiro'),
(6, 'Brasília'),
(7, 'Belo Horizonte'),
(8, 'Vitória'),
(9, 'Campo Grande'),
(10, 'Goiânia');

-- --------------------------------------------------------

--
-- Estrutura para tabela `conta_usuario`
--

CREATE TABLE `conta_usuario` (
  `id` int(11) NOT NULL,
  `primeiro_nome` varchar(50) DEFAULT NULL,
  `segundo_nome` varchar(50) DEFAULT NULL,
  `telefone` varchar(20) DEFAULT NULL,
  `e_mail` varchar(100) DEFAULT NULL,
  `senha` varchar(255) DEFAULT NULL,
  `data_de_entrada` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `conta_usuario`
--

INSERT INTO `conta_usuario` (`id`, `primeiro_nome`, `segundo_nome`, `telefone`, `e_mail`, `senha`, `data_de_entrada`) VALUES
(1, 'Andryus', 'Zolet', NULL, 'zolet@gmail.com', '$2y$10$/TTRskgWu5PwHZpIfp11p.M968XE7pojt54VqPizrjkt9dP34rszi', '2025-04-18'),
(2, 'Teste', 'Testado', NULL, 'teste@gmail.com', '$2y$10$Ov934knn5N/GAUeTRRjL7OS1Ej4N8rrehNOQHT1AORkrtXM0/Y20W', '2025-04-18'),
(3, 'Video', 'Teste', NULL, 'Video@gmail.com', '$2y$10$OB2cGJysp8p.M59In4HykurAtGPiii9d7LG3Y4XQrFW0F1BHYY3EC', '2025-04-23');

-- --------------------------------------------------------

--
-- Estrutura para tabela `dono`
--

CREATE TABLE `dono` (
  `id` int(11) NOT NULL,
  `conta_usuario_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `dono`
--

INSERT INTO `dono` (`id`, `conta_usuario_id`) VALUES
(1, 1),
(2, 3);

-- --------------------------------------------------------

--
-- Estrutura para tabela `estado`
--

CREATE TABLE `estado` (
  `id` int(11) NOT NULL,
  `cidade_id` int(11) DEFAULT NULL,
  `estado_nome` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `estado`
--

INSERT INTO `estado` (`id`, `cidade_id`, `estado_nome`) VALUES
(1, NULL, 'Rio Grande do Sul'),
(2, NULL, 'Santa Catarina'),
(3, NULL, 'Paraná'),
(4, NULL, 'São Paulo'),
(5, NULL, 'Rio de Janeiro'),
(6, NULL, 'Distrito Federal'),
(7, NULL, 'Minas Gerais'),
(8, NULL, 'Espírito Santo'),
(9, NULL, 'Mato Grosso do Sul'),
(10, NULL, 'Goiás');

-- --------------------------------------------------------

--
-- Estrutura para tabela `favoritos`
--

CREATE TABLE `favoritos` (
  `veiculo_id` int(11) NOT NULL,
  `conta_usuario_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `imagem`
--

CREATE TABLE `imagem` (
  `id` int(11) NOT NULL,
  `veiculo_id` int(11) DEFAULT NULL,
  `imagem_url` varchar(255) DEFAULT NULL,
  `imagem_ordem` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `local`
--

CREATE TABLE `local` (
  `id` int(11) NOT NULL,
  `estado_id` int(11) DEFAULT NULL,
  `nome_local` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `reserva`
--

CREATE TABLE `reserva` (
  `id` int(11) NOT NULL,
  `veiculo_id` int(11) DEFAULT NULL,
  `conta_usuario_id` int(11) DEFAULT NULL,
  `reserva_data` date DEFAULT NULL,
  `devolucao_data` date DEFAULT NULL,
  `diaria_valor` decimal(10,2) DEFAULT NULL,
  `taxas_de_uso` decimal(10,2) DEFAULT NULL,
  `taxas_de_limpeza` decimal(10,2) DEFAULT NULL,
  `valor_total` decimal(10,2) DEFAULT NULL,
  `status` varchar(20) DEFAULT 'pendente'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `reserva`
--

INSERT INTO `reserva` (`id`, `veiculo_id`, `conta_usuario_id`, `reserva_data`, `devolucao_data`, `diaria_valor`, `taxas_de_uso`, `taxas_de_limpeza`, `valor_total`, `status`) VALUES
(5, 3, 3, '2025-04-24', '2025-04-25', 300.00, 20.00, 30.00, 350.00, 'confirmada');

-- --------------------------------------------------------

--
-- Estrutura para tabela `review_usuario`
--

CREATE TABLE `review_usuario` (
  `id` int(11) NOT NULL,
  `veiculo_id` int(11) DEFAULT NULL,
  `conta_usuario_id` int(11) DEFAULT NULL,
  `media_avaliacao` float DEFAULT NULL CHECK (`media_avaliacao` between 0 and 5),
  `comentario` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `veiculo`
--

CREATE TABLE `veiculo` (
  `id` int(11) NOT NULL,
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
  `descricao` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `veiculo`
--

INSERT INTO `veiculo` (`id`, `local_id`, `categoria_veiculo_id`, `dono_id`, `veiculo_marca`, `veiculo_modelo`, `veiculo_ano`, `veiculo_km`, `veiculo_placa`, `veiculo_cambio`, `veiculo_combustivel`, `veiculo_portas`, `veiculo_acentos`, `veiculo_tracao`, `disponivel`, `preco_diaria`, `descricao`) VALUES
(2, NULL, 2, 1, 'Honda', 'Civic Type R', 2025, 257, 'ABC-1234', 'Automático', 'Gasolina', 4, 5, 'Dianteira', 1, 460.00, NULL),
(3, NULL, 4, 1, 'Ford', 'Focus', 2020, 40940, 'CAP-4321', 'Manual', 'Gasolina', 4, 5, 'AWD', 1, 300.00, 'teste'),
(4, NULL, 2, 1, 'Volkswagen', 'Jetta', 2025, 5400, 'FGR-9256', 'Manual', 'Gasolina', 4, 5, 'Dianteira', 1, 5050.00, 'teste'),
(5, NULL, 2, 2, 'Nissan', 'Versa', 2018, 14000, 'RTM-1111', 'CVT', 'Gasolina', 4, 5, 'Dianteira', 0, 600.00, 'Teste');

--
-- Índices para tabelas despejadas
--

--
-- Índices de tabela `atributo`
--
ALTER TABLE `atributo`
  ADD PRIMARY KEY (`id`);

--
-- Índices de tabela `atributos_veiculos`
--
ALTER TABLE `atributos_veiculos`
  ADD PRIMARY KEY (`veiculo_id`,`atributo_id`),
  ADD KEY `atributo_id` (`atributo_id`);

--
-- Índices de tabela `categoria_veiculo`
--
ALTER TABLE `categoria_veiculo`
  ADD PRIMARY KEY (`id`);

--
-- Índices de tabela `cidade`
--
ALTER TABLE `cidade`
  ADD PRIMARY KEY (`id`);

--
-- Índices de tabela `conta_usuario`
--
ALTER TABLE `conta_usuario`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `e_mail` (`e_mail`);

--
-- Índices de tabela `dono`
--
ALTER TABLE `dono`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `conta_usuario_id` (`conta_usuario_id`);

--
-- Índices de tabela `estado`
--
ALTER TABLE `estado`
  ADD PRIMARY KEY (`id`),
  ADD KEY `cidade_id` (`cidade_id`);

--
-- Índices de tabela `favoritos`
--
ALTER TABLE `favoritos`
  ADD PRIMARY KEY (`veiculo_id`,`conta_usuario_id`),
  ADD KEY `conta_usuario_id` (`conta_usuario_id`);

--
-- Índices de tabela `imagem`
--
ALTER TABLE `imagem`
  ADD PRIMARY KEY (`id`),
  ADD KEY `veiculo_id` (`veiculo_id`);

--
-- Índices de tabela `local`
--
ALTER TABLE `local`
  ADD PRIMARY KEY (`id`),
  ADD KEY `estado_id` (`estado_id`);

--
-- Índices de tabela `reserva`
--
ALTER TABLE `reserva`
  ADD PRIMARY KEY (`id`),
  ADD KEY `veiculo_id` (`veiculo_id`),
  ADD KEY `conta_usuario_id` (`conta_usuario_id`);

--
-- Índices de tabela `review_usuario`
--
ALTER TABLE `review_usuario`
  ADD PRIMARY KEY (`id`),
  ADD KEY `veiculo_id` (`veiculo_id`),
  ADD KEY `conta_usuario_id` (`conta_usuario_id`);

--
-- Índices de tabela `veiculo`
--
ALTER TABLE `veiculo`
  ADD PRIMARY KEY (`id`),
  ADD KEY `local_id` (`local_id`),
  ADD KEY `categoria_veiculo_id` (`categoria_veiculo_id`),
  ADD KEY `dono_id` (`dono_id`);

--
-- AUTO_INCREMENT para tabelas despejadas
--

--
-- AUTO_INCREMENT de tabela `atributo`
--
ALTER TABLE `atributo`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `categoria_veiculo`
--
ALTER TABLE `categoria_veiculo`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT de tabela `cidade`
--
ALTER TABLE `cidade`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT de tabela `conta_usuario`
--
ALTER TABLE `conta_usuario`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de tabela `dono`
--
ALTER TABLE `dono`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de tabela `estado`
--
ALTER TABLE `estado`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT de tabela `imagem`
--
ALTER TABLE `imagem`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `local`
--
ALTER TABLE `local`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `reserva`
--
ALTER TABLE `reserva`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT de tabela `review_usuario`
--
ALTER TABLE `review_usuario`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `veiculo`
--
ALTER TABLE `veiculo`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- Restrições para tabelas despejadas
--

--
-- Restrições para tabelas `atributos_veiculos`
--
ALTER TABLE `atributos_veiculos`
  ADD CONSTRAINT `atributos_veiculos_ibfk_1` FOREIGN KEY (`veiculo_id`) REFERENCES `veiculo` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `atributos_veiculos_ibfk_2` FOREIGN KEY (`atributo_id`) REFERENCES `atributo` (`id`) ON DELETE CASCADE;

--
-- Restrições para tabelas `dono`
--
ALTER TABLE `dono`
  ADD CONSTRAINT `dono_ibfk_1` FOREIGN KEY (`conta_usuario_id`) REFERENCES `conta_usuario` (`id`) ON DELETE CASCADE;

--
-- Restrições para tabelas `estado`
--
ALTER TABLE `estado`
  ADD CONSTRAINT `estado_ibfk_1` FOREIGN KEY (`cidade_id`) REFERENCES `cidade` (`id`) ON DELETE CASCADE;

--
-- Restrições para tabelas `favoritos`
--
ALTER TABLE `favoritos`
  ADD CONSTRAINT `favoritos_ibfk_1` FOREIGN KEY (`veiculo_id`) REFERENCES `veiculo` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `favoritos_ibfk_2` FOREIGN KEY (`conta_usuario_id`) REFERENCES `conta_usuario` (`id`) ON DELETE CASCADE;

--
-- Restrições para tabelas `imagem`
--
ALTER TABLE `imagem`
  ADD CONSTRAINT `imagem_ibfk_1` FOREIGN KEY (`veiculo_id`) REFERENCES `veiculo` (`id`) ON DELETE CASCADE;

--
-- Restrições para tabelas `local`
--
ALTER TABLE `local`
  ADD CONSTRAINT `local_ibfk_1` FOREIGN KEY (`estado_id`) REFERENCES `estado` (`id`) ON DELETE CASCADE;

--
-- Restrições para tabelas `reserva`
--
ALTER TABLE `reserva`
  ADD CONSTRAINT `reserva_ibfk_1` FOREIGN KEY (`veiculo_id`) REFERENCES `veiculo` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `reserva_ibfk_2` FOREIGN KEY (`conta_usuario_id`) REFERENCES `conta_usuario` (`id`) ON DELETE CASCADE;

--
-- Restrições para tabelas `review_usuario`
--
ALTER TABLE `review_usuario`
  ADD CONSTRAINT `review_usuario_ibfk_1` FOREIGN KEY (`veiculo_id`) REFERENCES `veiculo` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `review_usuario_ibfk_2` FOREIGN KEY (`conta_usuario_id`) REFERENCES `conta_usuario` (`id`) ON DELETE CASCADE;

--
-- Restrições para tabelas `veiculo`
--
ALTER TABLE `veiculo`
  ADD CONSTRAINT `veiculo_ibfk_1` FOREIGN KEY (`local_id`) REFERENCES `local` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `veiculo_ibfk_2` FOREIGN KEY (`categoria_veiculo_id`) REFERENCES `categoria_veiculo` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `veiculo_ibfk_3` FOREIGN KEY (`dono_id`) REFERENCES `dono` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
