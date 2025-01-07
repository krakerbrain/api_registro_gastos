<?php

use FastRoute\RouteCollector;

return function (RouteCollector $r) {
    $baseUrl = ConfigUrl::get(); // Obtiene el prefijo base dinámico
    $r->addRoute('POST', $baseUrl . 'login', 'api\controllers\AuthController@login');
};
