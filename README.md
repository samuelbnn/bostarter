# ESG-BALANCE

## Descrizione del Progetto

**ESG-BALANCE** è una piattaforma web per la gestione integrata di bilanci economico-finanziari e indicatori di sostenibilità ESG (Environmental, Social and Governance). Il sistema supporta la registrazione di aziende, la creazione di bilanci, il collegamento con indicatori ambientali e sociali, e un processo di revisione multi-revisore.

**Corso**: Basi di Dati - A.A. 2025/2026  
**CdS**: Informatica per il Management  
**Docente**: Prof. Marco Di Felice  
**Università**: Università di Bologna

---

## Caratteristiche Principali

-  **Gestione Multi-Ruolo**: Amministratori, Revisori ESG, Responsabili Aziendali
-  **Database Relazionale**: MySQL con stored procedures, trigger e viste
-  **Database NoSQL**: MongoDB per logging eventi
-  **Sistema di Revisione**: Workflow completo con note e giudizi
-  **Indicatori ESG**: Gestione di indicatori ambientali e sociali
-  **Upload File**: Logo aziende, CV, immagini indicatori

---

## Requisiti di Sistema

### Software Necessario

1. **XAMPP** (versione 8.1 o superiore)
   - Include: Apache, PHP 8.1+, MySQL/MariaDB
   - Download: https://www.apachefriends.org/

2. **MongoDB** (versione 5.0 o superiore)
   - Download: https://www.mongodb.com/try/download/community
   - Oppure MongoDB Compass (interfaccia grafica)

3. **Composer** (per le dipendenze PHP)
   - Download: https://getcomposer.org/download/

### Estensioni PHP Richieste

Le seguenti estensioni devono essere abilitate in `php.ini`:
- `extension=pdo_mysql`
- `extension=mysqli`
- `extension=mongodb`

---

## Installazione

### 1. Clona o scarica il progetto

```bash
git clone https://github.com/tuo-username/esg-balance.git
cd esg-balance
```

Oppure scarica come ZIP ed estrai nella cartella `htdocs` di XAMPP:
```
C:\xampp\htdocs\esg-balance\  (Windows)
/Applications/XAMPP/htdocs/esg-balance/  (macOS)
/opt/lampp/htdocs/esg-balance/  (Linux)
```

### 2. Installa le dipendenze PHP

Apri il terminale nella cartella del progetto ed esegui:

```bash
composer install
```

Questo installerà:
- `mongodb/mongodb`: Driver MongoDB per PHP
- `psr/log`: Libreria di logging
- `symfony/polyfill-php85`: Polyfill per compatibilità

### 3. Configura il Database MySQL

#### 3.1 Avvia XAMPP
- Avvia **Apache** e **MySQL** dal pannello di controllo XAMPP

#### 3.2 Accedi a phpMyAdmin
- Apri il browser e vai su: http://localhost/phpmyadmin

#### 3.3 Crea il database
- Clicca su "Nuovo" nella barra laterale
- Nome database: `esg_balance`
- Collation: `utf8mb4_unicode_ci`
- Clicca "Crea"

#### 3.4 Importa lo schema

**Opzione A - Da phpMyAdmin:**
1. Seleziona il database `esg_balance`
2. Clicca sulla scheda "Importa"
3. Scegli il file `sql/schema.sql`
4. Clicca "Esegui"
5. Ripeti per `sql/stored_procedures.sql`, `sql/triggers.sql`, `sql/views.sql`

**Opzione B - Da terminale:**
```bash
# Windows (da C:\xampp\mysql\bin\)
mysql -u root -p esg_balance < sql/schema.sql
mysql -u root -p esg_balance < sql/stored_procedures.sql
mysql -u root -p esg_balance < sql/triggers.sql
mysql -u root -p esg_balance < sql/views.sql

# Linux/macOS
mysql -u root -p esg_balance < sql/schema.sql
mysql -u root -p esg_balance < sql/stored_procedures.sql
mysql -u root -p esg_balance < sql/triggers.sql
mysql -u root -p esg_balance < sql/views.sql
```

