<?php
session_start();

error_reporting(E_ALL);
ini_set('display_errors', 1);

if (!isset($_SESSION['rol'])) {
    header("Location: index.php");
    exit();
}

require __DIR__ . '/vendor/autoload.php';

use MongoDB\Client;
use MongoDB\BSON\ObjectId;
use MongoDB\Exception\Exception;

$mongoUri = "mongodb://mario1010:marito10@testmongo1.cluster-c9ccw6ywgi5c.us-east-1.docdb.amazonaws.com:27017/?tls=true&tlsCAFile=global-bundle.pem&retryWrites=false";
$mongoClient = new Client($mongoUri);
$cosechasCollection = $mongoClient->grupo6_agrohub->cosechas;
$siembrasCollection = $mongoClient->grupo6_agrohub->siembras;

$success = [];
$errors = [];

// Obtener los productos de las siembras
try {
    $siembrasCursor = $siembrasCollection->find();
    $siembras = iterator_to_array($siembrasCursor);
    $siembrasMap = [];

    foreach ($siembras as $siembra) {
        $siembrasMap[(string)$siembra->_id] = $siembra->producto ?? 'Producto desconocido';
    }
} catch (Exception $e) {
    $errors[] = 'Error al obtener las siembras: ' . $e->getMessage();
}

// Manejo de la eliminación de cosechas
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $id = $_GET['id'];

    try {
        if (strlen($id) == 24 && ctype_xdigit($id)) {
            $result = $cosechasCollection->deleteOne(['_id' => new ObjectId($id)]);
            if ($result->getDeletedCount() > 0) {
                $success[] = 'Cosecha eliminada exitosamente.';
            } else {
                $errors[] = 'No se encontró la cosecha para eliminar.';
            }
        } else {
            $errors[] = 'ID de cosecha inválido.';
        }
    } catch (Exception $e) {
        $errors[] = 'Error al eliminar la cosecha: ' . $e->getMessage();
    }

    header("Location: cosechas.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        if ($_POST['action'] === 'add_cosecha') {
            try {
                $detallesCosecha = [];

                // Obtener detalles de cosecha del formulario
                foreach ($_POST['cantidad_recolectada'] as $index => $cantidadRecolectada) {
                    $detallesCosecha[] = [
                        'cantidad_recolectada' => intval($cantidadRecolectada),
                        'calidad' => $_POST['calidad'][$index]
                    ];
                }

                $cosechaData = [
                    'siembra_id' => new ObjectId($_POST['siembra_id']),
                    'fecha_cosecha' => new MongoDB\BSON\UTCDateTime(new DateTime($_POST['fecha_cosecha'])),
                    'cantidad' => intval($_POST['cantidad']),
                    'unidad' => $_POST['unidad'],
                    'detalles_cosecha' => $detallesCosecha
                ];

                $result = $cosechasCollection->insertOne($cosechaData);
                if ($result->getInsertedCount() > 0) {
                    $success[] = 'Cosecha agregada exitosamente.';
                } else {
                    $errors[] = 'Error al agregar la cosecha.';
                }
            } catch (\MongoDB\Driver\Exception\Exception $e) {
                $errors[] = 'Error de MongoDB al manejar la cosecha: ' . $e->getMessage();
            } catch (\Exception $e) {
                $errors[] = 'Error general al manejar la cosecha: ' . $e->getMessage();
            }
        }

        if ($_POST['action'] === 'edit_cosecha' && isset($_POST['id'])) {
            $id = $_POST['id'];

            try {
                if (!empty($id) && is_string($id) && strlen($id) == 24 && ctype_xdigit($id)) {
                    $updateData = [
                        'siembra_id' => new ObjectId($_POST['siembra_id']),
                        'fecha_cosecha' => new MongoDB\BSON\UTCDateTime(new DateTime($_POST['fecha_cosecha'])),
                        'cantidad' => intval($_POST['cantidad']),
                        'unidad' => $_POST['unidad'],
                        'detalles_cosecha' => $_POST['detalles_cosecha'] // Asegúrate de enviar los detalles correctamente
                    ];

                    $result = $cosechasCollection->updateOne(
                        ['_id' => new ObjectId($id)],
                        ['$set' => $updateData]
                    );

                    if ($result->getModifiedCount() > 0) {
                        $success[] = 'Cosecha actualizada exitosamente.';
                    } else {
                        $errors[] = 'No se encontró la cosecha para actualizar o no hubo cambios.';
                    }
                } else {
                    $errors[] = 'ID de cosecha inválido.';
                }
            } catch (Exception $e) {
                $errors[] = 'Error al actualizar la cosecha: ' . $e->getMessage();
            }
        }
    }
}

