<?php
// responsabile/aziende.php
session_start();
require_once '../config/database.php';

if (!isset($_SESSION['id_utente']) || $_SESSION['tipo_utente'] !== 'responsabile_aziendale') {
    header('Location: ../auth/login.php');
    exit();
}

$db = getDB();
$id_responsabile = $_SESSION['id_utente'];

// Recupera aziende del responsabile
$stmt = $db->prepare("
    SELECT a.*, COUNT(DISTINCT b.id_bilancio) as bilanci_totali
    FROM azienda a
    LEFT JOIN bilancio_esercizio b ON a.id_azienda = b.id_azienda
    WHERE a.id_responsabile = :id_responsabile
    GROUP BY a.id_azienda
    ORDER BY a.nome
");
$stmt->execute([':id_responsabile' => $id_responsabile]);
$aziende = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Le Mie Aziende - ESG Balance</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <nav class="navbar">
        <div class="nav-brand">ESG-BALANCE</div>
        <div class="nav-menu">
            <a href="dashboard.php">Dashboard</a>
            <a href="aziende.php" class="active">Le Mie Aziende</a>
            <a href="bilanci.php">Bilanci</a>
            <a href="nuova_azienda.php">+ Nuova Azienda</a>
            <a href="../auth/logout.php">Logout</a>
        </div>
        <div class="nav-user"><?php echo htmlspecialchars($_SESSION['username']); ?></div>
    </nav>

    <div class="container">
        <div class="section">
            <div style="display: flex; justify-content: space-between; align-items: center;">
                <h1>Le Mie Aziende</h1>
                <a href="nuova_azienda.php" class="btn btn-primary">+ Registra Nuova Azienda</a>
            </div>

            <?php if (empty($aziende)): ?>
                <div class="alert alert-info">
                    Non hai ancora registrato aziende. 
                    <a href="nuova_azienda.php">Registra la prima azienda</a>
                </div>
            <?php else: ?>
                <div class="aziende-grid">
                    <?php foreach ($aziende as $azienda): ?>
                    <div class="azienda-card">
                        <div class="azienda-header">
                            <?php if ($azienda['logo']): ?>
                                <img src="../uploads/logos/<?php echo htmlspecialchars($azienda['logo']); ?>"
                                     alt="Logo" class="logo-card">
                            <?php else: ?>
                                <div class="logo-placeholder">
                                    <?php echo strtoupper(substr($azienda['nome'], 0, 2)); ?>
                                </div>
                            <?php endif; ?>
                            <h3><?php echo htmlspecialchars($azienda['nome']); ?></h3>
                        </div>

                        <div class="azienda-info">
                            <p><strong>Ragione Sociale:</strong><br>
                               <?php echo htmlspecialchars($azienda['ragione_sociale']); ?></p>
                            <p><strong>P.IVA:</strong> <?php echo htmlspecialchars($azienda['partita_iva']); ?></p>
                            <p><strong>Settore:</strong> <?php echo htmlspecialchars($azienda['settore'] ?? 'N/D'); ?></p>
                            <p><strong>Dipendenti:</strong> <?php echo number_format($azienda['nr_dipendenti']); ?></p>
                        </div>

                        <div class="azienda-stats">
                            <div class="stat-box">
                                <span class="stat-number"><?php echo $azienda['nr_bilanci']; ?></span>
                                <span class="stat-label">Bilanci Registrati</span>
                            </div>
                        </div>

                        <div class="azienda-actions">
                            <a href="dettaglio_azienda.php?id=<?php echo $azienda['id_azienda']; ?>" 
                               class="btn btn-sm">Dettagli</a>
                            <a href="nuovo_bilancio.php?id_azienda=<?php echo $azienda['id_azienda']; ?>" 
                               class="btn btn-sm btn-primary">+ Bilancio</a>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <style>
    .aziende-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
        gap: 2rem;
        margin-top: 2rem;
    }
    .azienda-card {
        background: white;
        border: 1px solid #e0e0e0;
        border-radius: 12px;
        padding: 1.5rem;
        transition: box-shadow 0.3s;
    }
    .azienda-card:hover {
        box-shadow: 0 4px 12px rgba(0,0,0,0.1);
    }
    .azienda-header {
        text-align: center;
        padding-bottom: 1rem;
        border-bottom: 1px solid #ecf0f1;
        margin-bottom: 1rem;
    }
    .logo-card {
        width: 80px;
        height: 80px;
        object-fit: contain;
        margin-bottom: 0.5rem;
    }
    .logo-placeholder {
        width: 80px;
        height: 80px;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 2rem;
        font-weight: bold;
        border-radius: 8px;
        margin: 0 auto 0.5rem;
    }
    .azienda-header h3 {
        margin: 0;
        color: #2c3e50;
        font-size: 1.3rem;
    }
    .azienda-info {
        margin: 1rem 0;
    }
    .azienda-info p {
        margin: 0.5rem 0;
        font-size: 0.9rem;
        color: #7f8c8d;
    }
    .azienda-stats {
        background: #f8f9fa;
        padding: 1rem;
        border-radius: 8px;
        margin: 1rem 0;
    }
    .stat-box {
        text-align: center;
    }
    .stat-number {
        display: block;
        font-size: 2rem;
        font-weight: bold;
        color: #3498db;
    }
    .stat-label {
        display: block;
        font-size: 0.85rem;
        color: #7f8c8d;
        margin-top: 0.25rem;
    }
    .azienda-actions {
        display: flex;
        gap: 0.5rem;
        margin-top: 1rem;
    }
    .azienda-actions .btn {
        flex: 1;
        text-align: center;
    }
    </style>
</body>
</html>
