<?php

namespace api\controllers;

use api\models\Categorias;
use api\libs\Database;

class CategoriasController
{
    protected $db;
    protected $categoriasModel;

    public function __construct()
    {
        $this->db = new Database(); // Se inicializa la clase Database
        $this->categoriasModel = new Categorias($this->db, null);
    }

    // Listar todas las categorias
    public function index()
    {
        try {
            $categorias = $this->categoriasModel->index();
            header('Content-Type: application/json');
            echo json_encode(['status' => 'success', 'data' => $categorias]);
        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        }
    }

    // Obtener los 6 gastos más frecuentes
    public function getTopGastos()
    {
        try {
            $topGastos = $this->categoriasModel->getTopGastos();
            header('Content-Type: application/json');
            echo json_encode(['status' => 'success', 'data' => $topGastos]);
        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        }
    }

    // Obtener los 12 detalles más frecuentes de un gasto
    public function getDetallesFrecuentes($vars)
    {
        try {
            $gastoId = $vars['id'] ?? null;
            if (!$gastoId) {
                http_response_code(400);
                echo json_encode(['status' => 'error', 'message' => 'ID del gasto es requerido.']);
                return;
            }

            $detalles = $this->categoriasModel->getDetallesFrecuentes($gastoId);
            header('Content-Type: application/json');
            echo json_encode(['status' => 'success', 'data' => $detalles]);
        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        }
    }

    //Delete categoria
    public function delete($vars)
    {
        try {
            $id = $vars['id'] ?? null;
            if (!$id) {
                http_response_code(400);
                echo json_encode(['status' => 'error', 'message' => 'ID de la categoria es requerido.']);
                return;
            }

            $result = $this->categoriasModel->deleteCategoria($id);
            header('Content-Type: application/json');
            echo json_encode(['status' => 'success', 'message' => "Categoria eliminada exitosamente"]);
        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        }
    }
}