// Obtener cosechas para mostrar en la tabla
try {
    $cosechas = $cosechasCollection->find()->toArray();
} catch (Exception $e) {
    $errors[] = 'Error al obtener las cosechas: ' . $e->getMessage();
}

// Manejo de solicitudes AJAX para obtener detalles de la cosecha
if (isset($_GET['id'])) {
    $id = $_GET['id'];
    if (is_string($id) && strlen($id) == 24 && ctype_xdigit($id)) {
        try {
            $objectId = new ObjectId($id);
            $cosecha = $cosechasCollection->findOne(['_id' => $objectId]);
            if ($cosecha) {
                $cosecha['_id'] = $cosecha['_id']->__toString();
                $cosecha['fecha_cosecha'] = $cosecha['fecha_cosecha']->toDateTime()->format('Y-m-d');
                echo json_encode($cosecha);
            } else {
                echo json_encode(['error' => 'Cosecha no encontrada']);
            }
        } catch (Exception $e) {
            echo json_encode(['error' => 'Error al obtener la cosecha: ' . $e->getMessage()]);
        }
    } else {
        echo json_encode(['error' => 'ID de cosecha inválido']);
    }
    exit();
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
            <a class="sidebar-brand d-flex align-items-center justify-content-center" href="admin.php">
                <div class="sidebar-brand-icon rotate-n-15">
                    <i class="fas fa-laugh-wink"></i>
                </div>
                <div class="sidebar-brand-text mx-3">Agro HUB  <sup></sup></div>
            </a>

            <!-- Divider -->
            <hr class="sidebar-divider my-0">

            <!-- Nav Item - Dashboard -->
            <li class="nav-item active">
                <a class="nav-link" href="admin.php">
                    <i class="fas fa-fw fa-tachometer-alt"></i>
                    <span>Dashboard</span></a>
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

                  
                    <!-- Topbar Navbar -->
                    <ul class="navbar-nav ml-auto">

                        

                       

                        <div class="topbar-divider d-none d-sm-block"></div>

                        <!-- Nav Item - User Information -->
                        <li class="nav-item dropdown no-arrow">
                            <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button"
                                data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                <span class="mr-2 d-none d-lg-inline text-gray-600 small"><?php echo htmlspecialchars($_SESSION['nombre_usuario']); ?></span>

                                <img class="img-profile rounded-circle"
                                    src="assets/images/undraw_profile.svg">
                            </a>
                            <!-- Dropdown - User Information -->
                            <div class="dropdown-menu dropdown-menu-right shadow animated--grow-in"
                                aria-labelledby="userDropdown">
                                <a class="dropdown-item" href="user.php">
                                    <i class="fas fa-user fa-sm fa-fw mr-2 text-gray-400"></i>
                                    Profile
                                </a>
                                <a class="dropdown-item" href="#">
                                    <i class="fas fa-cogs fa-sm fa-fw mr-2 text-gray-400"></i>
                                    Settings
                                </a>
                                <a class="dropdown-item" href="#">
                                    <i class="fas fa-list fa-sm fa-fw mr-2 text-gray-400"></i>
                                    Activity Log
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




<div class="container mt-4">
    <div class="row">
        <div class="col-lg-12">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Cosechas</h6>
                </div>
                <div class="card-body">

                    <!-- Mensajes de éxito -->
                    <?php if (!empty($success)): ?>
                    <div class="alert alert-success" role="alert">
                        <?php foreach ($success as $message): ?>
                        <?php echo htmlspecialchars($message); ?><br>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>

                    <!-- Mensajes de error -->
                    <?php if (!empty($errors)): ?>
                    <div class="alert alert-danger" role="alert">
                        <?php foreach ($errors as $message): ?>
                        <?php echo htmlspecialchars($message); ?><br>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>

                    <!-- Botón para abrir el modal de agregar cosecha (solo para admin) -->
                    <?php if ($_SESSION['rol'] === 'admin'): ?>
                    <button type="button" class="btn btn-primary mb-2" data-toggle="modal" data-target="#agregarDetalleCosechaModal">
                        <i class="fas fa-plus"></i> Agregar Cosecha
                    </button>
                    <?php endif; ?>

                    <!-- Tabla de Cosechas -->
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Producto Sembrado</th>
                                <th>Fecha de Cosecha</th>
                                <th>Cantidad Recolectada</th>
                                <th>Calidad</th>
                                <?php if ($_SESSION['rol'] === 'admin'): ?>
                                <th>Acciones</th>
                                <?php endif; ?>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($cosechas as $cosecha): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($siembrasMap[(string)$cosecha['siembra_id']] ?? 'Producto Desconocido'); ?></td>
                                <td><?php echo htmlspecialchars(date('Y-m-d', $cosecha['fecha_cosecha']->toDateTime()->getTimestamp())); ?></td>
                                <td>
                                    <?php 
                                    $totalCantidadRecolectada = 0;
                                    if (isset($cosecha['detalles_cosecha'])) {
                                        foreach ($cosecha['detalles_cosecha'] as $detalle) {
                                            $totalCantidadRecolectada += $detalle['cantidad_recolectada'] ?? 0;
                                        }
                                    }
                                    echo htmlspecialchars($totalCantidadRecolectada);
                                    ?>
                                </td>
                                <td>
                                    <?php
                                    $calidades = [];
                                    if (isset($cosecha['detalles_cosecha'])) {
                                        foreach ($cosecha['detalles_cosecha'] as $detalle) {
                                            $calidades[] = htmlspecialchars($detalle['calidad'] ?? 'N/A');
                                        }
                                    }
                                    echo implode(', ', $calidades);
                                    ?>
                                </td>
                                <?php if ($_SESSION['rol'] === 'admin'): ?>
                                <td>
                                    <button type="button" class="btn btn-info btn-sm" onclick="showDetallesCosecha(<?php echo htmlspecialchars(json_encode($cosecha['detalles_cosecha']), ENT_QUOTES, 'UTF-8'); ?>, '<?php echo htmlspecialchars($cosecha['_id']); ?>')">
                                        <i class="fas fa-info-circle"></i> Detalles
                                    </button>
                                    <button type="button" class="btn btn-warning btn-sm" onclick="editCosecha('<?php echo htmlspecialchars($cosecha['_id']); ?>', '<?php echo htmlspecialchars((string)$cosecha['siembra_id']); ?>', '<?php echo htmlspecialchars($cosecha['fecha_cosecha']->toDateTime()->format('Y-m-d')); ?>', '<?php echo htmlspecialchars($cosecha['cantidad']); ?>', '<?php echo htmlspecialchars($cosecha['unidad']); ?>', <?php echo htmlspecialchars(json_encode($cosecha['detalles_cosecha']), ENT_QUOTES, 'UTF-8'); ?>)">
                                        <i class="fas fa-edit"></i> Editar
                                    </button>
                                    <a href="cosechas.php?action=delete&id=<?php echo htmlspecialchars($cosecha['_id']); ?>" class="btn btn-danger btn-sm" onclick="return confirm('¿Estás seguro de eliminar esta cosecha?')">
                                        <i class="fas fa-trash"></i> Eliminar
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

<!-- Modal Agregar Cosecha (solo para admin) -->
<?php if ($_SESSION['rol'] === 'admin'): ?>
<div class="modal fade" id="agregarDetalleCosechaModal" tabindex="-1" role="dialog" aria-labelledby="agregarDetalleCosechaModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <form action="cosechas.php" method="POST" id="agregarDetalleCosechaForm" onsubmit="return validarFormularioDetalleCosecha()">
                <div class="modal-header">
                    <h5 class="modal-title" id="agregarDetalleCosechaModalLabel">Agregar Detalle de Cosecha</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <input type="hidden" id="siembra_id" name="siembra_id">

                    <div class="form-group">
                        <label for="fecha_cosecha">Fecha de Cosecha</label>
                        <input type="date" class="form-control" id="fecha_cosecha" name="fecha_cosecha" required>
                    </div>

                    <div class="form-group">
                        <label for="cantidad">Cantidad</label>
                        <input type="number" class="form-control" id="cantidad" name="cantidad" required>
                    </div>

                    <div class="form-group">
                        <label for="unidad">Unidad de Medida</label>
                        <input type="text" class="form-control" id="unidad" name="unidad" required>
                    </div>

                    <div class="form-group">
                        <label for="detalles_cosecha">Detalles de Cosecha</label>
                        <div id="detalles_cosecha_container">
                            <!-- Detalles de cosecha se agregarán dinámicamente aquí -->
                        </div>
                        <button type="button" class="btn btn-secondary" onclick="agregarDetalleCosecha()">Agregar Detalle</button>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary" name="action" value="add_detalle_cosecha">Agregar Detalle</button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endif; ?>

<script>
    function agregarDetalleCosecha() {
        const container = document.getElementById('detalles_cosecha_container');
        const newDetail = document.createElement('div');
        newDetail.classList.add('form-group');
        newDetail.innerHTML = `
            <div class="row">
                <div class="col-md-6">
                    <label>Cantidad Recolectada:</label>
                    <input type="number" class="form-control" name="cantidad_recolectada[]" required>
                </div>
                <div class="col-md-6">
                    <label>Calidad:</label>
                    <input type="text" class="form-control" name="calidad[]" required>
                </div>
            </div>
        `;
        container.appendChild(newDetail);
    }

    function validarFormularioDetalleCosecha() {
        // Aquí puedes agregar validaciones específicas para el formulario
        return true;
    }

    function showDetallesCosecha(detalles, id) {
        // Implementar función para mostrar detalles de cosecha
    }

    function editCosecha(id, siembra_id, fecha_cosecha, cantidad, unidad, detalles) {
        // Implementar función para editar cosecha
    }
</script>


<!-- Modal Editar Cosecha -->
<div class="modal fade" id="editarCosechaModal" tabindex="-1" role="dialog" aria-labelledby="editarCosechaModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editarCosechaModalLabel">Editar Cosecha</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form action="cosechas.php" method="POST" onsubmit="return validarFormulario()">
                    <input type="hidden" id="edit_id" name="id">
                    <input type="hidden" name="action" value="edit_cosecha">
                    <div class="form-group">
                        <label for="edit_nombre">Nombre</label>
                        <input type="text" class="form-control" id="edit_nombre" name="nombre" required>
                    </div>
                    <div class="form-group">
                        <label for="edit_descripcion">Descripción</label>
                        <textarea class="form-control" id="edit_descripcion" name="descripcion" required></textarea>
                    </div>
                    <div class="form-group">
                        <label for="edit_fecha">Fecha</label>
                        <input type="date" class="form-control" id="edit_fecha" name="fecha" required>
                    </div>
                    <div class="form-group">
                        <label for="edit_cantidad_recolectada">Cantidad Recolectada</label>
                        <input type="number" class="form-control" id="edit_cantidad_recolectada" name="cantidad_recolectada" step="0.01" required>
                    </div>
                    <div class="form-group">
                        <label for="edit_calidad">Calidad</label>
                        <input type="text" class="form-control" id="edit_calidad" name="calidad" required>
                    </div>
                    <button type="submit" class="btn btn-primary">Guardar Cambios</button>
                </form>
            </div>
        </div>
    </div>
</div>














                            
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
