<?php
session_start();

// Conectar a la base de datos MongoDB
require __DIR__ . '/vendor/autoload.php';

use MongoDB\Client;
use MongoDB\Exception\Exception;

// Verificar si el usuario está autenticado
if (!isset($_SESSION['rol'])) {
    header("Location: index.php");
    exit();
}

// Conexión a MongoDB con la URL proporcionada
$mongoUri = "mongodb://mario1010:marito10@testmongo1.cluster-c9ccw6ywgi5c.us-east-1.docdb.amazonaws.com:27017/?tls=true&tlsCAFile=global-bundle.pem&retryWrites=false";
$mongoClient = new Client($mongoUri);
$collection = $mongoClient->grupo6_agrohub->terrenos;

// Función para obtener los terrenos
function obtenerTerrenos($collection) {
    return $collection->find()->toArray();
}

// Función para agregar un terreno
function agregarTerreno($collection, $nombre, $ubicacion, $tamano, $estado, $descripcion) {
    try {
        $result = $collection->insertOne([
            'nombre' => $nombre,
            'ubicacion' => $ubicacion,
            'tamano' => $tamano,
            'estado' => $estado,
            'descripcion' => $descripcion
        ]);
        return $result->getInsertedCount() > 0;
    } catch (Exception $e) {
        return false;
    }
}

// Función para actualizar un terreno
function actualizarTerreno($collection, $id, $nombre, $ubicacion, $tamano, $estado, $descripcion) {
    try {
        $result = $collection->updateOne(
            ['_id' => new MongoDB\BSON\ObjectId($id)],
            ['$set' => [
                'nombre' => $nombre,
                'ubicacion' => $ubicacion,
                'tamano' => $tamano,
                'estado' => $estado,
                'descripcion' => $descripcion
            ]]
        );
        return $result->getModifiedCount() > 0;
    } catch (Exception $e) {
        return false;
    }
}

// Función para eliminar un terreno
function eliminarTerreno($collection, $id) {
    try {
        $result = $collection->deleteOne(['_id' => new MongoDB\BSON\ObjectId($id)]);
        return $result->getDeletedCount() > 0;
    } catch (Exception $e) {
        return false;
    }
}

// Procesar solicitudes de formularios
$success = [];
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        if ($_POST['action'] === 'add') {
            // Agregar terreno
            if (agregarTerreno($collection, $_POST['nombre'], $_POST['ubicacion'], $_POST['tamano'], $_POST['estado'], $_POST['descripcion'])) {
                $success[] = 'Terreno agregado exitosamente.';
            } else {
                $errors[] = 'Error al agregar terreno.';
            }
        } elseif ($_POST['action'] === 'update') {
            // Actualizar terreno
            if (actualizarTerreno($collection, $_POST['id'], $_POST['nombre'], $_POST['ubicacion'], $_POST['tamano'], $_POST['estado'], $_POST['descripcion'])) {
                $success[] = 'Terreno actualizado exitosamente.';
            } else {
                $errors[] = 'Error al actualizar terreno.';
            }
        }
    }
} elseif (isset($_GET['delete'])) {
    // Eliminar terreno
    if (eliminarTerreno($collection, $_GET['delete'])) {
        $success[] = 'Terreno eliminado exitosamente.';
    } else {
        $errors[] = 'Error al eliminar terreno.';
    }
}

// Obtener terrenos
$terrenos = obtenerTerrenos($collection);
?>


<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard</title>
    <!-- Bootstrap CSS -->
    <link href="vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <!-- Custom styles for this template-->
    <link href="css/sb-admin-2.min.css" rel="stylesheet">
    <!-- FontAwesome icons -->
    <link href="vendor/fontawesome-free/css/all.min.css" rel="stylesheet">
