<?php
namespace App\Core;

use PDO;
use PDOException;
use Exception;

class Model {
    protected $db;
    protected $table;
    protected $primaryKey = 'id';
    protected $fillable = [];
    protected $hidden = ['password'];
    protected $timestamps = true;
    protected $builder;
    protected $stmt;

    public function __construct() {
        try {
            $this->db = Database::getInstance();
            $this->builder = new QueryBuilder($this->db->getConnection());
        } catch (Exception $e) {
            throw new Exception('Failed to initialize database connection');
        }
    }

    protected function prepare($sql) {
        try {
            $this->stmt = $this->db->prepare($sql);
            return $this->stmt;
        } catch (PDOException $e) {
            throw new \Exception("Failed to prepare query: " . $e->getMessage());
        }
    }

    protected function execute($params = []) {
        try {
            return $this->stmt->execute($params);
        } catch (PDOException $e) {
            throw new \Exception("Failed to execute query: " . $e->getMessage());
        }
    }

    protected function getDbConnection() {
        if (!$this->db) {
            throw new PDOException('Database connection not initialized');
        }
        $connection = $this->db->getConnection();
        if (!$connection) {
            throw new PDOException('Invalid database connection');
        }
        return $connection;
    }

    protected function prepareQuery($sql) {
        return $this->getDbConnection()->prepare($sql);
    }

    protected function executeQuery($stmt, $params = []) {
        try {
            return $stmt->execute($params);
        } catch (PDOException $e) {
            throw new PDOException('Query execution failed: ' . $e->getMessage());
        }
    }

    protected function query($sql, $params = []) {
        $stmt = $this->prepareQuery($sql);
        $this->executeQuery($stmt, $params);
        return $stmt;
    }

    protected function single() {
        return $this->db->single();
    }

    protected function resultSet() {
        return $this->db->resultSet();
    }

    protected function lastInsertId() {
        return $this->db->lastInsertId();
    }

    protected function rowCount() {
        return $this->db->rowCount();
    }

    // Lấy tất cả records
    public function all() {
        $this->db->query("SELECT * FROM {$this->table}");
        return $this->db->resultSet();
    }

    // Lấy record theo ID
    public function find($id) {
        return $this->query("SELECT * FROM {$this->table} WHERE {$this->primaryKey} = ?", [$id])->fetch(PDO::FETCH_OBJ);
    }

    // Tìm record theo điều kiện
    public function where($column, $value) {
        return $this->query("SELECT * FROM {$this->table} WHERE {$column} = ?", [$value])->fetchAll(PDO::FETCH_OBJ);
    }

    // Tìm record đầu tiên theo điều kiện
    public function firstWhere($column, $value) {
        $this->db->query("SELECT * FROM {$this->table} WHERE {$column} = :value LIMIT 1");
        $this->db->bind(':value', $value);
        return $this->db->single();
    }

    // Tạo record mới
    public function create($data) {
        // Chỉ lấy các trường được phép fill
        $data = array_intersect_key($data, array_flip($this->fillable));
        
        // Thêm timestamps nếu cần
        if($this->timestamps) {
            $data['created_at'] = date('Y-m-d H:i:s');
            $data['updated_at'] = date('Y-m-d H:i:s');
        }

        $columns = implode(', ', array_keys($data));
        $values = ':' . implode(', :', array_keys($data));
        
        $this->db->query("INSERT INTO {$this->table} ({$columns}) VALUES ({$values})");
        
        // Bind values
        foreach($data as $key => $value) {
            $this->db->bind(":{$key}", $value);
        }

        if($this->db->execute()) {
            return $this->db->lastInsertId();
        }
        return false;
    }

    // Cập nhật record
    public function update($id, $data) {
        // Chỉ lấy các trường được phép fill
        $data = array_intersect_key($data, array_flip($this->fillable));
        
        // Thêm updated_at nếu cần
        if($this->timestamps) {
            $data['updated_at'] = date('Y-m-d H:i:s');
        }

        $set = '';
        foreach($data as $key => $value) {
            $set .= "{$key} = :{$key},";
        }
        $set = rtrim($set, ',');

        $this->db->query("UPDATE {$this->table} SET {$set} WHERE {$this->primaryKey} = :id");
        $this->db->bind(':id', $id);
        
        // Bind values
        foreach($data as $key => $value) {
            $this->db->bind(":{$key}", $value);
        }

        return $this->db->execute();
    }

    // Xóa record
    public function delete($id) {
        $this->db->query("DELETE FROM {$this->table} WHERE {$this->primaryKey} = :id");
        $this->db->bind(':id', $id);
        return $this->db->execute();
    }

    // Lấy số lượng records
    public function count() {
        $this->db->query("SELECT COUNT(*) as count FROM {$this->table}");
        return $this->db->single()->count;
    }

    // Lấy records có phân trang
    public function paginate($page = 1, $perPage = 10) {
        $offset = ($page - 1) * $perPage;
        
        $this->db->query("SELECT * FROM {$this->table} LIMIT :limit OFFSET :offset");
        $this->db->bind(':limit', $perPage, PDO::PARAM_INT);
        $this->db->bind(':offset', $offset, PDO::PARAM_INT);
        
        $data = $this->db->resultSet();
        $total = $this->count();
        
        return [
            'data' => $data,
            'total' => $total,
            'per_page' => $perPage,
            'current_page' => $page,
            'last_page' => ceil($total / $perPage)
        ];
    }

    // Ẩn các trường nhạy cảm
    protected function hideFields(array|object $data) {
        if(is_object($data)) {
            $data = (array)$data;
        }
        
        foreach($this->hidden as $field) {
            unset($data[$field]);
        }
        
        return $data;
    }

    protected function prepareAndExecute($sql, $params = []) {
        try {
            $stmt = $this->db->getConnection()->prepare($sql);
            $stmt->execute($params);
            return $stmt;
        } catch (PDOException $e) {
            throw new \Exception("Query failed: " . $e->getMessage());
        }
    }

    protected function getConnection() {
        if (!$this->db) {
            throw new \Exception('Database connection not initialized');
        }
        $connection = $this->db->getConnection();
        if (!$connection) {
            throw new \Exception('Invalid database connection');
        }
        return $connection;
    }
}