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
$cosechasCollection = $mongoClient->grupo6_agrohub->cosechas;
$siembrasCollection = $mongoClient->grupo6_agrohub->siembras;
$productosCollection = $mongoClient->grupo6_agrohub->productos;

// Variables para mensajes de éxito y error
$success = [];
$errors = [];

// Manejo de solicitudes de formulario
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['create'])) {
        // Crear (Insertar) un nuevo documento
        try {
            $newCosecha = [
                'siembra_id' => new ObjectId($_POST['siembra_id']),
                'fecha_cosecha' => new \DateTime($_POST['fecha_cosecha']),
                'cantidad' => (int)$_POST['cantidad'],
                'unidad' => $_POST['unidad'],
                'detalles_cosecha' => $_POST['detalles_cosecha']
            ];
            $result = $cosechasCollection->insertOne($newCosecha);
            $success[] = "Cosecha insertada con el ID: " . $result->getInsertedId();
        } catch (Exception $e) {
            $errors[] = 'Error al insertar la cosecha: ' . $e->getMessage();
        }
    } elseif (isset($_POST['update'])) {
        // Actualizar un documento
        try {
            $filter = ['_id' => new ObjectId($_POST['update_id'])];
            $update = [
                '$set' => [
                    'cantidad' => (int)$_POST['update_cantidad'],
                    'detalles_cosecha' => $_POST['update_detalles_cosecha']
                ]
            ];
            $result = $cosechasCollection->updateOne($filter, $update);
            $success[] = "Documentos modificados: " . $result->getModifiedCount();
        } catch (Exception $e) {
            $errors[] = 'Error al actualizar la cosecha: ' . $e->getMessage();
        }
    } elseif (isset($_POST['delete'])) {
        // Eliminar un documento
        try {
            $filter = ['_id' => new ObjectId($_POST['delete_id'])];
            $result = $cosechasCollection->deleteOne($filter);
            $success[] = "Documentos eliminados: " . $result->getDeletedCount();
        } catch (Exception $e) {
            $errors[] = 'Error al eliminar la cosecha: ' . $e->getMessage();
        }
    }
}

// Leer (Obtener) documentos
$cosechas = $cosechasCollection->find()->toArray();
$siembras = $siembrasCollection->find()->toArray();
$productos = $productosCollection->find()->toArray();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CRUD Cosechas</title>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
</head>
<body>
    <div class="container mt-4">
        <h1>CRUD Cosechas</h1>
        
        <!-- Mensajes de éxito o error -->
        <?php if (!empty($success)): ?>
            <div class="alert alert-success">
                <?php foreach ($success as $msg) echo $msg . "<br>"; ?>
            </div>
        <?php endif; ?>
        <?php if (!empty($errors)): ?>
            <div class="alert alert-danger">
                <?php foreach ($errors as $msg) echo $msg . "<br>"; ?>
            </div>
        <?php endif; ?>

        <!-- Formulario para crear una cosecha -->
        <h2>Agregar Cosecha</h2>
        <form method="POST">
            <div class="form-group">
                <label for="siembra_id">ID de Siembra</label>
                <select class="form-control" id="siembra_id" name="siembra_id" required>
                    <?php foreach ($siembras as $siembra): ?>
                        <option value="<?php echo $siembra->_id; ?>">
                            Siembra ID: <?php echo $siembra->_id; ?> - Producto ID: <?php echo $siembra->producto_id; ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label for="fecha_cosecha">Fecha de Cosecha</label>
                <input type="date" class="form-control" id="fecha_cosecha" name="fecha_cosecha" required>
            </div>
            <div class="form-group">
                <label for="cantidad">Cantidad</label>
                <input type="number" class="form-control" id="cantidad" name="cantidad" required>
            </div>
            <div class="form-group">
                <label for="unidad">Unidad</label>
                <input type="text" class="form-control" id="unidad" name="unidad" required>
            </div>
            <div class="form-group">
                <label for="detalles_cosecha">Detalles de Cosecha</label>
                <textarea class="form-control" id="detalles_cosecha" name="detalles_cosecha" required></textarea>
            </div>
            <button type="submit" name="create" class="btn btn-primary">Agregar</button>
        </form>

        <!-- Formulario para actualizar una cosecha -->
        <h2>Actualizar Cosecha</h2>
        <form method="POST">
            <div class="form-group">
                <label for="update_id">ID de Cosecha</label>
                <input type="text" class="form-control" id="update_id" name="update_id" required>
            </div>
            <div class="form-group">
                <label for="update_cantidad">Cantidad</label>
                <input type="number" class="form-control" id="update_cantidad" name="update_cantidad" required>
            </div>
            <div class="form-group">
                <label for="update_detalles_cosecha">Detalles de Cosecha</label>
                <textarea class="form-control" id="update_detalles_cosecha" name="update_detalles_cosecha" required></textarea>
            </div>
            <button type="submit" name="update" class="btn btn-warning">Actualizar</button>
        </form>

        <!-- Formulario para eliminar una cosecha -->
        <h2>Eliminar Cosecha</h2>
        <form method="POST">
            <div class="form-group">
                <label for="delete_id">ID de Cosecha</label>
                <input type="text" class="form-control" id="delete_id" name="delete_id" required>
            </div>
            <button type="submit" name="delete" class="btn btn-danger">Eliminar</button>
        </form>

        <!-- Mostrar todas las cosechas -->
        <h2>Lista de Cosechas</h2>
        <table class="table table-bordered">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Siembra ID</th>
                    <th>Fecha de Cosecha</th>
                    <th>Cantidad</th>
                    <th>Unidad</th>
                    <th>Detalles</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($cosechas as $cosecha): ?>
                    <tr>
                        <td><?php echo $cosecha->_id; ?></td>
                        <td><?php echo $cosecha->siembra_id; ?></td>
                        <td><?php echo $cosecha->fecha_cosecha->toDateTime()->format('Y-m-d'); ?></td>
                        <td><?php echo $cosecha->cantidad; ?></td>
                        <td><?php echo $cosecha->unidad; ?></td>
                        <td><?php echo $cosecha->detalles_cosecha; ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</body>
</html>
