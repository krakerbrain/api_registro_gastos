<?php

namespace api\models;


class Categorias
{
    private $db;


    public function __construct($db)
    {
        $this->db = $db;
    }

    //todas las categorias
    public function index()
    {
        $this->db->query("SELECT * FROM tipo_gastos");
        return $this->db->resultSet();
    }

    public function findCategoriaByNombre($nombre)
    {
        // Verificar si el tipo de gasto ya existe con el nombre normalizado
        $this->db->query("SELECT id FROM tipo_gastos WHERE descripcion = :nombre LIMIT 1");
        $this->db->bind(':nombre', $nombre);
        return $this->db->singleValue();
    }

    //findCategoriaById
    public function findCategoriaById($id)
    {
        // Consultar si la categoría existe por id
        $this->db->query("SELECT id FROM tipo_gastos WHERE id = :id LIMIT 1");
        $this->db->bind(':id', $id);
        return $this->db->singleValue();
    }


    // Obtener los 6 gastos más frecuentes
    public function getTopGastos($limit = 6)
    {
        $this->db->query("
             SELECT 
                 g.id,
                 COUNT(g.id) AS frecuencia,
                 t.descripcion AS tipo_gasto
             FROM gastos g
             LEFT JOIN tipo_gastos t ON g.tipo_gasto_id = t.id
             GROUP BY g.tipo_gasto_id
             ORDER BY frecuencia DESC
             LIMIT :limit
         ");
        $this->db->bind(':limit', $limit);
        return $this->db->resultSet();
    }

    // Obtener los 12 detalles más frecuentes de un gasto
    public function getDetallesFrecuentes($gastoId, $limit = 12)
    {
        $this->db->query("
             SELECT
                 dg.id, 
                 dg.descripcion,
                 COUNT(dg.id) AS frecuencia
             FROM descripcion_gastos dg
             JOIN descripcion_gasto_gasto dgg ON dg.id = dgg.descripcion_gasto_id
             WHERE dgg.gasto_id = :gasto_id
             GROUP BY dg.id
             ORDER BY frecuencia DESC
             LIMIT :limit
         ");
        $this->db->bind(':gasto_id', $gastoId);
        $this->db->bind(':limit', $limit);
        return $this->db->resultSet();
    }

    public function obtenerOInsertarTipoGasto($tipoGasto, $nombreTipoGasto)
    {
        // Si tipoGasto no está vacío, se usa directamente
        if (!empty($tipoGasto)) {
            // Verificamos si el tipoGasto es válido
            return $this->findCategoriaById($tipoGasto) ?: null;
        }

        // Si tipoGasto está vacío, usamos nombreTipoGasto para insertar uno nuevo
        if (!empty($nombreTipoGasto)) {
            // Normalizamos el nombre del tipo de gasto
            $nombreNormalizado = ucwords(strtolower($nombreTipoGasto));

            // Verificamos si ya existe una categoría con este nombre
            $tipoGastoId = $this->findCategoriaByNombre($nombreNormalizado);

            // Si no existe, lo insertamos
            if (!$tipoGastoId) {
                return $this->insertCategoria($nombreNormalizado);
            }

            return $tipoGastoId; // Si ya existe, retornamos el ID existente
        }

        return null; // Si ambos están vacíos, retornamos null
    }


    public function insertCategoria($nombre)
    {
        // Normalizar la descripción con la primera letra de cada palabra en mayúsculas
        $descripcionNormalizada = ucwords(strtolower($nombre));

        // Insertar la nueva categoría con la descripción normalizada
        $this->db->query("INSERT INTO tipo_gastos (descripcion) VALUES (:descripcion)");
        $this->db->bind(':descripcion', $descripcionNormalizada);

        // Ejecutar la inserción
        $this->db->execute();

        // Retornar el ID del nuevo tipo de gasto insertado
        return $this->db->lastInsertId();
    }

    //agregar eliminar categoria pero antes hay que verificar que no tenga gastos asociados
    public function deleteCategoria($id)
    {
        // Verificar si la categoría tiene gastos asociados

        $gastos = new Gastos($this->db);
        $gasto = $gastos->getGastoByTipoGastoId($id);
        // Si la categoría tiene gastos asociados, no se puede eliminar
        if ($gasto) {
            throw new \Exception("No se puede eliminar la categoría porque tiene gastos asociados.");
        }

        // Eliminar la categoría
        $this->db->query("DELETE FROM tipo_gastos WHERE id = :id");
        $this->db->bind(':id', $id);
        $this->db->execute();
    }
}
