<?php

namespace api\controllers;

use api\models\User;
use api\libs\Database;
use api\middlewares\AuthService;

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

            // Verificar permisos según el rol del usuario actual
            switch ($currentUser['role_id']) {
                case 1: // Superadministrador
                    // Puede registrar roles 2 (Administrador) o 3 (Usuario estándar)
                    if (!in_array($input['role_id'], [2, 3])) {
                        throw new \Exception('Permiso denegado para asignar este rol');
                    }
                    break;

                case 2: // Administrador
                    // Solo puede registrar usuarios estándar (Rol 3)
                    if ($input['role_id'] !== 3) {
                        throw new \Exception('Permiso denegado para asignar este rol');
                    }
                    // Asociar admin_id al usuario registrado
                    $input['admin_id'] = $currentUser['id'];
                    break;

                case 3: // Usuario estándar
                    // No tiene permisos para registrar usuarios
                    throw new \Exception('Permiso denegado para registrar usuarios');

                default:
                    throw new \Exception('Rol no válido');
            }

            // Crear un nuevo usuario en la base de datos
            $this->userModel->create($input);

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

            // Verificar permisos según el rol del usuario actual
            switch ($currentUser['role_id']) {
                case 1: // Superadministrador
                    // Puede modificar a cualquier usuario, no necesita restricciones adicionales
                    break;

                case 2: // Administrador
                    // Solo puede modificar su propio perfil o los usuarios Rol 3 que ha registrado
                    if ($currentUser['id'] !== $targetUser['id'] && $targetUser['role_id'] !== 3) {
                        http_response_code(403); // Forbidden
                        echo json_encode(['status' => 'error', 'message' => 'No tienes permisos para modificar este usuario']);
                        return;
                    }

                    // Verificar que el usuario con Rol 3 pertenece a este administrador
                    if ($targetUser['role_id'] === 3 && $targetUser['admin_id'] !== $currentUser['id']) {
                        http_response_code(403); // Forbidden
                        echo json_encode(['status' => 'error', 'message' => 'No tienes permisos para modificar este usuario']);
                        return;
                    }
                    break;

                case 3: // Usuario estándar
                    // No puede modificar a ningún usuario, incluido a sí mismo
                    http_response_code(403); // Forbidden
                    echo json_encode(['status' => 'error', 'message' => 'No tienes permisos para modificar usuarios']);
                    return;

                default:
                    http_response_code(400); // Bad Request
                    echo json_encode(['status' => 'error', 'message' => 'Rol no válido']);
                    return;
            }

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

            // Verificar permisos según el rol del usuario actual
            switch ($currentUser['role_id']) {
                case 1:
                    // Rol 1 puede actualizar la contraseña de cualquier usuario
                    break;
                case 2:
                    // Rol 2 puede actualizar su propia contraseña y la de los usuarios con rol 3 bajo su administración
                    if ($currentUser['id'] !== $targetUser['id'] && $targetUser['role_id'] !== 3) {
                        http_response_code(403); // Forbidden
                        echo json_encode(['status' => 'error', 'message' => 'No tienes permisos para cambiar la contraseña de este usuario']);
                        return;
                    }

                    // Verificar que el usuario con Rol 3 pertenece a este administrador
                    if ($targetUser['role_id'] === 3 && $targetUser['admin_id'] !== $currentUser['id']) {
                        http_response_code(403); // Forbidden
                        echo json_encode(['status' => 'error', 'message' => 'No tienes permisos para modificar la contraseña de este usuario']);
                        return;
                    }

                    break;
                case 3:
                    // Rol 3 solo puede actualizar su propia contraseña
                    if ($userId != $currentUser['id']) {
                        http_response_code(403); // Forbidden
                        echo json_encode(['status' => 'error', 'message' => 'No tienes permisos para cambiar tu contraseña']);
                        return;
                    }
                    break;
                default:
                    http_response_code(403); // Forbidden
                    echo json_encode(['status' => 'error', 'message' => 'Rol desconocido']);
                    return;
            }

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
            switch ($currentUser['role_id']) {
                case 1:
                    // Rol 1 puede eliminar cualquier usuario
                    break;
                case 2:
                    // Rol 2 solo puede eliminar usuarios con rol 3
                    if ($targetUser['role_id'] !== 3) {
                        http_response_code(403); // Forbidden
                        echo json_encode(['status' => 'error', 'message' => 'No tienes permisos para eliminar este usuario']);
                        return;
                    }
                    break;
                case 3:
                    // Rol 3 no puede eliminar a ningún usuario
                    http_response_code(403); // Forbidden
                    echo json_encode(['status' => 'error', 'message' => 'No tienes permisos para eliminar usuarios']);
                    return;
                default:
                    http_response_code(403); // Forbidden
                    echo json_encode(['status' => 'error', 'message' => 'Rol desconocido']);
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
