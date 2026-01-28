<?php
// Database configuration for LGU Road and Infrastructure Department
class Database
{
    private $host = 'localhost';
    private $username = 'root';
    private $password = '';
    private $database = 'lgu_road_infra';
    private $conn;

    public function __construct()
    {
        $this->connect();
    }

    private function connect()
    {
        try {
            $this->conn = new mysqli(
                $this->host,
                $this->username,
                $this->password,
                $this->database,
            );

            if ($this->conn->connect_error) {
                throw new Exception("Connection failed: " . $this->conn->connect_error);
            }

            // Set charset to utf8mb4
            $this->conn->set_charset("utf8mb4");

        } catch (Exception $e) {
            error_log("Database connection error: " . $e->getMessage());
            throw $e;
        }
    }

    public function getConnection()
    {
        return $this->conn;
    }

    public function close()
    {
        if ($this->conn) {
            $this->conn->close();
        }
    }

    // Prevent SQL injection with prepared statements
    public function prepare($sql)
    {
        return $this->conn->prepare($sql);
    }

    // Execute query with error handling
    public function query($sql)
    {
        $result = $this->conn->query($sql);
        if (!$result) {
            error_log("Query error: " . $this->conn->error);
            return false;
        }
        return $result;
    }

    // Get last insert ID
    public function getLastInsertId()
    {
        return $this->conn->insert_id;
    }

    // Escape string for security
    public function escapeString($string)
    {
        return $this->conn->real_escape_string($string);
    }

    public function __destruct()
    {
        $this->close();
    }
}
?>