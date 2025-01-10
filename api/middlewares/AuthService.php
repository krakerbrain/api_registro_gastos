<?php

namespace api\middlewares;

use api\models\Auth;

class AuthService
{
    private $authModel;

    public function __construct($db)
    {
        $this->authModel = new Auth($db);
    }

    // Extraer el token del encabezado
    public function getBearerToken()
    {
        $headers = getallheaders();
        if (isset($headers['Authorization'])) {
            $matches = [];
            if (preg_match('/Bearer (.+)/', $headers['Authorization'], $matches)) {
                return $matches[1];
            }
        }
        return null;
    }

    // Autenticar el usuario basado en el token
    public function authenticate()
    {
        $token = $this->getBearerToken();
        if (!$token) {
            throw new \Exception('Token no proporcionado', 401);
        }

        $user = $this->authModel->validateToken($token);
        if (!$user) {
            throw new \Exception('Token inválido o expirado', 401);
        }

        return $user; // Retorna el usuario autenticado
    }

    // Autorizar roles específicos
    public function authorize($user, $roles = [])
    {
        if (!in_array($user['role_id'], $roles)) {
            throw new \Exception('No tienes permiso para acceder a esta ruta', 403);
        }
    }
}
