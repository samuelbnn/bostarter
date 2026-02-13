<?php
// responsabile/modifica_bilancio.php
session_start();
require_once '../config/database.php';
require_once '../config/mongodb.php';

if (!isset($_SESSION['id_utente']) || $_SESSION['tipo_utente'] !== 'responsabile_aziendale') {
    header('Location: ../auth/login.php');
    exit();
}

$db = getDB();
$id_bilancio = intval($_GET['id'] ?? 0);

// Verifica bilancio
$stmt = $db->prepare("
    SELECT b.*, a.nome as nome_azienda, a.id_azienda
    FROM bilancio_esercizio b
    JOIN azienda a ON b.id_azienda = a.id_azienda
    WHERE b.id_bilancio = ? AND a.id_responsabile = ?
");
$stmt->execute([$id_bilancio, $_SESSION['id_utente']]);
$bilancio = $stmt->fetch();

if (!$bilancio) {
    header('Location: dashboard.php');
    exit();
}

// Recupera voci del bilancio
$stmt = $db->prepare("
    SELECT vvb.*, vc.nome, vc.descrizione
    FROM valore_voce_bilancio vvb
    JOIN voce_contabile vc ON vvb.id_voce = vc.id_voce
    WHERE vvb.id_bilancio = ?
    ORDER BY vc.nome
");
$stmt->execute([$id_bilancio]);
$voci_bilancio = $stmt->fetchAll();

// Recupera indicatori ESG
$indicatori = $db->query("
    SELECT i.*,
           ia.codice_normativa,
           iss.ambito_sociale, iss.frequenza_rilevazione
    FROM indicatore_esg i
    LEFT JOIN indicatore_ambientale ia ON i.id_indicatore = ia.id_indicatore
    LEFT JOIN indicatore_sociale iss ON i.id_indicatore = iss.id_indicatore
    ORDER BY i.tipo_indicatore, i.nome
")->fetchAll();

// Recupera collegamenti già esistenti
$stmt = $db->prepare("
    SELECT * FROM voce_indicatore WHERE id_bilancio = ?
");
$stmt->execute([$id_bilancio]);
$collegamenti = [];
foreach ($stmt->fetchAll() as $row) {
    $collegamenti[$row['id_voce']][$row['id_indicatore']] = $row;
}

$successo = '';
$errore = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['aggiungi_indicatore'])) {
    $id_voce = intval($_POST['id_voce']);
    $id_indicatore = intval($_POST['id_indicatore']);
    $valore = floatval($_POST['valore_indicatore']);
    $fonte = trim($_POST['fonte'] ?? '');
    $data_rilevazione = $_POST['data_rilevazione'] ?? date('Y-m-d');

    try {
        $stmt = $db->prepare("CALL sp_collega_indicatore_voce(:bilancio, :voce, :indicatore,
                              :valore, :fonte, :data)");
        $stmt->execute([
            ':bilancio' => $id_bilancio,
            ':voce' => $id_voce,
            ':indicatore' => $id_indicatore,
            ':valore' => $valore,
            ':fonte' => $fonte,
            ':data' => $data_rilevazione
        ]);
        $stmt->closeCursor();

        logEvent('indicatore_collegato', "Indicatore ESG collegato al bilancio #{$id_bilancio}",
                 $_SESSION['id_utente'], ['id_indicatore' => $id_indicatore]);

        $successo = "Indicatore collegato con successo!";
        header("Refresh:1");
    } catch (PDOException $e) {
        $errore = "Errore: " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dettaglio Bilancio - ESG Balance</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <nav class="navbar">
        <div class="nav-brand">ESG-BALANCE</div>
        <div class="nav-menu">
            <a href="dashboard.php">Dashboard</a>
            <a href="dettaglio_azienda.php?id=<?php echo $bilancio['id_azienda']; ?>">Torna all'Azienda</a>
            <a href="../auth/logout.php">Logout</a>
        </div>
        <div class="nav-user"><?php echo htmlspecialchars($_SESSION['username']); ?></div>
    </nav>

    <div class="container">
        <div class="section">
            <h1>Bilancio #<?php echo $id_bilancio; ?> - <?php echo htmlspecialchars($bilancio['nome_azienda']); ?></h1>

            <div class="bilancio-header">
                <span><strong>Data:</strong> <?php echo date('d/m/Y', strtotime($bilancio['data_creazione'])); ?></span>
                <span class="badge badge-<?php echo $bilancio['stato']; ?>">
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

            <?php foreach ($voci_bilancio as $voce): ?>
            <div class="voce-card">
                <div class="voce-header">
                    <h3><?php echo htmlspecialchars($voce['nome']); ?></h3>
                    <span class="voce-valore">€ <?php echo number_format($voce['valore'], 2, ',', '.'); ?></span>
                </div>
                <p class="voce-desc"><?php echo htmlspecialchars($voce['descrizione']); ?></p>

                <!-- Indicatori collegati -->
                <?php if (isset($collegamenti[$voce['id_voce']])): ?>
                <div class="indicatori-collegati">
                    <strong>Indicatori ESG collegati:</strong>
                    <ul>
                        <?php foreach ($collegamenti[$voce['id_voce']] as $coll):
                            $ind = reset(array_filter($indicatori, fn($i) => $i['id_indicatore'] == $coll['id_indicatore'])) ?: null;
                        ?>
                        <li>
                            <strong><?php echo htmlspecialchars($ind['nome'] ?? 'N/D'); ?>:</strong>
                            <?php echo number_format($coll['valore_indicatore'], 2); ?>
                            <span class="text-muted">
                                (Fonte: <?php echo htmlspecialchars($coll['fonte']); ?>,
                                <?php echo date('d/m/Y', strtotime($coll['data_rilevazione'])); ?>)
                            </span>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
                <?php endif; ?>

                <!-- Form aggiunta indicatore -->
                <?php if ($bilancio['stato'] === 'bozza'): ?>
                <details class="aggiungi-indicatore">
                    <summary>+ Collega Indicatore ESG</summary>
                    <form method="POST" class="form-inline">
                        <input type="hidden" name="id_voce" value="<?php echo $voce['id_voce']; ?>">

                        <div class="form-row">
                            <select name="id_indicatore" required>
                                <option value="">Seleziona indicatore...</option>
                                <?php
                                $tipo_corrente = '';
                                foreach ($indicatori as $ind):
                                    if ($tipo_corrente !== $ind['tipo_indicatore']):
                                        if ($tipo_corrente !== '') echo '</optgroup>';
                                        echo '<optgroup label="' . ucfirst($ind['tipo_indicatore']) . '">';
                                        $tipo_corrente = $ind['tipo_indicatore'];
                                    endif;
                                ?>
                                    <option value="<?php echo $ind['id_indicatore']; ?>">
                                        <?php echo htmlspecialchars($ind['nome']); ?>
                                    </option>
                                <?php endforeach; ?>
                                <?php if ($tipo_corrente !== '') echo '</optgroup>'; ?>
                            </select>

                            <input type="number" step="0.01" name="valore_indicatore"
                                   placeholder="Valore" required>

                            <input type="text" name="fonte" placeholder="Fonte dati" required>

                            <input type="date" name="data_rilevazione"
                                   value="<?php echo date('Y-m-d'); ?>" required>

                            <button type="submit" name="aggiungi_indicatore" class="btn btn-sm btn-primary">
                                Collega
                            </button>
                        </div>
                    </form>
                </details>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

    <style>
    .bilancio-header { display: flex; justify-content: space-between; align-items: center;
                       padding: 1rem; background: #f8f9fa; border-radius: 4px; margin-bottom: 1rem; }
    .voce-card { border: 1px solid #e0e0e0; padding: 1.5rem; border-radius: 8px; margin-bottom: 1rem; }
    .voce-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.5rem; }
    .voce-header h3 { margin: 0; color: #2c3e50; }
    .voce-valore { font-size: 1.5rem; font-weight: bold; color: #27ae60; }
    .voce-desc { color: #7f8c8d; margin-bottom: 1rem; }
    .indicatori-collegati { background: #e8f5e9; padding: 1rem; border-radius: 4px; margin: 1rem 0; }
    .indicatori-collegati ul { margin: 0.5rem 0 0 1.5rem; }
    .indicatori-collegati li { margin-bottom: 0.5rem; }
    .aggiungi-indicatore { margin-top: 1rem; }
    .aggiungi-indicatore summary { cursor: pointer; color: #3498db; font-weight: 500; }
    .form-inline { margin-top: 1rem; }
    .form-row { display: flex; gap: 0.5rem; flex-wrap: wrap; }
    .form-row select, .form-row input { padding: 0.5rem; border: 1px solid #ddd; border-radius: 4px; }
    .form-row select { flex: 2; min-width: 200px; }
    .form-row input[type="number"] { width: 120px; }
    .form-row input[type="text"] { flex: 1; min-width: 150px; }
    .form-row input[type="date"] { width: 140px; }
    .text-muted { color: #95a5a6; font-size: 0.9rem; }
    </style>
</body>
</html>
