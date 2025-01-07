<?php

use FastRoute\RouteCollector;

return function (RouteCollector $r) {
    $baseUrl = ConfigUrl::get(); // Obtiene el prefijo base dinámico

    $r->addRoute('GET', $baseUrl.'categorias', 'api\controllers\CategoriasController@index'); // todas las categorias
    $r->addRoute('GET', $baseUrl.'categorias/top', 'api\controllers\CategoriasController@getTopGastos'); // 6 más frecuentes
    $r->addRoute('GET', $baseUrl.'categorias/{id}/detalles', 'api\controllers\CategoriasController@getDetallesFrecuentes'); // 12 detalles de un gasto
};