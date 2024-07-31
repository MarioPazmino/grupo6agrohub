<?php
session_start();

error_reporting(E_ALL);
ini_set('display_errors', 1);

// Verificar si el usuario está autenticado
if (!isset($_SESSION['rol'])) {
    header("Location: index.php");
    exit();
}

require __DIR__ . '/vendor/autoload.php';

use MongoDB\Client;
use MongoDB\BSON\ObjectId;
use MongoDB\Exception\Exception;

// Conexión a MongoDB con la URL proporcionada
$mongoUri = "mongodb://mario1010:marito10@testmongo1.cluster-c9ccw6ywgi5c.us-east-1.docdb.amazonaws.com:27017/?tls=true&tlsCAFile=global-bundle.pem&retryWrites=false";
$mongoClient = new Client($mongoUri);
$productosCollection = $mongoClient->grupo6_agrohub->productos;

// Variables para mensajes de éxito y error
$success = [];
$errors = [];

// Manejo de la eliminación de productos
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $id = $_GET['id'];
    
    try {
        if (strlen($id) == 24 && ctype_xdigit($id)) {
            $result = $productosCollection->deleteOne(['_id' => new ObjectId($id)]);
            if ($result->getDeletedCount() > 0) {
                $success[] = 'Producto eliminado exitosamente.';
            } else {
                $errors[] = 'No se encontró el producto para eliminar.';
            }
        } else {
            $errors[] = 'ID de producto inválido.';
        }
    } catch (Exception $e) {
        $errors[] = 'Error al eliminar el producto: ' . $e->getMessage();
    }

    header("Location: productos.php");
    exit();
}

// Manejo de la actualización y agregación de productos
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_POST['action'])) {
    try {
        $variedades = json_decode($_POST['variedades'], true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception('El formato JSON para variedades no es válido.');
        }

        $productoData = [
            'nombre' => $_POST['nombre'],
            'descripcion' => $_POST['descripcion'],
            'tipo' => $_POST['tipo'],
            'precio_unitario' => floatval($_POST['precio_unitario']),
            'unidad' => $_POST['unidad'],
            'variedades' => $variedades
        ];

        if (isset($_POST['id']) && strlen($_POST['id']) == 24 && ctype_xdigit($_POST['id'])) {
            // Actualizar producto
            $result = $productosCollection->updateOne(
                ['_id' => new ObjectId($_POST['id'])],
                ['$set' => $productoData]
            );
            if ($result->getModifiedCount() > 0) {
                $success[] = 'Producto actualizado exitosamente.';
            } else {
                $errors[] = 'No se encontró el producto para actualizar o no hubo cambios.';
            }
        } else {
            // Agregar producto
            $result = $productosCollection->insertOne($productoData);
            if ($result->getInsertedCount() > 0) {
                $success[] = 'Producto agregado exitosamente.';
            } else {
                $errors[] = 'Error al agregar el producto.';
            }
        }
    } catch (Exception $e) {
        $errors[] = 'Error al manejar el producto: ' . $e->getMessage();
    }
}

// Manejo de la eliminación de variedades
if (isset($_GET['action']) && $_GET['action'] === 'delete_variedad' && isset($_GET['product_id']) && isset($_GET['variedad_nombre'])) {
    $product_id = $_GET['product_id'];
    $variedad_nombre = $_GET['variedad_nombre'];

    try {
        $result = $productosCollection->updateOne(
            ['_id' => new ObjectId($product_id)],
            ['$pull' => ['variedades' => ['nombre_variedad' => $variedad_nombre]]]
        );
        if ($result->getModifiedCount() > 0) {
            $success[] = 'Variedad eliminada exitosamente.';
        } else {
            $errors[] = 'No se pudo eliminar la variedad.';
        }
    } catch (Exception $e) {
        $errors[] = 'Error al eliminar la variedad: ' . $e->getMessage();
    }

    header('Location: productos.php');
    exit();
}

// Manejo de la agregación de variedades
if (isset($_POST['action']) && $_POST['action'] === 'add_variedad' && isset($_POST['product_id']) && isset($_POST['variedad_nombre']) && isset($_POST['caracteristicas'])) {
    $product_id = $_POST['product_id'];
    $variedad = [
        'nombre_variedad' => $_POST['variedad_nombre'],
        'caracteristicas' => $_POST['caracteristicas']
    ];

    try {
        $result = $productosCollection->updateOne(
            ['_id' => new ObjectId($product_id)],
            ['$push' => ['variedades' => $variedad]]
        );
        if ($result->getModifiedCount() > 0) {
            $success[] = 'Variedad agregada exitosamente.';
        } else {
            $errors[] = 'No se pudo agregar la variedad.';
        }
    } catch (Exception $e) {
        $errors[] = 'Error al agregar la variedad: ' . $e->getMessage();
    }

    header('Location: productos.php');
    exit();
}

// Obtener productos para mostrar en la tabla
try {
    $productos = $productosCollection->find()->toArray();
} catch (Exception $e) {
    $errors[] = 'Error al obtener los productos: ' . $e->getMessage();
}

// Contar el número total de empleados y tareas si el usuario es admin
$total_empleados = 0;
$total_tareas_pendientes = 0;
$total_tareas_proceso = 0;
$total_tareas_completadas = 0;

