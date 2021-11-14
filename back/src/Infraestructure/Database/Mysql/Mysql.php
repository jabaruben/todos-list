<?php

namespace TodosList\Infraestructure\Database\Mysql;

use TodosList\Infraestructure\Database\Interfaces\Database;

class Mysql implements Database
{

    private $conn = null;

    // this function is called everytime this class is instantiated		
    public function __construct($host = "localhost", $name = "myDataBaseName", $user = "root", $password = "")
    {

        try {

            $this->conn = new \PDO("mysql:host={$host};dbname={$name};charset=utf8mb4", $user, $password);
            // $this->conn->exec('SET CHARACTER SET utf8');
            // $this->conn->query("SET NAMES utf8");
            $this->conn->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
            $this->conn->setAttribute(\PDO::ATTR_DEFAULT_FETCH_MODE, \PDO::FETCH_ASSOC);
        } catch (\Exception $e) {
            throw new \Exception($e->getMessage());
        }
    }

    // Insert a row/s in a Database Table
    public function Insert($statement = "", $parameters = []): string
    {
        try {

            $this->executeStatement($statement, $parameters);
            return $this->conn->lastInsertId();
        } catch (\Exception $e) {
            throw new \Exception($e->getMessage());
        }
    }

    // Select a row/s in a Database Table
    public function Select($statement = "", $parameters = []): array
    {
        try {

            $stmt = $this->executeStatement($statement, $parameters);
            return $stmt->fetchAll();
        } catch (\Exception $e) {
            throw new \Exception($e->getMessage());
        }
    }

    // Update a row/s in a Database Table
    public function Update($statement = "", $parameters = []): void
    {
        try {

            $this->executeStatement($statement, $parameters);
        } catch (\Exception $e) {
            throw new \Exception($e->getMessage());
        }
    }

    // Remove a row/s in a Database Table
    public function Remove($statement = "", $parameters = []): void
    {
        try {

            $this->executeStatement($statement, $parameters);
        } catch (\Exception $e) {
            throw new \Exception($e->getMessage());
        }
    }

    // execute statement
    private function executeStatement($statement = "", $parameters = [])
    {
        try {

            $stmt = $this->conn->prepare($statement);
            $stmt->execute($parameters);
            return $stmt;
        } catch (\Exception $e) {
            throw new \Exception($e->getMessage());
        }
    }
}
