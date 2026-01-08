USE esg_balance;

DELIMITER $$

-- PROCEDURA: Registrazione utente generico
CREATE PROCEDURE sp_registra_utente(
    IN p_username VARCHAR(50),
    IN p_password VARCHAR(255),
    IN p_cf CHAR(16),
    IN p_data_nascita DATE,
    IN p_luogo_nascita VARCHAR(100),
    IN p_tipo_utente ENUM('amministratore', 'revisore_esg', 'responsabile_aziendale'),
    IN p_email VARCHAR(100),
    IN p_cv_pdf VARCHAR(255),
    OUT p_id_utente INT
)
BEGIN
    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        ROLLBACK;
        SET p_id_utente = -1;
    END;

    START TRANSACTION;

    -- Inserimento utente base
    INSERT INTO utente (username, password_hash, codice_fiscale, data_nascita, luogo_nascita, tipo_utente)
    VALUES (p_username, SHA2(p_password, 256), p_cf, p_data_nascita, p_luogo_nascita, p_tipo_utente);

    SET p_id_utente = LAST_INSERT_ID();

    -- Inserimento email
    INSERT INTO email_utente (id_utente, email, principale)
    VALUES (p_id_utente, p_email, TRUE);

    -- Inserimento nelle tabelle specializzate
    IF p_tipo_utente = 'revisore_esg' THEN
        INSERT INTO revisore_esg (id_utente) VALUES (p_id_utente);
    ELSEIF p_tipo_utente = 'responsabile_aziendale' THEN
        INSERT INTO responsabile_aziendale (id_utente, cv_pdf) VALUES (p_id_utente, p_cv_pdf);
    END IF;

    COMMIT;
END$$

-- PROCEDURA: Autenticazione utente
CREATE PROCEDURE sp_login_utente(
    IN p_username VARCHAR(50),
    IN p_password VARCHAR(255)
)
BEGIN
    SELECT
        u.id_utente,
        u.username,
        u.tipo_utente,
        u.codice_fiscale,
        eu.email
    FROM utente u
    LEFT JOIN email_utente eu ON u.id_utente = eu.id_utente AND eu.principale = TRUE
    WHERE u.username = p_username
    AND u.password_hash = SHA2(p_password, 256);
END$$

-- PROCEDURA: Registrazione azienda
CREATE PROCEDURE sp_registra_azienda(
    IN p_nome VARCHAR(200),
    IN p_ragione_sociale VARCHAR(200),
    IN p_piva CHAR(11),
    IN p_settore VARCHAR(100),
    IN p_nr_dipendenti INT,
    IN p_logo VARCHAR(255),
    IN p_id_responsabile INT,
    OUT p_id_azienda INT
)
BEGIN
    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        ROLLBACK;
        SET p_id_azienda = -1;
    END;

    START TRANSACTION;

    INSERT INTO azienda (nome, ragione_sociale, partita_iva, settore, nr_dipendenti, logo, id_responsabile)
    VALUES (p_nome, p_ragione_sociale, p_piva, p_settore, p_nr_dipendenti, p_logo, p_id_responsabile);

    SET p_id_azienda = LAST_INSERT_ID();

    COMMIT;
END$$

-- PROCEDURA: Creazione bilancio
CREATE PROCEDURE sp_crea_bilancio(
    IN p_id_azienda INT,
    IN p_data_creazione DATE,
    OUT p_id_bilancio INT
)
BEGIN
    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        ROLLBACK;
        SET p_id_bilancio = -1;
    END;

    START TRANSACTION;

    INSERT INTO bilancio_esercizio (id_azienda, data_creazione, stato)
    VALUES (p_id_azienda, p_data_creazione, 'bozza');

    SET p_id_bilancio = LAST_INSERT_ID();

    -- Incrementa contatore bilanci azienda (ridondanza)
    UPDATE azienda SET nr_bilanci = nr_bilanci + 1 WHERE id_azienda = p_id_azienda;

    COMMIT;
END$$

-- PROCEDURA: Inserimento valore voce di bilancio
CREATE PROCEDURE sp_inserisci_valore_voce(
    IN p_id_bilancio INT,
    IN p_id_voce INT,
    IN p_valore DECIMAL(15,2)
)
BEGIN
    INSERT INTO valore_voce_bilancio (id_bilancio, id_voce, valore)
    VALUES (p_id_bilancio, p_id_voce, p_valore)
    ON DUPLICATE KEY UPDATE valore = p_valore;
END$$

-- PROCEDURA: Collegamento indicatore ESG a voce
CREATE PROCEDURE sp_collega_indicatore_voce(
    IN p_id_bilancio INT,
    IN p_id_voce INT,
    IN p_id_indicatore INT,
    IN p_valore_indicatore DECIMAL(15,2),
    IN p_fonte VARCHAR(200),
    IN p_data_rilevazione DATE
)
BEGIN
    INSERT INTO voce_indicatore (id_bilancio, id_voce, id_indicatore, valore_indicatore, fonte, data_rilevazione)
    VALUES (p_id_bilancio, p_id_voce, p_id_indicatore, p_valore_indicatore, p_fonte, p_data_rilevazione)
    ON DUPLICATE KEY UPDATE
        valore_indicatore = p_valore_indicatore,
        fonte = p_fonte,
        data_rilevazione = p_data_rilevazione;
