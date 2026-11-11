
<?php
require_once 'includes/conexion.php';
require_once 'includes/funciones.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (isset($_SESSION['usuario'])) {
    header('Location: admin/index.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Rechaza el envío si el token CSRF no coincide con el de la sesión.
    verificarCsrf();

    $usuario = trim($_POST['usuario'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($usuario === '' || $password === '') {
        $error = 'Ingresá el usuario y la contraseña.';
    } else {
        $consulta = $conexion->prepare(
            'SELECT id, usuario, password FROM usuarios WHERE usuario = ?'
        );
        $consulta->execute([$usuario]);
        $datosUsuario = $consulta->fetch();

        if (
            $datosUsuario &&
            password_verify($password, $datosUsuario['password'])
        ) {
            session_regenerate_id(true);
            $_SESSION['usuario'] = $datosUsuario['usuario'];

            header('Location: admin/index.php');
            exit;
        }

        $error = 'Usuario o contraseña incorrectos.';
    }
}

$titulo = 'Iniciar sesión';
$descripcion = 'Iniciar sesión en el panel de administración de Yerbatera Misiones.';
$rutaBase = '';

require_once 'includes/header.php';
?>

<h2>Iniciar sesión</h2>

<?php if ($error !== ''): ?>
    <p class="mensaje-error" role="alert"><?= e($error) ?></p>
<?php endif; ?>

<form class="formulario formulario-angosto" method="post" action="login.php">
    <?= csrfCampo() ?>

    <label for="usuario">Usuario:</label>
    <input
        type="text"
        id="usuario"
        name="usuario"
        maxlength="50"
        autocomplete="username"
        required
    >

    <label for="password">Contraseña:</label>
    <input
        type="password"
        id="password"
        name="password"
        autocomplete="current-password"
        required
    >

    <button type="submit">Ingresar</button>
</form>

<?php require_once 'includes/footer.php'; ?>
