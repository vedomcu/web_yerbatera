
<?php

// Las funciones (e(), csrfCampo(), etc.) siempre disponibles en la plantilla.
require_once __DIR__ . '/funciones.php';

$titulo = $titulo ?? 'Yerbatera Misiones';
$descripcion = $descripcion ??
    'Yerbatera Misiones: catálogo de productos de yerba mate y panel de administración.';
$rutaBase = $rutaBase ?? '';
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="<?= e($descripcion) ?>">
    <title><?= e($titulo) ?></title>
    <link rel="stylesheet" href="<?= e($rutaBase) ?>css/estilos.css">
</head>
<body>

<a class="saltar-contenido" href="#contenido">Saltar al contenido</a>

<header>
    <div class="header-contenido">
        <h1>Yerbatera Misiones</h1>

        <nav>
            <a href="<?= e($rutaBase) ?>index.php">Inicio</a>
            <a href="<?= e($rutaBase) ?>productos.php">Productos</a>
            <a href="<?= e($rutaBase) ?>login.php">Administración</a>
        </nav>
    </div>
</header>

<main id="contenido">
