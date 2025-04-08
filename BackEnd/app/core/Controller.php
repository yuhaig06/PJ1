<?php

namespace App\Core;

class Controller {
    protected $db;
    protected $userModel;
    
    public function __construct() {
        // Khởi tạo logic cơ bản cho tất cả các controller
        // Khởi tạo database connection
        $this->db = new Database;
        
        // Load UserModel nếu cần
        if(method_exists($this, 'isLoggedIn')) {
            $this->userModel = $this->model('UserModel');
        }
    }
    
    public function model($model) {
        $modelPath = dirname(__DIR__) . '/models/' . $model . '.php';
        if (file_exists($modelPath)) {
            require_once $modelPath;
            return new $model();
        }
        throw new Exception("Model $model not found!");
    }

    public function view($view, $data = []) {
        // Thêm các biến mặc định cho tất cả views
        $data['isLoggedIn'] = isset($_SESSION['user_id']);
        $data['currentUser'] = isset($_SESSION['user_id']) ? $this->userModel->getUserById($_SESSION['user_id']) : null;
        
        // Đường dẫn chuẩn để tìm kiếm file view
        $viewPath = dirname(__DIR__) . '/views/' . $view . '.php';

        if (file_exists($viewPath)) {
            // Giải nén dữ liệu để có thể truy cập các biến trực tiếp trong view
            extract($data);
            require_once $viewPath;
        } else {
            throw new Exception("View $view not found!");
        }
    }
    
    // Kiểm tra user đã đăng nhập chưa
    protected function isLoggedIn() {
        return isset($_SESSION['user_id']);
    }
    
    // Kiểm tra user có phải admin không
    protected function isAdmin() {
        return isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin';
    }
    
    // Chuyển hướng với flash message
    protected function redirect($page, $message = '', $type = 'success') {
        if(!empty($message)) {
            flash($message, $type);
        }
        header('location: ' . URLROOT . '/' . $page);
        exit;
    }
    
    // Validate dữ liệu
    protected function validate($data, $rules) {
        $errors = [];
        
        foreach($rules as $field => $rule) {
            if(!isset($data[$field])) {
                $errors[$field] = "Trường $field là bắt buộc";
                continue;
            }
            
            $value = trim($data[$field]);
            
            if(empty($value) && strpos($rule, 'required') !== false) {
                $errors[$field] = "Trường $field không được để trống";
            }
            
            if(strpos($rule, 'email') !== false && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
                $errors[$field] = "Email không hợp lệ";
            }
            
            if(strpos($rule, 'min:') !== false) {
                preg_match('/min:(\d+)/', $rule, $matches);
                $min = $matches[1];
                if(strlen($value) < $min) {
                    $errors[$field] = "Trường $field phải có ít nhất $min ký tự";
                }
            }
            
            if(strpos($rule, 'max:') !== false) {
                preg_match('/max:(\d+)/', $rule, $matches);
                $max = $matches[1];
                if(strlen($value) > $max) {
                    $errors[$field] = "Trường $field không được vượt quá $max ký tự";
                }
            }
        }
        
        return $errors;
    }
    
    // Upload file
    protected function uploadFile($file, $destination, $allowedTypes = ['jpg', 'jpeg', 'png', 'gif'], $maxSize = 5242880) {
        $errors = [];
        
        // Kiểm tra lỗi upload
        if($file['error'] !== UPLOAD_ERR_OK) {
            $errors[] = "Có lỗi xảy ra khi upload file";
            return $errors;
        }
        
        // Kiểm tra kích thước
        if($file['size'] > $maxSize) {
            $errors[] = "File quá lớn. Kích thước tối đa là " . ($maxSize / 1048576) . "MB";
            return $errors;
        }
        
        // Kiểm tra loại file
        $fileType = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if(!in_array($fileType, $allowedTypes)) {
            $errors[] = "Loại file không được phép. Chỉ chấp nhận: " . implode(', ', $allowedTypes);
            return $errors;
        }
        
        // Tạo tên file mới
        $newFileName = uniqid() . '.' . $fileType;
        $uploadPath = $destination . '/' . $newFileName;
        
        // Upload file
        if(!move_uploaded_file($file['tmp_name'], $uploadPath)) {
            $errors[] = "Không thể upload file";
            return $errors;
        }
        
        return $newFileName;
    }
}