if ($_SESSION['rol'] === 'admin') {
    try {
        $usuariosCollection = $mongoClient->grupo6_agrohub->usuarios;
        $total_empleados = $usuariosCollection->countDocuments(['rol' => 'empleado']);

        $total_tareas_pendientes = $usuariosCollection->countDocuments([
            'rol' => 'empleado',
            'tareas_asignadas.estado' => 'pendiente'
        ]);

        $total_tareas_proceso = $usuariosCollection->countDocuments([
            'rol' => 'empleado',
            'tareas_asignadas.estado' => 'en_proceso'
        ]);

        $total_tareas_completadas = $usuariosCollection->countDocuments([
            'rol' => 'empleado',
            'tareas_asignadas.estado' => 'completada'
        ]);
    } catch (Exception $e) {
        $errors[] = 'Error al obtener información de empleados: ' . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Gestión de Productos</title>
    <!-- Aquí puedes incluir tus archivos CSS y JavaScript -->
</head>
<body>
    <h1>Gestión de Productos</h1>

    <?php if (!empty($success)) : ?>
        <div class="alert alert-success">
            <?php foreach ($success as $message) : ?>
                <p><?php echo htmlspecialchars($message, ENT_QUOTES, 'UTF-8'); ?></p>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <?php if (!empty($errors)) : ?>
        <div class="alert alert-danger">
            <?php foreach ($errors as $message) : ?>
                <p><?php echo htmlspecialchars($message, ENT_QUOTES, 'UTF-8'); ?></p>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <table>
        <thead>
            <tr>
                <th>Nombre</th>
                <th>Descripción</th>
                <th>Tipo</th>
                <th>Precio Unitario</th>
                <th>Unidad</th>
                <th>Variedades</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($productos as $producto) : ?>
                <tr>
                    <td><?php echo htmlspecialchars($producto['nombre'] ?? '', ENT_QUOTES, 'UTF-8'); ?></td>
                    <td><?php echo htmlspecialchars($producto['descripcion'] ?? '', ENT_QUOTES, 'UTF-8'); ?></td>
                    <td><?php echo htmlspecialchars($producto['tipo'] ?? '', ENT_QUOTES, 'UTF-8'); ?></td>
                    <td><?php echo htmlspecialchars($producto['precio_unitario'] ?? '', ENT_QUOTES, 'UTF-8'); ?></td>
                    <td><?php echo htmlspecialchars($producto['unidad'] ?? '', ENT_QUOTES, 'UTF-8'); ?></td>
                    <td>
                        <ul>
                            <?php if (!empty($producto['variedades'])) : ?>
                                <?php foreach ($producto['variedades'] as $variedad) : ?>
                                    <li>
                                        <?php echo htmlspecialchars($variedad['nombre_variedad'] ?? '', ENT_QUOTES, 'UTF-8'); ?>
                                        <a href="?action=delete_variedad&product_id=<?php echo urlencode((string) $producto['_id']); ?>&variedad_nombre=<?php echo urlencode($variedad['nombre_variedad']); ?>">Eliminar Variedad</a>
                                    </li>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </ul>
                    </td>
                    <td>
                        <a href="editar_producto.php?id=<?php echo urlencode((string) $producto['_id']); ?>">Editar</a> |
                        <a href="?action=delete&id=<?php echo urlencode((string) $producto['_id']); ?>" onclick="return confirm('¿Estás seguro de que deseas eliminar este producto?');">Eliminar</a>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <h2>Agregar/Actualizar Producto</h2>
    <form method="post" action="">
        <input type="hidden" name="id" value="<?php echo htmlspecialchars($_GET['id'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
        <label>Nombre:</label>
        <input type="text" name="nombre" required>
        <label>Descripción:</label>
        <textarea name="descripcion" required></textarea>
        <label>Tipo:</label>
        <input type="text" name="tipo" required>
        <label>Precio Unitario:</label>
        <input type="number" step="0.01" name="precio_unitario" required>
        <label>Unidad:</label>
        <input type="text" name="unidad" required>
        <label>Variedades (JSON):</label>
        <textarea name="variedades" required></textarea>
        <button type="submit">Guardar</button>
    </form>

    <h2>Agregar Variedad</h2>
    <form method="post" action="">
        <input type="hidden" name="action" value="add_variedad">
        <label>ID del Producto:</label>
        <input type="text" name="product_id" required>
        <label>Nombre de Variedad:</label>
        <input type="text" name="variedad_nombre" required>
        <label>Características:</label>
        <textarea name="caracteristicas" required></textarea>
        <button type="submit">Agregar Variedad</button>
    </form>

    <?php if ($_SESSION['rol'] === 'admin') : ?>
        <h2>Estadísticas de Empleados</h2>
        <p>Total de Empleados: <?php echo htmlspecialchars($total_empleados, ENT_QUOTES, 'UTF-8'); ?></p>
        <p>Tareas Pendientes: <?php echo htmlspecialchars($total_tareas_pendientes, ENT_QUOTES, 'UTF-8'); ?></p>
        <p>Tareas en Proceso: <?php echo htmlspecialchars($total_tareas_proceso, ENT_QUOTES, 'UTF-8'); ?></p>
        <p>Tareas Completadas: <?php echo htmlspecialchars($total_tareas_completadas, ENT_QUOTES, 'UTF-8'); ?></p>
    <?php endif; ?>
</body>
</html>
