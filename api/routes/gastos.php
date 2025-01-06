<?php

use FastRoute\RouteCollector;

return function (RouteCollector $r) {
    $r->addRoute('GET', '/api_registro_gastos/gastos', 'api\controllers\GastosController@index'); // Todos los gastos
    $r->addRoute('GET', '/api_registro_gastos/gastos/top', 'api\controllers\GastosController@getTopGastos'); // 6 más frecuentes
    $r->addRoute('GET', '/api_registro_gastos/gastos/{id}/detalles', 'api\controllers\GastosController@getDetallesFrecuentes'); // 12 detalles de un gasto
    $r->addRoute('POST', '/api_registro_gastos/gastos', 'api\controllers\GastosController@insert');
    $r->addRoute('PUT', '/api_registro_gastos/gastos/{id:\d+}', 'api\controllers\GastosController@update');
    $r->addRoute('DELETE', '/api_registro_gastos/gastos/{id:\d+}', 'api\controllers\GastosController@delete');
};
