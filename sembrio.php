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
$collection = $mongoClient->grupo6_agrohub->usuarios;
$terrenosCollection = $mongoClient->grupo6_agrohub->terrenos;
$siembrasCollection = $mongoClient->grupo6_agrohub->siembras;

// Variables para mensajes de éxito y error
$success = [];
$errors = [];

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

// Procesar eliminación de siembra
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action']) && $_GET['action'] === 'delete_siembra' && isset($_GET['id'])) {
    $siembra_id = $_GET['id'];

    try {
        $result = $siembrasCollection->deleteOne(['_id' => new ObjectId($siembra_id)]);
        if ($result->getDeletedCount() > 0) {
            $success[] = 'Siembra eliminada correctamente.';
        } else {
            $errors[] = 'No se encontró la siembra para eliminar.';
        }
    } catch (Exception $e) {
        $errors[] = 'Error al eliminar la siembra: ' . $e->getMessage();
    }
}

// Obtener siembra para mostrar detalles
if (isset($_GET['id'])) {
    $siembra_id = $_GET['id'];
    try {
        $siembra = $siembrasCollection->findOne(['_id' => new ObjectId($siembra_id)]);
        if ($siembra) {
            echo json_encode(['success' => true, 'siembra' => $siembra]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Siembra no encontrada']);
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Error al obtener la siembra: ' . $e->getMessage()]);
    }
}

// Procesar actualización de siembra
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_id'])) {
    $edit_id = $_POST['edit_id'];
    $empleado_id = $_POST['empleado_id'];
    $terreno_id = $_POST['terreno_id'];
    $producto_id = $_POST['producto_id'];
    $fecha_siembra = new \MongoDB\BSON\UTCDateTime(strtotime($_POST['fecha_siembra']) * 1000);
    $estado = $_POST['estado'];

    try {
        $result = $siembrasCollection->updateOne(
            ['_id' => new ObjectId($edit_id)],
            ['$set' => [
                'empleado_id' => new ObjectId($empleado_id),
                'terreno_id' => new ObjectId($terreno_id),
                'producto_id' => new ObjectId($producto_id),
                'fecha_siembra' => $fecha_siembra,
                'estado' => $estado
            ]]
        );

        if ($result->getModifiedCount() > 0) {
            echo json_encode(['success' => true, 'message' => 'Siembra actualizada correctamente.']);
        } else {
            echo json_encode(['success' => false, 'message' => 'No se encontró la siembra para actualizar.']);
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Error al actualizar la siembra: ' . $e->getMessage()]);
    }
    exit;
}

// Obtener siembra para mostrar detalles
if (isset($_GET['action']) && $_GET['action'] === 'get_siembra' && isset($_GET['id'])) {
    $siembra_id = $_GET['id'];
    try {
        $siembra = $siembrasCollection->findOne(['_id' => new ObjectId($siembra_id)]);
        if ($siembra) {
            echo json_encode(['success' => true, 'siembra' => $siembra]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Siembra no encontrada']);
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Error al obtener la siembra: ' . $e->getMessage()]);
    }
    exit;
}

// Obtener siembras
$siembras = [];
try {
    $siembras = $siembrasCollection->find()->toArray();
} catch (Exception $e) {
    $errors[] = 'Error al obtener información de siembras: ' . $e->getMessage();
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
                <h6 class="m-0 font-weight-bold text-primary">Siembras</h6>
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

                <!-- Botón de agregar siembra (solo para admin) -->
                <?php if ($_SESSION['rol'] === 'admin'): ?>
                <button type="button" class="btn btn-primary mb-2" data-toggle="modal" data-target="#agregarSiembraModal">
                    <i class="fas fa-plus"></i> Agregar Siembra
                </button>
                <?php endif; ?>

                <!-- Tabla de siembras -->
                <div class="table-responsive mt-4">
                    <table class="table table-bordered" id="siembrasTable" width="100%" cellspacing="0">
                        <thead>
                            <tr>
                                <th>Empleado</th>
                                <th>Terreno</th>
                                <th>Producto</th>
                                <th>Fecha Siembra</th>
                                <th>Estado</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($siembras as $siembra): ?>
                            <tr>
                                <td><?php 
                                    $empleado = $usuariosCollection->findOne(['_id' => $siembra->empleado_id]);
                                    echo htmlspecialchars($empleado->nombre . ' ' . $empleado->apellido); 
                                ?></td>
                                <td><?php 
                                    $terreno = $terrenosCollection->findOne(['_id' => $siembra->terreno_id]);
                                    echo htmlspecialchars($terreno->nombre); 
                                ?></td>
                                <td><?php 
                                    $producto = $productosCollection->findOne(['_id' => $siembra->producto_id]);
                                    echo htmlspecialchars($producto->nombre); 
                                ?></td>
                                <td><?php echo htmlspecialchars($siembra->fecha_siembra->toDateTime()->format('Y-m-d')); ?></td>
                                <td><?php echo htmlspecialchars($siembra->estado); ?></td>
                                <td>
                                    <?php if ($_SESSION['rol'] === 'admin'): ?>
                                    <a href="?action=edit_siembra&id=<?php echo htmlspecialchars($siembra->_id); ?>" class="btn btn-warning btn-sm" data-toggle="modal" data-target="#editSiembraModal">Editar</a>
                                    <a href="?action=delete_siembra&id=<?php echo htmlspecialchars($siembra->_id); ?>" class="btn btn-danger btn-sm">Eliminar</a>
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

<!-- Modal para agregar siembra -->
<div class="modal fade" id="agregarSiembraModal" tabindex="-1" role="dialog" aria-labelledby="agregarSiembraModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="agregarSiembraModalLabel">Agregar Siembra</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form action="siembras.php" method="POST">
                <div class="modal-body">
                    <div class="form-group">
                        <label for="empleado_id">Empleado</label>
                        <select id="empleado_id" name="empleado_id" class="form-control" required>
                            <?php
                            $empleados = $usuariosCollection->find(['rol' => 'empleado']);
                            foreach ($empleados as $empleado) {
                                echo '<option value="' . htmlspecialchars($empleado->_id) . '">' . htmlspecialchars($empleado->nombre . ' ' . $empleado->apellido) . '</option>';
                            }
                            ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="terreno_id">Terreno</label>
                        <select id="terreno_id" name="terreno_id" class="form-control" required>
                            <?php
                            $terrenos = $terrenosCollection->find();
                            foreach ($terrenos as $terreno) {
                                echo '<option value="' . htmlspecialchars($terreno->_id) . '">' . htmlspecialchars($terreno->nombre) . '</option>';
                            }
                            ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="producto_id">Producto</label>
                        <select id="producto_id" name="producto_id" class="form-control" required>
                            <?php
                            $productos = $productosCollection->find();
                            foreach ($productos as $producto) {
                                echo '<option value="' . htmlspecialchars($producto->_id) . '">' . htmlspecialchars($producto->nombre) . '</option>';
                            }
                            ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="fecha_siembra">Fecha Siembra</label>
                        <input type="date" id="fecha_siembra" name="fecha_siembra" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label for="estado">Estado</label>
                        <select id="estado" name="estado" class="form-control" required>
                            <option value="pendiente">Pendiente</option>
                            <option value="en_proceso">En Proceso</option>
                            <option value="completada">Completada</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Agregar</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal para editar siembra -->
<div class="modal fade" id="editSiembraModal" tabindex="-1" role="dialog" aria-labelledby="editSiembraModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editSiembraModalLabel">Editar Siembra</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form action="siembras.php" method="POST">
                <input type="hidden" name="edit_id" value="<?php echo htmlspecialchars($siembraToEdit->_id ?? ''); ?>">
                <div class="modal-body">
                    <div class="form-group">
                        <label for="empleado_id">Empleado</label>
                        <select id="empleado_id" name="empleado_id" class="form-control" required>
                            <?php
                            $empleados = $usuariosCollection->find(['rol' => 'empleado']);
                            foreach ($empleados as $empleado) {
                                $selected = ($empleado->_id == ($siembraToEdit->empleado_id ?? '')) ? 'selected' : '';
                                echo '<option value="' . htmlspecialchars($empleado->_id) . '" ' . $selected . '>' . htmlspecialchars($empleado->nombre . ' ' . $empleado->apellido) . '</option>';
                            }
                            ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="terreno_id">Terreno</label>
                        <select id="terreno_id" name="terreno_id" class="form-control" required>
                            <?php
                            $terrenos = $terrenosCollection->find();
                            foreach ($terrenos as $terreno) {
                                $selected = ($terreno->_id == ($siembraToEdit->terreno_id ?? '')) ? 'selected' : '';
                                echo '<option value="' . htmlspecialchars($terreno->_id) . '" ' . $selected . '>' . htmlspecialchars($terreno->nombre) . '</option>';
                            }
                            ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="producto_id">Producto</label>
                        <select id="producto_id" name="producto_id" class="form-control" required>
                            <?php
                            $productos = $productosCollection->find();
                            foreach ($productos as $producto) {
                                $selected = ($producto->_id == ($siembraToEdit->producto_id ?? '')) ? 'selected' : '';
                                echo '<option value="' . htmlspecialchars($producto->_id) . '" ' . $selected . '>' . htmlspecialchars($producto->nombre) . '</option>';
                            }
                            ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="fecha_siembra">Fecha Siembra</label>
                        <input type="date" id="fecha_siembra" name="fecha_siembra" class="form-control" value="<?php echo htmlspecialchars($siembraToEdit->fecha_siembra->toDateTime()->format('Y-m-d') ?? ''); ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="estado">Estado</label>
                        <select id="estado" name="estado" class="form-control" required>
                            <option value="pendiente" <?php echo ($siembraToEdit->estado ?? '') === 'pendiente' ? 'selected' : ''; ?>>Pendiente</option>
                            <option value="en_proceso" <?php echo ($siembraToEdit->estado ?? '') === 'en_proceso' ? 'selected' : ''; ?>>En Proceso</option>
                            <option value="completada" <?php echo ($siembraToEdit->estado ?? '') === 'completada' ? 'selected' : ''; ?>>Completada</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Actualizar</button>
                </div>
            </form>
        </div>
    </div>
</div>
</div>


<script>
function cargarDatosEdicion(siembraId) {
    $.ajax({
        url: 'siembras.php',
        method: 'GET',
        data: { action: 'get_siembra', id: siembraId },
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                var siembra = response.siembra;
                $('#edit_id').val(siembra._id.$oid);
                $('#empleado_id').val(siembra.empleado_id.$oid);
                $('#terreno_id').val(siembra.terreno_id.$oid);
                $('#producto_id').val(siembra.producto_id.$oid);
                $('#fecha_siembra').val(new Date(siembra.fecha_siembra.$date.$numberLong).toISOString().split('T')[0]);
                $('#estado').val(siembra.estado);
                $('#editSiembraModal').modal('show');
            } else {
                alert('Error al cargar los datos de la siembra: ' + response.message);
            }
        },
        error: function() {
            alert('Error de conexión al intentar obtener los datos de la siembra');
        }
    });
}

$(document).ready(function() {
    $('form').on('submit', function(e) {
        e.preventDefault();
        $.ajax({
            url: 'siembras.php',
            method: 'POST',
            data: $(this).serialize(),
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    alert('Siembra actualizada correctamente');
                    $('#editSiembraModal').modal('hide');
                    location.reload();
                } else {
                    alert('Error al actualizar la siembra: ' + response.message);
                }
            },
            error: function() {
                alert('Error de conexión al intentar actualizar la siembra');
            }
        });
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
