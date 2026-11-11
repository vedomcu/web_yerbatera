<?php
require_once '../includes/conexion.php';
require_once '../includes/auth.php';
require_once '../includes/funciones.php';

verificarSesion();

$error = '';
$editar = null;
$datosPost = null;

// Crear, actualizar o eliminar categorías.
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Rechaza el envío si el token CSRF no coincide con el de la sesión.
    verificarCsrf();

    $accion = $_POST['accion'] ?? '';

    try {
        if ($accion === 'crear' || $accion === 'editar') {
            $nombre = trim($_POST['nombre'] ?? '');
            $descripcion = trim($_POST['descripcion'] ?? '');

            if ($nombre === '' || strlen($nombre) > 100) {
                throw new Exception('El nombre es obligatorio y no debe superar los 100 caracteres.');
            }

            if (strlen($descripcion) > 255) {
                throw new Exception('La descripción no debe superar los 255 caracteres.');
            }

            if ($accion === 'crear') {
                $consulta = $conexion->prepare(
                    'INSERT INTO categorias (nombre, descripcion) VALUES (?, ?)'
                );
                $consulta->execute([$nombre, $descripcion ?: null]);
            } else {
                $id = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT);

                if (!$id || $id < 1) {
                    throw new Exception('El identificador de la categoría no es válido.');
                }

                $consulta = $conexion->prepare(
                    'UPDATE categorias SET nombre = ?, descripcion = ? WHERE id = ?'
                );
                $consulta->execute([$nombre, $descripcion ?: null, $id]);
            }

            // $accion solo puede ser 'crear' o 'editar' acá (ambos claves
            // válidas de $mensajesExito, así que no se filtra nada de la URL).
            header('Location: categorias.php?exito=' . $accion);
            exit;
        }

        if ($accion === 'eliminar') {
            $id = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT);

            if (!$id || $id < 1) {
                throw new Exception('El identificador de la categoría no es válido.');
            }

            $consulta = $conexion->prepare(
                'DELETE FROM categorias WHERE id = ?'
            );
            $consulta->execute([$id]);

            header('Location: categorias.php?exito=eliminar');
            exit;
        }
    } catch (PDOException $e) {
        error_log($e->getMessage());
        $error = 'No se pudo realizar la operación. Verificá si la categoría tiene productos asociados.';
    } catch (Exception $e) {
        $error = $e->getMessage();
    }

    // Si la operación falló, se conserva lo que el usuario tipeó para que
    // no tenga que escribir todo de nuevo.
    if ($error !== '' && ($accion === 'crear' || $accion === 'editar')) {
        $datosPost = [
            'nombre' => trim($_POST['nombre'] ?? ''),
            'descripcion' => trim($_POST['descripcion'] ?? ''),
        ];
    }
}

// Cargar categoría para editar.
if (isset($_GET['editar'])) {
    $idEditar = filter_var($_GET['editar'], FILTER_VALIDATE_INT);

    if ($idEditar && $idEditar > 0) {
        $consulta = $conexion->prepare(
            'SELECT id, nombre, descripcion FROM categorias WHERE id = ?'
        );
        $consulta->execute([$idEditar]);
        $editar = $consulta->fetch() ?: null;
    }
}

// Obtener todas las categorías.
$consulta = $conexion->query(
    'SELECT id, nombre, descripcion FROM categorias ORDER BY id'
);
$categorias = $consulta->fetchAll();

// Valores que se muestran en el formulario: lo tipeado si falló la operación
// o, en su defecto, los de la categoría que se está editando.
$valores = $datosPost ?? ($editar ?: []);

// Mensajes de éxito: se muestran después de la redirección (patrón PRG).
$mensajesExito = [
    'crear' => 'La categoría se creó correctamente.',
    'editar' => 'Los cambios se guardaron correctamente.',
    'eliminar' => 'La categoría se eliminó correctamente.',
];

// Solo se acepta una clave conocida: nunca se imprime algo venga de la URL.
$claveExito = $_GET['exito'] ?? '';
$exito = is_string($claveExito) ? ($mensajesExito[$claveExito] ?? '') : '';

$titulo = 'Gestionar categorías';
$descripcion = 'Panel de administración de Yerbatera Misiones: alta, baja y '
    . 'modificación de categorías.';
$rutaBase = '../';

require_once '../includes/header.php';
?>

<h2>Gestionar categorías</h2>

<?php if ($error !== ''): ?>
    <p class="mensaje-error" role="alert"><?= e($error) ?></p>
<?php endif; ?>

<?php if ($exito !== ''): ?>
    <p class="mensaje-exito" role="status"><?= e($exito) ?></p>
<?php endif; ?>

<h3><?= $editar ? 'Editar categoría' : 'Agregar categoría' ?></h3>

<form
    class="formulario"
    method="post"
    action="categorias.php<?= $editar ? '?editar=' . e($editar['id']) : '' ?>"
>
    <?= csrfCampo() ?>

    <input
        type="hidden"
        name="accion"
        value="<?= $editar ? 'editar' : 'crear' ?>"
    >

    <?php if ($editar): ?>
        <input type="hidden" name="id" value="<?= e($editar['id']) ?>">
    <?php endif; ?>

    <label for="nombre">Nombre:</label>
    <input
        type="text"
        id="nombre"
        name="nombre"
        maxlength="100"
        required
        value="<?= e($valores['nombre'] ?? '') ?>"
    >

    <label for="descripcion">Descripción:</label>
    <textarea
        id="descripcion"
        name="descripcion"
        maxlength="255"
    ><?= e($valores['descripcion'] ?? '') ?></textarea>

    <button type="submit">
        <?= $editar ? 'Guardar cambios' : 'Agregar categoría' ?>
    </button>

    <?php if ($editar): ?>
        <a href="categorias.php">Cancelar</a>
    <?php endif; ?>
</form>

<h3>Categorías registradas</h3>

<?php if (count($categorias) > 0): ?>
    <div class="tabla-contenedor">
        <table class="tabla-responsive">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Nombre</th>
                    <th>Descripción</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($categorias as $categoria): ?>
                    <tr>
                        <td data-label="ID"><?= e($categoria['id']) ?></td>
                        <td data-label="Nombre"><?= e($categoria['nombre']) ?></td>
                        <td data-label="Descripción"><?= e($categoria['descripcion'] ?? '') ?></td>
                        <td class="acciones" data-label="Acciones">
                            <a href="categorias.php?editar=<?= e($categoria['id']) ?>">
                                Editar
                            </a>

                            <form
                                method="post"
                                action="categorias.php"
                                onsubmit="return confirm('¿Querés eliminar esta categoría?');"
                            >
                                <?= csrfCampo() ?>
                                <input type="hidden" name="accion" value="eliminar">
                                <input
                                    type="hidden"
                                    name="id"
                                    value="<?= e($categoria['id']) ?>"
                                >
                                <button type="submit" class="boton-peligro">Eliminar</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php else: ?>
    <p>No hay categorías registradas.</p>
<?php endif; ?>

<p><a href="index.php">Volver al panel administrativo</a></p>

<?php require_once '../includes/footer.php'; ?>