### 4. Configura MongoDB

#### 4.1 Avvia MongoDB

**Windows:**
```bash
# Avvia il servizio MongoDB
net start MongoDB
```

**Linux/macOS:**
```bash
# Avvia mongod
mongod --dbpath /path/to/data/directory
```

#### 4.2 Verifica la connessione
Il database MongoDB `esg_balance_logs` verrà creato automaticamente al primo evento.

### 5. Configura le credenziali del database

Modifica il file `config/database.php` se necessario:

```php
private $host = "localhost";
private $db_name = "esg_balance";
private $username = "root";
private $password = "";  // Inserisci la password di MySQL se configurata
```

Modifica il file `config/mongodb.php` se necessario:

```php
private $host = "localhost";
private $port = "27017";
private $db_name = "esg_balance_logs";
```

### 6. Crea le cartelle per gli upload

Il sistema creerà automaticamente le cartelle necessarie, ma puoi crearle manualmente:

```bash
mkdir -p uploads/logos
mkdir -p uploads/cv
mkdir -p uploads/indicatori
```

Assicurati che abbiano i permessi di scrittura:

**Linux/macOS:**
```bash
chmod -R 755 uploads/
```

**Windows:** Non necessario

---

## Avvio dell'Applicazione

### 1. Avvia i servizi

- **XAMPP**: Avvia Apache e MySQL
- **MongoDB**: Assicurati che il servizio sia attivo

### 2. Accedi all'applicazione

Apri il browser e vai su:
```
http://localhost/esg-balance/
```

### 3. Registra il primo utente amministratore

1. Clicca su "Registrati"
2. Compila il form con:
   - **Tipo Utente**: Amministratore
   - Username, password, email, dati personali
3. Una volta registrato, effettua il login

---

## Creazione Utenti Demo

Per testare tutte le funzionalità, crea almeno un utente per ogni ruolo:

### Amministratore
- Username: `admin`
- Password: `admin123`
- CF: `RSSMRA85M01H501Z`
- Tipo: Amministratore

### Revisore ESG
- Username: `revisore1`
- Password: `rev123`
- CF: `RSSGNN80A01H501K`
- Tipo: Revisore ESG

### Responsabile Aziendale
- Username: `responsabile1`
- Password: `resp123`
- CF: `RSSLCA90A01H501W`
- Tipo: Responsabile Aziendale
- Upload CV (file PDF)

---

## Guida all'Utilizzo

### Per Amministratori

1. **Dashboard**: Visualizza statistiche generali del sistema
2. **Template Bilanci**: Aggiungi voci contabili al template condiviso
   - Es: "Ricavi vendite", "Costo del personale", etc.
3. **Indicatori ESG**: Gestisci gli indicatori ambientali e sociali
   - Tipo: Ambientale (con codice normativa)
   - Tipo: Sociale (con ambito e frequenza)
   - Tipo: Generico
4. **Assegna Revisori**: Collega revisori ESG ai bilanci aziendali
5. **Log Eventi**: Visualizza tutti gli eventi registrati su MongoDB

### Per Revisori ESG

1. **Dashboard**: Visualizza bilanci assegnati e statistiche personali
2. **Competenze**: Dichiara le proprie competenze (nome + livello 1-5)
3. **Revisiona Bilancio**: 
   - Visualizza voci e indicatori ESG collegati
   - Aggiungi note su ogni voce
   - Emetti giudizio finale:
     - ✓ Approvazione
     - ⚠ Approvazione con rilievi
     - ✗ Respingimento

### Per Responsabili Aziendali

1. **Dashboard**: Visualizza aziende gestite e bilanci recenti
2. **Nuova Azienda**: Registra un'azienda con:
   - Nome, ragione sociale, P.IVA
   - Settore, numero dipendenti
   - Logo aziendale
3. **Nuovo Bilancio**: 
   - Seleziona l'azienda
   - Inserisci valori per le voci contabili
4. **Collega Indicatori ESG**:
   - Per ogni voce, aggiungi indicatori con:
     - Valore numerico
     - Fonte
     - Data rilevazione

