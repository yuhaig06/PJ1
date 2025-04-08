<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Models\UserModel;
use App\Middleware\AuthMiddleware;
use App\Middleware\CSRFMiddleware;
use App\Middleware\RateLimitMiddleware;
use App\Config\Database;

class AuthController extends Controller
{
    private $db;
    private $userModel;
    private $authMiddleware;
    private $csrfMiddleware;
    private $rateLimitMiddleware;

    public function __construct()
    {
        parent::__construct(); // Đảm bảo lớp cha có phương thức __construct()
        $this->db = Database::getInstance()->getConnection();
        $this->userModel = new UserModel($this->db);
        $this->authMiddleware = new AuthMiddleware();
        $this->csrfMiddleware = new CSRFMiddleware();
        $this->rateLimitMiddleware = new RateLimitMiddleware();
    }

    /**
     * Đăng ký tài khoản mới
     */
    public function register()
    {
        try {
            $data = json_decode(file_get_contents('php://input'), true);
            
            if (!isset($data['username']) || !isset($data['email']) || !isset($data['password'])) {
                return $this->jsonResponse(['success' => false, 'message' => 'All fields are required'], 400);
            }

            if ($this->userModel->findByEmail($data['email'])) {
                return $this->jsonResponse(['success' => false, 'message' => 'Email already exists'], 400);
            }

            $data['password'] = AuthMiddleware::hashPassword($data['password']);
            $data['verification_token'] = bin2hex(random_bytes(32));
            
            $userId = $this->userModel->create($data);
            
            if ($userId) {
                // Send verification email
                $this->sendVerificationEmail($data['email'], $data['verification_token']);
                
                return $this->jsonResponse([
                    'success' => true,
                    'message' => 'Registration successful. Please check your email to verify your account.'
                ]);
            }
            
            return $this->jsonResponse(['success' => false, 'message' => 'Registration failed'], 500);
        } catch (\Exception $e) {
            return $this->jsonResponse(['success' => false, 'message' => 'Registration failed'], 500);
        }
    }

    /**
     * Đăng nhập
     */
    public function login()
    {
        try {
            $data = json_decode(file_get_contents('php://input'), true);
            
            if (!isset($data['email']) || !isset($data['password'])) {
                return $this->jsonResponse(['success' => false, 'message' => 'Email and password are required'], 400);
            }

            $user = $this->userModel->findByEmail($data['email']);
            
            if (!$user || !AuthMiddleware::validatePassword($data['password'], $user['password'])) {
                return $this->jsonResponse(['success' => false, 'message' => 'Invalid email or password'], 401);
            }

            $token = AuthMiddleware::generateToken($user);
            
            unset($user['password']);
            return $this->jsonResponse([
                'success' => true,
                'token' => $token,
                'user' => $user
            ]);
        } catch (\Exception $e) {
            return $this->jsonResponse(['success' => false, 'message' => 'Login failed'], 500);
        }
    }

    /**
     * Đăng xuất
     */
    public function logout()
    {
        // Kiểm tra đăng nhập
        if (!$this->authMiddleware->check()) {
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
        // Kiểm tra đăng nhập
        if (!$this->authMiddleware->check()) {
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

            $hashedPassword = AuthMiddleware::hashPassword($data['newPassword']);
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
    public function updateProfile()
    {
        // Kiểm tra đăng nhập
        if (!$this->authMiddleware->check()) {
            return $this->json([
                'success' => false,
                'message' => 'Chưa đăng nhập'
            ], 401);
        }

        // Validate dữ liệu
        $data = $this->validate([
            'full_name' => 'required|max:100',
            'avatar' => 'nullable|image|max:2048'
        ]);

        if (!$data['success']) {
            return $this->json([
                'success' => false,
                'message' => 'Dữ liệu không hợp lệ',
                'errors' => $data['errors']
            ], 422);
        }

        $userId = $_SESSION['user_id'];
        $updateData = [
            'full_name' => $data['data']['full_name']
        ];

        // Xử lý upload avatar
        if (isset($_FILES['avatar'])) {
            $avatar = $this->uploadFile('avatar', 'avatars');
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
    public function changePassword()
    {
        // Kiểm tra đăng nhập
        if (!$this->authMiddleware->check()) {
            return $this->json([
                'success' => false,
                'message' => 'Chưa đăng nhập'
            ], 401);
        }

        // Validate dữ liệu
        $data = $this->validate([
            'current_password' => 'required',
            'new_password' => 'required|min:6|max:50'
        ]);

        if (!$data['success']) {
            return $this->json([
                'success' => false,
                'message' => 'Dữ liệu không hợp lệ',
                'errors' => $data['errors']
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
}