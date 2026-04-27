-- Personal Finance Tracker - Database Schema
-- Database: finance_tracker

CREATE DATABASE IF NOT EXISTS finance_tracker
    DEFAULT CHARACTER SET utf8mb4
    DEFAULT COLLATE utf8mb4_unicode_ci;

USE finance_tracker;

-- Tabella utenti
CREATE TABLE IF NOT EXISTS utenti (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    email VARCHAR(100) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabella conti
CREATE TABLE IF NOT EXISTS conti (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    nome_conto VARCHAR(100) NOT NULL,
    saldo_iniziale DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    colore VARCHAR(7) NOT NULL DEFAULT '#007bff',
    FOREIGN KEY (user_id) REFERENCES utenti(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabella categorie
CREATE TABLE IF NOT EXISTS categorie (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(100) NOT NULL,
    tipo ENUM('entrata', 'uscita') NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabella transazioni
CREATE TABLE IF NOT EXISTS transazioni (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    conto_id INT UNSIGNED NOT NULL,
    categoria_id INT UNSIGNED NOT NULL,
    importo DECIMAL(10,2) NOT NULL,
    data DATE NOT NULL,
    descrizione VARCHAR(255),
    FOREIGN KEY (user_id) REFERENCES utenti(id) ON DELETE CASCADE,
    FOREIGN KEY (conto_id) REFERENCES conti(id) ON DELETE CASCADE,
    FOREIGN KEY (categoria_id) REFERENCES categorie(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dati di esempio: categorie predefinite
INSERT INTO categorie (nome, tipo) VALUES
    ('Stipendio', 'entrata'),
    ('Freelance', 'entrata'),
    ('Investimenti', 'entrata'),
    ('Altri proventi', 'entrata'),
    ('Alimentari', 'uscita'),
    ('Affitto', 'uscita'),
    ('Trasporti', 'uscita'),
    ('Utenze', 'uscita'),
    ('Salute', 'uscita'),
    ('Svago', 'uscita'),
    ('Abbigliamento', 'uscita'),
    ('Tecnologia', 'uscita'),
    ('Istruzione', 'uscita'),
    ('Altro', 'uscita');

-- Utente demo (password: demo1234 — hash bcrypt)
INSERT INTO utenti (username, email, password_hash) VALUES
    ('demo', 'demo@financetracker.local', '$2y$12$lBnFDiRBfOlOQVxLh/p9Iex5R.Xt8JmmSwMJn3Qoib.CkPLwcr3Yi');

-- Conto demo
INSERT INTO conti (user_id, nome_conto, saldo_iniziale, colore) VALUES
    (1, 'Conto Corrente', 1500.00, '#28a745'),
    (1, 'Risparmio', 5000.00, '#007bff');

-- Transazioni demo
INSERT INTO transazioni (user_id, conto_id, categoria_id, importo, data, descrizione) VALUES
    (1, 1, 1, 2000.00, CURDATE() - INTERVAL 10 DAY, 'Stipendio mensile'),
    (1, 1, 5, 150.00, CURDATE() - INTERVAL 8 DAY, 'Spesa settimanale'),
    (1, 1, 6, 600.00, CURDATE() - INTERVAL 5 DAY, 'Affitto'),
    (1, 1, 7, 50.00, CURDATE() - INTERVAL 3 DAY, 'Abbonamento trasporti'),
    (1, 2, 3, 300.00, CURDATE() - INTERVAL 2 DAY, 'Dividendi');
