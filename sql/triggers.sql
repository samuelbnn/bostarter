USE esg_balance;

DELIMITER $$

-- TRIGGER 1: Cambio stato bilancio a "in_revisione" quando viene assegnato un revisore
CREATE TRIGGER trg_stato_in_revisione
AFTER INSERT ON revisione
FOR EACH ROW
BEGIN
    UPDATE bilancio_esercizio
    SET stato = 'in_revisione'
    WHERE id_bilancio = NEW.id_bilancio
    AND stato = 'bozza';
END$$

-- TRIGGER 2: Cambio stato bilancio basato sui giudizi dei revisori
CREATE TRIGGER trg_aggiorna_stato_bilancio
AFTER INSERT ON giudizio
FOR EACH ROW
BEGIN
    DECLARE v_totale_revisori INT;
    DECLARE v_giudizi_inseriti INT;
    DECLARE v_respingimenti INT;

    -- Conta il numero totale di revisori assegnati al bilancio
    SELECT COUNT(*) INTO v_totale_revisori
    FROM revisione
    WHERE id_bilancio = NEW.id_bilancio;

    -- Conta quanti giudizi sono stati inseriti
    SELECT COUNT(*) INTO v_giudizi_inseriti
    FROM giudizio
    WHERE id_bilancio = NEW.id_bilancio;

    -- Se tutti i revisori hanno espresso il giudizio
    IF v_giudizi_inseriti = v_totale_revisori THEN
        -- Conta eventuali respingimenti
        SELECT COUNT(*) INTO v_respingimenti
        FROM giudizio
        WHERE id_bilancio = NEW.id_bilancio
        AND esito = 'respingimento';

        -- Se c'è almeno un respingimento, stato = respinto
        IF v_respingimenti > 0 THEN
            UPDATE bilancio_esercizio
            SET stato = 'respinto'
            WHERE id_bilancio = NEW.id_bilancio;
        ELSE
            -- Altrimenti stato = approvato
            UPDATE bilancio_esercizio
            SET stato = 'approvato'
            WHERE id_bilancio = NEW.id_bilancio;
        END IF;
    END IF;
END$$

-- TRIGGER 3: Aggiornamento indice affidabilità revisore
CREATE TRIGGER trg_aggiorna_affidabilita_revisore
AFTER UPDATE ON giudizio
FOR EACH ROW
BEGIN
    DECLARE v_totale_giudizi INT;
    DECLARE v_approvazioni_nette INT;
    DECLARE v_nuova_affidabilita DECIMAL(3,2);

    -- Conta totale giudizi del revisore
    SELECT COUNT(*) INTO v_totale_giudizi
    FROM giudizio
    WHERE id_revisore = NEW.id_revisore;

    -- Conta approvazioni senza rilievi
    SELECT COUNT(*) INTO v_approvazioni_nette
    FROM giudizio
    WHERE id_revisore = NEW.id_revisore
    AND esito = 'approvazione';

    -- Calcola nuovo indice
    IF v_totale_giudizi > 0 THEN
        SET v_nuova_affidabilita = v_approvazioni_nette / v_totale_giudizi;

        UPDATE revisore_esg
        SET indice_affidabilita = v_nuova_affidabilita
        WHERE id_utente = NEW.id_revisore;
    END IF;
END$$

DELIMITER ;