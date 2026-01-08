<?php
// revisore/dashboard.php
session_start();
require_once '../config/database.php';

if (!isset($_SESSION['id_utente']) || $_SESSION['tipo_utente'] !== 'revisore_esg') {
    header('Location: ../auth/login.php');
    exit();
}

$db = getDB();
$id_revisore = $_SESSION['id_utente'];

// Statistiche revisore
$stmt = $db->prepare("SELECT * FROM revisore_esg WHERE id_utente = ?");
$stmt->execute([$id_revisore]);
$stats = $stmt->fetch();

// Bilanci assegnati
$stmt = $db->prepare("
    SELECT b.*, a.nome as nome_azienda, a.ragione_sociale,
           r.data_inizio,
           g.esito, g.data_giudizio
    FROM revisione r
    JOIN bilancio_esercizio b ON r.id_bilancio = b.id_bilancio
    JOIN azienda a ON b.id_azienda = a.id_azienda
    LEFT JOIN giudizio g ON r.id_revisore = g.id_revisore AND r.id_bilancio = g.id_bilancio
    WHERE r.id_revisore = ?
    ORDER BY r.data_inizio DESC
");
$stmt->execute([$id_revisore]);
$bilanci_assegnati = $stmt->fetchAll();

// Competenze
$stmt = $db->prepare("
    SELECT c.nome, rc.livello
    FROM revisore_competenza rc
    JOIN competenza c ON rc.id_competenza = c.id_competenza
    WHERE rc.id_utente = ?
    ORDER BY rc.livello DESC, c.nome
");
$stmt->execute([$id_revisore]);
$competenze = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Revisore - ESG Balance</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <nav class="navbar">
        <div class="nav-brand">ESG-BALANCE</div>
        <div class="nav-menu">
            <a href="dashboard.php" class="active">Dashboard</a>
            <a href="competenze.php">Le Mie Competenze</a>
            <a href="../auth/logout.php">Logout</a>
        </div>
        <div class="nav-user"><?php echo htmlspecialchars($_SESSION['username']); ?> (Revisore ESG)</div>
    </nav>

    <div class="container">
        <h1>Dashboard Revisore ESG</h1>
        <p>Benvenuto, <?php echo htmlspecialchars($_SESSION['username']); ?>!</p>

        <div class="stats-grid">
            <div class="stat-card">
                <h3><?php echo $stats['nr_revisioni']; ?></h3>
                <p>Revisioni Completate</p>
            </div>
            <div class="stat-card">
                <h3><?php echo number_format($stats['indice_affidabilita'] * 100, 1); ?>%</h3>
                <p>Indice Affidabilità</p>
            </div>
            <div class="stat-card">
                <h3><?php echo count($competenze); ?></h3>
                <p>Competenze Registrate</p>
            </div>
            <div class="stat-card">
                <h3><?php echo count($bilanci_assegnati); ?></h3>
                <p>Bilanci Assegnati</p>
            </div>
        </div>

        <div class="section">
            <h2>Le Mie Competenze</h2>
            <?php if (empty($competenze)): ?>
                <p>Nessuna competenza registrata. <a href="competenze.php">Aggiungi le tue competenze</a></p>
            <?php else: ?>
                <div class="competenze-grid">
                    <?php foreach ($competenze as $comp): ?>
                    <div class="competenza-card">
                        <strong><?php echo htmlspecialchars($comp['nome']); ?></strong>
                        <div class="livello-bar">
                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                <span class="<?php echo $i <= $comp['livello'] ? 'active' : ''; ?>">●</span>
                            <?php endfor; ?>
                        </div>
                        <span class="livello-text">Livello <?php echo $comp['livello']; ?>/5</span>
                    </div>
                    <?php endforeach; ?>
                </div>
                <p class="mt-3"><a href="competenze.php">Gestisci competenze</a></p>
            <?php endif; ?>
        </div>

        <div class="section">
            <h2>Bilanci Assegnati</h2>

            <?php if (empty($bilanci_assegnati)): ?>
                <p>Nessun bilancio assegnato al momento.</p>
            <?php else: ?>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Azienda</th>
                            <th>Data Bilancio</th>
                            <th>Stato</th>
                            <th>Assegnato il</th>
                            <th>Giudizio</th>
                            <th>Azioni</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($bilanci_assegnati as $bil): ?>
                        <tr>
                            <td><?php echo $bil['id_bilancio']; ?></td>
                            <td><?php echo htmlspecialchars($bil['nome_azienda']); ?></td>
                            <td><?php echo date('d/m/Y', strtotime($bil['data_creazione'])); ?></td>
                            <td>
                                <span class="badge badge-<?php echo $bil['stato']; ?>">
                                    <?php echo ucfirst(str_replace('_', ' ', $bil['stato'])); ?>
                                </span>
                            </td>
                            <td><?php echo date('d/m/Y H:i', strtotime($bil['data_inizio'])); ?></td>
                            <td>
                                <?php if ($bil['esito']): ?>
                                    <span class="badge badge-<?php echo $bil['esito'] === 'approvazione' ? 'approvato' :
                                          ($bil['esito'] === 'respingimento' ? 'respinto' : 'in_revisione'); ?>">
                                        <?php echo ucfirst(str_replace('_', ' ', $bil['esito'])); ?>
                                    </span>
                                <?php else: ?>
                                    <span class="text-muted">Da emettere</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <a href="revisiona_bilancio.php?id=<?php echo $bil['id_bilancio']; ?>"
                                   class="btn btn-sm btn-primary">Revisiona</a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>

    <style>
    .competenze-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(250px, 1fr)); gap: 1rem; }
    .competenza-card { border: 1px solid #e0e0e0; padding: 1rem; border-radius: 8px; text-align: center; }
    .livello-bar { margin: 0.5rem 0; font-size: 1.5rem; }
    .livello-bar span { color: #ddd; }
    .livello-bar span.active { color: #f39c12; }
    .livello-text { font-size: 0.9rem; color: #7f8c8d; }
    </style>
</body>
</html>