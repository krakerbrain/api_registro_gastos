<?php

namespace api\controllers;

use api\models\User;
use api\libs\Database;

class UserController
{
    protected $db;
    protected $userModel;

    public function __construct()
    {
        $this->db = new Database(); // Se inicializa la clase Database
        $this->userModel = new User($this->db); // Se inicializa el modelo User
    }

    // Método para obtener todos los usuarios
    public function index()
    {
        try {
            // Usamos el modelo para obtener los usuarios
            $users = $this->userModel->getAllUsers();

            header('Content-Type: application/json');
            echo json_encode(['status' => 'success', 'data' => $users]);
        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        }
    }

    // Método para crear un usuario
    public function store()
    {
        try {
            // Obtener los datos del cuerpo de la solicitud (en formato JSON)
            $input = json_decode(file_get_contents('php://input'), true);

            // Validación básica de los parámetros recibidos
            if (empty($input['name']) || empty($input['email']) || empty($input['password'])) {
                http_response_code(400); // Bad Request
                echo json_encode(['status' => 'error', 'message' => 'Datos incompletos']);
                return;
            }

            // Crear un nuevo usuario
            $this->userModel->create($input['name'], $input['email'], $input['password']);

            header('Content-Type: application/json');
            echo json_encode(['status' => 'success', 'message' => 'Usuario creado']);
        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        }
    }

    // Método para actualizar un usuario
    public function update($id)
    {
        try {
            // Obtener los datos del cuerpo de la solicitud (en formato JSON)
            $input = json_decode(file_get_contents('php://input'), true);

            $userId = $id['id'] ?? null;
            if (!$userId) {
                http_response_code(400);
                echo json_encode(['status' => 'error', 'message' => 'ID del usuario es requerido.']);
                return;
            }

            // Validación básica de los parámetros recibidos
            if (empty($input['name']) || empty($input['email'])) {
                http_response_code(400); // Bad Request
                echo json_encode(['status' => 'error', 'message' => 'Datos incompletos']);
                return;
            }

            // Llamamos al método de actualización en el modelo
            $updated = $this->userModel->update($userId, $input['name'], $input['email']);

            if ($updated) {
                header('Content-Type: application/json');
                echo json_encode(['status' => 'success', 'message' => 'Usuario actualizado']);
            } else {
                http_response_code(404); // Not Found
                echo json_encode(['status' => 'error', 'message' => 'Usuario no encontrado']);
            }
        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        }
    }

    // Método para actualizar la contraseña del usuario
    public function updatePassword($id)
    {
        try {
            $input = json_decode(file_get_contents('php://input'), true);

            $userId = $id['id'] ?? null;
            if (!$userId) {
                http_response_code(400);
                echo json_encode(['status' => 'error', 'message' => 'ID del usuario es requerido.']);
                return;
            }

            // Validación de los campos
            if (empty($input['current_password']) || empty($input['new_password'])) {
                http_response_code(400);
                echo json_encode(['status' => 'error', 'message' => 'Contraseña actual y nueva son requeridas']);
                return;
            }

            // Consultamos el usuario
            $userData = $this->userModel->getUserById($userId);

            // Verificar la contraseña actual
            if (!password_verify($input['current_password'], $userData['password'])) {
                http_response_code(400);
                echo json_encode(['status' => 'error', 'message' => 'Contraseña actual incorrecta']);
                return;
            }

            // Actualizamos la contraseña
            $newPassword = password_hash($input['new_password'], PASSWORD_DEFAULT);
            $this->userModel->updatePassword($userId, $newPassword);

            echo json_encode(['status' => 'success', 'message' => 'Contraseña actualizada']);
        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        }
    }

    // Método para eliminar un usuario
    public function delete($id)
    {
        try {

            $userId = $id['id'] ?? null;
            if (!$userId) {
                http_response_code(400);
                echo json_encode(['status' => 'error', 'message' => 'ID del usuario es requerido.']);
                return;
            }
            // Llamamos al método de eliminación en el modelo
            $deleted = $this->userModel->delete($userId);

            if ($deleted) {
                header('Content-Type: application/json');
                echo json_encode(['status' => 'success', 'message' => 'Usuario eliminado']);
            } else {
                http_response_code(404); // Not Found
                echo json_encode(['status' => 'error', 'message' => 'Usuario no encontrado']);
            }
        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        }
    }
}
