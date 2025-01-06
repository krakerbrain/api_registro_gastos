<?php

class ConfigUrl
{
    public static function get()
    {
        if ($_SERVER['HTTP_HOST'] == 'localhost') {
            return 'api_registro_gastos/';
        } else {
            return 'https://gastosapi.icu/';
        }
    }
}

/**
 * USAR
  require_once __DIR__ . '/classes/ConfigUrl.php';
  $baseUrl = ConfigUrl::get();
 * 
 */
