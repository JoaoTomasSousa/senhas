CREATE DATABASE IF NOT EXISTS db_senhas CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE db_senhas;

DROP TABLE IF EXISTS senhas;
DROP TABLE IF EXISTS clientes;
DROP TABLE IF EXISTS balcoes;
DROP TABLE IF EXISTS servicos;

CREATE TABLE servicos (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nome VARCHAR(50) NOT NULL,
  prefixo VARCHAR(3) NOT NULL
) ENGINE=InnoDB;

CREATE TABLE balcoes (
  id INT AUTO_INCREMENT PRIMARY KEY,
  numero INT NOT NULL,
  servico_id INT NOT NULL,
  FOREIGN KEY (servico_id) REFERENCES servicos(id)
) ENGINE=InnoDB;

CREATE TABLE clientes (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nome VARCHAR(80) NOT NULL,
  data_registo DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE senhas (
  id INT AUTO_INCREMENT PRIMARY KEY,
  codigo VARCHAR(10) NOT NULL,
  servico_id INT NOT NULL,
  cliente_id INT NOT NULL,
  estado ENUM('espera','chamado','concluido','cancelado') NOT NULL DEFAULT 'espera',
  balcao_id INT NULL,
  data_criacao DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  ultima_chamada DATETIME(3) NULL,
  FOREIGN KEY (servico_id) REFERENCES servicos(id),
  FOREIGN KEY (cliente_id) REFERENCES clientes(id),
  FOREIGN KEY (balcao_id) REFERENCES balcoes(id),
  INDEX idx_estado (estado, servico_id),
  INDEX idx_chamada (ultima_chamada)
) ENGINE=InnoDB;

INSERT INTO servicos (nome, prefixo) VALUES
  ('Tesouraria','T'),
  ('Apoio ao Cliente','A'),
  ('Apoio Online','O'),
  ('RH','R');

INSERT INTO balcoes (numero, servico_id) VALUES
  (1,1),
  (2,2),
  (3,2),
  (4,3),
  (5,4);