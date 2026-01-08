<?php
// admin/logs.php
session_start();
require_once '../config/database.php';
require_once '../config/mongodb.php';

if (!isset($_SESSION['id_utente']) || $_SESSION['tipo_utente'] !== 'amministratore') {
    header('Location: ../auth/login.php');
    exit();
}

$tipo_filtro = $_GET['tipo'] ?? null;
$limit = intval($_GET['limit'] ?? 50);

// Recupera eventi da MongoDB
$eventi = getRecentEvents($limit, $tipo_filtro);

// Conta eventi per tipo
try {
    $mongo = new MongoDB_Connection();
    $collection = $mongo->getCollection('eventi');

    $pipeline = [
        ['$group' => [
            '_id' => '$tipo_evento',
            'count' => ['$sum' => 1]
        ]],
        ['$sort' => ['count' => -1]]
    ];

    $stats_tipi = iterator_to_array($collection->aggregate($pipeline));
} catch (Exception $e) {
    $stats_tipi = [];
}
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <title>Log Eventi - ESG Balance</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <nav class="navbar">
        <div class="nav-brand">ESG-BALANCE</div>
        <div class="nav-menu">
            <a href="dashboard.php">Dashboard</a>
            <a href="gestione_template.php">Template Bilanci</a>
            <a href="gestione_indicatori.php">Indicatori ESG</a>
            <a href="assegna_revisore.php">Assegna Revisori</a>
            <a href="logs.php" class="active">Log Eventi</a>
            <a href="../auth/logout.php">Logout</a>
        </div>
        <div class="nav-user"><?php echo htmlspecialchars($_SESSION['username']); ?></div>
    </nav>

    <div class="container">
        <div class="section">
            <h1>Log Eventi Sistema</h1>

            <div class="filtri-box">
                <strong>Filtra per tipo:</strong>
                <a href="logs.php" class="btn btn-sm <?php echo !$tipo_filtro ? 'active' : ''; ?>">Tutti</a>
                <?php foreach ($stats_tipi as $stat): ?>
                <a href="?tipo=<?php echo urlencode($stat['_id']); ?>"
                   class="btn btn-sm <?php echo $tipo_filtro === $stat['_id'] ? 'active' : ''; ?>">
                    <?php echo htmlspecialchars($stat['_id']); ?>
                    <span class="badge-count"><?php echo $stat['count']; ?></span>
                </a>
                <?php endforeach; ?>
            </div>

            <div class="filtri-box">
                <strong>Mostra:</strong>
                <a href="?limit=50<?php echo $tipo_filtro ? '&tipo='.$tipo_filtro : ''; ?>"
                   class="btn btn-sm <?php echo $limit === 50 ? 'active' : ''; ?>">50</a>
                <a href="?limit=100<?php echo $tipo_filtro ? '&tipo='.$tipo_filtro : ''; ?>"
                   class="btn btn-sm <?php echo $limit === 100 ? 'active' : ''; ?>">100</a>
                <a href="?limit=200<?php echo $tipo_filtro ? '&tipo='.$tipo_filtro : ''; ?>"
                   class="btn btn-sm <?php echo $limit === 200 ? 'active' : ''; ?>">200</a>
            </div>
        </div>

        <div class="section">
            <h2>Eventi Recenti (<?php echo count($eventi); ?>)</h2>

            <?php if (empty($eventi)): ?>
                <p>Nessun evento registrato.</p>
            <?php else: ?>
                <div class="eventi-list">
                    <?php foreach ($eventi as $evento): ?>
                    <div class="evento-card">
                        <div class="evento-header">
                            <span class="evento-tipo <?php echo $evento['tipo_evento']; ?>">
                                <?php echo htmlspecialchars($evento['tipo_evento']); ?>
                            </span>
                            <span class="evento-data">
                                <?php echo $evento['data_formattata']; ?>
                            </span>
                        </div>
                        <p class="evento-desc"><?php echo htmlspecialchars($evento['descrizione']); ?></p>
                        <div class="evento-footer">
                            <?php if (isset($evento['id_utente'])): ?>
                                <span class="text-muted">User ID: <?php echo $evento['id_utente']; ?></span>
                            <?php endif; ?>
                            <span class="text-muted">IP: <?php echo htmlspecialchars($evento['ip_address']); ?></span>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <div class="section">
            <h2>Statistiche Eventi</h2>
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Tipo Evento</th>
                        <th>Occorrenze</th>
                        <th>Percentuale</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $totale = array_sum(array_column($stats_tipi, 'count'));
                    foreach ($stats_tipi as $stat):
                    ?>
                    <tr>
                        <td><strong><?php echo htmlspecialchars($stat['_id']); ?></strong></td>
                        <td><?php echo $stat['count']; ?></td>
                        <td>
                            <div class="progress-bar" style="width: 150px;">
                                <div class="progress-fill"
                                     style="width: <?php echo ($stat['count'] / $totale * 100); ?>%"></div>
                            </div>
                            <?php echo number_format($stat['count'] / $totale * 100, 1); ?>%
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <style>
    .filtri-box { background: #f8f9fa; padding: 1rem; border-radius: 8px; margin-bottom: 1rem; }
    .filtri-box .btn { margin: 0.25rem; }
    .filtri-box .btn.active { background: #3498db; color: white; }
    .badge-count { background: #e74c3c; color: white; padding: 2px 6px; border-radius: 10px;
                   font-size: 0.8rem; margin-left: 5px; }
    .eventi-list { display: flex; flex-direction: column; gap: 1rem; }
    .evento-card { border: 1px solid #e0e0e0; padding: 1rem; border-radius: 8px; }
    .evento-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.5rem; }
    .evento-tipo { padding: 0.25rem 0.75rem; border-radius: 4px; font-size: 0.85rem;
                   font-weight: 500; background: #ecf0f1; }
    .evento-tipo.login { background: #d4edda; color: #155724; }
    .evento-tipo.logout { background: #f8d7da; color: #721c24; }
    .evento-tipo.registrazione { background: #d1ecf1; color: #0c5460; }
    .evento-tipo.bilancio_creato { background: #fff3cd; color: #856404; }
    .evento-tipo.giudizio_emesso { background: #cce5ff; color: #004085; }
    .evento-data { font-size: 0.9rem; color: #7f8c8d; }
    .evento-desc { margin: 0.5rem 0; }
    .evento-footer { display: flex; gap: 1rem; font-size: 0.85rem; }
    .text-muted { color: #95a5a6; }
    .progress-bar { height: 20px; background: #ecf0f1; border-radius: 10px;
                    overflow: hidden; display: inline-block; margin-right: 10px; }
    .progress-fill { height: 100%; background: linear-gradient(90deg, #3498db, #27ae60); }
    </style>
</body>
</html>