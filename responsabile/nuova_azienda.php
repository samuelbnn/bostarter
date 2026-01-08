<?php
// responsabile/nuova_azienda.php
session_start();
require_once '../config/database.php';
require_once '../config/mongodb.php';

if (!isset($_SESSION['id_utente']) || $_SESSION['tipo_utente'] !== 'responsabile_aziendale') {
    header('Location: ../auth/login.php');
    exit();
}

$db = getDB();
$errore = '';
$successo = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome = trim($_POST['nome'] ?? '');
    $ragione_sociale = trim($_POST['ragione_sociale'] ?? '');
    $piva = trim($_POST['partita_iva'] ?? '');
    $settore = trim($_POST['settore'] ?? '');
    $nr_dipendenti = intval($_POST['nr_dipendenti'] ?? 0);

    // Upload logo
    $logo = null;
    if (isset($_FILES['logo']) && $_FILES['logo']['error'] === UPLOAD_ERR_OK) {
        $allowed = ['jpg', 'jpeg', 'png', 'gif'];
        $file_ext = strtolower(pathinfo($_FILES['logo']['name'], PATHINFO_EXTENSION));

        if (in_array($file_ext, $allowed)) {
            $target_dir = "../uploads/logos/";
            if (!file_exists($target_dir)) {
                mkdir($target_dir, 0777, true);
            }

            $logo = uniqid() . '_' . basename($_FILES['logo']['name']);
            $target_file = $target_dir . $logo;

            if (!move_uploaded_file($_FILES['logo']['tmp_name'], $target_file)) {
                $errore = "Errore upload logo";
            }
        } else {
            $errore = "Formato logo non valido. Usa JPG, PNG o GIF";
        }
    }

    if (empty($errore)) {
        if (empty($nome) || empty($ragione_sociale) || empty($piva)) {
            $errore = "Nome, ragione sociale e P.IVA sono obbligatori";
        } elseif (!preg_match('/^\d{11}$/', $piva)) {
            $errore = "Partita IVA non valida (deve essere 11 cifre)";
        }
    }

    if (empty($errore)) {
        try {
            $stmt = $db->prepare("CALL sp_registra_azienda(:nome, :ragione, :piva, :settore,
                                  :dipendenti, :logo, :responsabile, @id_azienda)");
            $stmt->execute([
                ':nome' => $nome,
                ':ragione' => $ragione_sociale,
                ':piva' => $piva,
                ':settore' => $settore,
                ':dipendenti' => $nr_dipendenti,
                ':logo' => $logo,
                ':responsabile' => $_SESSION['id_utente']
            ]);
            $stmt->closeCursor();

            $result = $db->query("SELECT @id_azienda AS id")->fetch();

            if ($result['id'] > 0) {
                logEvent('azienda_creata', "Nuova azienda: {$nome} (P.IVA: {$piva})",
                         $_SESSION['id_utente'], ['id_azienda' => $result['id']]);

                $successo = "Azienda registrata con successo!";
            } else {
                $errore = "Errore durante la registrazione";
            }
        } catch (PDOException $e) {
            if (strpos($e->getMessage(), 'Duplicate') !== false) {
                $errore = "Ragione sociale o P.IVA già registrati";
            } else {
                $errore = "Errore: " . $e->getMessage();
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nuova Azienda - ESG Balance</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <nav class="navbar">
        <div class="nav-brand">ESG-BALANCE</div>
        <div class="nav-menu">
            <a href="dashboard.php">Dashboard</a>
            <a href="aziende.php">Le Mie Aziende</a>
            <a href="nuova_azienda.php" class="active">+ Nuova Azienda</a>
            <a href="../auth/logout.php">Logout</a>
        </div>
        <div class="nav-user"><?php echo htmlspecialchars($_SESSION['username']); ?></div>
    </nav>

    <div class="container">
        <div class="section">
            <h1>Registra Nuova Azienda</h1>

            <?php if ($errore): ?>
                <div class="alert alert-error"><?php echo htmlspecialchars($errore); ?></div>
            <?php endif; ?>

            <?php if ($successo): ?>
                <div class="alert alert-success">
                    <?php echo htmlspecialchars($successo); ?>
                    <br><a href="dashboard.php">Torna alla dashboard</a>
                </div>
            <?php else: ?>
                <form method="POST" enctype="multipart/form-data">
                    <div class="form-group">
                        <label for="nome">Nome Azienda *</label>
                        <input type="text" id="nome" name="nome" required
                               value="<?php echo htmlspecialchars($_POST['nome'] ?? ''); ?>">
                    </div>

                    <div class="form-group">
                        <label for="ragione_sociale">Ragione Sociale *</label>
                        <input type="text" id="ragione_sociale" name="ragione_sociale" required
                               value="<?php echo htmlspecialchars($_POST['ragione_sociale'] ?? ''); ?>">
                    </div>

                    <div class="form-group">
                        <label for="partita_iva">Partita IVA * (11 cifre)</label>
                        <input type="text" id="partita_iva" name="partita_iva" required
                               maxlength="11" pattern="\d{11}"
                               value="<?php echo htmlspecialchars($_POST['partita_iva'] ?? ''); ?>">
                    </div>

                    <div class="form-group">
                        <label for="settore">Settore</label>
                        <select id="settore" name="settore">
                            <option value="">Seleziona...</option>
                            <option value="Manifatturiero">Manifatturiero</option>
                            <option value="Servizi">Servizi</option>
                            <option value="Tecnologia">Tecnologia</option>
                            <option value="Alimentare">Alimentare</option>
                            <option value="Energia">Energia</option>
                            <option value="Trasporti">Trasporti</option>
                            <option value="Commercio">Commercio</option>
                            <option value="Altro">Altro</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="nr_dipendenti">Numero Dipendenti</label>
                        <input type="number" id="nr_dipendenti" name="nr_dipendenti" min="0"
                               value="<?php echo htmlspecialchars($_POST['nr_dipendenti'] ?? '0'); ?>">
                    </div>

                    <div class="form-group">
                        <label for="logo">Logo Aziendale (JPG, PNG, GIF)</label>
                        <input type="file" id="logo" name="logo" accept="image/*">
                    </div>

                    <button type="submit" class="btn btn-primary">Registra Azienda</button>
                    <a href="dashboard.php" class="btn">Annulla</a>
                </form>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>