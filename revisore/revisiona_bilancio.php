<?php
// revisore/revisiona_bilancio.php
session_start();
require_once '../config/database.php';
require_once '../config/mongodb.php';

if (!isset($_SESSION['id_utente']) || $_SESSION['tipo_utente'] !== 'revisore_esg') {
    header('Location: ../auth/login.php');
    exit();
}

$db = getDB();
$id_revisore = $_SESSION['id_utente'];
$id_bilancio = intval($_GET['id'] ?? 0);

// Verifica assegnazione
$stmt = $db->prepare("
    SELECT b.*, a.nome as nome_azienda, a.ragione_sociale,
           r.data_inizio
    FROM revisione r
    JOIN bilancio_esercizio b ON r.id_bilancio = b.id_bilancio
    JOIN azienda a ON b.id_azienda = a.id_azienda
    WHERE r.id_bilancio = ? AND r.id_revisore = ?
");
$stmt->execute([$id_bilancio, $id_revisore]);
$bilancio = $stmt->fetch();

if (!$bilancio) {
    header('Location: dashboard.php');
    exit();
}

// Recupera voci e indicatori
$stmt = $db->prepare("
    SELECT vvb.*, vc.nome, vc.descrizione,
           GROUP_CONCAT(CONCAT(ie.nome, ':', vi.valore_indicatore) SEPARATOR '|') as indicatori
    FROM valore_voce_bilancio vvb
    JOIN voce_contabile vc ON vvb.id_voce = vc.id_voce
    LEFT JOIN voce_indicatore vi ON vvb.id_bilancio = vi.id_bilancio AND vvb.id_voce = vi.id_voce
    LEFT JOIN indicatore_esg ie ON vi.id_indicatore = ie.id_indicatore
    WHERE vvb.id_bilancio = ?
    GROUP BY vvb.id_bilancio, vvb.id_voce
    ORDER BY vc.nome
");
$stmt->execute([$id_bilancio]);
$voci = $stmt->fetchAll();

// Recupera note esistenti
$stmt = $db->prepare("
    SELECT n.*, vc.nome as nome_voce
    FROM nota_revisore n
    JOIN voce_contabile vc ON n.id_voce = vc.id_voce
    WHERE n.id_revisore = ? AND n.id_bilancio = ?
    ORDER BY n.data_nota DESC
");
$stmt->execute([$id_revisore, $id_bilancio]);
$note = $stmt->fetchAll();

// Recupera giudizio se esiste
$stmt = $db->prepare("SELECT * FROM giudizio WHERE id_revisore = ? AND id_bilancio = ?");
$stmt->execute([$id_revisore, $id_bilancio]);
$giudizio = $stmt->fetch();

$successo = '';
$errore = '';

// Aggiungi nota
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['aggiungi_nota'])) {
    $id_voce = intval($_POST['id_voce']);
    $testo = trim($_POST['testo']);

    if (empty($testo)) {
        $errore = "Il testo della nota è obbligatorio";
    } else {
        try {
            $stmt = $db->prepare("CALL sp_inserisci_nota_revisore(:revisore, :bilancio, :voce, :data, :testo)");
            $stmt->execute([
                ':revisore' => $id_revisore,
                ':bilancio' => $id_bilancio,
                ':voce' => $id_voce,
                ':data' => date('Y-m-d'),
                ':testo' => $testo
            ]);
            $stmt->closeCursor();

            logEvent('nota_aggiunta', "Nota aggiunta al bilancio #{$id_bilancio}", $id_revisore);
            $successo = "Nota aggiunta con successo!";
            header("Refresh:1");
        } catch (PDOException $e) {
            $errore = "Errore: " . $e->getMessage();
        }
    }
}

// Inserisci giudizio
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['inserisci_giudizio'])) {
    $esito = $_POST['esito'] ?? '';
    $rilievi = trim($_POST['rilievi'] ?? '');

    if (empty($esito)) {
        $errore = "Seleziona un esito";
    } else {
        try {
            $stmt = $db->prepare("CALL sp_inserisci_giudizio(:revisore, :bilancio, :esito, :data, :rilievi)");
            $stmt->execute([
                ':revisore' => $id_revisore,
                ':bilancio' => $id_bilancio,
                ':esito' => $esito,
                ':data' => date('Y-m-d'),
                ':rilievi' => $rilievi
            ]);
            $stmt->closeCursor();

            logEvent('giudizio_emesso', "Giudizio emesso per bilancio #{$id_bilancio}: {$esito}",
                     $id_revisore, ['esito' => $esito]);

            $successo = "Giudizio registrato con successo!";
            header("Refresh:1");
        } catch (PDOException $e) {
            $errore = "Errore: " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Revisione Bilancio - ESG Balance</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <nav class="navbar">
        <div class="nav-brand">ESG-BALANCE</div>
        <div class="nav-menu">
            <a href="dashboard.php">Dashboard</a>
            <a href="dashboard.php">Bilanci Assegnati</a>
            <a href="../auth/logout.php">Logout</a>
        </div>
        <div class="nav-user"><?php echo htmlspecialchars($_SESSION['username']); ?></div>
    </nav>

    <div class="container">
        <div class="section">
            <h1>Revisione Bilancio #<?php echo $id_bilancio; ?></h1>

            <div class="bilancio-info">
                <div>
                    <strong>Azienda:</strong> <?php echo htmlspecialchars($bilancio['nome_azienda']); ?><br>
                    <strong>Data Bilancio:</strong> <?php echo date('d/m/Y', strtotime($bilancio['data_creazione'])); ?><br>
                    <strong>Assegnato il:</strong> <?php echo date('d/m/Y H:i', strtotime($bilancio['data_inizio'])); ?>
                </div>
                <span class="badge badge-<?php echo $bilancio['stato']; ?> badge-large">
                    <?php echo ucfirst(str_replace('_', ' ', $bilancio['stato'])); ?>
                </span>
            </div>

            <?php if ($successo): ?>
                <div class="alert alert-success"><?php echo htmlspecialchars($successo); ?></div>
            <?php endif; ?>

            <?php if ($errore): ?>
                <div class="alert alert-error"><?php echo htmlspecialchars($errore); ?></div>
            <?php endif; ?>
        </div>

        <div class="section">
            <h2>Voci di Bilancio</h2>

            <?php foreach ($voci as $voce): ?>
            <div class="voce-revisione">
                <div class="voce-header">
                    <h3><?php echo htmlspecialchars($voce['nome']); ?></h3>
                    <span class="voce-valore">€ <?php echo number_format($voce['valore'], 2, ',', '.'); ?></span>
                </div>
                <p class="voce-desc"><?php echo htmlspecialchars($voce['descrizione']); ?></p>

                <?php if ($voce['indicatori']): ?>
                <div class="indicatori-box">
                    <strong>Indicatori ESG:</strong>
                    <ul>
                        <?php foreach (explode('|', $voce['indicatori']) as $ind):
                            list($nome, $valore) = explode(':', $ind);
                        ?>
                        <li><?php echo htmlspecialchars($nome); ?>: <strong><?php echo number_format($valore, 2); ?></strong></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
                <?php endif; ?>

                <details class="aggiungi-nota-box">
                    <summary>+ Aggiungi Nota</summary>
                    <form method="POST" class="nota-form">
                        <input type="hidden" name="id_voce" value="<?php echo $voce['id_voce']; ?>">
                        <textarea name="testo" rows="3" placeholder="Inserisci la tua nota di revisione..." required></textarea>
                        <button type="submit" name="aggiungi_nota" class="btn btn-sm btn-primary">Salva Nota</button>
                    </form>
                </details>
            </div>
            <?php endforeach; ?>
        </div>

        <?php if (!empty($note)): ?>
        <div class="section">
            <h2>Le Mie Note (<?php echo count($note); ?>)</h2>
            <?php foreach ($note as $nota): ?>
            <div class="nota-card">
                <div class="nota-header">
                    <strong><?php echo htmlspecialchars($nota['nome_voce']); ?></strong>
                    <span class="nota-data"><?php echo date('d/m/Y', strtotime($nota['data_nota'])); ?></span>
                </div>
                <p><?php echo nl2br(htmlspecialchars($nota['testo'])); ?></p>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <div class="section">
            <h2>Giudizio Complessivo</h2>

            <?php if ($giudizio): ?>
                <div class="giudizio-emesso">
                    <div class="giudizio-header">
                        <span class="badge badge-<?php echo $giudizio['esito'] === 'approvazione' ? 'approvato' :
                              ($giudizio['esito'] === 'respingimento' ? 'respinto' : 'in_revisione'); ?> badge-large">
                            <?php echo ucfirst(str_replace('_', ' ', $giudizio['esito'])); ?>
                        </span>
                        <span>Emesso il: <?php echo date('d/m/Y', strtotime($giudizio['data_giudizio'])); ?></span>
                    </div>
                    <?php if ($giudizio['rilievi']): ?>
                        <p><strong>Rilievi:</strong><br><?php echo nl2br(htmlspecialchars($giudizio['rilievi'])); ?></p>
                    <?php endif; ?>
                </div>
            <?php else: ?>
                <form method="POST" class="giudizio-form">
                    <div class="form-group">
                        <label for="esito">Esito Revisione *</label>
                        <select id="esito" name="esito" required>
                            <option value="">Seleziona esito...</option>
                            <option value="approvazione">✓ Approvazione</option>
                            <option value="approvazione_con_rilievi">⚠ Approvazione con Rilievi</option>
                            <option value="respingimento">✗ Respingimento</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="rilievi">Rilievi / Note Conclusive</label>
                        <textarea id="rilievi" name="rilievi" rows="5"
                                  placeholder="Inserisci eventuali rilievi o note conclusive..."></textarea>
                    </div>

                    <button type="submit" name="inserisci_giudizio" class="btn btn-primary">
                        Emetti Giudizio Finale
                    </button>
                </form>
            <?php endif; ?>
        </div>
    </div>

    <style>
    .bilancio-info { display: flex; justify-content: space-between; align-items: center;
                     padding: 1rem; background: #f8f9fa; border-radius: 8px; margin: 1rem 0; }
    .badge-large { font-size: 1.1rem; padding: 0.5rem 1.5rem; }
    .voce-revisione { border: 1px solid #e0e0e0; padding: 1.5rem; border-radius: 8px; margin-bottom: 1rem; }
    .voce-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.5rem; }
    .voce-header h3 { margin: 0; color: #2c3e50; }
    .voce-valore { font-size: 1.5rem; font-weight: bold; color: #27ae60; }
    .voce-desc { color: #7f8c8d; margin-bottom: 1rem; }
    .indicatori-box { background: #e3f2fd; padding: 1rem; border-radius: 4px; margin: 1rem 0; }
    .indicatori-box ul { margin: 0.5rem 0 0 1.5rem; }
    .aggiungi-nota-box { margin-top: 1rem; }
    .aggiungi-nota-box summary { cursor: pointer; color: #3498db; font-weight: 500; }
    .nota-form { margin-top: 1rem; }
    .nota-form textarea { width: 100%; padding: 0.75rem; border: 1px solid #ddd;
                          border-radius: 4px; font-family: inherit; }
    .nota-card { background: #fff3cd; border-left: 4px solid #f39c12; padding: 1rem;
                 border-radius: 4px; margin-bottom: 1rem; }
    .nota-header { display: flex; justify-content: space-between; margin-bottom: 0.5rem; }
    .nota-data { color: #7f8c8d; font-size: 0.9rem; }
    .giudizio-form { max-width: 600px; }
    .giudizio-emesso { background: #f8f9fa; padding: 1.5rem; border-radius: 8px; }
    .giudizio-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem; }
    </style>
</body>
</html>