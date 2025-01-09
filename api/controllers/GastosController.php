<?php

namespace api\controllers;

use api\models\Gastos;
use api\models\Categorias;
use api\libs\Database;
use api\models\Auth; // Importa el modelo de autenticación

class GastosController
{
    protected $db;
    protected $gastosModel;
    protected $categoriasModel;
    protected $authModel; // Instanciar el modelo Auth

    public function __construct()
    {
        $this->db = new Database(); // Se inicializa la clase Database
        $this->gastosModel = new Gastos($this->db);
        $this->categoriasModel = new Categorias($this->db);
        $this->authModel = new Auth($this->db); // Instancia del modelo Auth
    }

    // Método para extraer el token desde la cabecera de la solicitud
    private function getBearerToken()
    {
        $headers = getallheaders();
        if (isset($headers['Authorization'])) {
            $matches = [];
            if (preg_match('/Bearer (.+)/', $headers['Authorization'], $matches)) {
                return $matches[1];  // Token extraído de la cabecera
            }
        }
        return null;  // Si no se encuentra el token
    }

    // Verificación del token antes de la acción
    private function authenticate()
    {
        $token = $this->getBearerToken();
        if (!$token) {
            http_response_code(401); // No autorizado
            echo json_encode(['status' => 'error', 'message' => 'Token no proporcionado']);
            exit;
        }

        // Verificar el token
        $user = $this->authModel->validateToken($token);
        if (!$user) {
            http_response_code(401); // No autorizado
            echo json_encode(['status' => 'error', 'message' => 'Token inválido o expirado']);
            exit;
        }

        return $user; // Devuelve el usuario si el token es válido
    }

    // Listar todos los gastos
    public function index()
    {
        try {
            // Verificar token
            $user = $this->authenticate(); // Si el token no es válido, el proceso se detiene aquí

            $gastos = $this->gastosModel->getAllGastos($user['id']);
            header('Content-Type: application/json');
            echo json_encode(['status' => 'success', 'data' => $gastos]);
        } catch (\Exception $e) {
            http_response_code(500);
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
