USE esg_balance;

-- VISTA 1: Numero aziende registrate
CREATE OR REPLACE VIEW v_numero_aziende AS
SELECT COUNT(*) AS totale_aziende
FROM azienda;

-- VISTA 2: Numero revisori ESG registrati
CREATE OR REPLACE VIEW v_numero_revisori AS
SELECT COUNT(*) AS totale_revisori
FROM revisore_esg;

-- VISTA 3: Affidabilità aziende (% bilanci approvati senza rilievi)
CREATE OR REPLACE VIEW v_affidabilita_aziende AS
SELECT
    a.id_azienda,
    a.nome,
    a.ragione_sociale,
    COUNT(DISTINCT b.id_bilancio) AS totale_bilanci,
    COUNT(DISTINCT CASE
        WHEN b.stato = 'approvato'
        AND NOT EXISTS (
            SELECT 1 FROM giudizio g
            WHERE g.id_bilancio = b.id_bilancio
            AND g.esito != 'approvazione'
        )
        THEN b.id_bilancio
    END) AS bilanci_approvati_puliti,
    CASE
        WHEN COUNT(DISTINCT b.id_bilancio) > 0
        THEN ROUND(
            (COUNT(DISTINCT CASE
                WHEN b.stato = 'approvato'
                AND NOT EXISTS (
                    SELECT 1 FROM giudizio g
                    WHERE g.id_bilancio = b.id_bilancio
                    AND g.esito != 'approvazione'
                )
                THEN b.id_bilancio
            END) * 100.0) / COUNT(DISTINCT b.id_bilancio),
            2
        )
        ELSE 0
    END AS percentuale_affidabilita
FROM azienda a
LEFT JOIN bilancio_esercizio b ON a.id_azienda = b.id_azienda
GROUP BY a.id_azienda, a.nome, a.ragione_sociale
ORDER BY percentuale_affidabilita DESC;

-- VISTA 4: Azienda con affidabilità massima
CREATE OR REPLACE VIEW v_azienda_piu_affidabile AS
SELECT
    id_azienda,
    nome,
    ragione_sociale,
    totale_bilanci,
    percentuale_affidabilita
FROM v_affidabilita_aziende
ORDER BY percentuale_affidabilita DESC, totale_bilanci DESC
LIMIT 1;

-- VISTA 5: Classifica bilanci per numero indicatori ESG
CREATE OR REPLACE VIEW v_classifica_bilanci_esg AS
SELECT
    b.id_bilancio,
    a.nome AS nome_azienda,
    a.ragione_sociale,
    b.data_creazione,
    b.stato,
    COUNT(DISTINCT vi.id_indicatore) AS nr_indicatori_esg
FROM bilancio_esercizio b
INNER JOIN azienda a ON b.id_azienda = a.id_azienda
LEFT JOIN valore_voce_bilancio vvb ON b.id_bilancio = vvb.id_bilancio
LEFT JOIN voce_indicatore vi ON vvb.id_bilancio = vi.id_bilancio AND vvb.id_voce = vi.id_voce
GROUP BY b.id_bilancio, a.nome, a.ragione_sociale, b.data_creazione, b.stato
ORDER BY nr_indicatori_esg DESC;

-- VISTA 6: Dashboard generale per amministratori
CREATE OR REPLACE VIEW v_dashboard_admin AS
SELECT
    (SELECT COUNT(*) FROM azienda) AS totale_aziende,
    (SELECT COUNT(*) FROM revisore_esg) AS totale_revisori,
    (SELECT COUNT(*) FROM bilancio_esercizio) AS totale_bilanci,
    (SELECT COUNT(*) FROM bilancio_esercizio WHERE stato = 'bozza') AS bilanci_bozza,
    (SELECT COUNT(*) FROM bilancio_esercizio WHERE stato = 'in_revisione') AS bilanci_in_revisione,
    (SELECT COUNT(*) FROM bilancio_esercizio WHERE stato = 'approvato') AS bilanci_approvati,
    (SELECT COUNT(*) FROM bilancio_esercizio WHERE stato = 'respinto') AS bilanci_respinti;

-- VISTA 7: Lista revisori con statistiche
CREATE OR REPLACE VIEW v_statistiche_revisori AS
SELECT
    u.id_utente,
    u.username,
    r.nr_revisioni,
    r.indice_affidabilita,
    COUNT(DISTINCT rc.id_competenza) AS nr_competenze,
    GROUP_CONCAT(CONCAT(c.nome, ' (', rc.livello, ')') SEPARATOR ', ') AS competenze
FROM utente u
INNER JOIN revisore_esg r ON u.id_utente = r.id_utente
LEFT JOIN revisore_competenza rc ON u.id_utente = rc.id_utente
LEFT JOIN competenza c ON rc.id_competenza = c.id_competenza
GROUP BY u.id_utente, u.username, r.nr_revisioni, r.indice_affidabilita;

-- VISTA 8: Dettaglio bilanci con progressione revisione
CREATE OR REPLACE VIEW v_dettaglio_bilanci AS
SELECT
    b.id_bilancio,
    a.nome AS azienda,
    a.ragione_sociale,
    b.data_creazione,
    b.stato,
    COUNT(DISTINCT r.id_revisore) AS nr_revisori_assegnati,
    COUNT(DISTINCT g.id_giudizio) AS nr_giudizi_inseriti,
    COUNT(DISTINCT n.id_nota) AS nr_note_totali
FROM bilancio_esercizio b
INNER JOIN azienda a ON b.id_azienda = a.id_azienda
LEFT JOIN revisione r ON b.id_bilancio = r.id_bilancio
LEFT JOIN giudizio g ON b.id_bilancio = g.id_bilancio
LEFT JOIN nota_revisore n ON b.id_bilancio = n.id_bilancio
GROUP BY b.id_bilancio, a.nome, a.ragione_sociale, b.data_creazione, b.stato;