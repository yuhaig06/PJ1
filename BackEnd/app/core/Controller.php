<?php

namespace App\Core;

class Controller {
    protected $db;
    protected $model;

    public function __construct() {
        $this->db = Database::getInstance();
    }

    protected function model($model) {
        $modelClass = "App\\Models\\" . $model;
        return new $modelClass();
    }

    protected function view($view, $data = []) {
        header('Content-Type: application/json');
        echo json_encode([
            'status' => 'success',
            'data' => $data
        ]);
    }
}