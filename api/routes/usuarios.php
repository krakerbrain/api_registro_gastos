<?php

use FastRoute\RouteCollector;

return function (RouteCollector $r) {
    // Ruta para obtener todos los usuarios
    $r->addRoute('GET', '/api_registro_gastos/usuarios', 'api\controllers\UserController@index');

    // Ruta para crear un nuevo usuario
    $r->addRoute('POST', '/api_registro_gastos/usuarios', 'api\controllers\UserController@store');

    // Ruta para actualizar un usuario (PUT, ya que es para modificar datos)
    $r->addRoute('PUT', '/api_registro_gastos/usuarios/{id:\d+}', 'api\controllers\UserController@update');

    // Ruta para actualizar la contraseña
    $r->addRoute('PUT', '/api_registro_gastos/usuarios/{id}/password', 'api\controllers\UserController@updatePassword');

    // Ruta para eliminar un usuario
    $r->addRoute('DELETE', '/api_registro_gastos/usuarios/{id:\d+}', 'api\controllers\UserController@delete');
};
