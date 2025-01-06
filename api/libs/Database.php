<?php

namespace api\libs;

class Database
{
    private $db;
    private $stmt;

    public function __construct()
    {
        $this->connectDB();
    }

    private function connectDB()
    {
        $servername = $_ENV['HOST'];
        $username = $_ENV['USUARIO'];
        $password = $_ENV['PASS'];
        $dbname = $_ENV['BD'];

        try {
            $this->db = new \PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
            $this->db->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
        } catch (\PDOException $e) {
            echo "Connection failed: " . $e->getMessage();
        }
    }

    public function query($sql)
    {
        $this->stmt = $this->db->prepare($sql);
    }

    public function bind($param, $value, $type = null)
    {
        if (is_null($type)) {
            switch (true) {
                case is_int($value):
                    $type = \PDO::PARAM_INT;
                    break;
                case is_bool($value):
                    $type = \PDO::PARAM_BOOL;
                    break;
                case is_null($value):
                    $type = \PDO::PARAM_NULL;
                    break;
                default:
                    $type = \PDO::PARAM_STR;
            }
        }
        $this->stmt->bindValue($param, $value, $type);
    }

    public function execute()
    {
        return $this->stmt->execute();
    }

    public function resultSet()
    {
        $this->execute();
        return $this->stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function single()
    {
        $this->execute();
        return $this->stmt->fetch(\PDO::FETCH_ASSOC);
    }

    public function singleValue()
    {
        $this->execute();
        $result = $this->stmt->fetch(\PDO::FETCH_ASSOC);
        return $result ? array_values($result)[0] : null; // Retorna el primer valor o null si no hay resultados
    }

    public function rowCount()
    {
        return $this->stmt->rowCount();
    }

    public function lastInsertId()
    {
        return $this->db->lastInsertId();
    }

    public function beginTransaction()
    {
        return $this->db->beginTransaction();
    }

    public function endTransaction()
    {
        return $this->db->commit();
    }

    public function cancelTransaction()
    {
        return $this->db->rollBack();
    }
}
