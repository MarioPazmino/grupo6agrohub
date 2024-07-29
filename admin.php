<?php
session_start();

// Verificar si el usuario está autenticado y tiene el rol de 'admin'
if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'admin') {
    header("Location: index.php");
    exit();
}

require __DIR__ . '/vendor/autoload.php';

use MongoDB\Client;
use MongoDB\Exception\Exception;

// Conexión a MongoDB
$mongoUri = "mongodb://mario1010:marito10@testmongo1.cluster-c9ccw6ywgi5c.us-east-1.docdb.amazonaws.com:27017/?tls=true&tlsCAFile=global-bundle.pem&retryWrites=false";
$mongoClient = new Client($mongoUri);
$db = $mongoClient->selectDatabase("grupo6_agrohub");
$collection = $db->usuarios;

// Obtener todos los usuarios
$usuarios = $collection->find();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <!-- Aquí iría el código del encabezado similar al proporcionado anteriormente -->
</head>

<body id="page-top">

    <!-- Aquí iría el código del Page Wrapper similar al proporcionado anteriormente -->

    <div id="content-wrapper" class="d-flex flex-column">
        <div id="content">

            <!-- Aquí iría el código del Topbar similar al proporcionado anteriormente -->

            <div class="container-fluid">
                <h1 class="h3 mb-4 text-gray-800">Gestión de Usuarios</h1>

                <!-- Tabla de usuarios -->
                <div class="card shadow mb-4">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary">Usuarios</h6>
                    </div>
                    <div class="card-body">
                        <a href="agregar_usuario.php" class="btn btn-primary mb-3">Agregar Usuario</a>
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Nombre</th>
                                    <th>Apellido</th>
                                    <th>Email</th>
                                    <th>Teléfono</th>
                                    <th>Cédula</th>
                                    <th>Rol</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($usuarios as $usuario): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($usuario['nombre']); ?></td>
                                        <td><?php echo htmlspecialchars($usuario['apellido']); ?></td>
                                        <td><?php echo htmlspecialchars($usuario['email']); ?></td>
                                        <td><?php echo htmlspecialchars($usuario['telefono']); ?></td>
                                        <td><?php echo htmlspecialchars($usuario['cedula']); ?></td>
                                        <td><?php echo htmlspecialchars($usuario['rol']); ?></td>
                                        <td>
                                            <a href="editar_usuario.php?id=<?php echo $usuario['_id']; ?>" class="btn btn-warning btn-sm">Editar</a>
                                            <a href="eliminar_usuario.php?id=<?php echo $usuario['_id']; ?>" class="btn btn-danger btn-sm">Eliminar</a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

        </div>
    </div>

    <!-- Aquí iría el código del Footer y otros scripts similares al proporcionado anteriormente -->

</body>

</html>
