<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Models\UserModel;
use App\Middleware\AuthMiddleware;
use App\Middleware\CSRFMiddleware;
use App\Middleware\RateLimitMiddleware;
use App\Config\Database;
use App\Traits\JsonResponseTrait;
use PDOException;
use InvalidArgumentException;
use RuntimeException;

class AuthController extends Controller
{
    use JsonResponseTrait;

    private $dbConnection;  // Thay thế private $db
    private $userModel;
    private $authMiddleware;
    private $csrfMiddleware;
    private $rateLimitMiddleware;

    public function __construct()
    {
        parent::__construct(); // Đảm bảo lớp cha có phương thức __construct()
        $this->dbConnection = Database::getInstance()->getConnection();
        $this->userModel = new UserModel($this->dbConnection);
        $this->authMiddleware = new AuthMiddleware();
        $this->csrfMiddleware = new CSRFMiddleware();
        $this->rateLimitMiddleware = new RateLimitMiddleware();
    }

    private function checkUserLoggedIn() {
        return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
    }

    /**
     * Đăng ký tài khoản mới
     */
    public function register()
    {
        try {
            // Get POST data
            $data = json_decode(file_get_contents('php://input'), true);

            // Validate required fields
            $required = ['username', 'email', 'password', 'fullname', 'phone'];
            foreach ($required as $field) {
                if (empty($data[$field])) {
                    return $this->json([
                        'status' => 'error',
                        'message' => "Field $field is required"
                    ], 400);
                }
            }

            // Check if username or email already exists
            if ($this->userModel->findByUsername($data['username'])) {
                return $this->json([
                    'status' => 'error',
                    'message' => 'Username already exists'
                ], 400);
            }

            if ($this->userModel->findByEmail($data['email'])) {
                return $this->json([
                    'status' => 'error',
                    'message' => 'Email already exists'
                ], 400);
            }

            // Create user
            $userId = $this->userModel->create([
                'username' => $data['username'],
                'email' => $data['email'],
                'password' => password_hash($data['password'], PASSWORD_DEFAULT),
                'fullname' => $data['fullname'],
                'phone' => $data['phone'],
                'role' => 'user'
            ]);

            return $this->json([
                'status' => 'success',
                'message' => 'Registration successful'
            ]);

        } catch (PDOException $e) {
            error_log('Database error: ' . $e->getMessage());
            return $this->json([
                'status' => 'error',
                'message' => 'Lỗi kết nối cơ sở dữ liệu'
            ], 500);
        } catch (InvalidArgumentException $e) {
            return $this->json([
                'status' => 'error', 
                'message' => $e->getMessage()
            ], 400);
        } catch (RuntimeException $e) {
            error_log('Runtime error: ' . $e->getMessage());
            return $this->json([
                'status' => 'error',
                'message' => 'Lỗi hệ thống'
            ], 500);
        }
    }

