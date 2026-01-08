<?php
// admin/dashboard.php
session_start();
require_once '../config/database.php';

if (!isset($_SESSION['id_utente']) || $_SESSION['tipo_utente'] !== 'amministratore') {
    header('Location: ../auth/login.php');
    exit();
}

$db = getDB();

// Statistiche generali
$stats = $db->query("SELECT * FROM v_dashboard_admin")->fetch();

// Statistiche revisori
$stmt = $db->query("SELECT * FROM v_statistiche_revisori ORDER BY nr_revisioni DESC LIMIT 10");
$top_revisori = $stmt->fetchAll();

// Classifica bilanci ESG
$stmt = $db->query("SELECT * FROM v_classifica_bilanci_esg LIMIT 10");
$classifica_bilanci = $stmt->fetchAll();

// Azienda più affidabile
$azienda_top = $db->query("SELECT * FROM v_azienda_piu_affidabile")->fetch();
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Admin - ESG Balance</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <nav class="navbar">
        <div class="nav-brand">ESG-BALANCE</div>
        <div class="nav-menu">
            <a href="dashboard.php" class="active">Dashboard</a>
            <a href="gestione_template.php">Template Bilanci</a>
            <a href="gestione_indicatori.php">Indicatori ESG</a>
            <a href="assegna_revisore.php">Assegna Revisori</a>
            <a href="logs.php">Log Eventi</a>
            <a href="../auth/logout.php">Logout</a>
        </div>
        <div class="nav-user"><?php echo htmlspecialchars($_SESSION['username']); ?> (Admin)</div>
    </nav>

    <div class="container">
        <h1>Dashboard Amministratore</h1>

        <div class="stats-grid">
            <div class="stat-card">
                <h3><?php echo $stats['totale_aziende']; ?></h3>
                <p>Aziende Registrate</p>
            </div>
            <div class="stat-card">
                <h3><?php echo $stats['totale_revisori']; ?></h3>
                <p>Revisori ESG</p>
            </div>
            <div class="stat-card">
                <h3><?php echo $stats['totale_bilanci']; ?></h3>
                <p>Bilanci Totali</p>
            </div>
            <div class="stat-card">
                <h3><?php echo $stats['bilanci_in_revisione']; ?></h3>
                <p>In Revisione</p>
            </div>
        </div>

        <div class="grid-2">
            <div class="section">
                <h2>Stato Bilanci</h2>
                <table class="data-table">
                    <tr>
                        <td>Bozze</td>
                        <td><strong><?php echo $stats['bilanci_bozza']; ?></strong></td>
                    </tr>
                    <tr>
                        <td>In Revisione</td>
                        <td><strong><?php echo $stats['bilanci_in_revisione']; ?></strong></td>
                    </tr>
                    <tr>
                        <td>Approvati</td>
                        <td><strong><?php echo $stats['bilanci_approvati']; ?></strong></td>
                    </tr>
                    <tr>
                        <td>Respinti</td>
                        <td><strong><?php echo $stats['bilanci_respinti']; ?></strong></td>
                    </tr>
                </table>
            </div>

            <div class="section">
                <h2>Azienda Più Affidabile</h2>
                <?php if ($azienda_top): ?>
                    <div class="award-box">
                        <div class="award-icon">🏆</div>
                        <h3><?php echo htmlspecialchars($azienda_top['nome']); ?></h3>
                        <p><?php echo htmlspecialchars($azienda_top['ragione_sociale']); ?></p>
                        <div class="award-stats">
                            <div>
                                <strong><?php echo number_format($azienda_top['percentuale_affidabilita'], 1); ?>%</strong>
                                <span>Affidabilità</span>
                            </div>
                            <div>
                                <strong><?php echo $azienda_top['totale_bilanci']; ?></strong>
                                <span>Bilanci</span>
                            </div>
                        </div>
                    </div>
                <?php else: ?>
                    <p>Nessun dato disponibile</p>
                <?php endif; ?>
            </div>
        </div>

        <div class="section">
            <h2>Top 10 Revisori per Numero Revisioni</h2>
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
                    <?php foreach ($top_revisori as $rev): ?>
                    <tr>
                        <td><strong><?php echo htmlspecialchars($rev['username']); ?></strong></td>
                        <td><?php echo $rev['nr_revisioni']; ?></td>
                        <td>
                            <div class="progress-bar">
                                <div class="progress-fill"
                                     style="width: <?php echo ($rev['indice_affidabilita'] * 100); ?>%"></div>
                            </div>
                            <?php echo number_format($rev['indice_affidabilita'] * 100, 1); ?>%
                        </td>
                        <td>
                            <small><?php echo $rev['competenze'] ?
                                   substr(htmlspecialchars($rev['competenze']), 0, 50) . '...' : 'N/D'; ?></small>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <div class="section">
            <h2>Classifica Bilanci per Indicatori ESG</h2>
            <table class="data-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Azienda</th>
                        <th>Data</th>
                        <th>Stato</th>
                        <th>Nr. Indicatori ESG</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($classifica_bilanci as $bil): ?>
                    <tr>
                        <td><?php echo $bil['id_bilancio']; ?></td>
                        <td><?php echo htmlspecialchars($bil['nome_azienda']); ?></td>
                        <td><?php echo date('d/m/Y', strtotime($bil['data_creazione'])); ?></td>
                        <td>
                            <span class="badge badge-<?php echo $bil['stato']; ?>">
                                <?php echo ucfirst(str_replace('_', ' ', $bil['stato'])); ?>
                            </span>
                        </td>
                        <td><strong><?php echo $bil['nr_indicatori_esg']; ?></strong></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <style>
    .grid-2 { display: grid; grid-template-columns: repeat(auto-fit, minmax(350px, 1fr)); gap: 2rem; }
    .award-box { text-align: center; padding: 2rem; }
    .award-icon { font-size: 4rem; margin-bottom: 1rem; }
    .award-box h3 { color: #2c3e50; margin: 0.5rem 0; }
    .award-box p { color: #7f8c8d; margin-bottom: 1rem; }
    .award-stats { display: flex; justify-content: center; gap: 3rem; margin-top: 1.5rem; }
    .award-stats > div { text-align: center; }
    .award-stats strong { display: block; font-size: 2rem; color: #f39c12; }
    .award-stats span { color: #7f8c8d; font-size: 0.9rem; }
    .progress-bar { width: 100px; height: 20px; background: #ecf0f1; border-radius: 10px;
                    overflow: hidden; display: inline-block; margin-right: 10px; }
    .progress-fill { height: 100%; background: linear-gradient(90deg, #3498db, #27ae60); }
    </style>
</body>
</html>