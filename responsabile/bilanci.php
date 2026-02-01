<?php
// responsabile/bilanci.php
session_start();
require_once '../config/database.php';

if (!isset($_SESSION['id_utente']) || $_SESSION['tipo_utente'] !== 'responsabile_aziendale') {
    header('Location: ../auth/login.php');
    exit();
}

$db = getDB();
$id_responsabile = $_SESSION['id_utente'];

// Filtri
$filtro_stato = $_GET['stato'] ?? '';
$filtro_azienda = intval($_GET['azienda'] ?? 0);

// Query base
$sql = "
    SELECT b.*, a.nome as nome_azienda, a.ragione_sociale,
           COUNT(DISTINCT r.id_revisore) as nr_revisori,
           COUNT(DISTINCT g.id_giudizio) as nr_giudizi
    FROM bilancio_esercizio b
    INNER JOIN azienda a ON b.id_azienda = a.id_azienda
    LEFT JOIN revisione r ON b.id_bilancio = r.id_bilancio
    LEFT JOIN giudizio g ON b.id_bilancio = g.id_bilancio
    WHERE a.id_responsabile = :id_responsabile
";

// Applica filtri
if ($filtro_stato) {
    $sql .= " AND b.stato = :stato";
}
if ($filtro_azienda > 0) {
    $sql .= " AND a.id_azienda = :id_azienda";
}

$sql .= " GROUP BY b.id_bilancio ORDER BY b.data_creazione DESC";

$stmt = $db->prepare($sql);
$params = [':id_responsabile' => $id_responsabile];
if ($filtro_stato) $params[':stato'] = $filtro_stato;
if ($filtro_azienda > 0) $params[':id_azienda'] = $filtro_azienda;

$stmt->execute($params);
$bilanci = $stmt->fetchAll();

// Recupera aziende per filtro
$stmt = $db->prepare("SELECT id_azienda, nome FROM azienda WHERE id_responsabile = ? ORDER BY nome");
$stmt->execute([$id_responsabile]);
$aziende = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>I Miei Bilanci - ESG Balance</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <nav class="navbar">
        <div class="nav-brand">ESG-BALANCE</div>
        <div class="nav-menu">
            <a href="dashboard.php">Dashboard</a>
            <a href="aziende.php">Le Mie Aziende</a>
            <a href="bilanci.php" class="active">Bilanci</a>
            <a href="nuova_azienda.php">+ Nuova Azienda</a>
            <a href="../auth/logout.php">Logout</a>
        </div>
        <div class="nav-user"><?php echo htmlspecialchars($_SESSION['username']); ?></div>
    </nav>

    <div class="container">
        <div class="section">
            <h1>I Miei Bilanci</h1>

            <!-- Filtri -->
            <form method="GET" class="filtri-box">
                <div class="filtri-row">
                    <div class="filtro-group">
                        <label for="azienda">Azienda:</label>
                        <select name="azienda" id="azienda">
                            <option value="">Tutte le aziende</option>
                            <?php foreach ($aziende as $az): ?>
                            <option value="<?php echo $az['id_azienda']; ?>"
                                    <?php echo $filtro_azienda == $az['id_azienda'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($az['nome']); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="filtro-group">
                        <label for="stato">Stato:</label>
                        <select name="stato" id="stato">
                            <option value="">Tutti gli stati</option>
                            <option value="bozza" <?php echo $filtro_stato === 'bozza' ? 'selected' : ''; ?>>
                                Bozza
                            </option>
                            <option value="in_revisione" <?php echo $filtro_stato === 'in_revisione' ? 'selected' : ''; ?>>
                                In Revisione
                            </option>
                            <option value="approvato" <?php echo $filtro_stato === 'approvato' ? 'selected' : ''; ?>>
                                Approvato
                            </option>
                            <option value="respinto" <?php echo $filtro_stato === 'respinto' ? 'selected' : ''; ?>>
                                Respinto
                            </option>
                        </select>
                    </div>

                    <button type="submit" class="btn btn-sm btn-primary">Filtra</button>
                    <a href="bilanci.php" class="btn btn-sm">Reset</a>
                </div>
            </form>

            <!-- Statistiche -->
            <div class="stats-mini">
                <div class="stat-mini">
                    <strong><?php echo count(array_filter($bilanci, fn($b) => $b['stato'] === 'bozza')); ?></strong>
                    <span>Bozze</span>
                </div>
                <div class="stat-mini">
                    <strong><?php echo count(array_filter($bilanci, fn($b) => $b['stato'] === 'in_revisione')); ?></strong>
                    <span>In Revisione</span>
                </div>
                <div class="stat-mini">
                    <strong><?php echo count(array_filter($bilanci, fn($b) => $b['stato'] === 'approvato')); ?></strong>
                    <span>Approvati</span>
                </div>
                <div class="stat-mini">
                    <strong><?php echo count(array_filter($bilanci, fn($b) => $b['stato'] === 'respinto')); ?></strong>
                    <span>Respinti</span>
                </div>
            </div>

            <!-- Tabella bilanci -->
            <?php if (empty($bilanci)): ?>
                <div class="alert alert-info">
                    Nessun bilancio trovato con i criteri selezionati.
                </div>
            <?php else: ?>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Azienda</th>
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
                            <td><strong>#<?php echo $bilancio['id_bilancio']; ?></strong></td>
                            <td><?php echo htmlspecialchars($bilancio['nome_azienda']); ?></td>
                            <td><?php echo date('d/m/Y', strtotime($bilancio['data_creazione'])); ?></td>
                            <td>
                                <span class="badge badge-<?php echo $bilancio['stato']; ?>">
                                    <?php echo ucfirst(str_replace('_', ' ', $bilancio['stato'])); ?>
                                </span>
                            </td>
                            <td><?php echo $bilancio['nr_revisori']; ?></td>
                            <td><?php echo $bilancio['nr_giudizi']; ?> / <?php echo $bilancio['nr_revisori']; ?></td>
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
    .filtri-box {
        background: #f8f9fa;
        padding: 1rem;
        border-radius: 8px;
        margin-bottom: 1.5rem;
    }
    .filtri-row {
        display: flex;
        gap: 1rem;
        align-items: flex-end;
        flex-wrap: wrap;
    }
    .filtro-group {
        display: flex;
        flex-direction: column;
        gap: 0.25rem;
    }
    .filtro-group label {
        font-size: 0.9rem;
        color: #7f8c8d;
    }
    .filtro-group select {
        padding: 0.5rem;
        border: 1px solid #ddd;
        border-radius: 4px;
        min-width: 180px;
    }
    .stats-mini {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
        gap: 1rem;
        margin-bottom: 1.5rem;
    }
    .stat-mini {
        background: white;
        border: 1px solid #e0e0e0;
        padding: 1rem;
        border-radius: 8px;
        text-align: center;
    }
    .stat-mini strong {
        display: block;
        font-size: 1.8rem;
        color: #3498db;
        margin-bottom: 0.25rem;
    }
    .stat-mini span {
        font-size: 0.85rem;
        color: #7f8c8d;
    }
    </style>
</body>
</html>
