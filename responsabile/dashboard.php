<?php
// responsabile/dashboard.php
session_start();
require_once '../config/database.php';
require_once '../config/mongodb.php';

// Verifica autenticazione
if (!isset($_SESSION['id_utente']) || $_SESSION['tipo_utente'] !== 'responsabile_aziendale') {
    header('Location: ../auth/login.php');
    exit();
}

$db = getDB();
$id_responsabile = $_SESSION['id_utente'];

// Recupera aziende del responsabile
$stmt = $db->prepare("
    SELECT a.*, COUNT(DISTINCT b.id_bilancio) as bilanci_reali
    FROM azienda a
    LEFT JOIN bilancio_esercizio b ON a.id_azienda = b.id_azienda
    WHERE a.id_responsabile = :id_responsabile
    GROUP BY a.id_azienda
");
$stmt->execute([':id_responsabile' => $id_responsabile]);
$aziende = $stmt->fetchAll();

// Recupera bilanci recenti
$stmt = $db->prepare("
    SELECT b.*, a.nome as nome_azienda, a.ragione_sociale
    FROM bilancio_esercizio b
    INNER JOIN azienda a ON b.id_azienda = a.id_azienda
    WHERE a.id_responsabile = :id_responsabile
    ORDER BY b.data_creazione DESC
    LIMIT 10
");
$stmt->execute([':id_responsabile' => $id_responsabile]);
$bilanci_recenti = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Responsabile - ESG Balance</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <nav class="navbar">
        <div class="nav-brand">ESG-BALANCE</div>
        <div class="nav-menu">
            <a href="dashboard.php" class="active">Dashboard</a>
            <a href="aziende.php">Le Mie Aziende</a>
            <a href="bilanci.php">Bilanci</a>
            <a href="nuova_azienda.php">+ Nuova Azienda</a>
            <a href="../auth/logout.php">Logout</a>
        </div>
        <div class="nav-user">
            <?php echo htmlspecialchars($_SESSION['username']); ?> (Responsabile Aziendale)
        </div>
    </nav>

    <div class="container">
        <h1>Dashboard Responsabile Aziendale</h1>
        <p>Benvenuto, <?php echo htmlspecialchars($_SESSION['username']); ?>!</p>

        <div class="stats-grid">
            <div class="stat-card">
                <h3><?php echo count($aziende); ?></h3>
                <p>Aziende Gestite</p>
            </div>
            <div class="stat-card">
                <h3><?php echo array_sum(array_column($aziende, 'nr_bilanci')); ?></h3>
                <p>Bilanci Totali</p>
            </div>
            <div class="stat-card">
                <h3><?php
                    echo count(array_filter($bilanci_recenti, function($b) {
                        return $b['stato'] === 'bozza';
                    }));
                ?></h3>
                <p>Bilanci in Bozza</p>
            </div>
            <div class="stat-card">
                <h3><?php
                    echo count(array_filter($bilanci_recenti, function($b) {
                        return $b['stato'] === 'in_revisione';
                    }));
                ?></h3>
                <p>In Revisione</p>
            </div>
        </div>

        <div class="section">
            <h2>Le Mie Aziende</h2>
            <?php if (empty($aziende)): ?>
                <p>Non hai ancora registrato aziende. <a href="nuova_azienda.php">Registra la prima azienda</a></p>
            <?php else: ?>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Logo</th>
                            <th>Nome</th>
                            <th>Ragione Sociale</th>
                            <th>P.IVA</th>
                            <th>Settore</th>
                            <th>Dipendenti</th>
                            <th>Bilanci</th>
                            <th>Azioni</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($aziende as $azienda): ?>
                        <tr>
                            <td>
                                <?php if ($azienda['logo']): ?>
                                    <img src="../uploads/logos/<?php echo htmlspecialchars($azienda['logo']); ?>"
                                         alt="Logo" class="logo-small">
                                <?php else: ?>
                                    <span class="no-logo">N/A</span>
                                <?php endif; ?>
                            </td>
                            <td><?php echo htmlspecialchars($azienda['nome']); ?></td>
                            <td><?php echo htmlspecialchars($azienda['ragione_sociale']); ?></td>
                            <td><?php echo htmlspecialchars($azienda['partita_iva']); ?></td>
                            <td><?php echo htmlspecialchars($azienda['settore'] ?? 'N/D'); ?></td>
                            <td><?php echo number_format($azienda['nr_dipendenti']); ?></td>
                            <td><?php echo $azienda['nr_bilanci']; ?></td>
                            <td>
                                <a href="dettaglio_azienda.php?id=<?php echo $azienda['id_azienda']; ?>"
                                   class="btn btn-sm">Dettagli</a>
                                <a href="nuovo_bilancio.php?id_azienda=<?php echo $azienda['id_azienda']; ?>"
                                   class="btn btn-sm btn-primary">+ Bilancio</a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>

        <div class="section">
            <h2>Bilanci Recenti</h2>
            <?php if (empty($bilanci_recenti)): ?>
                <p>Nessun bilancio presente</p>
            <?php else: ?>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Azienda</th>
                            <th>Data Creazione</th>
                            <th>Stato</th>
                            <th>Azioni</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($bilanci_recenti as $bilancio): ?>
                        <tr>
                            <td><?php echo $bilancio['id_bilancio']; ?></td>
                            <td><?php echo htmlspecialchars($bilancio['nome_azienda']); ?></td>
                            <td><?php echo date('d/m/Y', strtotime($bilancio['data_creazione'])); ?></td>
                            <td>
                                <span class="badge badge-<?php echo $bilancio['stato']; ?>">
                                    <?php echo ucfirst(str_replace('_', ' ', $bilancio['stato'])); ?>
                                </span>
                            </td>
                            <td>
                                <a href="modifica_bilancio.php?id=<?php echo $bilancio['id_bilancio']; ?>"
                                   class="btn btn-sm">Visualizza</a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>