    /**
     * Đăng nhập
     */
    public function login($data = null) 
    {
        try {
            if (!$data) {
                $data = json_decode(file_get_contents('php://input'), true);
            }
            
            error_log('Login data: ' . print_r($data, true));

            if (!isset($data['email']) || !isset($data['password'])) {
                return $this->jsonResponse([
                    'success' => false,
                    'message' => 'Email và mật khẩu không được để trống'
                ], 400);
            }

            $user = $this->userModel->findByEmail($data['email']);
            
            if (!$user) {
                return $this->jsonResponse([
                    'success' => false,
                    'message' => 'Email hoặc mật khẩu không đúng'
                ], 401);
            }

            if (!password_verify($data['password'], $user['password'])) {
                return $this->jsonResponse([
                    'success' => false,
                    'message' => 'Email hoặc mật khẩu không đúng'
                ], 401);
            }

            // Generate token
            $token = bin2hex(random_bytes(32));
            
            // Save user session
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['token'] = $token;

            // Remove password from response
            unset($user['password']);

            return $this->jsonResponse([
                'success' => true,
                'message' => 'Đăng nhập thành công',
                'token' => $token,
                'user' => $user
            ]);

        } catch (PDOException $e) {
            error_log('Database error: ' . $e->getMessage());
            return $this->jsonResponse([
                'success' => false,
                'message' => 'Lỗi kết nối cơ sở dữ liệu'
            ], 500);
        } catch (InvalidArgumentException $e) {
            return $this->jsonResponse([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        } catch (RuntimeException $e) {
            error_log('Runtime error: ' . $e->getMessage());
            return $this->jsonResponse([
                'success' => false, 
                'message' => 'Lỗi xử lý yêu cầu'
            ], 500);
        }
    }

    /**
     * Đăng xuất
     */
    public function logout()
    {
        if (!$this->checkUserLoggedIn()) {
            return $this->json([
                'success' => false,
                'message' => 'Chưa đăng nhập'
            ], 401);
        }

        // Xóa phiên đăng nhập
        $sessionId = session_id();
        $this->userModel->deleteSession($sessionId);

        // Hủy session
        session_destroy();

        return $this->json([
            'success' => true,
            'message' => 'Đăng xuất thành công'
        ]);
    }

    /**
     * Lấy thông tin người dùng hiện tại
     */
    public function me()
    {
        if (!$this->checkUserLoggedIn()) {
            return $this->json([
                'success' => false,
                'message' => 'Chưa đăng nhập'
            ], 401);
        }

        $userId = $_SESSION['user_id'];
        $user = $this->userModel->find($userId);

        if (!$user) {
            return $this->json([
                'success' => false,
                'message' => 'Không tìm thấy thông tin người dùng'
            ], 404);
        }

        return $this->json([
            'success' => true,
            'data' => [
                'id' => $user['id'],
                'username' => $user['username'],
                'email' => $user['email'],
                'full_name' => $user['full_name'],
                'avatar' => $user['avatar'],
                'role' => $user['role'],
                'permissions' => $this->userModel->getUserPermissions($userId)
            ]
        ]);
    }

    /**
     * Gửi yêu cầu đặt lại mật khẩu
     */
    public function forgotPassword()
    {
        try {
            $data = json_decode(file_get_contents('php://input'), true);
            
            if (!isset($data['email'])) {
                return $this->jsonResponse(['success' => false, 'message' => 'Email is required'], 400);
            }

            $user = $this->userModel->findByEmail($data['email']);
            
            if (!$user) {
                return $this->jsonResponse(['success' => false, 'message' => 'Email not found'], 404);
            }

            $resetToken = bin2hex(random_bytes(32));
            $this->userModel->updateResetToken($user['id'], $resetToken);
            
            // Send reset password email
            $this->sendResetPasswordEmail($data['email'], $resetToken);
            
            return $this->jsonResponse([
                'success' => true,
                'message' => 'Password reset instructions have been sent to your email'
            ]);
        } catch (\Exception $e) {
            return $this->jsonResponse(['success' => false, 'message' => 'Failed to process request'], 500);
        }
    }

    /**
     * Đặt lại mật khẩu
     */
    public function resetPassword()
    {
        try {
            $data = json_decode(file_get_contents('php://input'), true);
            
            if (!isset($data['token']) || !isset($data['newPassword'])) {
                return $this->jsonResponse(['success' => false, 'message' => 'Token and new password are required'], 400);
            }

            $user = $this->userModel->findByResetToken($data['token']);
            
            if (!$user) {
                return $this->jsonResponse(['success' => false, 'message' => 'Invalid or expired token'], 400);
            }

            // Thay thế AuthMiddleware::hashPassword bằng password_hash
            $hashedPassword = password_hash($data['newPassword'], PASSWORD_DEFAULT);
            $this->userModel->updatePassword($user['id'], $hashedPassword);
            $this->userModel->clearResetToken($user['id']);
            
            return $this->jsonResponse([
                'success' => true,
                'message' => 'Password has been reset successfully'
            ]);
        } catch (\Exception $e) {
            return $this->jsonResponse(['success' => false, 'message' => 'Failed to reset password'], 500);
        }
    }

    /**
     * Cập nhật thông tin cá nhân
     */
    private function validateProfileData($data) {
        $errors = [];
        
        // Validate full name
        if (empty($data['full_name'])) {
            $errors['full_name'] = 'Họ tên không được để trống';
        } elseif (strlen($data['full_name']) > 100) {
            $errors['full_name'] = 'Họ tên không được vượt quá 100 ký tự';
        }

        // Validate avatar if exists
        if (isset($_FILES['avatar'])) {
            $file = $_FILES['avatar'];
            // Check file size (max 2MB)
            if ($file['size'] > 2 * 1024 * 1024) {
                $errors['avatar'] = 'Kích thước file không được vượt quá 2MB';
            }
            // Check file type
            $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
            if (!in_array($file['type'], $allowedTypes)) {
                $errors['avatar'] = 'Chỉ chấp nhận file ảnh (JPG, PNG, GIF)';
            }
        }

        return [
            'success' => empty($errors),
            'errors' => $errors,
            'data' => $data
        ];
    }

    public function updateProfile()
    {
        if (!$this->checkUserLoggedIn()) {
            return $this->json([
                'success' => false,
                'message' => 'Chưa đăng nhập'
            ], 401);
        }

        // Get and validate data
        $data = [
            'full_name' => $_POST['full_name'] ?? ''
        ];
        
        $validationResult = $this->validateProfileData($data);
        
        if (!$validationResult['success']) {
            return $this->json([
                'success' => false,
                'message' => 'Dữ liệu không hợp lệ',
                'errors' => $validationResult['errors']
            ], 422);
        }

        $userId = $_SESSION['user_id'];
        $updateData = [
            'full_name' => $data['full_name']
        ];

        // Xử lý upload avatar
        if (isset($_FILES['avatar'])) {
            $avatar = $this->handleFileUpload('avatar', 'avatars');
            if ($avatar['success']) {
                $updateData['avatar'] = $avatar['path'];
            }
        }

        // Cập nhật thông tin
        if (!$this->userModel->updateUser($userId, $updateData)) {
            return $this->json([
                'success' => false,
                'message' => 'Không thể cập nhật thông tin'
            ], 500);
        }

        return $this->json([
            'success' => true,
            'message' => 'Cập nhật thông tin thành công'
        ]);
    }

    /**
     * Đổi mật khẩu
     */
    private function validatePasswordData($data) {
        $errors = [];
        
        // Validate current password
        if (empty($data['current_password'])) {
            $errors['current_password'] = 'Mật khẩu hiện tại không được để trống';
        }

        // Validate new password
        if (empty($data['new_password'])) {
            $errors['new_password'] = 'Mật khẩu mới không được để trống';
        } elseif (strlen($data['new_password']) < 6) {
            $errors['new_password'] = 'Mật khẩu mới phải có ít nhất 6 ký tự';
        } elseif (strlen($data['new_password']) > 50) {
            $errors['new_password'] = 'Mật khẩu mới không được vượt quá 50 ký tự';
        }

        return [
            'success' => empty($errors),
            'errors' => $errors,
            'data' => $data
        ];
    }

    public function changePassword()
    {
        if (!$this->checkUserLoggedIn()) {
            return $this->json([
                'success' => false,
                'message' => 'Chưa đăng nhập'
            ], 401);
        }

        // Get and validate password data
        $data = [
            'current_password' => $_POST['current_password'] ?? '',
            'new_password' => $_POST['new_password'] ?? ''
        ];
        
        $validationResult = $this->validatePasswordData($data);
        
        if (!$validationResult['success']) {
            return $this->json([
                'success' => false,
                'message' => 'Dữ liệu không hợp lệ',
                'errors' => $validationResult['errors']
            ], 422);
        }

        $userId = $_SESSION['user_id'];
        $user = $this->userModel->find($userId);

        // Kiểm tra mật khẩu hiện tại
        if (!password_verify($data['data']['current_password'], $user['password'])) {
            return $this->json([
                'success' => false,
                'message' => 'Mật khẩu hiện tại không đúng'
            ], 400);
        }

        // Cập nhật mật khẩu mới
        if (!$this->userModel->updateUser($userId, [
            'password' => $data['data']['new_password']
        ])) {
            return $this->json([
                'success' => false,
                'message' => 'Không thể cập nhật mật khẩu'
            ], 500);
        }

        return $this->json([
            'success' => true,
            'message' => 'Đổi mật khẩu thành công'
        ]);
    }

    public function socialLogin() {
        try {
            $data = json_decode(file_get_contents('php://input'), true);

            if (!isset($data['code']) || !isset($data['provider'])) {
                return $this->jsonResponse(['success' => false, 'message' => 'Invalid request'], 400);
            }

            $code = $data['code'];
            $provider = $data['provider'];

            switch ($provider) {
                case 'google':
                    $tokenResponse = $this->exchangeGoogleCodeForToken($code);
                    break;
                case 'facebook':
                    $tokenResponse = $this->exchangeFacebookCodeForToken($code);
                    break;
                case 'twitter':
                    $tokenResponse = $this->exchangeTwitterCodeForToken($code);
                    break;
                default:
                    return $this->jsonResponse(['success' => false, 'message' => 'Unsupported provider'], 400);
            }

            if (!isset($tokenResponse['access_token'])) {
                return $this->jsonResponse(['success' => false, 'message' => 'Failed to retrieve access token'], 500);
            }

            // Retrieve user info from the provider
            $userInfo = $this->getUserInfoFromProvider($provider, $tokenResponse['access_token']);

            if (!$userInfo) {
                return $this->jsonResponse(['success' => false, 'message' => 'Failed to retrieve user info'], 500);
            }

            // Check if user exists in the database
            $user = $this->userModel->findByEmail($userInfo['email']);
            if (!$user) {
                // Create a new user if not exists
                $userId = $this->userModel->create([
                    'username' => $userInfo['name'],
                    'email' => $userInfo['email'],
                    'password' => null, // Social login users don't have passwords
                    'avatar' => $userInfo['picture'] ?? null,
                ]);
                $user = $this->userModel->findById($userId);
            }

            // Generate JWT token
            $token = AuthMiddleware::generateToken($user);

            return $this->jsonResponse([
                'success' => true,
                'access_token' => $token,
                'user' => $user,
            ]);
        } catch (\Exception $e) {
            return $this->jsonResponse(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    private function exchangeGoogleCodeForToken($code) {
        $clientId = $_ENV['GOOGLE_CLIENT_ID'];
        $clientSecret = $_ENV['GOOGLE_CLIENT_SECRET'];
        $redirectUri = $_ENV['GOOGLE_REDIRECT_URI'];

        $response = $this->httpPost('https://oauth2.googleapis.com/token', [
            'code' => $code,
            'client_id' => $clientId,
            'client_secret' => $clientSecret,
            'redirect_uri' => $redirectUri,
            'grant_type' => 'authorization_code',
        ]);

        return json_decode($response, true);
    }

    private function exchangeFacebookCodeForToken($code) {
        $clientId = $_ENV['FACEBOOK_CLIENT_ID'];
        $clientSecret = $_ENV['FACEBOOK_CLIENT_SECRET'];
        $redirectUri = $_ENV['FACEBOOK_REDIRECT_URI'];

        $response = $this->httpGet("https://graph.facebook.com/v10.0/oauth/access_token", [
            'client_id' => $clientId,
            'redirect_uri' => $redirectUri,
            'client_secret' => $clientSecret,
            'code' => $code,
        ]);

        return json_decode($response, true);
    }

    private function exchangeTwitterCodeForToken($code) {
        // Implement Twitter token exchange logic here
        return [];
    }

    private function getUserInfoFromProvider($provider, $accessToken) {
        switch ($provider) {
            case 'google':
                $response = $this->httpGet('https://www.googleapis.com/oauth2/v2/userinfo', [
                    'access_token' => $accessToken,
                ]);
                return json_decode($response, true);
            case 'facebook':
                $response = $this->httpGet('https://graph.facebook.com/me', [
                    'fields' => 'id,name,email,picture',
                    'access_token' => $accessToken,
                ]);
                return json_decode($response, true);
            case 'twitter':
                // Implement Twitter user info retrieval logic here
                return [];
            default:
                return null;
        }
    }

    private function httpPost($url, $data) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
        $response = curl_exec($ch);
        curl_close($ch);
        return $response;
    }

    private function httpGet($url, $params) {
        $url .= '?' . http_build_query($params);
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $response = curl_exec($ch);
        curl_close($ch);
        return $response;
    }

    private function sendVerificationEmail($email, $token) {
        // Implement email sending logic here
        // You can use PHPMailer or other email libraries
    }

    private function sendResetPasswordEmail($email, $token) {
        // Implement email sending logic here
        // You can use PHPMailer or other email libraries
    }

    private function jsonResponse($data, $statusCode = 200) {
        http_response_code($statusCode);
        header('Content-Type: application/json');
        echo json_encode($data);
        exit;
    }

    private function handleFileUpload($inputName, $uploadDir) {
        if (!isset($_FILES[$inputName])) {
            return [
                'success' => false,
                'message' => 'No file uploaded'
            ];
        }

        $file = $_FILES[$inputName];
        $fileName = $file['name'];
        $fileTmpName = $file['tmp_name'];
        $fileSize = $file['size'];
        $fileError = $file['error'];

        // Validate file
        if ($fileError !== UPLOAD_ERR_OK) {
            return [
                'success' => false,
                'message' => 'Upload failed with error code: ' . $fileError
            ];
        }

        // Validate file size (max 2MB)
        if ($fileSize > 2097152) {
            return [
                'success' => false,
                'message' => 'File size too large (max 2MB)'
            ];
        }

        // Validate file type
        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
        $fileType = mime_content_type($fileTmpName);
        if (!in_array($fileType, $allowedTypes)) {
            return [
                'success' => false,
                'message' => 'Invalid file type'
            ];
        }

        // Generate unique filename
        $fileExt = pathinfo($fileName, PATHINFO_EXTENSION);
        $newFileName = uniqid() . '.' . $fileExt;

        // Create upload directory if not exists
        $uploadPath = __DIR__ . '/../../../uploads/' . $uploadDir;
        if (!file_exists($uploadPath)) {
            mkdir($uploadPath, 0777, true);
        }

        // Move uploaded file
        $destination = $uploadPath . '/' . $newFileName;
        if (!move_uploaded_file($fileTmpName, $destination)) {
            return [
                'success' => false,
                'message' => 'Failed to move uploaded file'
            ];
        }

        return [
            'success' => true,
            'path' => '/uploads/' . $uploadDir . '/' . $newFileName
        ];
    }
}