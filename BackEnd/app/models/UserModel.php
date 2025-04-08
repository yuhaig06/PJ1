<?php

namespace App\Models;

use App\Core\Model;
use App\Core\Database;
use PDO;

class UserModel extends Model
{
    protected $table = 'users';
    protected $fillable = ['username', 'email', 'password'];

    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Tìm người dùng theo email
     */
    public function findByEmail($email)
    {
        $sql = "SELECT * FROM users WHERE email = :email";
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':email', $email);
        $stmt->execute();
        
        return $stmt->fetch(\PDO::FETCH_ASSOC);
    }

    /**
     * Tìm người dùng theo username
     */
    public function findByUsername($username)
    {
        return $this->findOne(['username' => $username]);
    }

    /**
     * Tìm người dùng theo remember token
     */
    public function findByRememberToken($token)
    {
        return $this->findOne(['remember_token' => $token]);
    }

    /**
     * Tạo người dùng mới
     */
    public function createUser($data)
    {
        if (isset($data['password'])) {
            $data['password'] = password_hash($data['password'], PASSWORD_DEFAULT);
        }
        return $this->create($data);
    }

    /**
     * Cập nhật thông tin người dùng
     */
    public function updateUser($id, $data)
    {
        if (isset($data['password'])) {
            $data['password'] = password_hash($data['password'], PASSWORD_DEFAULT);
        }
        return $this->update($id, $data);
    }

    /**
     * Xác thực người dùng
     */
    public function authenticate($email, $password)
    {
        $user = $this->findByEmail($email);
        if ($user && password_verify($password, $user['password'])) {
            return $user;
        }
        return false;
    }

    /**
     * Kiểm tra quyền của người dùng
     */
    public function hasPermission($userId, $permission)
    {
        $sql = "SELECT p.name 
                FROM permissions p 
                JOIN role_permissions rp ON p.id = rp.permission_id 
                JOIN users u ON u.role = rp.role 
                WHERE u.id = ? AND p.name = ?";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$userId, $permission]);
        return $stmt->fetch() !== false;
    }

    /**
     * Lấy danh sách quyền của người dùng
     */
    public function getUserPermissions($userId)
    {
        $sql = "SELECT p.name 
                FROM permissions p 
                JOIN role_permissions rp ON p.id = rp.permission_id 
                JOIN users u ON u.role = rp.role 
                WHERE u.id = ?";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$userId]);
        return $stmt->fetchAll(\PDO::FETCH_COLUMN);
    }

    /**
     * Tạo phiên đăng nhập
     */
    public function createSession($userId, $sessionId)
    {
        $sql = "INSERT INTO user_sessions (user_id, session_id, ip_address, user_agent) 
                VALUES (?, ?, ?, ?)";
        
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            $userId,
            $sessionId,
            $_SERVER['REMOTE_ADDR'] ?? null,
            $_SERVER['HTTP_USER_AGENT'] ?? null
        ]);
    }

    /**
     * Xóa phiên đăng nhập
     */
    public function deleteSession($sessionId)
    {
        $sql = "DELETE FROM user_sessions WHERE session_id = ?";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([$sessionId]);
    }

    /**
     * Tạo token đặt lại mật khẩu
     */
    public function createPasswordReset($email)
    {
        $token = bin2hex(random_bytes(32));
        $sql = "INSERT INTO password_resets (email, token) VALUES (?, ?)";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([$email, $token]) ? $token : false;
    }

    /**
     * Kiểm tra token đặt lại mật khẩu
     */
    public function verifyPasswordReset($email, $token)
    {
        $sql = "SELECT * FROM password_resets 
                WHERE email = ? AND token = ? 
                AND created_at > DATE_SUB(NOW(), INTERVAL 1 HOUR)";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$email, $token]);
        return $stmt->fetch() !== false;
    }

    /**
     * Xóa token đặt lại mật khẩu
     */
    public function deletePasswordReset($email)
    {
        $sql = "DELETE FROM password_resets WHERE email = ?";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([$email]);
    }

    /**
     * Cập nhật thời gian hoạt động cuối cùng
     */
    public function updateLastActivity($userId)
    {
        return $this->update($userId, ['last_activity' => date('Y-m-d H:i:s')]);
    }

    /**
     * Lấy danh sách người dùng theo vai trò
     */
    public function getUsersByRole($role)
    {
        return $this->findAll(['role' => $role]);
    }

    /**
     * Lấy danh sách người dùng theo trạng thái
     */
    public function getUsersByStatus($status)
    {
        return $this->findAll(['status' => $status]);
    }

    /**
     * Tìm kiếm người dùng
     */
    public function searchUsers($keyword)
    {
        $sql = "SELECT * FROM users 
                WHERE username LIKE ? 
                OR email LIKE ? 
                OR full_name LIKE ?";
        
        $keyword = "%{$keyword}%";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$keyword, $keyword, $keyword]);
        return $stmt->fetchAll();
    }

    public function create($data)
    {
        $sql = "INSERT INTO users (username, email, password, full_name, verification_token, created_at) 
                VALUES (:username, :email, :password, :full_name, :verification_token, NOW())";
        
        $stmt = $this->db->prepare($sql);
        
        $stmt->bindParam(':username', $data['username']);
        $stmt->bindParam(':email', $data['email']);
        $stmt->bindParam(':password', $data['password']);
        $stmt->bindParam(':full_name', $data['full_name']);
        $stmt->bindParam(':verification_token', $data['verification_token']);
        
        if ($stmt->execute()) {
            return $this->db->lastInsertId();
        }
        
        return false;
    }

    /**
     * @param int $id
     * @return array|null
     */
    public function getById(int $id): ?array
    {
        $sql = "SELECT * FROM users WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        
        return $stmt->fetch(\PDO::FETCH_ASSOC);
    }

    public function findByVerificationToken($token)
    {
        $sql = "SELECT * FROM users WHERE verification_token = :token";
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':token', $token);
        $stmt->execute();
        
        return $stmt->fetch(\PDO::FETCH_ASSOC);
    }

    public function findByResetToken($token)
    {
        $sql = "SELECT * FROM users WHERE reset_token = :token AND reset_token_expires > NOW()";
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':token', $token);
        $stmt->execute();
        
        return $stmt->fetch(\PDO::FETCH_ASSOC);
    }

    public function update($id, $data)
    {
        $fields = [];
        $values = [];
        
        foreach ($data as $key => $value) {
            $fields[] = "$key = :$key";
            $values[":$key"] = $value;
        }
        
        $sql = "UPDATE users SET " . implode(', ', $fields) . " WHERE id = :id";
        $values[':id'] = $id;
        
        $stmt = $this->db->prepare($sql);
        return $stmt->execute($values);
    }

    public function updatePassword($id, $password)
    {
        $sql = "UPDATE users SET password = :password WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        
        $stmt->bindParam(':password', $password);
        $stmt->bindParam(':id', $id);
        
        return $stmt->execute();
    }

    public function updateResetToken($id, $token)
    {
        $sql = "UPDATE users SET reset_token = :token, reset_token_expires = DATE_ADD(NOW(), INTERVAL 1 HOUR) 
                WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        
        $stmt->bindParam(':token', $token);
        $stmt->bindParam(':id', $id);
        
        return $stmt->execute();
    }

    public function clearResetToken($id)
    {
        $sql = "UPDATE users SET reset_token = NULL, reset_token_expires = NULL WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        
        $stmt->bindParam(':id', $id);
        
        return $stmt->execute();
    }

    public function verifyEmail($id)
    {
        $sql = "UPDATE users SET email_verified = 1, verification_token = NULL WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        
        $stmt->bindParam(':id', $id);
        
        return $stmt->execute();
    }

    public function findOne($conditions = []) {
        $sql = "SELECT * FROM {$this->table} WHERE 1=1";
        $params = [];
        
        foreach ($conditions as $key => $value) {
            $sql .= " AND {$key} = ?";
            $params[] = $value;
        }

        $stmt = $this->query($sql, $params);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function findAll($conditions = []) {
        $sql = "SELECT * FROM {$this->table} WHERE 1=1";
        $params = [];
        
        foreach ($conditions as $key => $value) {
            $sql .= " AND {$key} = ?";
            $params[] = $value;
        }

        $stmt = $this->query($sql, $params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function findById(int $id): ?array {
        $stmt = $this->db->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch(\PDO::FETCH_ASSOC) ?: null;
    }
}
