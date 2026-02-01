USE esg_balance;

DELIMITER $$

-- TRIGGER 1: Stato in revisione
CREATE TRIGGER trg_stato_in_revisione
AFTER INSERT ON revisione
FOR EACH ROW
BEGIN
    UPDATE bilancio_esercizio
    SET stato = 'in_revisione'
    WHERE id_bilancio = NEW.id_bilancio
    AND stato = 'bozza';
END$$

-- TRIGGER 2a: Aggiorna stato bilancio - INSERT
CREATE TRIGGER trg_aggiorna_stato_bilancio_insert
AFTER INSERT ON giudizio
FOR EACH ROW
BEGIN
    DECLARE v_totale_revisori INT;
    DECLARE v_giudizi_inseriti INT;
    DECLARE v_respingimenti INT;

    SELECT COUNT(*) INTO v_totale_revisori
    FROM revisione WHERE id_bilancio = NEW.id_bilancio;

    SELECT COUNT(*) INTO v_giudizi_inseriti
    FROM giudizio WHERE id_bilancio = NEW.id_bilancio;

    IF v_totale_revisori > 0 AND v_giudizi_inseriti = v_totale_revisori THEN
        SELECT COUNT(*) INTO v_respingimenti
        FROM giudizio
        WHERE id_bilancio = NEW.id_bilancio AND esito = 'respingimento';

        IF v_respingimenti > 0 THEN
            UPDATE bilancio_esercizio SET stato = 'respinto'
            WHERE id_bilancio = NEW.id_bilancio;
        ELSE
            UPDATE bilancio_esercizio SET stato = 'approvato'
            WHERE id_bilancio = NEW.id_bilancio;
        END IF;
    END IF;
END$$

-- TRIGGER 2b: Aggiorna stato bilancio - UPDATE (QUESTO MANCA!)
CREATE TRIGGER trg_aggiorna_stato_bilancio_update
AFTER UPDATE ON giudizio
FOR EACH ROW
BEGIN
    DECLARE v_totale_revisori INT;
    DECLARE v_giudizi_inseriti INT;
    DECLARE v_respingimenti INT;

    SELECT COUNT(*) INTO v_totale_revisori
    FROM revisione WHERE id_bilancio = NEW.id_bilancio;

    SELECT COUNT(*) INTO v_giudizi_inseriti
    FROM giudizio WHERE id_bilancio = NEW.id_bilancio;

    IF v_totale_revisori > 0 AND v_giudizi_inseriti = v_totale_revisori THEN
        SELECT COUNT(*) INTO v_respingimenti
        FROM giudizio
        WHERE id_bilancio = NEW.id_bilancio AND esito = 'respingimento';

        IF v_respingimenti > 0 THEN
            UPDATE bilancio_esercizio SET stato = 'respinto'
            WHERE id_bilancio = NEW.id_bilancio;
        ELSE
            UPDATE bilancio_esercizio SET stato = 'approvato'
            WHERE id_bilancio = NEW.id_bilancio;
        END IF;
    END IF;
END$$

-- TRIGGER 3a: Affidabilità revisore - INSERT (QUESTO MANCA!)
CREATE TRIGGER trg_aggiorna_affidabilita_revisore_insert
AFTER INSERT ON giudizio
FOR EACH ROW
BEGIN
    DECLARE v_totale_giudizi INT;
    DECLARE v_approvazioni_nette INT;
    DECLARE v_nuova_affidabilita DECIMAL(3,2);

    SELECT COUNT(*) INTO v_totale_giudizi
    FROM giudizio WHERE id_revisore = NEW.id_revisore;

    SELECT COUNT(*) INTO v_approvazioni_nette
    FROM giudizio
    WHERE id_revisore = NEW.id_revisore AND esito = 'approvazione';

    IF v_totale_giudizi > 0 THEN
        SET v_nuova_affidabilita = v_approvazioni_nette / v_totale_giudizi;
        UPDATE revisore_esg
        SET indice_affidabilita = v_nuova_affidabilita
        WHERE id_utente = NEW.id_revisore;
    END IF;
END$$

-- TRIGGER 3b: Affidabilità revisore - UPDATE
CREATE TRIGGER trg_aggiorna_affidabilita_revisore_update
AFTER UPDATE ON giudizio
FOR EACH ROW
BEGIN
    DECLARE v_totale_giudizi INT;
    DECLARE v_approvazioni_nette INT;
    DECLARE v_nuova_affidabilita DECIMAL(3,2);

    SELECT COUNT(*) INTO v_totale_giudizi
    FROM giudizio WHERE id_revisore = NEW.id_revisore;

    SELECT COUNT(*) INTO v_approvazioni_nette
    FROM giudizio
    WHERE id_revisore = NEW.id_revisore AND esito = 'approvazione';

    IF v_totale_giudizi > 0 THEN
        SET v_nuova_affidabilita = v_approvazioni_nette / v_totale_giudizi;
        UPDATE revisore_esg
        SET indice_affidabilita = v_nuova_affidabilita
        WHERE id_utente = NEW.id_revisore;
    END IF;
END$$

DELIMITER ;
