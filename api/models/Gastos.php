<?php

namespace api\models;

class Gastos
{
    private $db;

    public function __construct($db)
    {
        $this->db = $db;
    }

    // Obtener todos los gastos
    public function getAllGastos()
    {
        $this->db->query("
            SELECT 
                g.id,
                g.idusuario,
                g.monto_gasto,
                g.tipo_gasto_id,
                t.descripcion AS tipo_gasto,
                GROUP_CONCAT(dg.descripcion SEPARATOR ', ') AS descripciones,
                g.created_at,
                g.updated_at
            FROM gastos g
            LEFT JOIN tipo_gastos t ON g.tipo_gasto_id = t.id
            LEFT JOIN descripcion_gasto_gasto dgg ON g.id = dgg.gasto_id
            LEFT JOIN descripcion_gastos dg ON dgg.descripcion_gasto_id = dg.id
            GROUP BY g.id
        ");
        return $this->db->resultSet();
    }

    // Insertar un gasto, 
    public function insertGasto($data)
    {
        $fecha = date('Y-m-d H:i:s'); // Obtener la fecha actual
        $this->db->beginTransaction(); // Iniciar transacción

        try {
            // Insertar el gasto principal
            $this->db->query("
            INSERT INTO gastos (idusuario, monto_gasto, tipo_gasto_id, created_at, updated_at)
            VALUES (:idusuario, :monto, :tipoGasto, :fecha, :fecha)
        ");
            $this->db->bind(':idusuario', $data['idusuario']);
            $this->db->bind(':monto', $data['monto']);
            $this->db->bind(':tipoGasto', $data['tipoGasto']);
            $this->db->bind(':fecha', $fecha);
            $this->db->execute();

            // Obtener el ID del gasto recién insertado
            $gasto_id = $this->db->lastInsertId();

            // Insertar los detalles del gasto (si existen)
            if (!empty($data['detallesId']) && is_array($data['detallesId'])) {
                foreach ($data['detallesId'] as $detalleId) {
                    $this->db->query("
                    INSERT INTO descripcion_gasto_gasto (gasto_id, descripcion_gasto_id, created_at, updated_at)
                    VALUES (:gasto_id, :descripcion_gasto_id, :fecha, :fecha)
                ");
                    $this->db->bind(':gasto_id', $gasto_id);
                    $this->db->bind(':descripcion_gasto_id', $detalleId);
                    $this->db->bind(':fecha', $fecha);
                    $this->db->execute();
                }
            }

            $this->db->endTransaction(); // Confirmar la transacción

            // Retornar el ID del gasto insertado como respuesta
            return [
                'gasto_id' => $gasto_id,
                'message' => 'Gasto registrado exitosamente'
            ];
        } catch (\Exception $e) {
            $this->db->cancelTransaction(); // Revertir la transacción en caso de error
            throw new \Exception("Error al insertar el gasto: " . $e->getMessage());
        }
    }

    public function updateGasto($data)
    {
        $fecha = date('Y-m-d H:i:s'); // Fecha de actualización
        $this->db->beginTransaction(); // Iniciar transacción

        try {
            // Actualizar el monto del gasto
            $this->db->query("
            UPDATE gastos
            SET monto_gasto = :monto, updated_at = :fecha
            WHERE id = :id
        ");
            $this->db->bind(':monto', $data['monto_gasto']);
            $this->db->bind(':fecha', $fecha);
            $this->db->bind(':id', $data['id']);
            $this->db->execute();

            // Eliminar las descripciones existentes para este gasto
            $this->db->query("
            DELETE FROM descripcion_gasto_gasto
            WHERE gasto_id = :id
        ");
            $this->db->bind(':id', $data['id']);
            $this->db->execute();

            // Insertar las nuevas descripciones
            if (!empty($data['descripciones']) && is_array($data['descripciones'])) {
                foreach ($data['descripciones'] as $descripcionId) {
                    $this->db->query("
                    INSERT INTO descripcion_gasto_gasto (gasto_id, descripcion_gasto_id, created_at, updated_at)
                    VALUES (:gasto_id, :descripcion_gasto_id, :fecha, :fecha)
                ");
                    $this->db->bind(':gasto_id', $data['id']);
                    $this->db->bind(':descripcion_gasto_id', $descripcionId);
                    $this->db->bind(':fecha', $fecha);
                    $this->db->execute();
                }
            }

            $this->db->endTransaction(); // Confirmar la transacción

            return [
                'message' => 'Gasto actualizado exitosamente'
            ];
        } catch (\Exception $e) {
            $this->db->cancelTransaction(); // Revertir la transacción en caso de error
            throw new Exception("Error al actualizar el gasto: " . $e->getMessage());
        }
    }

    public function deleteGasto($id)
    {
        try {
            // Comienza una transacción
            $this->db->beginTransaction();

            // Eliminar el gasto
            $this->db->query("DELETE FROM gastos WHERE id = :id");
            $this->db->bind(':id', $id);
            $this->db->execute();

            // Confirma la transacción
            $this->db->endTransaction();

            return [
                'message' => 'Gasto eliminado exitosamente'
            ];
        } catch (\Exception $e) {
            // Revertir la transacción en caso de error
            $this->db->cancelTransaction();
            throw new Exception("Error al eliminar el gasto: " . $e->getMessage());
        }
    }
}
