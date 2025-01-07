<?php

use FastRoute\RouteCollector;

return function (RouteCollector $r) {
    $baseUrl = ConfigUrl::get(); // Obtiene el prefijo base dinámico

    // Define las rutas usando el prefijo dinámico
    $r->addRoute('GET', $baseUrl . 'gastos', 'api\controllers\GastosController@index'); // Todos los gastos
    $r->addRoute('GET', $baseUrl . 'gastos/top', 'api\controllers\GastosController@getTopGastos'); // 6 más frecuentes
    $r->addRoute('GET', $baseUrl . 'gastos/{id}/detalles', 'api\controllers\GastosController@getDetallesFrecuentes'); // 12 detalles de un gasto
    $r->addRoute('POST', $baseUrl . 'gastos', 'api\controllers\GastosController@insert');
    $r->addRoute('PUT', $baseUrl . 'gastos/{id:\d+}', 'api\controllers\GastosController@update');
    $r->addRoute('DELETE', $baseUrl . 'gastos/{id:\d+}', 'api\controllers\GastosController@delete');
};
