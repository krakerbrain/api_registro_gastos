<?php

namespace api\controllers;

use api\models\Gastos;
use api\models\User;
use api\models\Categorias;
use api\libs\Database;
use api\middlewares\AuthService;

class GastosController
{
    protected $db;
    protected $gastosModel;
    protected $userModel;
    protected $categoriasModel;
    private $authService;

    public function __construct()
    {
        $this->db = new Database(); // Se inicializa la clase Database
        $this->gastosModel = new Gastos($this->db);
        $this->userModel = new User($this->db);
        $this->categoriasModel = new Categorias($this->db);
        $this->authService = new AuthService($this->db);
    }

    // Listar todos los gastos
    public function getGastos($vars = null)
    {
        try {
            // Autenticar usuario
            $user = $this->authService->authenticate();
            $userId = $vars['idUsuario'] ?? null;

            // Filtrar según el rol del usuario
            if ($user['role_id'] === 1) {
                // Rol 1: Admin
                if ($vars) {
                    // Validar que el usuario existe antes de consultar
                    if (!$this->userModel->userExists($userId)) {
                        throw new \Exception('Usuario no encontrado', 404);
                    }
                    $gastos = $this->gastosModel->getGastosByUser($userId);
                } else {
                    $gastos = $this->gastosModel->getAllGastos();
                }
            } elseif ($user['role_id'] === 2) {
                // Rol 2: Gestor, puede ver los gastos propios y los que administra
                $gastos = $this->gastosModel->getGastosByAdminOrUser($user['id']);
            } elseif ($user['role_id'] === 3) {
                // Rol 3: Usuario, solo puede ver sus propios gastos
                if ($userId && $userId != $user['id']) {
                    throw new \Exception('Acceso denegado', 403);
                }
                $gastos = $this->gastosModel->getGastosByUser($user['id']);
            } else {
                throw new \Exception('Rol no permitido', 403);
            }

            // Responder con los datos
            header('Content-Type: application/json');
            echo json_encode(['status' => 'success', 'data' => $gastos]);
        } catch (\Exception $e) {
            http_response_code($e->getCode() ?: 500);
            echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        }
    }

    // Insertar un nuevo gasto
    public function insert()
    {
        try {
            // Verificar token
            $user = $this->authenticate(); // Si el token no es válido, el proceso se detiene aquí

            // Obtener los datos del cuerpo de la solicitud
            $data = json_decode(file_get_contents('php://input'), true);

            // Validar los datos necesarios
            if (empty($data['monto'])) {
                throw new \Exception("Faltan datos obligatorios.");
            }

            // Preparar los datos para insertar el gasto
            $gastoData = [
                'idusuario' => $user['id'],  // Usar el ID del usuario autenticado
                'monto' => $data['monto'],
                'tipoGasto' => $data['tipoGasto'] ?? null,
                'detallesId' => $data['detallesId'] ?? [],  // Agregar detallesId al array
                'nombreTipoGasto' => $data['nombreTipoGasto'] ?? null,
            ];

            // Insertar el gasto y obtener la respuesta
            $gasto = $this->gastosModel->insertGasto($gastoData);

            // Responder con el gasto insertado
            header('Content-Type: application/json');
            echo json_encode(['status' => 'success', 'data' => $gasto]);
        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        }
    }

    // Actualizar un gasto
    public function update()
    {
        try {
            // Verificar token
            $user = $this->authenticate(); // Si el token no es válido, el proceso se detiene aquí

            // Obtener los datos del cuerpo de la solicitud
            $data = json_decode(file_get_contents('php://input'), true);

            // Validar los datos necesarios
            if (empty($data['id']) || !isset($data['monto_gasto'])) {
                throw new \Exception("Faltan datos obligatorios.");
            }

            // Actualizar el gasto y obtener la respuesta
            $response = $this->gastosModel->updateGasto($data);

            // Enviar la respuesta como JSON
            header('Content-Type: application/json');
            echo json_encode(['status' => 'success', 'data' => $response]);
        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        }
    }

    // Eliminar un gasto
    public function delete($id)
    {
        try {
            // Verificar token
            $user = $this->authenticate(); // Si el token no es válido, el proceso se detiene aquí

            $gastoId = $id['id'] ?? null;
            if (!$gastoId) {
                http_response_code(400);
                echo json_encode(['status' => 'error', 'message' => 'ID del gasto es requerido.']);
                return;
            }

            // Eliminar el gasto y responder
            $response = $this->gastosModel->deleteGasto($gastoId);

            header('Content-Type: application/json');
            echo json_encode(['status' => 'success', 'data' => $response]);
        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        }
    }
}
