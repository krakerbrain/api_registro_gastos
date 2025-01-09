<?php

namespace api\controllers;

use api\models\Auth;
use api\libs\Database;

class AuthController
{
    private $auth;

    public function __construct()
    {
        $db = new Database();
        $this->auth = new Auth($db);
    }

    public function login()
    {
        try {
            $input = json_decode(file_get_contents('php://input'), true);

            if (empty($input['email']) || empty($input['password'])) {
                http_response_code(400);
                echo json_encode(['status' => 'error', 'message' => 'Datos incompletos']);
                return;
            }

            $user = $this->auth->authenticate($input['email'], $input['password']);

            if ($user) {
                $token = $this->auth->generateToken($user['id']);
                echo json_encode([
                    'status' => 'success',
                    'token' => $token,
                    'user' => [
                        'id' => $user['id'],
                        'name' => $user['name'],
                        'email' => $user['email'],
                        'role' => $user['role_id']
                    ]
                ]);
            } else {
                http_response_code(401);
                echo json_encode(['status' => 'error', 'message' => 'Credenciales inválidas']);
            }
        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        }
    }
}
