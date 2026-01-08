<?php
// admin/gestione_template.php
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

// Aggiungi voce contabile
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['aggiungi'])) {
    $nome = trim($_POST['nome'] ?? '');
    $descrizione = trim($_POST['descrizione'] ?? '');

    if (empty($nome)) {
        $errore = "Nome voce obbligatorio";
    } else {
        try {
            $stmt = $db->prepare("CALL sp_aggiungi_voce_contabile(:nome, :descrizione, @id)");
            $stmt->execute([':nome' => $nome, ':descrizione' => $descrizione]);
            $stmt->closeCursor();

            logEvent('voce_aggiunta', "Nuova voce contabile: {$nome}", $_SESSION['id_utente']);
            $successo = "Voce contabile aggiunta con successo!";
        } catch (PDOException $e) {
            $errore = "Errore: " . $e->getMessage();
        }
    }
}

// Recupera tutte le voci
$voci = $db->query("SELECT * FROM voce_contabile ORDER BY nome")->fetchAll();
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <title>Gestione Template - ESG Balance</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <nav class="navbar">
        <div class="nav-brand">ESG-BALANCE</div>
        <div class="nav-menu">
            <a href="dashboard.php">Dashboard</a>
            <a href="gestione_template.php" class="active">Template Bilanci</a>
            <a href="gestione_indicatori.php">Indicatori ESG</a>
            <a href="assegna_revisore.php">Assegna Revisori</a>
            <a href="../auth/logout.php">Logout</a>
        </div>
        <div class="nav-user"><?php echo htmlspecialchars($_SESSION['username']); ?></div>
    </nav>

    <div class="container">
        <div class="section">
            <h1>Gestione Template Bilanci</h1>

            <?php if ($successo): ?>
                <div class="alert alert-success"><?php echo htmlspecialchars($successo); ?></div>
            <?php endif; ?>

            <?php if ($errore): ?>
                <div class="alert alert-error"><?php echo htmlspecialchars($errore); ?></div>
            <?php endif; ?>

            <form method="POST" class="form-inline-box">
                <h3>Aggiungi Nuova Voce Contabile</h3>
                <div class="form-row">
                    <div class="form-group" style="flex: 1;">
                        <label for="nome">Nome Voce *</label>
                        <input type="text" id="nome" name="nome" required
                               placeholder="Es: Ricavi vendite">
                    </div>
                    <div class="form-group" style="flex: 2;">
                        <label for="descrizione">Descrizione</label>
                        <input type="text" id="descrizione" name="descrizione"
                               placeholder="Descrizione della voce contabile">
                    </div>
                    <div class="form-group" style="align-self: flex-end;">
                        <button type="submit" name="aggiungi" class="btn btn-primary">Aggiungi</button>
                    </div>
                </div>
            </form>
        </div>

        <div class="section">
            <h2>Voci Contabili Template (<?php echo count($voci); ?>)</h2>

            <table class="data-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Nome</th>
                        <th>Descrizione</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($voci as $voce): ?>
                    <tr>
                        <td><?php echo $voce['id_voce']; ?></td>
                        <td><strong><?php echo htmlspecialchars($voce['nome']); ?></strong></td>
                        <td><?php echo htmlspecialchars($voce['descrizione']); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <style>
    .form-inline-box { background: #f8f9fa; padding: 1.5rem; border-radius: 8px; margin-bottom: 2rem; }
    .form-row { display: flex; gap: 1rem; align-items: flex-start; }
    .form-row .form-group { margin-bottom: 0; }
    </style>
</body>
</html>