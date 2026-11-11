
<?php

require_once 'includes/conexion.php';

// Script de instalación: solo disponible en modo desarrollo.
// En producción queda deshabilitado (el usuario de prueba ya viene en yerbatera.sql).
if (APP_ENTORNO === 'produccion') {
    exit('Este script solo está disponible en modo desarrollo.');
}

require_once 'includes/auth.php';

// Solo se puede usar sin sesión si todavía no hay ningún usuario cargado
// (primer arranque del sistema). Si ya hay usuarios, hay que iniciar sesión
// antes de poder crear otro.
$consulta = $conexion->query('SELECT COUNT(*) AS total FROM usuarios');
$totalUsuarios = (int) $consulta->fetch()['total'];

if ($totalUsuarios > 0 && !isset($_SESSION['usuario'])) {
    exit('Iniciá sesión en el panel para poder crear un usuario nuevo.');
}

$usuario = 'admin';
$password = password_hash('admin123', PASSWORD_DEFAULT);

try {
    $consulta = $conexion->prepare(
        "SELECT id FROM usuarios WHERE usuario = :usuario"
    );
    $consulta->execute([':usuario' => $usuario]);

    if ($consulta->fetch()) {
        exit('El usuario admin ya existe.');
    }

    $consulta = $conexion->prepare(
        "INSERT INTO usuarios (usuario, password)
         VALUES (:usuario, :password)"
    );

    $consulta->execute([
        ':usuario' => $usuario,
        ':password' => $password
    ]);

    echo 'Usuario administrador creado correctamente.';

} catch (PDOException $e) {
    error_log($e->getMessage());
    echo 'Error al crear el usuario: ' .
         htmlspecialchars($e->getMessage());
}
