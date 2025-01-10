<?php

use FastRoute\RouteCollector;

return function (RouteCollector $r) {
    $baseUrl = ConfigUrl::get(); // Obtiene el prefijo base dinámico

    // Define las rutas usando el prefijo dinámico
    $r->addRoute('GET', $baseUrl . 'gastos[/{idUsuario:\d+}]', 'api\controllers\GastosController@getGastos');
    $r->addRoute('POST', $baseUrl . 'gastos', 'api\controllers\GastosController@insert');
    $r->addRoute('PUT', $baseUrl . 'gastos/{id:\d+}', 'api\controllers\GastosController@update');
    $r->addRoute('DELETE', $baseUrl . 'gastos/{id:\d+}', 'api\controllers\GastosController@delete');
};
