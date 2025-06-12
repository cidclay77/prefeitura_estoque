<?php
class Database {
    private $host = "localhost";
    private $db_name = "prefeitura_estoque";
    private $username = "root";
    private $password = "";
    public $conn;

    public function getConnection() {
        $this->conn = null;

        try {
            $this->conn = new PDO("mysql:host=" . $this->host . ";dbname=" . $this->db_name, $this->username, $this->password);
            $this->conn->exec("set names utf8");
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch(PDOException $exception) {
            echo "Connection error: " . $exception->getMessage();
        }

        return $this->conn;
    }

    public function createDatabase() {
        try {
            // Conectar sem especificar database
            $conn = new PDO("mysql:host=" . $this->host, $this->username, $this->password);
            $conn->exec("set names utf8");
            $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            
            // Criar database se não existir
            $conn->exec("CREATE DATABASE IF NOT EXISTS " . $this->db_name . " DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci");
            
            // Conectar à database criada
            $this->conn = new PDO("mysql:host=" . $this->host . ";dbname=" . $this->db_name, $this->username, $this->password);
            $this->conn->exec("set names utf8");
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            
            return $this->conn;
        } catch(PDOException $exception) {
            echo "Database creation error: " . $exception->getMessage();
            return null;
        }
    }
}
?>