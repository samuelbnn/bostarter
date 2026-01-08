<?php
// auth/register.php
session_start();
require_once '../config/database.php';
require_once '../config/mongodb.php';

$errore = '';
$successo = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $password_confirm = $_POST['password_confirm'] ?? '';
    $cf = strtoupper(trim($_POST['codice_fiscale'] ?? ''));
    $data_nascita = $_POST['data_nascita'] ?? '';
    $luogo_nascita = trim($_POST['luogo_nascita'] ?? '');
    $tipo_utente = $_POST['tipo_utente'] ?? '';
    $email = trim($_POST['email'] ?? '');

    // Upload CV per responsabili aziendali
    $cv_pdf = null;
    if ($tipo_utente === 'responsabile_aziendale' && isset($_FILES['cv_pdf'])) {
        $target_dir = "../uploads/cv/";
        if (!file_exists($target_dir)) {
            mkdir($target_dir, 0777, true);
        }

        $file_extension = pathinfo($_FILES['cv_pdf']['name'], PATHINFO_EXTENSION);
        if (strtolower($file_extension) === 'pdf') {
            $cv_pdf = uniqid() . '_' . basename($_FILES['cv_pdf']['name']);
            $target_file = $target_dir . $cv_pdf;

            if (!move_uploaded_file($_FILES['cv_pdf']['tmp_name'], $target_file)) {
                $errore = "Errore durante l'upload del CV";
            }
        } else {
            $errore = "Il CV deve essere in formato PDF";
        }
    }

    // Validazioni
    if (empty($errore)) {
        if (strlen($username) < 4) {
            $errore = "L'username deve contenere almeno 4 caratteri";
        } elseif (strlen($password) < 6) {
            $errore = "La password deve contenere almeno 6 caratteri";
        } elseif ($password !== $password_confirm) {
            $errore = "Le password non corrispondono";
        } elseif (!preg_match('/^[A-Z]{6}\d{2}[A-Z]\d{2}[A-Z]\d{3}[A-Z]$/', $cf)) {
            $errore = "Codice fiscale non valido";
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errore = "Email non valida";
        }
    }

    if (empty($errore)) {
        try {
            $db = getDB();

            // Chiama stored procedure per registrazione
            $stmt = $db->prepare("CALL sp_registra_utente(:username, :password, :cf, :data_nascita,
                                  :luogo_nascita, :tipo_utente, :email, :cv_pdf, @p_id_utente)");
            $stmt->execute([
                ':username' => $username,
                ':password' => $password,
                ':cf' => $cf,
                ':data_nascita' => $data_nascita,
                ':luogo_nascita' => $luogo_nascita,
                ':tipo_utente' => $tipo_utente,
                ':email' => $email,
                ':cv_pdf' => $cv_pdf
            ]);
            $stmt->closeCursor();

            // Recupera ID utente creato
            $result = $db->query("SELECT @p_id_utente AS id_utente")->fetch();

            if ($result['id_utente'] > 0) {
                $successo = "Registrazione completata! Effettua il login per accedere.";

                // Log evento su MongoDB
                logEvent('registrazione', "Nuovo utente registrato: {$username} ({$tipo_utente})",
                         $result['id_utente']);
            } else {
                $errore = "Errore durante la registrazione. Username o CF già esistenti.";
            }
        } catch (PDOException $e) {
            if (strpos($e->getMessage(), 'Duplicate entry') !== false) {
                $errore = "Username, codice fiscale o email già registrati";
            } else {
                $errore = "Errore durante la registrazione: " . $e->getMessage();
            }
            error_log($e->getMessage());
        }
    }
}
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registrazione - ESG Balance</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <div class="register-container">
        <div class="register-box">
            <h1>Registrazione</h1>

            <?php if ($errore): ?>
                <div class="alert alert-error"><?php echo htmlspecialchars($errore); ?></div>
            <?php endif; ?>

            <?php if ($successo): ?>
                <div class="alert alert-success"><?php echo htmlspecialchars($successo); ?></div>
                <a href="login.php" class="btn btn-primary">Vai al Login</a>
            <?php else: ?>
                <form method="POST" action="" enctype="multipart/form-data">
                    <div class="form-group">
                        <label for="username">Username *</label>
                        <input type="text" id="username" name="username" required
                               value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>">
                    </div>

                    <div class="form-group">
                        <label for="password">Password *</label>
                        <input type="password" id="password" name="password" required minlength="6">
                    </div>

                    <div class="form-group">
                        <label for="password_confirm">Conferma Password *</label>
                        <input type="password" id="password_confirm" name="password_confirm" required>
                    </div>

                    <div class="form-group">
                        <label for="codice_fiscale">Codice Fiscale *</label>
                        <input type="text" id="codice_fiscale" name="codice_fiscale" required
                               maxlength="16" style="text-transform: uppercase"
                               value="<?php echo htmlspecialchars($_POST['codice_fiscale'] ?? ''); ?>">
                    </div>

                    <div class="form-group">
                        <label for="email">Email *</label>
                        <input type="email" id="email" name="email" required
                               value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
                    </div>

                    <div class="form-group">
                        <label for="data_nascita">Data di Nascita *</label>
                        <input type="date" id="data_nascita" name="data_nascita" required
                               value="<?php echo htmlspecialchars($_POST['data_nascita'] ?? ''); ?>">
                    </div>

                    <div class="form-group">
                        <label for="luogo_nascita">Luogo di Nascita *</label>
                        <input type="text" id="luogo_nascita" name="luogo_nascita" required
                               value="<?php echo htmlspecialchars($_POST['luogo_nascita'] ?? ''); ?>">
                    </div>

                    <div class="form-group">
                        <label for="tipo_utente">Tipo Utente *</label>
                        <select id="tipo_utente" name="tipo_utente" required onchange="toggleCVUpload()">
                            <option value="">Seleziona...</option>
                            <option value="revisore_esg">Revisore ESG</option>
                            <option value="responsabile_aziendale">Responsabile Aziendale</option>
                        </select>
                    </div>

                    <div class="form-group" id="cv_upload_group" style="display: none;">
                        <label for="cv_pdf">Curriculum Vitae (PDF)</label>
                        <input type="file" id="cv_pdf" name="cv_pdf" accept=".pdf">
                    </div>

                    <button type="submit" class="btn btn-primary">Registrati</button>
                </form>

                <p class="text-center mt-3">
                    Hai già un account? <a href="login.php">Accedi</a>
                </p>
            <?php endif; ?>
        </div>
    </div>

    <script>
        function toggleCVUpload() {
            const tipoUtente = document.getElementById('tipo_utente').value;
            const cvGroup = document.getElementById('cv_upload_group');
            cvGroup.style.display = (tipoUtente === 'responsabile_aziendale') ? 'block' : 'none';
        }
    </script>
</body>
</html>