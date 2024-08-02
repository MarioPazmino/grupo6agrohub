<?php
session_start();

// Verificar si el usuario está autenticado
if (!isset($_SESSION['rol'])) {
    header("Location: index.php");
    exit();
}

require __DIR__ . '/vendor/autoload.php';

use MongoDB\Client;
use MongoDB\Exception\Exception;

// Conexión a MongoDB con la URL proporcionada
$mongoUri = "mongodb://mario1010:marito10@testmongo1.cluster-c9ccw6ywgi5c.us-east-1.docdb.amazonaws.com:27017/?tls=true&tlsCAFile=global-bundle.pem&retryWrites=false";
$mongoClient = new Client($mongoUri);
$terrenosCollection = $mongoClient->grupo6_agrohub->terrenos;
$usuariosCollection = $mongoClient->grupo6_agrohub->usuarios;

// Variables para mensajes de éxito y error
$success = [];
$errors = [];

// Manejo de la eliminación de terrenos
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $id = $_GET['id'];
    error_log("Intentando eliminar terreno con ID: " . $id);
    
    try {
        if (strlen($id) == 24 && ctype_xdigit($id)) {
            $result = $terrenosCollection->deleteOne(['_id' => new MongoDB\BSON\ObjectId($id)]);
            if ($result->getDeletedCount() > 0) {
                $success[] = 'Terreno eliminado exitosamente.';
            } else {
                $errors[] = 'No se encontró el terreno para eliminar.';
            }
        } else {
            $errors[] = 'ID de terreno inválido.';
        }
    } catch (Exception $e) {
        $errors[] = 'Error al eliminar el terreno: ' . $e->getMessage();
    }

    // Redireccionar después de procesar la eliminación
    header("Location: terrenos.php");
    exit();
}

// Manejo de la actualización de terrenos
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['id'])) {
        // Actualizar terreno
        try {
            $result = $terrenosCollection->updateOne(
                ['_id' => new MongoDB\BSON\ObjectId($_POST['id'])],
                ['$set' => [
                    'nombre' => $_POST['nombre'],
                    'ubicacion' => $_POST['ubicacion'],
                    'tamano' => $_POST['tamano'],
                    'estado' => $_POST['estado'],
                    'descripcion' => $_POST['descripcion']
                ]]
            );
            if ($result->getModifiedCount() > 0) {
                $success[] = 'Terreno actualizado exitosamente.';
            } else {
                $errors[] = 'No se encontró el terreno para actualizar o no hubo cambios.';
            }
        } catch (Exception $e) {
            $errors[] = 'Error al actualizar el terreno: ' . $e->getMessage();
        }
    } else {
        // Agregar terreno
        try {
            $result = $terrenosCollection->insertOne([
                'nombre' => $_POST['nombre'],
                'ubicacion' => $_POST['ubicacion'],
                'tamano' => $_POST['tamano'],
                'estado' => $_POST['estado'],
                'descripcion' => $_POST['descripcion']
            ]);
            if ($result->getInsertedCount() > 0) {
                $success[] = 'Terreno agregado exitosamente.';
            }
        } catch (Exception $e) {
            $errors[] = 'Error al agregar el terreno: ' . $e->getMessage();
        }
    }
}

// Obtener terrenos para mostrar en la tabla
$terrenos = $terrenosCollection->find()->toArray();

// Debug: Imprimir IDs de terrenos
foreach ($terrenos as $terreno) {
    error_log("Terreno ID: " . $terreno->_id);
}

// Contar el número total de empleados y tareas si el usuario es admin
$total_empleados = 0;
$total_tareas_pendientes = 0;
$total_tareas_proceso = 0;
$total_tareas_completadas = 0;

