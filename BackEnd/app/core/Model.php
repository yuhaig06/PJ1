<?php
class Model {
    protected $db;
    protected $table;
    protected $primaryKey = 'id';
    protected $fillable = [];
    protected $hidden = ['password'];
    protected $timestamps = true;

    public function __construct() {
        $this->db = Database::getInstance();
    }

    // Lấy tất cả records
    public function all() {
        $this->db->query("SELECT * FROM {$this->table}");
        return $this->db->resultSet();
    }

    // Lấy record theo ID
    public function find($id) {
        $this->db->query("SELECT * FROM {$this->table} WHERE {$this->primaryKey} = :id");
        $this->db->bind(':id', $id);
        return $this->db->single();
    }

    // Tìm record theo điều kiện
    public function where($column, $value) {
        $this->db->query("SELECT * FROM {$this->table} WHERE {$column} = :value");
        $this->db->bind(':value', $value);
        return $this->db->resultSet();
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
    protected function hideFields($data) {
        if(is_object($data)) {
            $data = (array)$data;
        }
        
        foreach($this->hidden as $field) {
            unset($data[$field]);
        }
        
        return $data;
    }
}