<?php
// responsabile/dettaglio_azienda.php
session_start();
require_once '../config/database.php';

if (!isset($_SESSION['id_utente']) || $_SESSION['tipo_utente'] !== 'responsabile_aziendale') {
    header('Location: ../auth/login.php');
    exit();
}

$db = getDB();
$id_azienda = intval($_GET['id'] ?? 0);

$stmt = $db->prepare("SELECT * FROM azienda WHERE id_azienda = ? AND id_responsabile = ?");
$stmt->execute([$id_azienda, $_SESSION['id_utente']]);
$azienda = $stmt->fetch();

if (!$azienda) {
    header('Location: dashboard.php');
    exit();
}

// Recupera bilanci
$stmt = $db->prepare("
    SELECT b.*,
           COUNT(DISTINCT r.id_revisore) as nr_revisori,
           COUNT(DISTINCT g.id_giudizio) as nr_giudizi
    FROM bilancio_esercizio b
    LEFT JOIN revisione r ON b.id_bilancio = r.id_bilancio
    LEFT JOIN giudizio g ON b.id_bilancio = g.id_bilancio
    WHERE b.id_azienda = ?
    GROUP BY b.id_bilancio
    ORDER BY b.data_creazione DESC
");
$stmt->execute([$id_azienda]);
$bilanci = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dettaglio Azienda - ESG Balance</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <nav class="navbar">
        <div class="nav-brand">ESG-BALANCE</div>
        <div class="nav-menu">
            <a href="dashboard.php">Dashboard</a>
            <a href="dashboard.php">Le Mie Aziende</a>
            <a href="../auth/logout.php">Logout</a>
        </div>
        <div class="nav-user"><?php echo htmlspecialchars($_SESSION['username']); ?></div>
    </nav>

    <div class="container">
        <div class="section">
            <h1><?php echo htmlspecialchars($azienda['nome']); ?></h1>

            <div class="azienda-info">
                <?php if ($azienda['logo']): ?>
                    <img src="../uploads/logos/<?php echo htmlspecialchars($azienda['logo']); ?>"
                         alt="Logo" class="logo-large">
                <?php endif; ?>

                <table class="info-table">
                    <tr>
                        <th>Ragione Sociale:</th>
                        <td><?php echo htmlspecialchars($azienda['ragione_sociale']); ?></td>
                    </tr>
                    <tr>
                        <th>Partita IVA:</th>
                        <td><?php echo htmlspecialchars($azienda['partita_iva']); ?></td>
                    </tr>
                    <tr>
                        <th>Settore:</th>
                        <td><?php echo htmlspecialchars($azienda['settore'] ?? 'N/D'); ?></td>
                    </tr>
                    <tr>
                        <th>Numero Dipendenti:</th>
                        <td><?php echo number_format($azienda['nr_dipendenti']); ?></td>
                    </tr>
                    <tr>
                        <th>Data Registrazione:</th>
                        <td><?php echo date('d/m/Y', strtotime($azienda['data_registrazione'])); ?></td>
                    </tr>
                    <tr>
                        <th>Bilanci Registrati:</th>
                        <td><strong><?php echo $azienda['nr_bilanci']; ?></strong></td>
                    </tr>
                </table>
            </div>
        </div>

        <div class="section">
            <div style="display: flex; justify-content: space-between; align-items: center;">
                <h2>Bilanci di Esercizio</h2>
                <a href="nuovo_bilancio.php?id_azienda=<?php echo $id_azienda; ?>"
                   class="btn btn-primary">+ Nuovo Bilancio</a>
            </div>

            <?php if (empty($bilanci)): ?>
                <p>Nessun bilancio presente. Crea il primo bilancio!</p>
            <?php else: ?>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Data Creazione</th>
                            <th>Stato</th>
                            <th>Revisori</th>
                            <th>Giudizi</th>
                            <th>Azioni</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($bilanci as $bilancio): ?>
                        <tr>
                            <td><?php echo $bilancio['id_bilancio']; ?></td>
                            <td><?php echo date('d/m/Y', strtotime($bilancio['data_creazione'])); ?></td>
                            <td>
                                <span class="badge badge-<?php echo $bilancio['stato']; ?>">
                                    <?php echo ucfirst(str_replace('_', ' ', $bilancio['stato'])); ?>
                                </span>
                            </td>
                            <td><?php echo $bilancio['nr_revisori']; ?></td>
                            <td><?php echo $bilancio['nr_giudizi']; ?></td>
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

    <style>
    .azienda-info { display: flex; gap: 2rem; align-items: flex-start; }
    .logo-large { max-width: 200px; max-height: 200px; object-fit: contain; border-radius: 8px; }
    .info-table { flex: 1; }
    .info-table th { text-align: left; padding: 0.5rem 1rem 0.5rem 0; color: #7f8c8d; font-weight: normal; }
    .info-table td { padding: 0.5rem 0; font-weight: 500; }
    </style>
</body>
</html>