if ($_SESSION['rol'] === 'admin') {
    try {
        // Contar el número total de empleados
        $total_empleados = $usuariosCollection->countDocuments(['rol' => 'empleado']);

        // Contar el número de tareas pendientes, en proceso y completadas
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
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="description" content="">
    <meta name="author" content="">

    <title>SB Admin 2 - Dashboard</title>

    <!-- Custom fonts for this template-->
    <link href="vendor/fontawesome-free/css/all.min.css" rel="stylesheet" type="text/css">
    <link href="https://fonts.googleapis.com/css?family=Nunito:200,200i,300,300i,400,400i,600,600i,700,700i,800,800i,900,900i" rel="stylesheet">

    <!-- Custom styles for this template-->
    <link href="css/user/sb-admin-2.min.css" rel="stylesheet">
    <style>
        .task-card {
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 15px;
        }
        .task-card .card-body {
            display: flex;
            flex-direction: column;
            align-items: center;
            width: 100%; /* Hacer el card más ancho */
        }
        .task-card .card-text {
            font-size: 1.1rem;
        }
        .task-card .btn {
            margin-top: 10px;
        }
        .task-status-pendiente {
            color: #dc3545; /* Rojo para pendiente */
        }
        .task-status-en-progreso {
            color: #ffc107; /* Amarillo para en progreso */
        }
        .task-status-completada {
            color: #28a745; /* Verde para completada */
        }
    </style>
</head>

<body id="page-top">

    <!-- Page Wrapper -->
    <div id="wrapper">

        <!-- Sidebar -->
        <ul class="navbar-nav bg-gradient-primary sidebar sidebar-dark accordion" id="accordionSidebar">

            <!-- Sidebar - Brand -->
            <a class="sidebar-brand d-flex align-items-center justify-content-center" 
   href="<?php echo htmlspecialchars($_SESSION['rol'] === 'admin' ? 'admin.php' : 'user.php'); ?>">
    <div class="sidebar-brand-icon rotate-n-15">
        <i class="fas fa-laugh-wink"></i>
    </div>
    <div class="sidebar-brand-text mx-3">Agro HUB <sup></sup></div>
</a>


            <!-- Divider -->
            <hr class="sidebar-divider my-0">

            <!-- Nav Item - Dashboard -->
            <li class="nav-item active">
    <?php
    // Determinar la página a la que se debe redirigir según el rol del usuario
    $link = $_SESSION['rol'] === 'admin' ? 'admin.php' : 'user.php';
    ?>
    <a class="nav-link" href="<?php echo htmlspecialchars($link); ?>">
        <i class="fas fa-fw fa-tachometer-alt"></i>
        <span>Dashboard</span>
    </a>
</li>


            <!-- Divider -->
            <hr class="sidebar-divider">

            <!-- Heading -->
            <div class="sidebar-heading">
                Interface
            </div>

            <!-- Nav Item - Pages Collapse Menu -->
            <li class="nav-item">
                <a class="nav-link collapsed" href="#" data-toggle="collapse" data-target="#collapseTwo"
                    aria-expanded="true" aria-controls="collapseTwo">
                    <i class="fas fa-fw fa-tractor"></i>
                    <span>Agrícola</span>
                </a>
                <div id="collapseTwo" class="collapse" aria-labelledby="headingTwo" data-parent="#accordionSidebar">
                    <div class="bg-white py-2 collapse-inner rounded">
                        <h6 class="collapse-header">Mi granja:</h6>
                        <a class="collapse-item" href="terrenos.php">Terrenos</a>
                        <a class="collapse-item" href="productos.php">Productos</a>
                        <a class="collapse-item" href="sembrio.php">Sembríos</a>
                        <a class="collapse-item" href="cosechas.php">Cosechas</a>
                    </div>
                </div>
            </li>

            <!-- Divider -->
            <hr class="sidebar-divider">

            <!-- Nav Item - Charts -->
            <li class="nav-item">
                <a class="nav-link" href="ventas.php">
                    <i class="fas fa-fw fa-cart-plus"></i>
                    <span>Ventas</span></a>
            </li>

            <!-- Divider -->
            <hr class="sidebar-divider d-none d-md-block">

            <!-- Sidebar Toggler (Sidebar) -->
            <div class="text-center d-none d-md-inline">
                <button class="rounded-circle border-0" id="sidebarToggle"></button>
            </div>

        </ul>
        <!-- End of Sidebar -->

        <!-- Content Wrapper -->
        <div id="content-wrapper" class="d-flex flex-column">

            <!-- Main Content -->
            <div id="content">

                <!-- Topbar -->
                <nav class="navbar navbar-expand navbar-light bg-white topbar mb-4 static-top shadow">

                    <!-- Sidebar Toggle (Topbar) -->
                    <button id="sidebarToggleTop" class="btn btn-link d-md-none rounded-circle mr-3">
                        <i class="fa fa-bars"></i>
                    </button>

                    <!-- Topbar Search -->
                    <form class="d-none d-sm-inline-block form-inline mr-auto ml-md-3 my-2 my-md-0 mw-100 navbar-search">
                        <div class="input-group">
                            <input type="text" class="form-control bg-light border-0 small" placeholder="Search for..."
                                aria-label="Search" aria-describedby="basic-addon2">
                            <div class="input-group-append">
                                <button class="btn btn-primary" type="button">
                                    <i class="fas fa-search fa-sm"></i>
                                </button>
                            </div>
                        </div>
                    </form>

                    <!-- Topbar Navbar -->
                    <ul class="navbar-nav ml-auto">

                        <!-- Nav Item - User Information -->
                         <li class="nav-item dropdown no-arrow">
                            <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button"
                                data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                <span class="mr-2 d-none d-lg-inline text-gray-600 small"><?php echo htmlspecialchars($_SESSION['nombre_usuario']); ?></span>

                                <img class="img-profile rounded-circle"
                                    src="assets/images/undraw_profile.svg">
                            </a>
                            <!-- Dropdown - User Information -->
                            <div class="dropdown-menu dropdown-menu-right shadow animated--grow-in" aria-labelledby="userDropdown">
                                <a class="dropdown-item" href="#">
                                    <i class="fas fa-user fa-sm fa-fw mr-2 text-gray-400"></i>
                                    Perfil
                                </a>
                                <a class="dropdown-item" href="#">
                                    <i class="fas fa-cogs fa-sm fa-fw mr-2 text-gray-400"></i>
                                    Ajustes
                                </a>
                                <a class="dropdown-item" href="#">
                                    <i class="fas fa-list fa-sm fa-fw mr-2 text-gray-400"></i>
                                    Actividad
                                </a>
                                <div class="dropdown-divider"></div>
                                <a class="dropdown-item" href="logout.php">
                                    <i class="fas fa-sign-out-alt fa-sm fa-fw mr-2 text-gray-400"></i>
                                    Salir
                                </a>
                            </div>
                        </li>

                    </ul>
                </nav>
                <!-- End of Topbar -->

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
                        <div class="col-xl-3 col-md-6 mb-4">
                            <div class="card border-left-primary shadow h-100 py-2">
                                <div class="card-body">
                                    <div class="row no-gutters align-items-center">
                                        <div class="col mr-2">
                                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                                Total Empleados</div>
                                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $total_empleados; ?></div>
                                        </div>
                                        <div class="col-auto">
                                            <i class="fas fa-users fa-2x text-gray-300"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Tareas Pendientes -->
                        <div class="col-xl-3 col-md-6 mb-4">
                            <div class="card border-left-warning shadow h-100 py-2">
                                <div class="card-body">
                                    <div class="row no-gutters align-items-center">
                                        <div class="col mr-2">
                                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                                Tareas Pendientes</div>
                                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $total_tareas_pendientes; ?></div>
                                        </div>
                                        <div class="col-auto">
                                            <i class="fas fa-tasks fa-2x text-gray-300"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Tareas en Proceso -->
                        <div class="col-xl-3 col-md-6 mb-4">
                            <div class="card border-left-info shadow h-100 py-2">
                                <div class="card-body">
                                    <div class="row no-gutters align-items-center">
                                        <div class="col mr-2">
                                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                                                Tareas en Proceso</div>
                                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $total_tareas_proceso; ?></div>
                                        </div>
                                        <div class="col-auto">
                                            <i class="fas fa-spinner fa-2x text-gray-300"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Tareas Completadas -->
                        <div class="col-xl-3 col-md-6 mb-4">
                            <div class="card border-left-success shadow h-100 py-2">
                                <div class="card-body">
                                    <div class="row no-gutters align-items-center">
                                        <div class="col mr-2">
                                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                                Tareas Completadas</div>
                                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $total_tareas_completadas; ?></div>
                                        </div>
                                        <div class="col-auto">
                                            <i class="fas fa-check-circle fa-2x text-gray-300"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
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
                        <th>Tamaño (ha)</th>
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
        <td><?php echo htmlspecialchars($terreno->estado); ?></td>
        <td><?php echo htmlspecialchars($terreno->descripcion); ?></td>
        <?php if ($_SESSION['rol'] === 'admin'): ?>
        <td>
            <button type="button" class="btn btn-warning btn-sm" data-toggle="modal" data-target="#editarTerrenoModal"
                    data-id="<?php echo $terreno->_id; ?>"
                    data-nombre="<?php echo htmlspecialchars($terreno->nombre); ?>"
                    data-ubicacion="<?php echo htmlspecialchars($terreno->ubicacion); ?>"
                    data-tamano="<?php echo htmlspecialchars($terreno->tamano); ?>"
                    data-estado="<?php echo htmlspecialchars($terreno->estado); ?>"
                    data-descripcion="<?php echo htmlspecialchars($terreno->descripcion); ?>">
                Editar
            </button>
            <a href="?action=delete&id=<?php echo $terreno->_id; ?>" class="btn btn-danger btn-sm" onclick="return confirm('¿Estás seguro de que deseas eliminar este terreno?');">
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
                    <form action="terrenos.php" method="POST">
                        <div class="form-group">
                            <label for="nombre">Nombre</label>
                            <input type="text" class="form-control" id="nombre" name="nombre" required>
                        </div>
                        <div class="form-group">
                            <label for="ubicacion">Ubicación</label>
                            <input type="text" class="form-control" id="ubicacion" name="ubicacion" required>
                        </div>
                        <div class="form-group">
                            <label for="tamano">Tamaño (ha)</label>
                            <input type="number" class="form-control" id="tamano" name="tamano" required>
                        </div>
                        <div class="form-group">
                            <label for="estado">Estado</label>
                            <select class="form-control" id="estado" name="estado" required>
                                <option value="Activo">Activo</option>
                                <option value="Ocupado">Ocupado</option>
                                <option value="En Mantenimiento">En Mantenimiento</option>
                                <option value="Desactivado">Desactivado</option>
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
                    <form action="terrenos.php" method="POST">
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
                            <label for="edit_tamano">Tamaño (ha)</label>
                            <input type="number" class="form-control" id="edit_tamano" name="tamano" required>
                        </div>
                        <div class="form-group">
                            <label for="edit_estado">Estado</label>
                            <select class="form-control" id="edit_estado" name="estado" required>
                                <option value="Activo">Activo</option>
                                <option value="Ocupado">Ocupado</option>
                                <option value="En Mantenimiento">En Mantenimiento</option>
                                <option value="Desactivado">Desactivado</option>
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
 <script>
$('#editarTerrenoModal').on('show.bs.modal', function (event) {
    var button = $(event.relatedTarget)
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

                               
                </div>
                <!-- /.container-fluid -->
            </div>
            <!-- End of Main Content -->
    
            <!-- Footer -->
            <footer class="sticky-footer bg-white">
                <div class="container my-auto">
                    <div class="copyright text-center my-auto">
                        <span>Copyright &copy; AgroHUB 2024</span>
                    </div>
                </div>
            </footer>
            <!-- End of Footer -->

        </div>
        <!-- End of Content Wrapper -->

    </div>
    <!-- End of Page Wrapper -->

    <!-- Scroll to Top Button-->
    <a class="scroll-to-top rounded" href="#page-top">
        <i class="fas fa-angle-up"></i>
    </a>

    <!-- Logout Modal-->
    <div class="modal fade" id="logoutModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel"
        aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="exampleModalLabel">Ready to Leave?</h5>
                    <button class="close" type="button" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">×</span>
                    </button>
                </div>
                <div class="modal-body">Select "Logout" below if you are ready to end your current session.</div>
                <div class="modal-footer">
                    <button class="btn btn-secondary" type="button" data-dismiss="modal">Cancel</button>
                    <a class="btn btn-primary" href="login.html">Logout</a>
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
    <script src="components/user/sb-admin-2.min.js"></script>

    <!-- Page level plugins -->
    <script src="vendor/chart.js/Chart.min.js"></script>

    <!-- Page level custom scripts -->
    <script src="components/user/demo/chart-area-demo.js"></script>
    <script src="components/user/demo/chart-pie-demo.js"></script>

</body>

</html> 
