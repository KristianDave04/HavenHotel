<?php
// classes/Database.php

class Database {
    private $host = "localhost";
    private $db_user = "root";
    private $db_pass = "";
    private $db_name = "haven_hotel";
    public $conn;

    // Constructor creates connection automatically upon instantiation
    public function __construct() {
        $this->conn = new mysqli($this->host, $this->db_user, $this->db_pass, $this->db_name);
        
        if ($this->conn->connect_error) {
            die("Database Connection Error via OOP Framework: " . $this->conn->connect_error);
        }
    }

    public function getConnection() {
        return $this->conn;
    }

    public function closeConnection() {
        if ($this->conn) {
            $this->conn->close();
        }
    }
}