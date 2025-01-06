<?php

namespace api\models;

use DateTime;
use DateTimeZone;

class Auth
{
    private $db;

    public function __construct($db)
    {
        $this->db = $db;
    }

    public function authenticate($email, $password)
    {
        $sql = "SELECT * FROM users WHERE email = :email";
        $this->db->query($sql);
        $this->db->bind(':email', $email);
        $user = $this->db->single();

        if ($user && password_verify($password, $user['password'])) {
            return $user;
        }

        return false;
    }

    public function generateToken($userId)
    {
        $token = bin2hex(random_bytes(16)); // Genera un token seguro
        $expiry = new DateTime('now', new DateTimeZone('UTC'));
        $expiry->add(new \DateInterval('PT1H')); // Token válido por 1 hora

        // Actualizar el token en la base de datos
        $sql = "UPDATE users SET remember_token = :token, token_expiry = :expiry WHERE id = :id";
        $this->db->query($sql);
        $this->db->bind(':token', $token);
        $this->db->bind(':expiry', $expiry->format('Y-m-d H:i:s'));
        $this->db->bind(':id', $userId);
        $this->db->execute();

        return $token;
    }

    public function validateToken($token)
    {
        $sql = "SELECT * FROM users WHERE remember_token = :token AND token_expiry > NOW()";
        $this->db->query($sql);
        $this->db->bind(':token', $token);
        return $this->db->single();
    }
}
