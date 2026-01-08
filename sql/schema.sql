-- DATABASE ESG-BALANCE
-- Creazione database
DROP DATABASE IF EXISTS esg_balance;
CREATE DATABASE esg_balance CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE esg_balance;

-- TABELLA UTENTI (generalizzazione)
CREATE TABLE utente (
    id_utente INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    codice_fiscale CHAR(16) UNIQUE NOT NULL,
    data_nascita DATE NOT NULL,
    luogo_nascita VARCHAR(100) NOT NULL,
    tipo_utente ENUM('amministratore', 'revisore_esg', 'responsabile_aziendale') NOT NULL,
    data_registrazione TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_username (username),
    INDEX idx_tipo (tipo_utente)
) ENGINE=InnoDB;

-- TABELLA EMAIL (relazione 1:N con utente)
CREATE TABLE email_utente (
    id_email INT AUTO_INCREMENT PRIMARY KEY,
    id_utente INT NOT NULL,
    email VARCHAR(100) NOT NULL,
    principale BOOLEAN DEFAULT FALSE,
    FOREIGN KEY (id_utente) REFERENCES utente(id_utente) ON DELETE CASCADE,
    UNIQUE KEY unique_email (email),
    INDEX idx_utente (id_utente)
) ENGINE=InnoDB;

-- TABELLA REVISORE ESG (specializzazione)
CREATE TABLE revisore_esg (
    id_utente INT PRIMARY KEY,
    nr_revisioni INT DEFAULT 0,
    indice_affidabilita DECIMAL(3,2) DEFAULT 0.00,
    CHECK (indice_affidabilita BETWEEN 0 AND 1),
    CHECK (nr_revisioni >= 0),
    FOREIGN KEY (id_utente) REFERENCES utente(id_utente) ON DELETE CASCADE
) ENGINE=InnoDB;

-- TABELLA COMPETENZE
CREATE TABLE competenza (
    id_competenza INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(100) UNIQUE NOT NULL
) ENGINE=InnoDB;

-- TABELLA COMPETENZE REVISORE (relazione N:M con attributi)
CREATE TABLE revisore_competenza (
    id_utente INT,
    id_competenza INT,
    livello TINYINT NOT NULL CHECK (livello BETWEEN 0 AND 5),
    PRIMARY KEY (id_utente, id_competenza),
    FOREIGN KEY (id_utente) REFERENCES revisore_esg(id_utente) ON DELETE CASCADE,
    FOREIGN KEY (id_competenza) REFERENCES competenza(id_competenza) ON DELETE CASCADE
) ENGINE=InnoDB;

-- TABELLA RESPONSABILE AZIENDALE (specializzazione)
CREATE TABLE responsabile_aziendale (
    id_utente INT PRIMARY KEY,
    cv_pdf VARCHAR(255),
    FOREIGN KEY (id_utente) REFERENCES utente(id_utente) ON DELETE CASCADE
) ENGINE=InnoDB;

-- TABELLA AZIENDA
CREATE TABLE azienda (
    id_azienda INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(200) NOT NULL,
    ragione_sociale VARCHAR(200) UNIQUE NOT NULL,
    partita_iva CHAR(11) UNIQUE NOT NULL,
    settore VARCHAR(100),
    nr_dipendenti INT,
    logo VARCHAR(255),
    nr_bilanci INT DEFAULT 0, -- RIDONDANZA CONCETTUALE
    id_responsabile INT NOT NULL,
    data_registrazione TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CHECK (nr_dipendenti >= 0),
    CHECK (nr_bilanci >= 0),
    FOREIGN KEY (id_responsabile) REFERENCES responsabile_aziendale(id_utente),
    INDEX idx_ragione_sociale (ragione_sociale),
    INDEX idx_responsabile (id_responsabile)
) ENGINE=InnoDB;

-- TABELLA VOCE CONTABILE (template condiviso)
CREATE TABLE voce_contabile (
    id_voce INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(200) UNIQUE NOT NULL,
    descrizione TEXT,
    INDEX idx_nome (nome)
) ENGINE=InnoDB;

-- TABELLA BILANCIO ESERCIZIO
CREATE TABLE bilancio_esercizio (
    id_bilancio INT AUTO_INCREMENT PRIMARY KEY,
    id_azienda INT NOT NULL,
    data_creazione DATE NOT NULL,
    stato ENUM('bozza', 'in_revisione', 'approvato', 'respinto') DEFAULT 'bozza',
    FOREIGN KEY (id_azienda) REFERENCES azienda(id_azienda) ON DELETE CASCADE,
    INDEX idx_azienda (id_azienda),
    INDEX idx_stato (stato)
) ENGINE=InnoDB;

-- TABELLA VALORI VOCI DI BILANCIO
CREATE TABLE valore_voce_bilancio (
    id_bilancio INT,
    id_voce INT,
    valore DECIMAL(15,2) NOT NULL,
    PRIMARY KEY (id_bilancio, id_voce),
    FOREIGN KEY (id_bilancio) REFERENCES bilancio_esercizio(id_bilancio) ON DELETE CASCADE,
    FOREIGN KEY (id_voce) REFERENCES voce_contabile(id_voce) ON DELETE CASCADE
) ENGINE=InnoDB;

-- TABELLA INDICATORE ESG (generalizzazione)
CREATE TABLE indicatore_esg (
    id_indicatore INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(200) UNIQUE NOT NULL,
    immagine VARCHAR(255),
    rilevanza TINYINT CHECK (rilevanza BETWEEN 0 AND 10),
    tipo_indicatore ENUM('ambientale', 'sociale', 'generico') NOT NULL,
    INDEX idx_nome (nome),
    INDEX idx_tipo (tipo_indicatore)
) ENGINE=InnoDB;

