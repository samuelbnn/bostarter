<?php
// revisore/competenze.php
session_start();
require_once '../config/database.php';
require_once '../config/mongodb.php';

if (!isset($_SESSION['id_utente']) || $_SESSION['tipo_utente'] !== 'revisore_esg') {
    header('Location: ../auth/login.php');
    exit();
}

$db = getDB();
$id_revisore = $_SESSION['id_utente'];

$successo = '';
$errore = '';

// Aggiungi/Modifica competenza
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['aggiungi'])) {
    $nome_competenza = trim($_POST['nome_competenza'] ?? '');
    $livello = intval($_POST['livello'] ?? 0);

    if (empty($nome_competenza)) {
        $errore = "Nome competenza obbligatorio";
    } elseif ($livello < 0 || $livello > 5) {
        $errore = "Livello deve essere tra 0 e 5";
    } else {
        try {
            $stmt = $db->prepare("CALL sp_inserisci_competenza_revisore(:revisore, :competenza, :livello)");
            $stmt->execute([
                ':revisore' => $id_revisore,
                ':competenza' => $nome_competenza,
                ':livello' => $livello
            ]);
            $stmt->closeCursor();

            logEvent('competenza_aggiunta', "Competenza aggiunta: {$nome_competenza} (Livello {$livello})", $id_revisore);

            $successo = "Competenza aggiunta/aggiornata con successo!";
        } catch (PDOException $e) {
            $errore = "Errore: " . $e->getMessage();
        }
    }
}

// Elimina competenza
if (isset($_GET['elimina'])) {
    $id_competenza = intval($_GET['elimina']);
    try {
        $stmt = $db->prepare("DELETE FROM revisore_competenza WHERE id_utente = ? AND id_competenza = ?");
        $stmt->execute([$id_revisore, $id_competenza]);
        $successo = "Competenza eliminata";
        header("Refresh:1");
    } catch (PDOException $e) {
        $errore = "Errore eliminazione: " . $e->getMessage();
    }
}

// Recupera competenze del revisore
$stmt = $db->prepare("
    SELECT c.id_competenza, c.nome, rc.livello
    FROM revisore_competenza rc
    JOIN competenza c ON rc.id_competenza = c.id_competenza
    WHERE rc.id_utente = ?
    ORDER BY c.nome
");
$stmt->execute([$id_revisore]);
$mie_competenze = $stmt->fetchAll();

// Recupera tutte le competenze disponibili
$tutte_competenze = $db->query("SELECT * FROM competenza ORDER BY nome")->fetchAll();
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Le Mie Competenze - ESG Balance</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <nav class="navbar">
        <div class="nav-brand">ESG-BALANCE</div>
        <div class="nav-menu">
            <a href="dashboard.php">Dashboard</a>
            <a href="competenze.php" class="active">Le Mie Competenze</a>
            <a href="../auth/logout.php">Logout</a>
        </div>
        <div class="nav-user"><?php echo htmlspecialchars($_SESSION['username']); ?></div>
    </nav>

    <div class="container">
        <div class="section">
            <h1>Gestione Competenze</h1>

            <?php if ($successo): ?>
                <div class="alert alert-success"><?php echo htmlspecialchars($successo); ?></div>
            <?php endif; ?>

            <?php if ($errore): ?>
                <div class="alert alert-error"><?php echo htmlspecialchars($errore); ?></div>
            <?php endif; ?>

            <form method="POST" class="competenza-form">
                <h3>Aggiungi/Modifica Competenza</h3>

                <div class="form-row">
                    <div class="form-group" style="flex: 2;">
                        <label for="nome_competenza">Competenza</label>
                        <input type="text" id="nome_competenza" name="nome_competenza"
                               placeholder="Es: Risk Assessment" required list="competenze-list">
                        <datalist id="competenze-list">
                            <?php foreach ($tutte_competenze as $comp): ?>
                                <option value="<?php echo htmlspecialchars($comp['nome']); ?>">
                            <?php endforeach; ?>
                        </datalist>
                        <small>Seleziona da lista o scrivi una nuova competenza</small>
                    </div>

                    <div class="form-group">
                        <label for="livello">Livello (0-5)</label>
                        <select id="livello" name="livello" required>
                            <option value="">Seleziona...</option>
                            <option value="1">1 - Base</option>
                            <option value="2">2 - Elementare</option>
                            <option value="3">3 - Intermedio</option>
                            <option value="4">4 - Avanzato</option>
                            <option value="5">5 - Esperto</option>
                        </select>
                    </div>

                    <div class="form-group" style="align-self: flex-end;">
                        <button type="submit" name="aggiungi" class="btn btn-primary">Aggiungi</button>
                    </div>
                </div>
            </form>
        </div>

        <div class="section">
            <h2>Le Mie Competenze (<?php echo count($mie_competenze); ?>)</h2>

            <?php if (empty($mie_competenze)): ?>
                <p>Nessuna competenza registrata. Aggiungi la prima competenza!</p>
            <?php else: ?>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Competenza</th>
                            <th>Livello</th>
                            <th>Visualizzazione</th>
                            <th>Azioni</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($mie_competenze as $comp): ?>
                        <tr>
                            <td><strong><?php echo htmlspecialchars($comp['nome']); ?></strong></td>
                            <td>Livello <?php echo $comp['livello']; ?>/5</td>
                            <td>
                                <div class="livello-bar">
                                    <?php for ($i = 1; $i <= 5; $i++): ?>
                                        <span class="<?php echo $i <= $comp['livello'] ? 'active' : ''; ?>">●</span>
                                    <?php endfor; ?>
                                </div>
                            </td>
                            <td>
                                <a href="?elimina=<?php echo $comp['id_competenza']; ?>"
                                   class="btn btn-sm btn-danger"
                                   onclick="return confirm('Eliminare questa competenza?')">Elimina</a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>

        <div class="section">
            <h3>Competenze Suggerite</h3>
            <div class="competenze-suggerite">
                <span class="badge-comp">Risk Assessment</span>
                <span class="badge-comp">Sostenibilità ambientale</span>
                <span class="badge-comp">Audit finanziario</span>
                <span class="badge-comp">Compliance normativa</span>
                <span class="badge-comp">Analisi dati ESG</span>
                <span class="badge-comp">ISO 14001</span>
                <span class="badge-comp">GRI Standards</span>
                <span class="badge-comp">CSRD</span>
                <span class="badge-comp">Carbon Footprint</span>
                <span class="badge-comp">Social Impact</span>
            </div>
        </div>
    </div>

    <style>
    .competenza-form { background: #f8f9fa; padding: 1.5rem; border-radius: 8px; margin-bottom: 2rem; }
    .form-row { display: flex; gap: 1rem; align-items: flex-start; }
    .form-row .form-group { margin-bottom: 0; }
    small { display: block; color: #7f8c8d; margin-top: 0.25rem; }
    .livello-bar { font-size: 1.3rem; }
    .livello-bar span { color: #ddd; margin: 0 2px; }
    .livello-bar span.active { color: #f39c12; }
    .competenze-suggerite { display: flex; flex-wrap: wrap; gap: 0.5rem; }
    .badge-comp { display: inline-block; padding: 0.5rem 1rem; background: #ecf0f1;
                  border-radius: 20px; font-size: 0.9rem; cursor: pointer; }
    .badge-comp:hover { background: #3498db; color: white; }
    </style>

    <script>
    document.querySelectorAll('.badge-comp').forEach(badge => {
        badge.addEventListener('click', function() {
            document.getElementById('nome_competenza').value = this.textContent;
        });
    });
    </script>
</body>
</html>