END$$

-- PROCEDURA: Assegnazione revisore a bilancio
CREATE PROCEDURE sp_assegna_revisore(
    IN p_id_revisore INT,
    IN p_id_bilancio INT
)
BEGIN
    INSERT IGNORE INTO revisione (id_revisore, id_bilancio)
    VALUES (p_id_revisore, p_id_bilancio);
END$$

-- PROCEDURA: Inserimento nota revisore
CREATE PROCEDURE sp_inserisci_nota_revisore(
    IN p_id_revisore INT,
    IN p_id_bilancio INT,
    IN p_id_voce INT,
    IN p_data_nota DATE,
    IN p_testo TEXT
)
BEGIN
    INSERT INTO nota_revisore (id_revisore, id_bilancio, id_voce, data_nota, testo)
    VALUES (p_id_revisore, p_id_bilancio, p_id_voce, p_data_nota, p_testo);
END$$

-- PROCEDURA: Inserimento giudizio complessivo
CREATE PROCEDURE sp_inserisci_giudizio(
    IN p_id_revisore INT,
    IN p_id_bilancio INT,
    IN p_esito ENUM('approvazione', 'approvazione_con_rilievi', 'respingimento'),
    IN p_data_giudizio DATE,
    IN p_rilievi TEXT
)
BEGIN
    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        ROLLBACK;
    END;

    START TRANSACTION;

    INSERT INTO giudizio (id_revisore, id_bilancio, esito, data_giudizio, rilievi)
    VALUES (p_id_revisore, p_id_bilancio, p_esito, p_data_giudizio, p_rilievi)
    ON DUPLICATE KEY UPDATE
        esito = p_esito,
        data_giudizio = p_data_giudizio,
        rilievi = p_rilievi;

    -- Incrementa contatore revisioni del revisore
    UPDATE revisore_esg SET nr_revisioni = nr_revisioni + 1 WHERE id_utente = p_id_revisore;

    COMMIT;
END$$

-- PROCEDURA: Inserimento competenza revisore
CREATE PROCEDURE sp_inserisci_competenza_revisore(
    IN p_id_revisore INT,
    IN p_nome_competenza VARCHAR(100),
    IN p_livello TINYINT
)
BEGIN
    DECLARE v_id_competenza INT;

    -- Verifica se la competenza esiste, altrimenti la crea
    SELECT id_competenza INTO v_id_competenza
    FROM competenza
    WHERE nome = p_nome_competenza;

    IF v_id_competenza IS NULL THEN
        INSERT INTO competenza (nome) VALUES (p_nome_competenza);
        SET v_id_competenza = LAST_INSERT_ID();
    END IF;

    -- Inserisce o aggiorna la competenza del revisore
    INSERT INTO revisore_competenza (id_utente, id_competenza, livello)
    VALUES (p_id_revisore, v_id_competenza, p_livello)
    ON DUPLICATE KEY UPDATE livello = p_livello;
END$$

-- PROCEDURA: Eliminazione azienda e bilanci
CREATE PROCEDURE sp_elimina_azienda(
    IN p_id_azienda INT
)
BEGIN
    DELETE FROM azienda WHERE id_azienda = p_id_azienda;
END$$

-- PROCEDURA: Popolamento template voci contabili (admin)
CREATE PROCEDURE sp_aggiungi_voce_contabile(
    IN p_nome VARCHAR(200),
    IN p_descrizione TEXT,
    OUT p_id_voce INT
)
BEGIN
    INSERT INTO voce_contabile (nome, descrizione)
    VALUES (p_nome, p_descrizione);
    SET p_id_voce = LAST_INSERT_ID();
END$$

-- PROCEDURA: Popolamento indicatori ESG (admin)
CREATE PROCEDURE sp_aggiungi_indicatore_esg(
    IN p_nome VARCHAR(200),
    IN p_immagine VARCHAR(255),
    IN p_rilevanza TINYINT,
    IN p_tipo_indicatore ENUM('ambientale', 'sociale', 'generico'),
    IN p_codice_normativa VARCHAR(50),
    IN p_ambito_sociale VARCHAR(100),
    IN p_frequenza_rilevazione VARCHAR(50),
    OUT p_id_indicatore INT
)
BEGIN
    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        ROLLBACK;
        SET p_id_indicatore = -1;
    END;

    START TRANSACTION;

    INSERT INTO indicatore_esg (nome, immagine, rilevanza, tipo_indicatore)
    VALUES (p_nome, p_immagine, p_rilevanza, p_tipo_indicatore);

    SET p_id_indicatore = LAST_INSERT_ID();

    IF p_tipo_indicatore = 'ambientale' THEN
        INSERT INTO indicatore_ambientale (id_indicatore, codice_normativa)
        VALUES (p_id_indicatore, p_codice_normativa);
    ELSEIF p_tipo_indicatore = 'sociale' THEN
        INSERT INTO indicatore_sociale (id_indicatore, ambito_sociale, frequenza_rilevazione)
        VALUES (p_id_indicatore, p_ambito_sociale, p_frequenza_rilevazione);
    END IF;

    COMMIT;
END$$

DELIMITER ;