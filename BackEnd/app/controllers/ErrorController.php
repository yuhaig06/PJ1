<?php

namespace App\Controllers;

class ErrorController {
    public function notFound() {
        http_response_code(404);
        
        // Render view 404
        require_once APPROOT . '/views/errors/404.php';
    }
    
    public function error500() {
        http_response_code(500);
        
        // Render view 500
        require_once APPROOT . '/views/errors/500.php';
    }
}