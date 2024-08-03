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

// Mapear productos para fácil acceso por ID
$productosMap = [];
foreach ($productos as $producto) {
    $productosMap[(string)$producto->_id] = $producto->nombre;
}

// Mapear siembras para obtener nombres de productos
$siembrasMap = [];
foreach ($siembras as $siembra) {
    $productoNombre = isset($productosMap[(string)$siembra->producto_id]) ? $productosMap[(string)$siembra->producto_id] : 'Desconocido';
    $siembrasMap[(string)$siembra->_id] = $productoNombre;
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
    <div class="col-lg-12">
        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">Cosechas</h6>
            </div>
            <div class="card-body">

                <!-- Mensajes de éxito y error -->
                <?php if (!empty($success)): ?>
                <div class="alert alert-success" role="alert">
                    <?php foreach ($success as $message): ?>
                    <?php echo htmlspecialchars($message); ?><br>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>

                <?php if (!empty($errors)): ?>
                <div class="alert alert-danger" role="alert">
                    <?php foreach ($errors as $message): ?>
                    <?php echo htmlspecialchars($message); ?><br>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>

                <!-- Botón de agregar cosecha (solo para admin) -->
                <?php if ($_SESSION['rol'] === 'admin'): ?>
                <button type="button" class="btn btn-primary mb-2" data-toggle="modal" data-target="#agregarCosechaModal">
                    <i class="fas fa-plus"></i> Agregar Cosecha
                </button>
                <?php endif; ?>

                <!-- Tabla de cosechas -->
                <div class="table-responsive mt-4">
                    <table class="table table-bordered" id="cosechasTable" width="100%" cellspacing="0">
                        <thead>
                            <tr>
                                <th>Siembra</th>
                                <th>Fecha Cosecha</th>
                                <th>Cantidad</th>
                                <th>Unidad</th>
                                <th>Detalles</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($cosechas as $cosecha): ?>
                            <tr>
                                <td><?php 
                                    // Obtener la siembra correspondiente
                                    $siembra = $siembrasCollection->findOne(['_id' => $cosecha->siembra_id]);
                                    // Verificar si la siembra fue encontrada y si el campo 'producto_id' está presente
                                    $productoId = isset($siembra->producto_id) ? $siembra->producto_id : null;
                                    $productoNombre = 'Desconocido';
                                    if ($productoId) {
                                        $producto = $productosCollection->findOne(['_id' => $productoId]);
                                        if ($producto && isset($producto->nombre)) {
                                            // Asegurarse de que el nombre es un string
                                            $productoNombre = is_array($producto->nombre) ? implode(', ', $producto->nombre) : (string)$producto->nombre;
                                        }
                                    }
                                    echo htmlspecialchars($productoNombre); 
                                ?></td>
                                <td><?php echo htmlspecialchars($cosecha->fecha_cosecha->toDateTime()->format('Y-m-d')); ?></td>
                                <td><?php echo htmlspecialchars((string)$cosecha->cantidad); ?></td>
                                <td><?php echo htmlspecialchars((string)$cosecha->unidad); ?></td>
                                <td><?php echo htmlspecialchars((string)$cosecha->detalles_cosecha); ?></td>
                                <td>
                                    <?php if ($_SESSION['rol'] === 'admin'): ?>
                                    <a href="?action=delete_cosecha&id=<?php echo htmlspecialchars($cosecha->_id); ?>" class="btn btn-danger btn-sm" onclick="return confirm('¿Estás seguro de que deseas eliminar esta cosecha?');">
                                        <i class="fas fa-trash"></i>
                                    </a>
                                    <button type="button" class="btn btn-warning btn-sm" data-toggle="modal" data-target="#editarCosechaModal" data-id="<?php echo htmlspecialchars($cosecha->_id); ?>" data-siembra="<?php echo htmlspecialchars($cosecha->siembra_id); ?>" data-fecha="<?php echo htmlspecialchars($cosecha->fecha_cosecha->toDateTime()->format('Y-m-d')); ?>" data-cantidad="<?php echo htmlspecialchars($cosecha->cantidad); ?>" data-unidad="<?php echo htmlspecialchars($cosecha->unidad); ?>" data-detalles="<?php echo htmlspecialchars($cosecha->detalles_cosecha); ?>">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <?php endif; ?>
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

<!-- Modal HTML -->
    <div class="modal fade" id="agregarCosechaModal" tabindex="-1" role="dialog" aria-labelledby="agregarCosechaModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="agregarCosechaModalLabel">Agregar Nueva Cosecha</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <!-- Formulario para agregar cosechas -->
                    <form method="POST" action="">
                        <!-- Campos del formulario -->
                        <div class="form-group">
                            <label for="siembra_id">ID de Siembra</label>
                            <input type="text" class="form-control" id="siembra_id" name="siembra_id" required>
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
                            <label for="detalles_cosecha">Detalles de la Cosecha</label>
                            <textarea class="form-control" id="detalles_cosecha" name="detalles_cosecha"></textarea>
                        </div>
                        <button type="submit" class="btn btn-primary" name="create">Guardar</button>
                    </form>
                </div>
            </div>
        </div>
    </div>    </div>

<!-- Modal para editar siembra -->
<div class="modal fade" id="editarSiembraModal" tabindex="-1" role="dialog" aria-labelledby="editarSiembraModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editarSiembraModalLabel">Editar Siembra</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form id="editarSiembraForm" action="" method="POST">
                    <input type="hidden" id="edit_siembra_id" name="siembra_id">
                    <div class="form-group">
                        <label for="edit_empleado_id">Empleado</label>
                        <select id="edit_empleado_id" name="empleado_id" class="form-control" required>
                            <?php
                            $empleados = $usuariosCollection->find(['rol' => 'empleado']);
                            foreach ($empleados as $empleado) {
                                echo '<option value="' . htmlspecialchars($empleado->_id) . '">' . htmlspecialchars($empleado->nombre . ' ' . $empleado->apellido) . '</option>';
                            }
                            ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="edit_terreno_id">Terreno</label>
                        <select id="edit_terreno_id" name="terreno_id" class="form-control" required>
                            <?php
                            $terrenos = $terrenosCollection->find();
                            foreach ($terrenos as $terreno) {
                                echo '<option value="' . htmlspecialchars($terreno->_id) . '">' . htmlspecialchars($terreno->nombre) . '</option>';
                            }
                            ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="edit_producto_id">Producto</label>
                        <select id="edit_producto_id" name="producto_id" class="form-control" required>
                            <?php
                            $productos = $productosCollection->find();
                            foreach ($productos as $producto) {
                                echo '<option value="' . htmlspecialchars($producto->_id) . '">' . htmlspecialchars($producto->nombre) . '</option>';
                            }
                            ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="edit_fecha_siembra">Fecha de Siembra</label>
                        <input type="date" id="edit_fecha_siembra" name="fecha_siembra" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label for="edit_estado">Estado</label>
                        <select id="edit_estado" name="estado" class="form-control" required>
                            <option value="pendiente">Pendiente</option>
                            <option value="en_proceso">En Proceso</option>
                            <option value="completada">Completada</option>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-primary">Actualizar</button>
                </form>
            </div>
        </div>
    </div>
</div>



                    
<script>
    // Espera a que el DOM esté completamente cargado
    document.addEventListener('DOMContentLoaded', function() {
        // Inicializa el modal de Bootstrap
        $('#agregarCosechaModal').on('shown.bs.modal', function (e) {
            // Aquí puedes agregar cualquier lógica adicional para cuando el modal se muestre
        });

        // Manejador de eventos para cuando se hace clic en el botón de agregar cosecha
        $('#agregarCosechaModal').on('show.bs.modal', function (e) {
            // Opcional: puedes inicializar campos o cargar datos dinámicamente aquí
        });

        // Si estás usando algún sistema de formularios AJAX para enviar datos, puedes manejarlo aquí
        $('form').on('submit', function(event) {
            event.preventDefault();
            // Aquí puedes agregar la lógica para enviar el formulario por AJAX si es necesario
        });
    });
</script>

                    

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



    </body>

</html>


