
<?php

// Configuracion de la conexion a la BD

define('DB_HOST', 'localhost');
define('DB_PORT', '3306');
define('DB_NAME', 'nombre_de_base_de_datos');
define('DB_USER', 'usuario');
define('DB_PASS', 'contraseña');

// Indica si se encuentra en desarrollo o en produccion :)
// se pueden mostrar errores en desarrollo, pero en produccion no se deben mostrar los errores al usuario
define('APP_ENTORNO', 'desarrollo');

if (APP_ENTORNO === 'produccion') {
    ini_set('display_errors', '0');
    ini_set('log_errors', '1');
}
