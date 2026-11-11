
<?php
require_once '../includes/conexion.php';
require_once '../includes/auth.php';
require_once '../includes/funciones.php';

verificarSesion();

$error = '';
$editar = null;
$datosPost = null;

// Crear, actualizar o eliminar productos.
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Rechaza el envío si el token CSRF no coincide con el de la sesión.
    verificarCsrf();

    $accion = $_POST['accion'] ?? '';

    try {
        if ($accion === 'crear' || $accion === 'editar') {
            $nombre = trim($_POST['nombre'] ?? '');
            $descripcion = trim($_POST['descripcion'] ?? '');
            $categoriaId = filter_var(
                $_POST['categoria_id'] ?? null,
                FILTER_VALIDATE_INT
            );
            $precioTexto = trim($_POST['precio'] ?? '');

            if ($nombre === '' || strlen($nombre) > 100) {
                throw new Exception('El nombre es obligatorio y no debe superar los 100 caracteres.');
            }

            if (strlen($descripcion) > 255) {
                throw new Exception('La descripción no debe superar los 255 caracteres.');
            }

            if (!$categoriaId || $categoriaId < 1) {
                throw new Exception('Seleccioná una categoría válida.');
            }

            $consulta = $conexion->prepare(
                'SELECT id FROM categorias WHERE id = ?'
            );
            $consulta->execute([$categoriaId]);

            if (!$consulta->fetch()) {
                throw new Exception('La categoría seleccionada no existe.');
            }

            if (
                $precioTexto === '' ||
                !is_numeric($precioTexto) ||
                !is_finite((float) $precioTexto) ||
                (float) $precioTexto < 0 ||
                (float) $precioTexto > 99999999.99
            ) {
                throw new Exception('Ingresá un precio válido.');
            }

            $precio = (float) $precioTexto;

            $pesoTexto = trim($_POST['peso'] ?? '');
            $unidad = $_POST['unidad'] ?? 'g';

            if (
                $pesoTexto === '' ||
                !is_numeric($pesoTexto) ||
                !is_finite((float) $pesoTexto) ||
                (float) $pesoTexto <= 0 ||
                (float) $pesoTexto > 99999999.99
            ) {
                throw new Exception('Ingresá un peso válido.');
            }

            // La unidad se valida contra una lista permitida (gramos o kilos).
            if (!in_array($unidad, ['g', 'kg'], true)) {
                throw new Exception('La unidad de peso debe ser gramos o kilos.');
            }

            $peso = (float) $pesoTexto;

            if ($accion === 'crear') {
                $consulta = $conexion->prepare(
                    'INSERT INTO productos
                    (categoria_id, nombre, descripcion, precio, peso, unidad)
                    VALUES (?, ?, ?, ?, ?, ?)'
                );

                $consulta->execute([
                    $categoriaId,
                    $nombre,
                    $descripcion ?: null,
                    $precio,
                    $peso,
                    $unidad
                ]);
            } else {
                $id = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT);

                if (!$id || $id < 1) {
                    throw new Exception('El identificador del producto no es válido.');
                }

                $consulta = $conexion->prepare(
                    'UPDATE productos
                    SET categoria_id = ?, nombre = ?, descripcion = ?, precio = ?,
                        peso = ?, unidad = ?
                    WHERE id = ?'
                );

                $consulta->execute([
                    $categoriaId,
                    $nombre,
                    $descripcion ?: null,
                    $precio,
                    $peso,
                    $unidad,
                    $id
                ]);
            }

            // $accion solo puede ser 'crear' o 'editar' acá (ambos claves
            // válidas de $mensajesExito, así que no se filtra nada de la URL).
            header('Location: productos.php?exito=' . $accion);
            exit;
        }

        if ($accion === 'eliminar') {
            $id = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT);

            if (!$id || $id < 1) {
                throw new Exception('El identificador del producto no es válido.');
            }

            $consulta = $conexion->prepare(
                'DELETE FROM productos WHERE id = ?'
            );
            $consulta->execute([$id]);

            header('Location: productos.php?exito=eliminar');
            exit;
        }
    } catch (PDOException $e) {
        error_log($e->getMessage());
        $error = 'No se pudo realizar la operación.';
    } catch (Exception $e) {
        $error = $e->getMessage();
    }

    // Si la operación falló, se conserva lo que el usuario tipeó para que
    // no tenga que escribir todo de nuevo.
    if ($error !== '' && ($accion === 'crear' || $accion === 'editar')) {
        $datosPost = [
            'categoria_id' => $_POST['categoria_id'] ?? '',
            'nombre' => trim($_POST['nombre'] ?? ''),
            'descripcion' => trim($_POST['descripcion'] ?? ''),
            'precio' => trim($_POST['precio'] ?? ''),
            'peso' => trim($_POST['peso'] ?? ''),
            'unidad' => $_POST['unidad'] ?? 'g',
        ];
    }
}

// Cargar producto para editar.
if (isset($_GET['editar'])) {
    $idEditar = filter_var($_GET['editar'], FILTER_VALIDATE_INT);

    if ($idEditar && $idEditar > 0) {
        $consulta = $conexion->prepare(
            'SELECT id, categoria_id, nombre, descripcion, precio, peso, unidad
             FROM productos WHERE id = ?'
        );
        $consulta->execute([$idEditar]);
        $editar = $consulta->fetch() ?: null;
    }
}

// Obtener categorías para el select.
$consulta = $conexion->query(
    'SELECT id, nombre FROM categorias ORDER BY nombre'
);
$categorias = $consulta->fetchAll();

