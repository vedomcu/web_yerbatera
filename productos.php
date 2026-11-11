
<?php
require_once 'includes/conexion.php';
require_once 'includes/funciones.php';

$titulo = 'Catálogo de productos';
$descripcion = 'Catálogo de productos de Yerbatera Misiones: yerba mate con su '
    . 'categoría, peso, descripción y precio.';
$rutaBase = '';

$consulta = $conexion->query(
    "SELECT productos.nombre, productos.descripcion,
            productos.precio, productos.peso, productos.unidad,
            categorias.nombre AS categoria
     FROM productos
     INNER JOIN categorias
        ON productos.categoria_id = categorias.id
     ORDER BY productos.nombre"
);

$productos = $consulta->fetchAll();

require_once 'includes/header.php';
?>

<h2>Catálogo de productos</h2>

<?php if (count($productos) > 0): ?>
    <div class="tabla-contenedor">
        <table class="tabla-responsive">
            <thead>
                <tr>
                    <th>Producto</th>
                    <th>Categoría</th>
                    <th>Peso</th>
                    <th>Descripción</th>
                    <th>Precio</th>
                </tr>
            </thead>

            <tbody>
                <?php foreach ($productos as $producto): ?>
                    <tr>
                        <td data-label="Producto"><?= e($producto['nombre']) ?></td>
                        <td data-label="Categoría"><?= e($producto['categoria']) ?></td>
                        <td data-label="Peso"><?= e(formatPeso(
                            $producto['peso'] ?? null,
                            $producto['unidad'] ?? null
                        )) ?></td>
                        <td data-label="Descripción"><?= e($producto['descripcion'] ?? '') ?></td>
                        <td data-label="Precio">$<?= number_format((float) $producto['precio'], 2, ',', '.') ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php else: ?>
    <p>No hay productos disponibles.</p>
<?php endif; ?>

<?php require_once 'includes/footer.php'; ?>
