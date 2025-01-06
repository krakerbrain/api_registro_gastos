<?php

use FastRoute\RouteCollector;

return function (RouteCollector $r) {
    $r->addRoute('POST', '/api_registro_gastos/login', 'api\controllers\AuthController@login');
};
