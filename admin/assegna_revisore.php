<?php
// admin/assegna_revisore.php
session_start();
require_once '../config/database.php';
require_once '../config/mongodb.php';

if (!isset($_SESSION['id_utente']) || $_SESSION['tipo_utente'] !== 'amministratore') {
    header('Location: ../auth/login.php');
    exit();
}

$db = getDB();
$successo = '';
$errore = '';

// Assegna revisore
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['assegna'])) {
    $id_bilancio = intval($_POST['id_bilancio']);
    $id_revisore = intval($_POST['id_revisore']);

    try {
        $stmt = $db->prepare("CALL sp_assegna_revisore(:revisore, :bilancio)");
        $stmt->execute([':revisore' => $id_revisore, ':bilancio' => $id_bilancio]);
        $stmt->closeCursor();

        logEvent('revisore_assegnato', "Revisore #{$id_revisore} assegnato a bilancio #{$id_bilancio}",
                 $_SESSION['id_utente']);

        $successo = "Revisore assegnato con successo!";
    } catch (PDOException $e) {
        $errore = "Errore: " . $e->getMessage();
    }
}

// Recupera bilanci
$bilanci = $db->query("
    SELECT b.*, a.nome as nome_azienda,
           COUNT(DISTINCT r.id_revisore) as nr_revisori
    FROM bilancio_esercizio b
    JOIN azienda a ON b.id_azienda = a.id_azienda
    LEFT JOIN revisione r ON b.id_bilancio = r.id_bilancio
    WHERE b.stato IN ('bozza', 'in_revisione')
    GROUP BY b.id_bilancio
    ORDER BY b.data_creazione DESC
")->fetchAll();

// Recupera revisori
$revisori = $db->query("
    SELECT u.id_utente, u.username, r.nr_revisioni, r.indice_affidabilita,
           COUNT(DISTINCT rc.id_competenza) as nr_competenze
    FROM utente u
    JOIN revisore_esg r ON u.id_utente = r.id_utente
    LEFT JOIN revisore_competenza rc ON u.id_utente = rc.id_utente
    GROUP BY u.id_utente
    ORDER BY r.indice_affidabilita DESC, r.nr_revisioni DESC
")->fetchAll();
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <title>Assegna Revisori - ESG Balance</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <nav class="navbar">
        <div class="nav-brand">ESG-BALANCE</div>
        <div class="nav-menu">
            <a href="dashboard.php">Dashboard</a>
            <a href="gestione_template.php">Template Bilanci</a>
            <a href="gestione_indicatori.php">Indicatori ESG</a>
            <a href="assegna_revisore.php" class="active">Assegna Revisori</a>
            <a href="../auth/logout.php">Logout</a>
        </div>
        <div class="nav-user"><?php echo htmlspecialchars($_SESSION['username']); ?></div>
    </nav>

    <div class="container">
        <div class="section">
            <h1>Assegnazione Revisori ai Bilanci</h1>

            <?php if ($successo): ?>
                <div class="alert alert-success"><?php echo htmlspecialchars($successo); ?></div>
            <?php endif; ?>

            <?php if ($errore): ?>
                <div class="alert alert-error"><?php echo htmlspecialchars($errore); ?></div>
            <?php endif; ?>

            <form method="POST" class="assegna-form">
                <h3>Nuova Assegnazione</h3>
                <div class="form-row">
                    <div class="form-group" style="flex: 2;">
                        <label for="id_bilancio">Bilancio *</label>
                        <select id="id_bilancio" name="id_bilancio" required>
                            <option value="">Seleziona bilancio...</option>
                            <?php foreach ($bilanci as $bil): ?>
                            <option value="<?php echo $bil['id_bilancio']; ?>">
                                #<?php echo $bil['id_bilancio']; ?> -
                                <?php echo htmlspecialchars($bil['nome_azienda']); ?>
                                (<?php echo date('d/m/Y', strtotime($bil['data_creazione'])); ?>) -
                                <?php echo $bil['nr_revisori']; ?> revisori già assegnati
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group" style="flex: 2;">
                        <label for="id_revisore">Revisore *</label>
                        <select id="id_revisore" name="id_revisore" required>
                            <option value="">Seleziona revisore...</option>
                            <?php foreach ($revisori as $rev): ?>
                            <option value="<?php echo $rev['id_utente']; ?>">
                                <?php echo htmlspecialchars($rev['username']); ?> -
                                <?php echo $rev['nr_revisioni']; ?> revisioni -
                                Affidabilità: <?php echo number_format($rev['indice_affidabilita'] * 100, 1); ?>% -
                                <?php echo $rev['nr_competenze']; ?> competenze
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group" style="align-self: flex-end;">
                        <button type="submit" name="assegna" class="btn btn-primary">Assegna</button>
                    </div>
                </div>
            </form>
        </div>

        <div class="grid-2">
            <div class="section">
                <h2>Bilanci in Attesa (<?php echo count($bilanci); ?>)</h2>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Azienda</th>
                            <th>Data</th>
                            <th>Stato</th>
                            <th>Revisori</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($bilanci as $bil): ?>
                        <tr>
                            <td><?php echo $bil['id_bilancio']; ?></td>
                            <td><?php echo htmlspecialchars($bil['nome_azienda']); ?></td>
                            <td><?php echo date('d/m/Y', strtotime($bil['data_creazione'])); ?></td>
                            <td>
                                <span class="badge badge-<?php echo $bil['stato']; ?>">
                                    <?php echo ucfirst(str_replace('_', ' ', $bil['stato'])); ?>
                                </span>
                            </td>
                            <td><strong><?php echo $bil['nr_revisori']; ?></strong></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <div class="section">
                <h2>Revisori Disponibili (<?php echo count($revisori); ?>)</h2>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Username</th>
                            <th>Revisioni</th>
                            <th>Affidabilità</th>
                            <th>Competenze</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($revisori as $rev): ?>
                        <tr>
                            <td><strong><?php echo htmlspecialchars($rev['username']); ?></strong></td>
                            <td><?php echo $rev['nr_revisioni']; ?></td>
                            <td>
                                <div class="progress-bar-small">
                                    <div class="progress-fill"
                                         style="width: <?php echo ($rev['indice_affidabilita'] * 100); ?>%"></div>
                                </div>
                                <?php echo number_format($rev['indice_affidabilita'] * 100, 1); ?>%
                            </td>
                            <td><?php echo $rev['nr_competenze']; ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <style>
    .assegna-form { background: #f8f9fa; padding: 1.5rem; border-radius: 8px; margin-bottom: 2rem; }
    .form-row { display: flex; gap: 1rem; align-items: flex-start; }
    .form-row .form-group { margin-bottom: 0; }
    .grid-2 { display: grid; grid-template-columns: repeat(auto-fit, minmax(450px, 1fr)); gap: 2rem; }
    .progress-bar-small { width: 80px; height: 10px; background: #ecf0f1; border-radius: 5px;
                          overflow: hidden; display: inline-block; margin-right: 10px; }
    .progress-fill { height: 100%; background: linear-gradient(90deg, #3498db, #27ae60); }
    </style>
</body>
</html>