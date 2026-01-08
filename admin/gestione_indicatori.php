<?php
// admin/gestione_indicatori.php
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

// Aggiungi indicatore
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['aggiungi'])) {
    $nome = trim($_POST['nome'] ?? '');
    $rilevanza = intval($_POST['rilevanza'] ?? 5);
    $tipo = $_POST['tipo_indicatore'] ?? '';
    $codice_normativa = trim($_POST['codice_normativa'] ?? '');
    $ambito_sociale = trim($_POST['ambito_sociale'] ?? '');
    $frequenza = trim($_POST['frequenza_rilevazione'] ?? '');

    // Upload immagine
    $immagine = null;
    if (isset($_FILES['immagine']) && $_FILES['immagine']['error'] === UPLOAD_ERR_OK) {
        $allowed = ['jpg', 'jpeg', 'png', 'gif'];
        $file_ext = strtolower(pathinfo($_FILES['immagine']['name'], PATHINFO_EXTENSION));

        if (in_array($file_ext, $allowed)) {
            $target_dir = "../uploads/indicatori/";
            if (!file_exists($target_dir)) {
                mkdir($target_dir, 0777, true);
            }
            $immagine = uniqid() . '.' . $file_ext;
            move_uploaded_file($_FILES['immagine']['tmp_name'], $target_dir . $immagine);
        }
    }

    if (empty($nome) || empty($tipo)) {
        $errore = "Nome e tipo indicatore sono obbligatori";
    } else {
        try {
            $stmt = $db->prepare("CALL sp_aggiungi_indicatore_esg(:nome, :immagine, :rilevanza,
                                  :tipo, :codice, :ambito, :frequenza, @id)");
            $stmt->execute([
                ':nome' => $nome,
                ':immagine' => $immagine,
                ':rilevanza' => $rilevanza,
                ':tipo' => $tipo,
                ':codice' => $codice_normativa,
                ':ambito' => $ambito_sociale,
                ':frequenza' => $frequenza
            ]);
            $stmt->closeCursor();

            logEvent('indicatore_aggiunto', "Nuovo indicatore ESG: {$nome} ({$tipo})", $_SESSION['id_utente']);
            $successo = "Indicatore ESG aggiunto con successo!";
        } catch (PDOException $e) {
            $errore = "Errore: " . $e->getMessage();
        }
    }
}