// Obtener productos con su categoría.
$consulta = $conexion->query(
    'SELECT productos.id, productos.nombre, productos.descripcion,
            productos.precio, productos.peso, productos.unidad,
            productos.categoria_id,
            categorias.nombre AS categoria
     FROM productos
     INNER JOIN categorias
        ON productos.categoria_id = categorias.id
     ORDER BY productos.id'
);
$productos = $consulta->fetchAll();

// Valores que se muestran en el formulario: lo tipeado si falló la operación
// o, en su defecto, los del producto que se está editando.
$valores = $datosPost ?? ($editar ?: []);

// Mensajes de éxito: se muestran después de la redirección (patrón PRG).
$mensajesExito = [
    'crear' => 'El producto se creó correctamente.',
    'editar' => 'Los cambios se guardaron correctamente.',
    'eliminar' => 'El producto se eliminó correctamente.',
];

// Solo se acepta una clave conocida: nunca se imprime algo venga de la URL.
$claveExito = $_GET['exito'] ?? '';
$exito = is_string($claveExito) ? ($mensajesExito[$claveExito] ?? '') : '';

$titulo = 'Gestionar productos';
$descripcion = 'Panel de administración de Yerbatera Misiones: alta, baja y '
    . 'modificación de productos.';
$rutaBase = '../';

require_once '../includes/header.php';
?>

<h2>Gestionar productos</h2>

<?php if ($error !== ''): ?>
    <p class="mensaje-error" role="alert"><?= e($error) ?></p>
<?php endif; ?>

<?php if ($exito !== ''): ?>
    <p class="mensaje-exito" role="status"><?= e($exito) ?></p>
<?php endif; ?>

<h3><?= $editar ? 'Editar producto' : 'Agregar producto' ?></h3>

<?php if (count($categorias) > 0): ?>
    <form
        class="formulario"
        method="post"
        action="productos.php<?= $editar ? '?editar=' . e($editar['id']) : '' ?>"
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

        <label for="categoria_id">Categoría:</label>
        <select id="categoria_id" name="categoria_id" required>
            <option value="">Seleccionar categoría</option>

            <?php foreach ($categorias as $categoria): ?>
                <option
                    value="<?= e($categoria['id']) ?>"
                    <?= isset($valores['categoria_id']) &&
                        (int) $valores['categoria_id'] === (int) $categoria['id']
                        ? 'selected'
                        : '' ?>
                >
                    <?= e($categoria['nombre']) ?>
                </option>
            <?php endforeach; ?>
        </select>

        <label for="descripcion">Descripción:</label>
        <textarea
            id="descripcion"
            name="descripcion"
            maxlength="255"
        ><?= e($valores['descripcion'] ?? '') ?></textarea>

        <label for="precio">Precio:</label>
        <input
            type="number"
            id="precio"
            name="precio"
            min="0"
            max="99999999.99"
            step="0.01"
            required
            value="<?= e($valores['precio'] ?? '') ?>"
        >

        <label for="peso">Peso:</label>
        <input
            type="number"
            id="peso"
            name="peso"
            min="0.01"
            max="99999999.99"
            step="0.01"
            required
            value="<?= e($valores['peso'] ?? '') ?>"
        >

        <label for="unidad">Unidad de peso:</label>
        <select id="unidad" name="unidad" required>
            <option
                value="g"
                <?= ($valores['unidad'] ?? 'g') === 'g' ? 'selected' : '' ?>
            >
                Gramos
            </option>
            <option
                value="kg"
                <?= ($valores['unidad'] ?? '') === 'kg' ? 'selected' : '' ?>
            >
                Kilos
            </option>
        </select>

        <button type="submit">
            <?= $editar ? 'Guardar cambios' : 'Agregar producto' ?>
        </button>

        <?php if ($editar): ?>
            <a href="productos.php">Cancelar</a>
        <?php endif; ?>
    </form>
<?php else: ?>
    <p>Primero tenés que crear una categoría para poder agregar productos.</p>
<?php endif; ?>

<h3>Productos registrados</h3>

<?php if (count($productos) > 0): ?>
    <div class="tabla-contenedor">
        <table class="tabla-responsive">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Producto</th>
                    <th>Categoría</th>
                    <th>Peso</th>
                    <th>Descripción</th>
                    <th>Precio</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($productos as $producto): ?>
                    <tr>
                        <td data-label="ID"><?= e($producto['id']) ?></td>
                        <td data-label="Producto"><?= e($producto['nombre']) ?></td>
                        <td data-label="Categoría"><?= e($producto['categoria']) ?></td>
                        <td data-label="Peso"><?= e(formatPeso(
                            $producto['peso'] ?? null,
                            $producto['unidad'] ?? null
                        )) ?></td>
                        <td data-label="Descripción"><?= e($producto['descripcion'] ?? '') ?></td>
                        <td data-label="Precio">$<?= number_format((float) $producto['precio'], 2, ',', '.') ?></td>
                        <td class="acciones" data-label="Acciones">
                            <a href="productos.php?editar=<?= e($producto['id']) ?>">
                                Editar
                            </a>

                            <form
                                method="post"
                                action="productos.php"
                                onsubmit="return confirm('¿Querés eliminar este producto?');"
                            >
                                <?= csrfCampo() ?>
                                <input type="hidden" name="accion" value="eliminar">
                                <input
                                    type="hidden"
                                    name="id"
                                    value="<?= e($producto['id']) ?>"
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
    <p>No hay productos registrados.</p>
<?php endif; ?>

<p><a href="index.php">Volver al panel administrativo</a></p>

<?php require_once '../includes/footer.php'; ?>
