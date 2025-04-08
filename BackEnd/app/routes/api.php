<?php
namespace App\Routes;

class Router {
    private static $routes = [
        'auth' => [
            'login' => ['AuthController', 'login', 'POST'],
            'register' => ['AuthController', 'register', 'POST'],
            'profile' => ['AuthController', 'profile', 'GET']
        ],
        'news' => [
            'list' => ['NewsController', 'index', 'GET'],
            'detail' => ['NewsController', 'show', 'GET'],
            'latest' => ['NewsController', 'latest', 'GET']
        ],
        'products' => [
            'list' => ['ProductController', 'index', 'GET'],
            'detail' => ['ProductController', 'show', 'GET'],
            'categories' => ['ProductController', 'categories', 'GET']
        ],
        'games' => [
            'list' => ['GameController', 'index', 'GET'],
            'detail' => ['GameController', 'show', 'GET'],
            'reviews' => ['GameController', 'reviews', 'GET']
        ]
    ];

    public static function getRoutes() {
        return self::$routes;
    }
}
