<?php

use FastRoute\RouteCollector;

return function (RouteCollector $r) {
    $baseUrl = ConfigUrl::get(); // Obtiene el prefijo base dinámico

    // Ruta para obtener todos los usuarios
    $r->addRoute('GET', $baseUrl . 'usuarios', 'api\controllers\UserController@index');

    // Ruta para crear un nuevo usuario
    $r->addRoute('POST', $baseUrl . 'usuarios', 'api\controllers\UserController@store');

    // Ruta para actualizar un usuario (PUT, ya que es para modificar datos)
    $r->addRoute('PUT', $baseUrl . 'usuarios/{id:\d+}', 'api\controllers\UserController@update');

    // Ruta para actualizar la contraseña
    $r->addRoute('PUT', $baseUrl . 'usuarios/{id}/password', 'api\controllers\UserController@updatePassword');

    // Ruta para eliminar un usuario
    $r->addRoute('DELETE', $baseUrl . 'usuarios/{id:\d+}', 'api\controllers\UserController@delete');
};
