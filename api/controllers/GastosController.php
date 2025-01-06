<?php

namespace api\controllers;

use api\models\Gastos;
use api\models\Categorias;
use api\libs\Database;

class GastosController
{
    protected $db;
    protected $gastosModel;
    protected $categoriasModel;

    public function __construct()
    {
        $this->db = new Database(); // Se inicializa la clase Database
        $this->gastosModel = new Gastos($this->db);
        $this->categoriasModel = new Categorias($this->db);
    }

    // Listar todos los gastos
    public function index()
    {
        try {
            $gastos = $this->gastosModel->getAllGastos();
            header('Content-Type: application/json');
            echo json_encode(['status' => 'success', 'data' => $gastos]);
        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        }
    }

    public function insert()
    {
        try {
            // Obtener los datos del cuerpo de la solicitud
            $data = json_decode(file_get_contents('php://input'), true);

            // Validar los datos necesarios
            if (empty($data['idusuario']) || empty($data['monto'])) {
                throw new \Exception("Faltan datos obligatorios.");
            }

            // Verificar si tipoGasto es vacío
            $tipoGasto = $data['tipoGasto'] ?? null;
            $nombreTipoGasto = $data['nombreTipoGasto'] ?? null;

            if (empty($tipoGasto)) {
                // Si tipoGasto está vacío, verificar el nombre del tipo de gasto
                if (empty($nombreTipoGasto)) {
                    throw new \Exception("Debe proporcionar un nombre de tipo de gasto.");
                }

                // Normalizar el nombre a minúsculas antes de buscar
                $nombreTipoGasto = strtolower($nombreTipoGasto);

                // Verificar si el tipo de gasto existe por nombre
                $tipoGastoId = $this->categoriasModel->findCategoriaByNombre($nombreTipoGasto);

                if (!$tipoGastoId) {
                    // Si no existe, insertar la nueva categoría con el nombre normalizado
                    $tipoGastoId = $this->categoriasModel->insertCategoria($nombreTipoGasto);
                }
            } else {
                // Si tipoGasto no está vacío, usar el ID proporcionado
                $tipoGastoId = $tipoGasto;

                // Verificar que el ID del tipo de gasto exista
                $categoria = $this->categoriasModel->findCategoriaById($tipoGastoId);
                if (!$categoria) {
                    throw new \Exception("El tipo de gasto con ID {$tipoGastoId} no existe.");
                }
            }

            // Preparar los datos para insertar el gasto
            $gastoData = [
                'idusuario' => $data['idusuario'],
                'monto' => $data['monto'],
                'tipoGasto' => $tipoGastoId, // Usar el ID del tipo de gasto
                'detallesId' => $data['detallesId'] ?? []
            ];

            // Insertar el gasto y obtener la respuesta
            $gasto = $this->gastosModel->insertGasto($gastoData);

            // Responder con el gasto insertado
            header('Content-Type: application/json');
            echo json_encode(['status' => 'success', 'data' => $gasto]);
        } catch (\Exception $e) {
            // Manejar errores
            http_response_code(500);
            echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        }
    }




    public function update()
    {
        try {
            // Obtener los datos del cuerpo de la solicitud
            $data = json_decode(file_get_contents('php://input'), true);

            // Validar los datos necesarios
            if (empty($data['id']) || !isset($data['monto_gasto']) || !isset($data['descripciones'])) {
                throw new Exception("Faltan datos obligatorios.");
            }

            // Actualizar el gasto y obtener la respuesta
            $response = $this->gastosModel->updateGasto($data);

            // Enviar la respuesta como JSON
            header('Content-Type: application/json');
            echo json_encode(['status' => 'success', 'data' => $response]);
        } catch (\Exception $e) {
            // Manejar errores
            http_response_code(500);
            echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        }
    }

    public function delete($id)
    {
        try {

            $gastoId = $id['id'] ?? null;
            if (!$gastoId) {
                http_response_code(400);
                echo json_encode(['status' => 'error', 'message' => 'ID del gasto es requerido.']);
                return;
            }
            // Validar que el ID no esté vacío y sea numérico
            if (!is_numeric($gastoId) || $gastoId <= 0) {
                http_response_code(400);
                echo json_encode(['status' => 'error', 'message' => 'ID inválido. Debe ser un número positivo.']);
                return;
            }

            // Llamar al método del modelo para eliminar el gasto
            $response = $this->gastosModel->deleteGasto($gastoId);

            // Responder con éxito
            header('Content-Type: application/json');
            echo json_encode(['status' => 'success', 'data' => $response]);
        } catch (\Exception $e) {
            // Manejar errores
            http_response_code(500);
            echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        }
    }
}
