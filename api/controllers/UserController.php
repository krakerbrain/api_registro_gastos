<?php

namespace api\controllers;

use api\models\User;
use api\libs\Database;
use api\middlewares\AuthService;
use api\middlewares\RoleValidator;

class UserController
{
    protected $db;
    protected $userModel;
    private $authService;

    public function __construct()
    {
        $this->db = new Database(); // Se inicializa la clase Database
        $this->userModel = new User($this->db); // Se inicializa el modelo User
        $this->authService = new AuthService($this->db);
    }

    // Método para obtener todos los usuarios
    public function index()
    {
        try {
            // Autenticar al usuario actual para obtener su información
            $currentUser = $this->authService->authenticate(); // Ejemplo: devuelve ['id' => 1, 'role_id' => 2]

            // Filtrar usuarios según el rol del usuario autenticado
            switch ($currentUser['role_id']) {
                case 1: // Superadministrador
                    // Puede ver todos los usuarios
                    $users = $this->userModel->getAllUsers();
                    break;

                case 2: // Administrador
                    // Puede ver su propio usuario y los que ha registrado (por admin_id)
                    $users = $this->userModel->getUsersByAdmin($currentUser['id']);
                    break;

                case 3: // Usuario estándar
                    // Solo puede ver su propio usuario
                    $users = $this->userModel->getUserById($currentUser['id']);
                    break;

                default:
                    throw new \Exception('Rol no válido');
            }

            // Responder con los usuarios filtrados
            header('Content-Type: application/json');
            echo json_encode(['status' => 'success', 'data' => $users]);
        } catch (\Exception $e) {
            http_response_code($e->getCode() ?: 500);
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
            if (empty($input['name']) || empty($input['email']) || empty($input['password']) || empty($input['role_id'])) {
                http_response_code(400); // Bad Request
                echo json_encode(['status' => 'error', 'message' => 'Datos incompletos']);
                return;
            }

            // Autenticar al usuario actual para obtener su rol
            $currentUser = $this->authService->authenticate(); // Ejemplo: devuelve ['id' => 1, 'role_id' => 2]

            // Validar el rol utilizando RoleValidator
            RoleValidator::validate($currentUser['role_id'], $input['role_id']);

            // Crear un nuevo usuario en la base de datos
            $this->userModel->create($input, $currentUser['id']);

            // Responder con éxito
            header('Content-Type: application/json');
            echo json_encode(['status' => 'success', 'message' => 'Usuario creado']);
        } catch (\Exception $e) {
            http_response_code($e->getCode() ?: 500);
            echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        }
    }

    // Método para obtener los datos de un usuario por ID
    public function update($userId)
    {
        $id = $userId['id'] ?? null;
        try {
            // Autenticar al usuario actual para obtener su información
            $currentUser = $this->authService->authenticate(); // Devuelve ['id' => 1, 'role_id' => 2]

            // Validación de parámetros
            $input = json_decode(file_get_contents('php://input'), true);
            if (empty($id) || empty($input['name']) || empty($input['email'])) {
                http_response_code(400); // Bad Request
                echo json_encode(['status' => 'error', 'message' => 'Datos incompletos o ID faltante']);
                return;
            }

            // Obtener información del usuario a modificar
            $targetUser = $this->userModel->getUserById($id);
            if (!$targetUser) {
                http_response_code(404); // Not Found
                echo json_encode(['status' => 'error', 'message' => 'Usuario no encontrado']);
                return;
            }

            // Validar permisos utilizando RoleValidator
            RoleValidator::validateUpdatePermission($currentUser, $targetUser, false);

            // Realizar la actualización
            $updated = $this->userModel->update($id, $input['name'], $input['email']);
            if ($updated) {
                header('Content-Type: application/json');
                echo json_encode(['status' => 'success', 'message' => 'Usuario actualizado']);
            } else {
                http_response_code(500); // Error interno
                echo json_encode(['status' => 'error', 'message' => 'No se pudo actualizar el usuario']);
            }
        } catch (\Exception $e) {
            http_response_code(500); // Error interno
            echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        }
    }

    // Método para actualizar la contraseña del usuario
    public function updatePassword($id)
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

            // Validación de los campos
            if (empty($input['current_password']) || empty($input['new_password'])) {
                http_response_code(400);
                echo json_encode(['status' => 'error', 'message' => 'Contraseña actual y nueva son requeridas']);
                return;
            }

            // Autenticar al usuario actual para obtener su información
            $currentUser = $this->authService->authenticate(); // Devuelve ['id' => 1, 'role_id' => 2]

            // Obtener información del usuario a modificar
            $targetUser = $this->userModel->getUserById($userId);

            // Validar permisos utilizando RoleValidator
            RoleValidator::validateUpdatePermission($currentUser, $targetUser, true);

            // Usamos el método del modelo para verificar la contraseña actual **después** de haber comprobado los permisos
            $isPasswordValid = $this->userModel->verifyPassword($userId, $input['current_password']);
            if (!$isPasswordValid) {
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

            // Autenticar al usuario actual para obtener su información
            $currentUser = $this->authService->authenticate(); // Devuelve ['id' => 1, 'role_id' => 2]

            // Obtener información del usuario a eliminar
            $targetUser = $this->userModel->getUserById($userId);

            // Verificar permisos según el rol del usuario actual
            RoleValidator::validateDeletePermission($currentUser, $targetUser);

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
