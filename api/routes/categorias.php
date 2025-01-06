<?php

use FastRoute\RouteCollector;

return function (RouteCollector $r) {
    $r->addRoute('GET', '/api_registro_gastos/categorias', 'api\controllers\CategoriasController@index'); // todas las categorias
    $r->addRoute('GET', '/api_registro_gastos/categorias/top', 'api\controllers\CategoriasController@getTopGastos'); // 6 más frecuentes
    $r->addRoute('GET', '/api_registro_gastos/categorias/{id}/detalles', 'api\controllers\CategoriasController@getDetallesFrecuentes'); // 12 detalles de un gasto
};
