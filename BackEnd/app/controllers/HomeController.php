<?php
namespace App\Controllers;

use App\Core\Controller;

class HomeController extends Controller {
    public function __construct() {
        parent::__construct();
    }

    public function index() {
        // Set headers
        header('Content-Type: application/json; charset=utf-8');
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Methods: GET');

        // Build API info response
        $response = [
            'status' => 'success',
            'message' => 'Welcome to WarStorm API',
            'version' => '1.0.0',
            'timestamp' => date('Y-m-d H:i:s'),
            'documentation' => [
                'description' => 'RESTful API for WarStorm Gaming Platform',
                'base_url' => 'http://localhost/PJ1/BackEnd/public/api',
                'endpoints' => [
                    'auth' => [
                        'login' => ['POST /auth/login', 'Đăng nhập hệ thống'],
                        'register' => ['POST /auth/register', 'Đăng ký tài khoản mới'],
                        'profile' => ['GET /auth/profile', 'Xem thông tin cá nhân']
                    ],
                    'news' => [
                        'list' => ['GET /news', 'Danh sách tin tức'],
                        'detail' => ['GET /news/{id}', 'Chi tiết tin tức'],
                        'latest' => ['GET /news/latest', 'Tin tức mới nhất']
                    ],
                    'products' => [
                        'list' => ['GET /products', 'Danh sách sản phẩm'],
                        'detail' => ['GET /products/{id}', 'Chi tiết sản phẩm'],
                        'categories' => ['GET /products/categories', 'Danh mục sản phẩm']
                    ]
                ]
            ]
        ];

        // Output JSON with pretty print
        echo json_encode($response, 
            JSON_PRETTY_PRINT | 
            JSON_UNESCAPED_UNICODE | 
            JSON_UNESCAPED_SLASHES
        );
    }
}
?>