
<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function verificarSesion() {
    if (!isset($_SESSION['usuario'])) {
        header('Location: ../login.php');
        exit;
    }
}
