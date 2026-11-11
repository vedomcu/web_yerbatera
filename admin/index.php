
<?php
require_once '../includes/auth.php';
require_once '../includes/funciones.php';

verificarSesion();

$titulo = 'Panel administrativo';
$rutaBase = '../';

require_once '../includes/header.php';
?>

<h2>Panel de administración</h2>

<p>
    Bienvenido, <?= e($_SESSION['usuario']) ?>.
</p>

<p>Desde aquí podés administrar el catálogo de la yerbatera.</p>

<ul class="menu-admin">
    <li>
        <a href="categorias.php">Gestionar categorías</a>
    </li>
    <li>
        <a href="productos.php">Gestionar productos</a>
    </li>
    <li>
        <a href="../cierre_sesion.php">Cerrar sesión</a>
    </li>
</ul>

<?php require_once '../includes/footer.php'; ?>
