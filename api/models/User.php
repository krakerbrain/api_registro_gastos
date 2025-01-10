<?php

namespace api\models;

use api\libs\Database;

class User
{
    protected $db;

    public function __construct(Database $db)
    {
        $this->db = $db;
    }

    public function userExists($idUsuario)
    {
        $this->db->query("SELECT COUNT(*) as count FROM users WHERE id = :id");
        $this->db->bind(':id', $idUsuario);
        $result = $this->db->single();
        return $result['count'] > 0;
    }

    // Método para obtener todos los usuarios
    public function getAllUsers()
    {
        $this->db->query("SELECT id, role_id,name, email, created_at, updated_at FROM users");
        return $this->db->resultSet();
    }

    public function getUsersByAdmin($adminId)
    {
        $this->db->query("SELECT id, role_id,name, email, created_at, updated_at FROM users WHERE id = :adminId OR admin_id = :adminId");
        $this->db->bind(':adminId', $adminId);
        return $this->db->resultSet();
    }

    // Método para obtener los datos del usuario por ID
    public function getUserById($userId)
    {
        $sql = "SELECT id, role_id,admin_id,name, email, created_at, updated_at  FROM users WHERE id = :id";
        $this->db->query($sql);
        $this->db->bind(':id', $userId);
        return $this->db->single();
    }

    // Método para encontrar un usuario por su correo electrónico
    public function getUserByEmail($email)
    {
        $this->db->query("SELECT * FROM users WHERE email = :email");
        $this->db->bind(':email', $email);
        return $this->db->single(); // Devuelve un solo registro de usuario
    }

    // Método para crear un usuario
    public function create($data)
    {

        // Verificar si el usuario ya existe
        $user = $this->getUserByEmail($data['email']);
        if ($user) {
            throw new \Exception('El correo electrónico ya está registrado');
        }
        $adminId = isset($data['admin_id']) ? $data['admin_id'] : null;
        // Preparar la consulta SQL
        $this->db->query("INSERT INTO users (name, email, password, role_id, admin_id, created_at) 
              VALUES (:name, :email, :password, :role_id, :admin_id, NOW())");

        $this->db->bind(':name', $data['name']);
        $this->db->bind(':email', $data['email']);
        $this->db->bind(':password', password_hash($data['password'], PASSWORD_BCRYPT));
        $this->db->bind(':role_id', $data['role_id']);
        $this->db->bind(':admin_id', $adminId);
        $this->db->execute();
    }

    // Método para actualizar un usuario

    public function update($id, $name, $email)
    {
        //crear try catch para ver error
        try {

            $this->db->query("UPDATE users SET name = :name, email = :email, updated_at = NOW() WHERE id = :id");
            $this->db->bind(':name', $name);
            $this->db->bind(':email', $email);
            $this->db->bind(':id', $id);

            return $this->db->execute(); // Devuelve true si la actualización fue exitosa, false si no
        } catch (\Exception $e) {
            return $e->getMessage();
        }
    }

    public function verifyPassword($userId, $currentPassword)
    {

        // Preparamos la consulta para obtener solo el password del usuario
        $sql = "SELECT password FROM users WHERE id = :id";
        $this->db->query($sql);
        $this->db->bind(':id', $userId);

        // Ejecutamos la consulta y obtenemos el resultado
        $result = $this->db->single();

        // Si encontramos el usuario y su password, verificamos si coincide
        if ($result && password_verify($currentPassword, $result['password'])) {
            return true; // Contraseña válida
        } else {
            return false; // Contraseña incorrecta
        }
    }


    // Método para actualizar la contraseña del usuario
    public function updatePassword($id, $newPassword)
    {

        $sql = "UPDATE users SET password = :password, updated_at = NOW() WHERE id = :id";
        $this->db->query($sql);
        $this->db->bind(':password', $newPassword);
        $this->db->bind(':id', $id);
        $this->db->execute();
    }

    // Método para eliminar un usuario
    public function delete($id)
    {
        $this->db->query("DELETE FROM users WHERE id = :id");
        $this->db->bind(':id', $id);

        return $this->db->execute(); // Devuelve true si la eliminación fue exitosa, false si no
    }
}
