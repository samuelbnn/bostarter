<?php
// index.php
session_start();

// Se già loggato, redirect alla dashboard appropriata
if (isset($_SESSION['id_utente'])) {
    switch ($_SESSION['tipo_utente']) {
        case 'amministratore':
            header('Location: admin/dashboard.php');
            break;
        case 'revisore_esg':
            header('Location: revisore/dashboard.php');
            break;
        case 'responsabile_aziendale':
            header('Location: responsabile/dashboard.php');
            break;
    }
    exit();
}
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ESG-BALANCE - Piattaforma Gestione Bilanci ESG</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <div class="landing-container">
        <nav class="landing-nav">
            <div class="nav-brand">ESG-BALANCE</div>
            <div class="landing-menu">
                <a href="auth/login.php" class="btn">Accedi</a>
                <a href="auth/register.php" class="btn btn-primary">Registrati</a>
            </div>
        </nav>

        <div class="hero-section">
            <div class="hero-content">
                <h1>Gestisci i tuoi Bilanci ESG in modo Professionale</h1>
                <p class="hero-subtitle">
                    La piattaforma completa per la gestione, revisione e monitoraggio
                    dei bilanci economico-finanziari integrati con indicatori di sostenibilità ESG
                </p>
                <div class="hero-buttons">
                    <a href="auth/register.php" class="btn btn-primary btn-large">Inizia Ora</a>
                    <a href="auth/login.php" class="btn btn-large">Accedi</a>
                </div>
            </div>

            <div class="hero-image">
                <div class="feature-box">
                    <h3>📊 Bilanci Integrati</h3>
                    <p>Combina dati finanziari e indicatori ESG</p>
                </div>
                <div class="feature-box">
                    <h3>✅ Revisione Professionale</h3>
                    <p>Sistema di revisione multi-revisore</p>
                </div>
                <div class="feature-box">
                    <h3>📈 Statistiche in Real-Time</h3>
                    <p>Monitora le performance ESG</p>
                </div>
            </div>
        </div>

        <div class="features-section">
            <h2>Funzionalità Principali</h2>

            <div class="features-grid">
                <div class="feature-card">
                    <div class="feature-icon">🏢</div>
                    <h3>Per Responsabili Aziendali</h3>
                    <ul>
                        <li>Registrazione aziende</li>
                        <li>Creazione bilanci di esercizio</li>
                        <li>Collegamento indicatori ESG</li>
                        <li>Monitoraggio stato revisioni</li>
                    </ul>
                </div>

                <div class="feature-card">
                    <div class="feature-icon">🔍</div>
                    <h3>Per Revisori ESG</h3>
                    <ul>
                        <li>Gestione competenze</li>
                        <li>Revisione bilanci</li>
                        <li>Inserimento note e rilievi</li>
                        <li>Emissione giudizi</li>
                    </ul>
                </div>

                <div class="feature-card">
                    <div class="feature-icon">⚙️</div>
                    <h3>Per Amministratori</h3>
                    <ul>
                        <li>Gestione template bilanci</li>
                        <li>Configurazione indicatori ESG</li>
                        <li>Assegnazione revisori</li>
                        <li>Monitoraggio sistema</li>
                    </ul>
                </div>
            </div>
        </div>

        <div class="cta-section">
            <h2>Pronto a Iniziare?</h2>
            <p>Registrati gratuitamente e inizia a gestire i tuoi bilanci ESG</p>
            <a href="auth/register.php" class="btn btn-primary btn-large">Registrati Ora</a>
        </div>

        <footer class="landing-footer">
            <p>&copy; 2024-2026 ESG-BALANCE. Progetto Database - Università di Bologna</p>
        </footer>
    </div>

    <style>
    .landing-container { min-height: 100vh; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); }

    .landing-nav { display: flex; justify-content: space-between; align-items: center;
                   padding: 1.5rem 5%; background: rgba(255,255,255,0.1); backdrop-filter: blur(10px); }
    .landing-nav .nav-brand { color: white; font-size: 1.8rem; font-weight: bold; }
    .landing-menu { display: flex; gap: 1rem; }

    .hero-section { display: grid; grid-template-columns: 1fr 1fr; gap: 3rem;
                     padding: 5rem 5%; align-items: center; }
    .hero-content { color: white; }
    .hero-content h1 { font-size: 3rem; margin-bottom: 1rem; line-height: 1.2; }
    .hero-subtitle { font-size: 1.2rem; margin-bottom: 2rem; opacity: 0.9; }
    .hero-buttons { display: flex; gap: 1rem; }
    .btn-large { padding: 1rem 2rem; font-size: 1.1rem; }

    .hero-image { display: flex; flex-direction: column; gap: 1rem; }
    .feature-box { background: white; padding: 1.5rem; border-radius: 12px;
                   box-shadow: 0 10px 30px rgba(0,0,0,0.2); }
    .feature-box h3 { margin-bottom: 0.5rem; color: #2c3e50; }
    .feature-box p { color: #7f8c8d; }

    .features-section { padding: 5rem 5%; background: white; }
    .features-section h2 { text-align: center; font-size: 2.5rem; margin-bottom: 3rem; color: #2c3e50; }
    .features-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 2rem; }
    .feature-card { background: #f8f9fa; padding: 2rem; border-radius: 12px;
                    box-shadow: 0 4px 6px rgba(0,0,0,0.1); }
    .feature-icon { font-size: 3rem; margin-bottom: 1rem; }
    .feature-card h3 { color: #2c3e50; margin-bottom: 1rem; }
    .feature-card ul { list-style: none; padding: 0; }
    .feature-card li { padding: 0.5rem 0; color: #7f8c8d; }
    .feature-card li:before { content: "✓ "; color: #27ae60; font-weight: bold; }

    .cta-section { padding: 5rem 5%; text-align: center;
                    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; }
    .cta-section h2 { font-size: 2.5rem; margin-bottom: 1rem; }
    .cta-section p { font-size: 1.2rem; margin-bottom: 2rem; opacity: 0.9; }

    .landing-footer { padding: 2rem; text-align: center; background: #2c3e50; color: white; }

    @media (max-width: 768px) {
        .hero-section { grid-template-columns: 1fr; padding: 3rem 5%; }
        .hero-content h1 { font-size: 2rem; }
        .features-grid { grid-template-columns: 1fr; }
    }
    </style>
</body>
</html>