-- TABELLA INDICATORE AMBIENTALE (specializzazione)
CREATE TABLE indicatore_ambientale (
    id_indicatore INT PRIMARY KEY,
    codice_normativa VARCHAR(50),
    FOREIGN KEY (id_indicatore) REFERENCES indicatore_esg(id_indicatore) ON DELETE CASCADE
) ENGINE=InnoDB;

-- TABELLA INDICATORE SOCIALE (specializzazione)
CREATE TABLE indicatore_sociale (
    id_indicatore INT PRIMARY KEY,
    ambito_sociale VARCHAR(100),
    frequenza_rilevazione VARCHAR(50),
    FOREIGN KEY (id_indicatore) REFERENCES indicatore_esg(id_indicatore) ON DELETE CASCADE
) ENGINE=InnoDB;

-- TABELLA COLLEGAMENTO VOCE-INDICATORE (relazione N:M con attributi)
CREATE TABLE voce_indicatore (
    id_bilancio INT,
    id_voce INT,
    id_indicatore INT,
    valore_indicatore DECIMAL(15,2) NOT NULL,
    fonte VARCHAR(200),
    data_rilevazione DATE,
    PRIMARY KEY (id_bilancio, id_voce, id_indicatore),
    FOREIGN KEY (id_bilancio, id_voce) REFERENCES valore_voce_bilancio(id_bilancio, id_voce) ON DELETE CASCADE,
    FOREIGN KEY (id_indicatore) REFERENCES indicatore_esg(id_indicatore) ON DELETE CASCADE
) ENGINE=InnoDB;

-- TABELLA REVISIONE (relazione N:M tra revisore e bilancio)
CREATE TABLE revisione (
    id_revisore INT,
    id_bilancio INT,
    data_inizio TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id_revisore, id_bilancio),
    FOREIGN KEY (id_revisore) REFERENCES revisore_esg(id_utente) ON DELETE CASCADE,
    FOREIGN KEY (id_bilancio) REFERENCES bilancio_esercizio(id_bilancio) ON DELETE CASCADE,
    INDEX idx_bilancio (id_bilancio)
) ENGINE=InnoDB;

-- TABELLA NOTE DEL REVISORE
CREATE TABLE nota_revisore (
    id_nota INT AUTO_INCREMENT PRIMARY KEY,
    id_revisore INT,
    id_bilancio INT,
    id_voce INT,
    data_nota DATE NOT NULL,
    testo TEXT NOT NULL,
    FOREIGN KEY (id_revisore, id_bilancio) REFERENCES revisione(id_revisore, id_bilancio) ON DELETE CASCADE,
    FOREIGN KEY (id_voce) REFERENCES voce_contabile(id_voce) ON DELETE CASCADE,
    INDEX idx_revisione (id_revisore, id_bilancio)
) ENGINE=InnoDB;

-- TABELLA GIUDIZIO COMPLESSIVO
CREATE TABLE giudizio (
    id_giudizio INT AUTO_INCREMENT PRIMARY KEY,
    id_revisore INT,
    id_bilancio INT,
    esito ENUM('approvazione', 'approvazione_con_rilievi', 'respingimento') NOT NULL,
    data_giudizio DATE NOT NULL,
    rilievi TEXT,
    UNIQUE KEY unique_giudizio (id_revisore, id_bilancio),
    FOREIGN KEY (id_revisore, id_bilancio) REFERENCES revisione(id_revisore, id_bilancio) ON DELETE CASCADE,
    INDEX idx_bilancio (id_bilancio)
) ENGINE=InnoDB;

-- DATI DI ESEMPIO
-- Inserimento voci contabili template
INSERT INTO voce_contabile (nome, descrizione) VALUES
('Ricavi vendite', 'Ricavi derivanti dalla vendita di beni e servizi'),
('Costo del personale', 'Spese per stipendi e contributi'),
('Ammortamenti', 'Ammortamenti e svalutazioni'),
('Debiti verso fornitori', 'Debiti commerciali verso fornitori'),
('Capitale sociale', 'Capitale conferito dai soci'),
('Utile/Perdita esercizio', 'Risultato economico dell\'esercizio'),
('Immobilizzazioni materiali', 'Beni strumentali durevoli'),
('Costi operativi', 'Altri costi operativi aziendali');

-- Inserimento competenze
INSERT INTO competenza (nome) VALUES
('Risk Assessment'),
('Sostenibilità ambientale'),
('Audit finanziario'),
('Compliance normativa'),
('Analisi dati ESG');

-- Inserimento indicatori ESG
INSERT INTO indicatore_esg (nome, immagine, rilevanza, tipo_indicatore) VALUES
('Consumo energia elettrica', 'energia.png', 9, 'ambientale'),
('Consumo acqua potabile', 'acqua.png', 8, 'ambientale'),
('Emissioni CO2', 'co2.png', 10, 'ambientale'),
('Ore formazione dipendenti', 'formazione.png', 7, 'sociale'),
('Indice diversità genere', 'diversita.png', 8, 'sociale'),
('Tasso infortuni', 'sicurezza.png', 9, 'sociale');

-- Inserimento dati specializzazioni indicatori
INSERT INTO indicatore_ambientale (id_indicatore, codice_normativa) VALUES
(1, 'ISO-50001'),
(2, 'ISO-14046'),
(3, 'GHG-Protocol');

INSERT INTO indicatore_sociale (id_indicatore, ambito_sociale, frequenza_rilevazione) VALUES
(4, 'Sviluppo del personale', 'Annuale'),
(5, 'Inclusione e diversità', 'Annuale'),
(6, 'Salute e sicurezza', 'Trimestrale');