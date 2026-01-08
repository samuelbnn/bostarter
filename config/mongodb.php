<?php
// config/mongodb.php - Configurazione connessione MongoDB

require_once __DIR__ . '/../vendor/autoload.php'; // Se usi Composer

class MongoDB_Connection {
    private $host = "localhost";
    private $port = "27017";
    private $db_name = "esg_balance_logs";
    private $client;
    private $database;

    public function __construct() {
        try {
            $this->client = new MongoDB\Client("mongodb://{$this->host}:{$this->port}");
            $this->database = $this->client->{$this->db_name};
        } catch (Exception $e) {
            echo "Errore connessione MongoDB: " . $e->getMessage();
            die();
        }
    }

    public function getDatabase() {
        return $this->database;
    }

    public function getCollection($collection_name) {
        return $this->database->{$collection_name};
    }
}

// Funzione per registrare evento
function logEvent($tipo_evento, $descrizione, $id_utente = null, $dati_extra = []) {
    try {
        $mongo = new MongoDB_Connection();
        $collection = $mongo->getCollection('eventi');

        $documento = [
            'tipo_evento' => $tipo_evento,
            'descrizione' => $descrizione,
            'timestamp' => new MongoDB\BSON\UTCDateTime(),
            'data_formattata' => date('Y-m-d H:i:s'),
            'id_utente' => $id_utente,
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'Unknown',
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown'
        ];

        // Aggiungi dati extra se presenti
        if (!empty($dati_extra)) {
            $documento['dati'] = $dati_extra;
        }

        $collection->insertOne($documento);
        return true;
    } catch (Exception $e) {
        error_log("Errore log MongoDB: " . $e->getMessage());
        return false;
    }
}

// Funzione per recuperare eventi recenti
function getRecentEvents($limit = 50, $tipo = null) {
    try {
        $mongo = new MongoDB_Connection();
        $collection = $mongo->getCollection('eventi');

        $filter = [];
        if ($tipo) {
            $filter['tipo_evento'] = $tipo;
        }

        $options = [
            'sort' => ['timestamp' => -1],
            'limit' => $limit
        ];

        return $collection->find($filter, $options)->toArray();
    } catch (Exception $e) {
        error_log("Errore recupero eventi: " . $e->getMessage());
        return [];
    }
}
?>