---

## Struttura del Progetto

```
esg-balance/
│
├── admin/                      # Funzionalità amministratori
│   ├── dashboard.php
│   ├── gestione_template.php
│   ├── gestione_indicatori.php
│   ├── assegna_revisore.php
│   └── logs.php
│
├── auth/                       # Autenticazione
│   ├── login.php
│   ├── register.php
│   └── logout.php
│
├── config/                     # Configurazioni
│   ├── database.php           # Connessione MySQL
│   └── mongodb.php            # Connessione MongoDB
│
├── revisore/                   # Funzionalità revisori
│   ├── dashboard.php
│   ├── competenze.php
│   └── revisiona_bilancio.php
│
├── responsabile/               # Funzionalità responsabili
│   ├── dashboard.php
│   ├── nuova_azienda.php
│   ├── nuovo_bilancio.php
│   ├── dettaglio_azienda.php
│   └── modifica_bilancio.php
│
├── sql/                        # Script SQL
│   ├── schema.sql             # Schema database
│   ├── stored_procedures.sql  # Procedure
│   ├── triggers.sql           # Trigger
│   └── views.sql              # Viste
│
├── assets/
│   └── css/
│       └── style.css          # Stili CSS
│
├── uploads/                    # File caricati
│   ├── logos/
│   ├── cv/
│   └── indicatori/
│
├── vendor/                     # Dipendenze Composer
│
├── index.php                   # Home page
├── composer.json               # Dipendenze PHP
└── README.md                   # Questo file
```

---

## Configurazione Avanzata

### Modifica porta MySQL

Se MySQL non usa la porta standard (3306), modifica `config/database.php`:

```php
private $host = "localhost:3307";  // Esempio porta 3307
```

### Modifica porta MongoDB

Se MongoDB non usa la porta standard (27017), modifica `config/mongodb.php`:

```php
private $port = "27018";  // Esempio porta 27018
```

### Debug Mode

Per abilitare messaggi di errore dettagliati, aggiungi in `config/database.php`:

```php
error_reporting(E_ALL);
ini_set('display_errors', 1);
```

---

## Database

### Tabelle Principali

- **utente**: Utenti del sistema (generalizzazione)
- **email_utente**: Email degli utenti (1:N)
- **revisore_esg**: Specializzazione utenti revisori
- **responsabile_aziendale**: Specializzazione utenti responsabili
- **competenza**: Lista competenze
- **revisore_competenza**: Competenze dei revisori (N:M)
- **azienda**: Aziende registrate
- **voce_contabile**: Template voci di bilancio
- **bilancio_esercizio**: Bilanci aziendali
- **valore_voce_bilancio**: Valori delle voci nei bilanci
- **indicatore_esg**: Indicatori ESG (generalizzazione)
- **indicatore_ambientale**: Specializzazione indicatori ambientali
- **indicatore_sociale**: Specializzazione indicatori sociali
- **voce_indicatore**: Collegamento voci-indicatori (N:M)
- **revisione**: Assegnazione revisori a bilanci (N:M)
- **nota_revisore**: Note dei revisori
- **giudizio**: Giudizi complessivi

### Stored Procedures

1. `sp_registra_utente`: Registrazione nuovo utente
2. `sp_login_utente`: Autenticazione utente
3. `sp_registra_azienda`: Registrazione azienda
4. `sp_crea_bilancio`: Creazione bilancio
5. `sp_inserisci_valore_voce`: Inserimento valore voce
6. `sp_collega_indicatore_voce`: Collegamento indicatore a voce
7. `sp_assegna_revisore`: Assegnazione revisore
8. `sp_inserisci_nota_revisore`: Inserimento nota
9. `sp_inserisci_giudizio`: Inserimento giudizio
10. `sp_inserisci_competenza_revisore`: Gestione competenze
11. `sp_elimina_azienda`: Eliminazione azienda
12. `sp_aggiungi_voce_contabile`: Aggiunta voce template

### Trigger

