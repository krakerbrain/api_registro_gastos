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

    // Método para obtener todos los usuarios
    public function getAllUsers()
    {
        $this->db->query("SELECT id, name, email, created_at, updated_at FROM users");
        return $this->db->resultSet();
    }

    // Método para obtener los datos del usuario por ID
    public function getUserById($id)
    {
        $sql = "SELECT * FROM users WHERE id = :id";
        $this->db->query($sql);
        $this->db->bind(':id', $id);
        return $this->db->single();
    }

    // Método para encontrar un usuario por su correo electrónico
    public function getUserByEmail($email)
    {
        $this->db->query("SELECT * FROM users WHERE email = :email");
        $this->db->bind(':email', $email);
        return $this->db->single(); // Devuelve un solo registro de usuario
    }

    // Método para crear un nuevo usuario verificar antes si existe el email
    public function create($name, $email, $password)
    {
        // Verificar si el usuario ya existe
        $user = $this->getUserByEmail($email);
        if ($user) {
            throw new \Exception('El correo electrónico ya está registrado');
        }

        // Crear el nuevo usuario
        $this->db->query("INSERT INTO users (name, email, password, created_at) VALUES (:name, :email, :password, NOW())");
        $this->db->bind(':name', $name);
        $this->db->bind(':email', $email);
        $this->db->bind(':password', password_hash($password, PASSWORD_DEFAULT));
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