// Recupera indicatori
$indicatori = $db->query("
    SELECT i.*,
           ia.codice_normativa,
           iss.ambito_sociale, iss.frequenza_rilevazione
    FROM indicatore_esg i
    LEFT JOIN indicatore_ambientale ia ON i.id_indicatore = ia.id_indicatore
    LEFT JOIN indicatore_sociale iss ON i.id_indicatore = iss.id_indicatore
    ORDER BY i.tipo_indicatore, i.nome
")->fetchAll();
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <title>Gestione Indicatori ESG - ESG Balance</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <nav class="navbar">
        <div class="nav-brand">ESG-BALANCE</div>
        <div class="nav-menu">
            <a href="dashboard.php">Dashboard</a>
            <a href="gestione_template.php">Template Bilanci</a>
            <a href="gestione_indicatori.php" class="active">Indicatori ESG</a>
            <a href="assegna_revisore.php">Assegna Revisori</a>
            <a href="../auth/logout.php">Logout</a>
        </div>
        <div class="nav-user"><?php echo htmlspecialchars($_SESSION['username']); ?></div>
    </nav>

    <div class="container">
        <div class="section">
            <h1>Gestione Indicatori ESG</h1>

            <?php if ($successo): ?>
                <div class="alert alert-success"><?php echo htmlspecialchars($successo); ?></div>
            <?php endif; ?>

            <?php if ($errore): ?>
                <div class="alert alert-error"><?php echo htmlspecialchars($errore); ?></div>
            <?php endif; ?>

            <form method="POST" enctype="multipart/form-data" class="indicatore-form">
                <h3>Aggiungi Nuovo Indicatore ESG</h3>

                <div class="form-grid">
                    <div class="form-group">
                        <label for="nome">Nome Indicatore *</label>
                        <input type="text" id="nome" name="nome" required
                               placeholder="Es: Consumo energia elettrica">
                    </div>

                    <div class="form-group">
                        <label for="tipo_indicatore">Tipo *</label>
                        <select id="tipo_indicatore" name="tipo_indicatore" required onchange="toggleFields()">
                            <option value="">Seleziona...</option>
                            <option value="ambientale">Ambientale</option>
                            <option value="sociale">Sociale</option>
                            <option value="generico">Generico</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="rilevanza">Rilevanza (0-10)</label>
                        <input type="number" id="rilevanza" name="rilevanza" min="0" max="10" value="5">
                    </div>

                    <div class="form-group">
                        <label for="immagine">Immagine</label>
                        <input type="file" id="immagine" name="immagine" accept="image/*">
                    </div>
                </div>

                <div id="campi_ambientale" style="display: none;">
                    <div class="form-group">
                        <label for="codice_normativa">Codice Normativa</label>
                        <input type="text" id="codice_normativa" name="codice_normativa"
                               placeholder="Es: ISO-50001">
                    </div>
                </div>

                <div id="campi_sociale" style="display: none;">
                    <div class="form-grid">
                        <div class="form-group">
                            <label for="ambito_sociale">Ambito Sociale</label>
                            <input type="text" id="ambito_sociale" name="ambito_sociale"
                                   placeholder="Es: Sviluppo del personale">
                        </div>
                        <div class="form-group">
                            <label for="frequenza_rilevazione">Frequenza Rilevazione</label>
                            <select id="frequenza_rilevazione" name="frequenza_rilevazione">
                                <option value="">Seleziona...</option>
                                <option value="Mensile">Mensile</option>
                                <option value="Trimestrale">Trimestrale</option>
                                <option value="Semestrale">Semestrale</option>
                                <option value="Annuale">Annuale</option>
                            </select>
                        </div>
                    </div>
                </div>

                <button type="submit" name="aggiungi" class="btn btn-primary">Aggiungi Indicatore</button>
            </form>
        </div>

        <div class="section">
            <h2>Indicatori ESG (<?php echo count($indicatori); ?>)</h2>

            <?php
            $tipi = ['ambientale' => '🌱 Ambientali', 'sociale' => '👥 Sociali', 'generico' => '📊 Generici'];
            foreach ($tipi as $tipo_key => $tipo_label):
                $ind_tipo = array_filter($indicatori, fn($i) => $i['tipo_indicatore'] === $tipo_key);
                if (empty($ind_tipo)) continue;
            ?>
            <h3><?php echo $tipo_label; ?> (<?php echo count($ind_tipo); ?>)</h3>
            <table class="data-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Nome</th>
                        <th>Rilevanza</th>
                        <?php if ($tipo_key === 'ambientale'): ?>
                            <th>Codice Normativa</th>
                        <?php elseif ($tipo_key === 'sociale'): ?>
                            <th>Ambito</th>
                            <th>Frequenza</th>
                        <?php endif; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($ind_tipo as $ind): ?>
                    <tr>
                        <td><?php echo $ind['id_indicatore']; ?></td>
                        <td><strong><?php echo htmlspecialchars($ind['nome']); ?></strong></td>
                        <td>
                            <div class="rilevanza-bar">
                                <?php for ($i = 1; $i <= 10; $i++): ?>
                                    <span class="<?php echo $i <= $ind['rilevanza'] ? 'active' : ''; ?>">●</span>
                                <?php endfor; ?>
                            </div>
                        </td>
                        <?php if ($tipo_key === 'ambientale'): ?>
                            <td><?php echo htmlspecialchars($ind['codice_normativa'] ?? 'N/D'); ?></td>
                        <?php elseif ($tipo_key === 'sociale'): ?>
                            <td><?php echo htmlspecialchars($ind['ambito_sociale'] ?? 'N/D'); ?></td>
                            <td><?php echo htmlspecialchars($ind['frequenza_rilevazione'] ?? 'N/D'); ?></td>
                        <?php endif; ?>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php endforeach; ?>
        </div>
    </div>

    <style>
    .indicatore-form { background: #f8f9fa; padding: 1.5rem; border-radius: 8px; margin-bottom: 2rem; }
    .form-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; }
    .rilevanza-bar { font-size: 1rem; }
    .rilevanza-bar span { color: #ddd; margin: 0 1px; }
    .rilevanza-bar span.active { color: #27ae60; }
    </style>

    <script>
    function toggleFields() {
        const tipo = document.getElementById('tipo_indicatore').value;
        document.getElementById('campi_ambientale').style.display = tipo === 'ambientale' ? 'block' : 'none';
        document.getElementById('campi_sociale').style.display = tipo === 'sociale' ? 'block' : 'none';
    }
    </script>
</body>
</html>