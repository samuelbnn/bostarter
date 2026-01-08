<?php
// config/database.php - Configurazione connessione MySQL

class Database {
    private $host = "localhost";
    private $db_name = "esg_balance";
    private $username = "root";
    private $password = "";
    private $conn;

    public function getConnection() {
        $this->conn = null;

        try {
            $this->conn = new PDO(
                "mysql:host=" . $this->host . ";dbname=" . $this->db_name . ";charset=utf8mb4",
                $this->username,
                $this->password
            );
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        } catch(PDOException $e) {
            echo "Errore di connessione: " . $e->getMessage();
            die();
        }

        return $this->conn;
    }

    public function closeConnection() {
        $this->conn = null;
    }
}

// Funzione helper per ottenere connessione
function getDB() {
    $database = new Database();
    return $database->getConnection();
}
?>