<?php
// auth/login.php
session_start();
require_once '../config/database.php';
require_once '../config/mongodb.php';

$errore = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($username) || empty($password)) {
        $errore = "Username e password sono obbligatori";
    } else {
        try {
            $db = getDB();

            // Chiama stored procedure per login
            $stmt = $db->prepare("CALL sp_login_utente(:username, :password)");
            $stmt->execute([
                ':username' => $username,
                ':password' => $password
            ]);

            $utente = $stmt->fetch();
            $stmt->closeCursor();

            if ($utente) {
                // Salva dati in sessione
                $_SESSION['id_utente'] = $utente['id_utente'];
                $_SESSION['username'] = $utente['username'];
                $_SESSION['tipo_utente'] = $utente['tipo_utente'];
                $_SESSION['codice_fiscale'] = $utente['codice_fiscale'];
                $_SESSION['email'] = $utente['email'];

                // Log evento su MongoDB
                logEvent('login', "Login utente: {$username}", $utente['id_utente']);

                // Redirect in base al tipo utente
                switch ($utente['tipo_utente']) {
                    case 'amministratore':
                        header('Location: ../admin/dashboard.php');
                        break;
                    case 'revisore_esg':
                        header('Location: ../revisore/dashboard.php');
                        break;
                    case 'responsabile_aziendale':
                        header('Location: ../responsabile/dashboard.php');
                        break;
                }
                exit();
            } else {
                $errore = "Credenziali non valide";
                logEvent('login_fallito', "Tentativo login fallito per: {$username}");
            }
        } catch (PDOException $e) {
            $errore = "Errore durante il login: " . $e->getMessage();
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
    <title>Login - ESG Balance</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <div class="login-container">
        <div class="login-box">
            <h1>ESG-BALANCE</h1>
            <h2>Accedi alla piattaforma</h2>

            <?php if ($errore): ?>
                <div class="alert alert-error"><?php echo htmlspecialchars($errore); ?></div>
            <?php endif; ?>

            <form method="POST" action="">
                <div class="form-group">
                    <label for="username">Username</label>
                    <input type="text" id="username" name="username" required
                           value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>">
                </div>

                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" required>
                </div>

                <button type="submit" class="btn btn-primary">Accedi</button>
            </form>

            <p class="text-center mt-3">
                Non hai un account? <a href="register.php">Registrati</a>
            </p>
        </div>
    </div>
</body>
</html>