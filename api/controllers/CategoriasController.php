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
        $this->categoriasModel = new Categorias($this->db);
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

    //Insertar una categoria

}
