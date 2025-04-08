<?php
namespace App\Core;

use PDO;
use PDOException;

class QueryBuilder {
    protected $pdo;
    protected $query;
    protected $params = [];
    
    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
    }

    public function table($table) {
        $this->query = "SELECT * FROM {$table}";
        return $this;
    }

    public function select($columns = '*') {
        if (is_array($columns)) {
            $columns = implode(', ', $columns);
        }
        $this->query = "SELECT {$columns}";
        return $this;
    }

    public function from($table) {
        $this->query .= " FROM {$table}";
        return $this;
    }

    public function where($column, $operator, $value = null) {
        if ($value === null) {
            $value = $operator;
            $operator = '=';
        }
        
        $key = ':' . str_replace('.', '_', $column);
        $this->query .= " WHERE {$column} {$operator} {$key}";
        $this->params[$key] = $value;
        
        return $this;
    }

    public function execute() {
        try {
            $stmt = $this->pdo->prepare($this->query);
            $stmt->execute($this->params);
            return $stmt;
        } catch (PDOException $e) {
            throw new \Exception("Query execution failed: " . $e->getMessage());
        }
    }

    public function get() {
        return $this->execute()->fetchAll(PDO::FETCH_ASSOC);
    }

    public function first() {
        return $this->execute()->fetch(PDO::FETCH_ASSOC);
    }
}
