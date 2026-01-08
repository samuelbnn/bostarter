<?php
// responsabile/nuovo_bilancio.php
session_start();
require_once '../config/database.php';
require_once '../config/mongodb.php';

if (!isset($_SESSION['id_utente']) || $_SESSION['tipo_utente'] !== 'responsabile_aziendale') {
    header('Location: ../auth/login.php');
    exit();
}

$db = getDB();
$id_azienda = intval($_GET['id_azienda'] ?? 0);

// Verifica che l'azienda appartenga al responsabile
$stmt = $db->prepare("SELECT * FROM azienda WHERE id_azienda = ? AND id_responsabile = ?");
$stmt->execute([$id_azienda, $_SESSION['id_utente']]);
$azienda = $stmt->fetch();

if (!$azienda) {
    header('Location: dashboard.php');
    exit();
}

// Recupera template voci contabili
$voci = $db->query("SELECT * FROM voce_contabile ORDER BY nome")->fetchAll();

$errore = '';
$successo = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data_creazione = $_POST['data_creazione'] ?? date('Y-m-d');

    try {
        $db->beginTransaction();

        // Crea bilancio
        $stmt = $db->prepare("CALL sp_crea_bilancio(:id_azienda, :data, @id_bilancio)");
        $stmt->execute([
            ':id_azienda' => $id_azienda,
            ':data' => $data_creazione
        ]);
        $stmt->closeCursor();

        $result = $db->query("SELECT @id_bilancio AS id")->fetch();
        $id_bilancio = $result['id'];

        if ($id_bilancio > 0) {
            // Inserisci valori voci
            foreach ($_POST['voci'] ?? [] as $id_voce => $valore) {
                if (!empty($valore)) {
                    $stmt = $db->prepare("CALL sp_inserisci_valore_voce(:bilancio, :voce, :valore)");
                    $stmt->execute([
                        ':bilancio' => $id_bilancio,
                        ':voce' => $id_voce,
                        ':valore' => floatval($valore)
                    ]);
                    $stmt->closeCursor();
                }
            }

            $db->commit();

            logEvent('bilancio_creato', "Nuovo bilancio per azienda: {$azienda['nome']}",
                     $_SESSION['id_utente'], ['id_bilancio' => $id_bilancio]);

            $successo = "Bilancio creato con successo! ID: {$id_bilancio}";
        }
    } catch (PDOException $e) {
        $db->rollBack();
        $errore = "Errore creazione bilancio: " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nuovo Bilancio - ESG Balance</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <nav class="navbar">
        <div class="nav-brand">ESG-BALANCE</div>
        <div class="nav-menu">
            <a href="dashboard.php">Dashboard</a>
            <a href="dettaglio_azienda.php?id=<?php echo $id_azienda; ?>">Torna all'Azienda</a>
            <a href="../auth/logout.php">Logout</a>
        </div>
        <div class="nav-user"><?php echo htmlspecialchars($_SESSION['username']); ?></div>
    </nav>

    <div class="container">
        <div class="section">
            <h1>Nuovo Bilancio - <?php echo htmlspecialchars($azienda['nome']); ?></h1>

            <?php if ($errore): ?>
                <div class="alert alert-error"><?php echo htmlspecialchars($errore); ?></div>
            <?php endif; ?>

            <?php if ($successo): ?>
                <div class="alert alert-success">
                    <?php echo htmlspecialchars($successo); ?>
                    <br><a href="dettaglio_azienda.php?id=<?php echo $id_azienda; ?>">Torna all'azienda</a>
                    | <a href="modifica_bilancio.php?id=<?php echo $id_bilancio; ?>">Aggiungi indicatori ESG</a>
                </div>
            <?php else: ?>
                <form method="POST">
                    <div class="form-group">
                        <label for="data_creazione">Data Creazione Bilancio</label>
                        <input type="date" id="data_creazione" name="data_creazione"
                               value="<?php echo date('Y-m-d'); ?>" required>
                    </div>

                    <h3>Voci di Bilancio</h3>
                    <p class="text-muted">Inserisci i valori in Euro (€). Lascia vuoto se non applicabile.</p>

                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Voce Contabile</th>
                                <th>Descrizione</th>
                                <th>Valore (€)</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($voci as $voce): ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($voce['nome']); ?></strong></td>
                                <td><?php echo htmlspecialchars($voce['descrizione']); ?></td>
                                <td>
                                    <input type="number" step="0.01"
                                           name="voci[<?php echo $voce['id_voce']; ?>]"
                                           placeholder="0.00" style="width: 150px;">
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>

                    <div class="mt-3">
                        <button type="submit" class="btn btn-primary">Crea Bilancio</button>
                        <a href="dettaglio_azienda.php?id=<?php echo $id_azienda; ?>" class="btn">Annulla</a>
                    </div>
                </form>
            <?php endif; ?>
        </div>
    </div>

    <style>
    .text-muted { color: #7f8c8d; margin-bottom: 1rem; }
    input[type="number"] { padding: 0.5rem; border: 1px solid #ddd; border-radius: 4px; }
    </style>
</body>
</html>