1. **trg_stato_in_revisione**: Cambia stato a "in_revisione" quando assegnato revisore
2. **trg_aggiorna_stato_bilancio**: Cambia stato in "approvato"/"respinto" in base ai giudizi
3. **trg_aggiorna_affidabilita_revisore**: Aggiorna indice affidabilità revisore

### Viste

1. `v_numero_aziende`: Conta aziende registrate
2. `v_numero_revisori`: Conta revisori ESG
3. `v_affidabilita_aziende`: Calcola affidabilità per ogni azienda
4. `v_azienda_piu_affidabile`: Azienda con affidabilità massima
5. `v_classifica_bilanci_esg`: Bilanci ordinati per n° indicatori ESG
6. `v_dashboard_admin`: Dashboard statistiche amministratore
7. `v_statistiche_revisori`: Statistiche per revisore
8. `v_dettaglio_bilanci`: Dettagli bilanci con progressione revisione

---

## 📊 Collezione MongoDB

**Database**: `esg_balance_logs`  
**Collezione**: `eventi`

### Struttura Documento Evento

```json
{
  "_id": ObjectId("..."),
  "tipo_evento": "login",
  "descrizione": "Login utente: admin",
  "timestamp": ISODate("2026-01-29T10:30:00.000Z"),
  "data_formattata": "2026-01-29 10:30:00",
  "id_utente": 1,
  "ip_address": "127.0.0.1",
  "user_agent": "Mozilla/5.0...",
  "dati": {
    // Dati aggiuntivi opzionali
  }
}
```

### Tipi di Eventi Tracciati

- `login` / `logout` / `login_fallito`
- `registrazione`
- `azienda_creata`
- `bilancio_creato`
- `indicatore_collegato`
- `revisore_assegnato`
- `nota_aggiunta`
- `giudizio_emesso`
- `competenza_aggiunta`
- `voce_aggiunta`

---

## Risoluzione Problemi

### Errore: "Access denied for user 'root'@'localhost'"

**Soluzione**: Verifica username/password in `config/database.php`

### Errore: "MongoDB connection failed"

**Soluzione**: 
1. Verifica che MongoDB sia avviato: `mongo` o `mongosh`
2. Verifica porta in `config/mongodb.php`
3. Installa estensione PHP: `pecl install mongodb`

### Errore: "Call to undefined function MongoDB\..."

**Soluzione**: 
1. Esegui `composer install`
2. Verifica che `vendor/autoload.php` sia incluso

### Warning: "mkdir(): Permission denied"

**Soluzione**:
```bash
# Linux/macOS
sudo chmod -R 775 uploads/
sudo chown -R www-data:www-data uploads/

# Windows: esegui XAMPP come amministratore
```

### Le immagini non vengono caricate

**Soluzione**:
1. Verifica che le cartelle `uploads/` esistano
2. Controlla i permessi (755 o 775)
3. Verifica `upload_max_filesize` in `php.ini`

### Stored procedures non funzionano

**Soluzione**:
1. Verifica che siano state importate correttamente
2. Controlla con: `SHOW PROCEDURE STATUS WHERE Db = 'esg_balance';`
3. Re-importa `sql/stored_procedures.sql`

---

## Sicurezza

### Best Practices Implementate

 Password hashate con SHA2-256  
 Prepared statements per prevenire SQL injection  
 Validazione input lato server  
 Gestione sessioni PHP  
 Controllo ruoli utente su ogni pagina  
 Upload file con whitelist estensioni  

### Raccomandazioni per Produzione

 Usare HTTPS  
 Password più robuste (bcrypt invece di SHA2)  
 Rate limiting per login  
 CAPTCHA su registrazione  
 Sanitizzazione output HTML  
 Backup automatici database  

---

## Note Tecniche

- **PHP Version**: 8.1+
- **MySQL Version**: 5.7+ o MariaDB 10.3+
- **MongoDB Version**: 5.0+
- **Charset Database**: utf8mb4_unicode_ci
- **Storage Engine**: InnoDB (transazioni ACID)



---