</head>
<body id="page-top">

    <!-- Page Wrapper -->
    <div id="wrapper">

        <!-- Sidebar -->
        <!-- (Aquí iría tu código de la barra lateral) -->

        <!-- Content Wrapper -->
        <div id="content-wrapper" class="d-flex flex-column">

            <!-- Main Content -->
            <div id="content">

                <!-- Topbar -->
                <!-- (Aquí iría tu código de la barra superior) -->

                <!-- Begin Page Content -->
                <div class="container-fluid">

                    <!-- Page Heading -->
                    <div class="d-sm-flex align-items-center justify-content-between mb-4">
                        <h1 class="h3 mb-0 text-gray-800">Dashboard</h1>
                    </div>

                    <!-- Content Row -->
                    <div class="row">

                        <?php if ($_SESSION['rol'] === 'admin'): ?>
                        <!-- Total Empleados -->
                        <!-- (Aquí irían las tarjetas para mostrar estadísticas) -->
                        <?php endif; ?>

                    </div>

                    <!-- Content Row -->
                    <div class="row">

                        <!-- Terrenos -->
                        <div class="col-lg-12">
                            <div class="card shadow mb-4">
                                <div class="card-header py-3">
                                    <h6 class="m-0 font-weight-bold text-primary">Terrenos</h6>
                                </div>
                                <div class="card-body">

                                    <!-- Mensajes de éxito y error -->
                                    <?php if (!empty($success)): ?>
                                    <div class="alert alert-success" role="alert">
                                        <?php foreach ($success as $message): ?>
                                        <?php echo $message; ?><br>
                                        <?php endforeach; ?>
                                    </div>
                                    <?php endif; ?>

                                    <?php if (!empty($errors)): ?>
                                    <div class="alert alert-danger" role="alert">
                                        <?php foreach ($errors as $message): ?>
                                        <?php echo $message; ?><br>
                                        <?php endforeach; ?>
                                    </div>
                                    <?php endif; ?>

                                    <!-- Botón de agregar terreno -->
                                    <?php if ($_SESSION['rol'] === 'admin'): ?>
                                    <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#agregarTerrenoModal">
                                        Agregar Terreno
                                    </button>
                                    <?php endif; ?>

                                    <!-- Tabla de terrenos -->
                                    <div class="table-responsive mt-4">
                                        <table class="table table-bordered" id="dataTable" width="100%" cellspacing="0">
                                            <thead>
                                                <tr>
                                                    <th>Nombre</th>
                                                    <th>Ubicación</th>
                                                    <th>Tamaño (m²)</th>
                                                    <th>Estado</th>
                                                    <th>Descripción</th>
                                                    <?php if ($_SESSION['rol'] === 'admin'): ?>
                                                    <th>Acciones</th>
                                                    <?php endif; ?>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($terrenos as $terreno): ?>
                                                <tr>
                                                    <td><?php echo htmlspecialchars($terreno->nombre); ?></td>
                                                    <td><?php echo htmlspecialchars($terreno->ubicacion); ?></td>
                                                    <td><?php echo htmlspecialchars($terreno->tamano); ?></td>
                                                    <td class="<?php echo $terreno->estado === 'activo' ? 'text-success' : 'text-danger'; ?>">
                                                        <?php echo htmlspecialchars($terreno->estado); ?>
                                                    </td>
                                                    <td><?php echo htmlspecialchars($terreno->descripcion); ?></td>
                                                    <?php if ($_SESSION['rol'] === 'admin'): ?>
                                                    <td>
                                                        <button type="button" class="btn btn-warning btn-sm" data-toggle="modal" data-target="#editarTerrenoModal" 
                                                                data-id="<?php echo $terreno->id; ?>"
                                                                data-nombre="<?php echo htmlspecialchars($terreno->nombre); ?>"
                                                                data-ubicacion="<?php echo htmlspecialchars($terreno->ubicacion); ?>"
                                                                data-tamano="<?php echo htmlspecialchars($terreno->tamano); ?>"
                                                                data-estado="<?php echo htmlspecialchars($terreno->estado); ?>"
                                                                data-descripcion="<?php echo htmlspecialchars($terreno->descripcion); ?>">
                                                            Editar
                                                        </button>
                                                        <a href="?delete=<?php echo $terreno->id; ?>" class="btn btn-danger btn-sm" onclick="return confirm('¿Estás seguro de que quieres eliminar este terreno?');">
                                                            Eliminar
                                                        </a>
                                                    </td>
                                                    <?php endif; ?>
                                                </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>

                    </div>

                </div>
                <!-- End of Page Content -->

            </div>
            <!-- End of Content Wrapper -->

        </div>
        <!-- End of Page Wrapper -->

        <!-- Modal Agregar Terreno -->
        <div class="modal fade" id="agregarTerrenoModal" tabindex="-1" role="dialog" aria-labelledby="agregarTerrenoModalLabel" aria-hidden="true">
            <div class="modal-dialog" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="agregarTerrenoModalLabel">Agregar Terreno</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <form action="" method="POST">
                            <input type="hidden" name="action" value="add">
                            <div class="form-group">
                                <label for="nombre">Nombre</label>
                                <input type="text" class="form-control" id="nombre" name="nombre" required>
                            </div>
                            <div class="form-group">
                                <label for="ubicacion">Ubicación</label>
                                <input type="text" class="form-control" id="ubicacion" name="ubicacion" required>
                            </div>
                            <div class="form-group">
                                <label for="tamano">Tamaño (m²)</label>
                                <input type="number" class="form-control" id="tamano" name="tamano" required>
                            </div>
                            <div class="form-group">
                                <label for="estado">Estado</label>
                                <select class="form-control" id="estado" name="estado" required>
                                    <option value="activo">Activo</option>
                                    <option value="inactivo">Inactivo</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="descripcion">Descripción</label>
                                <textarea class="form-control" id="descripcion" name="descripcion"></textarea>
                            </div>
                            <button type="submit" class="btn btn-primary">Agregar Terreno</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal Editar Terreno -->
        <div class="modal fade" id="editarTerrenoModal" tabindex="-1" role="dialog" aria-labelledby="editarTerrenoModalLabel" aria-hidden="true">
            <div class="modal-dialog" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="editarTerrenoModalLabel">Editar Terreno</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <form action="" method="POST">
                            <input type="hidden" name="action" value="update">
                            <input type="hidden" id="edit_id" name="id">
                            <div class="form-group">
                                <label for="edit_nombre">Nombre</label>
                                <input type="text" class="form-control" id="edit_nombre" name="nombre" required>
                            </div>
                            <div class="form-group">
                                <label for="edit_ubicacion">Ubicación</label>
                                <input type="text" class="form-control" id="edit_ubicacion" name="ubicacion" required>
                            </div>
                            <div class="form-group">
                                <label for="edit_tamano">Tamaño (m²)</label>
                                <input type="number" class="form-control" id="edit_tamano" name="tamano" required>
                            </div>
                            <div class="form-group">
                                <label for="edit_estado">Estado</label>
                                <select class="form-control" id="edit_estado" name="estado" required>
                                    <option value="activo">Activo</option>
                                    <option value="inactivo">Inactivo</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="edit_descripcion">Descripción</label>
                                <textarea class="form-control" id="edit_descripcion" name="descripcion"></textarea>
                            </div>
                            <button type="submit" class="btn btn-primary">Actualizar Terreno</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <!-- Scroll to Top Button-->
        <a class="scroll-to-top rounded" href="#page-top">
            <i class="fas fa-angle-up"></i>
        </a>

        <!-- Logout Modal-->
        <div class="modal fade" id="logoutModal" tabindex="-1" role="dialog" aria-labelledby="logoutModalLabel" aria-hidden="true">
            <div class="modal-dialog" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="logoutModalLabel">¿Está seguro de que quiere salir?</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">Selecciona "Cerrar sesión" si estás listo para terminar la sesión actual.</div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                        <a class="btn btn-primary" href="logout.php">Cerrar sesión</a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Bootstrap core JavaScript-->
        <script src="vendor/jquery/jquery.min.js"></script>
        <script src="vendor/bootstrap/js/bootstrap.bundle.min.js"></script>

        <!-- Core plugin JavaScript-->
        <script src="vendor/jquery-easing/jquery.easing.min.js"></script>

        <!-- Custom scripts for all pages-->
        <script src="js/sb-admin-2.min.js"></script>

        <!-- Page level plugins -->
        <script src="vendor/datatables/jquery.dataTables.min.js"></script>
        <script src="vendor/datatables/dataTables.bootstrap4.min.js"></script>

        <!-- Page level custom scripts -->
        <script src="js/demo/datatables-demo.js"></script>

        <!-- Scripts para modales -->
        <script>
            $('#editarTerrenoModal').on('show.bs.modal', function (event) {
                var button = $(event.relatedTarget) // Button that triggered the modal
                var id = button.data('id')
                var nombre = button.data('nombre')
                var ubicacion = button.data('ubicacion')
                var tamano = button.data('tamano')
                var estado = button.data('estado')
                var descripcion = button.data('descripcion')

                var modal = $(this)
                modal.find('.modal-body #edit_id').val(id)
                modal.find('.modal-body #edit_nombre').val(nombre)
                modal.find('.modal-body #edit_ubicacion').val(ubicacion)
                modal.find('.modal-body #edit_tamano').val(tamano)
                modal.find('.modal-body #edit_estado').val(estado)
                modal.find('.modal-body #edit_descripcion').val(descripcion)
            })
        </script>

    </body>
</html>
