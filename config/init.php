<?php
define('BASE_PATH', dirname(__DIR__)); // Directorio raíz del proyecto (un nivel arriba de config)

// Cargar autoloader de Composer
require BASE_PATH . '/vendor/autoload.php'; // Ruta correcta al autoloader de Composer

// Cargar las variables de entorno desde la carpeta config
$dotenv = Dotenv\Dotenv::createImmutable(BASE_PATH . '/config'); // Ruta al archivo .env dentro de config
$dotenv->load();
