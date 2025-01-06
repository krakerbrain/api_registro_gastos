<?php

require_once 'config/init.php'; // Cargar configuración e inicialización

// Puedes cargar dinámicamente las rutas según la URI o el controlador que desees
$routeDefinition = null;
$uri = $_SERVER['REQUEST_URI'];


// Define qué archivo de rutas cargar según la URI
if (strpos($uri, '/api_registro_gastos/login') !== false) {
    $routeDefinition = require 'api/routes/login.php'; // Rutas de autenticación
} elseif (strpos($uri, '/api_registro_gastos/usuarios') !== false) {
    $routeDefinition = require 'api/routes/usuarios.php'; // Rutas de usuario
} elseif (strpos($uri, '/api_registro_gastos/categorias') !== false) {
    $routeDefinition = require 'api/routes/categorias.php'; // Rutas de usuario
} else {
    $routeDefinition = require 'api/routes/gastos.php'; // Ruta por defecto para gastos
}
// Usar el dispatcher con la función de rutas cargada
$dispatcher = FastRoute\simpleDispatcher($routeDefinition);

// Recoger la URI y el método HTTP
$httpMethod = $_SERVER['REQUEST_METHOD'];
$uri = $_SERVER['REQUEST_URI'];

// Limpiar la URI de cualquier parámetro adicional
$uri = strtok($uri, '?');  // Elimina cualquier parte de la cadena de consulta
$uri = rtrim($uri, '/');    // Elimina la barra final, si la hay

// Ejecutar el dispatcher
$routeInfo = $dispatcher->dispatch($httpMethod, $uri);
// echo $routeInfo[0];
// Manejar la respuesta según la ruta encontrada
switch ($routeInfo[0]) {
    case FastRoute\Dispatcher::NOT_FOUND:
        echo "404 Not Found";
        break;
    case FastRoute\Dispatcher::METHOD_NOT_ALLOWED:
        echo "405 Method Not Allowed";
        break;
    case FastRoute\Dispatcher::FOUND:
        try {
            // Llamar al controlador y acción correspondientes
            $handler = $routeInfo[1]; // Ejemplo: 'GastosController@index'
            $vars = $routeInfo[2]; // Parámetros de la ruta, si los hay
            list($controller, $method) = explode('@', $handler);

            // Verifica que la clase existe
            if (!class_exists($controller)) {
                throw new Exception("Controlador no encontrado: $controller");
            }

            // Crea una instancia del controlador
            $controllerInstance = new $controller(); // Pasar $db si el constructor lo necesita

            // Verifica que el método existe
            if (!method_exists($controllerInstance, $method)) {
                throw new Exception("Método no encontrado: $method en $controller");
            }

            // Llamar al método con los parámetros de la ruta
            call_user_func_array([$controllerInstance, $method], [$vars]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        }
        break;
}
