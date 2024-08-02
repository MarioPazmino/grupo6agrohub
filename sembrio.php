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

// Conexión a MongoDB
$mongoUri = "mongodb://mario1010:marito10@testmongo1.cluster-c9ccw6ywgi5c.us-east-1.docdb.amazonaws.com:27017/?tls=true&tlsCAFile=global-bundle.pem&retryWrites=false";
$mongoClient = new Client($mongoUri);
$siembrasCollection = $mongoClient->grupo6_agrohub->siembras;
$empleadosCollection = $mongoClient->grupo6_agrohub->usuarios;
$productosCollection = $mongoClient->grupo6_agrohub->productos;
$terrenosCollection = $mongoClient->grupo6_agrohub->terrenos;

$siembras = [];
$errors = [];

try {
    $siembras = $siembrasCollection->find()->toArray();
} catch (Exception $e) {
    $errors[] = 'Error al obtener las siembras: ' . $e->getMessage();
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



// Procesar formulario de agregar siembra
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['empleado_id'], $_POST['terreno_id'], $_POST['producto_id'], $_POST['fecha_siembra'], $_POST['estado'])) {
    $empleado_id = $_POST['empleado_id'];
    $terreno_id = $_POST['terreno_id'];
    $producto_id = $_POST['producto_id'];
    $fecha_siembra = new \MongoDB\BSON\UTCDateTime((new DateTime($_POST['fecha_siembra']))->getTimestamp() * 1000);
    $estado = $_POST['estado'];

    try {
        $siembrasCollection->insertOne([
            'empleado_id' => new \MongoDB\BSON\ObjectId($empleado_id),
            'terreno_id' => new \MongoDB\BSON\ObjectId($terreno_id),
            'producto_id' => new \MongoDB\BSON\ObjectId($producto_id),
            'fecha_siembra' => $fecha_siembra,
            'estado' => $estado
        ]);

        // Enviar respuesta JSON para AJAX
        if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
            echo json_encode(['success' => true, 'message' => 'Siembra agregada correctamente.']);
            exit;
        }

        $success[] = 'Siembra agregada correctamente.';
    } catch (Exception $e) {
        if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
            echo json_encode(['success' => false, 'message' => 'Error al agregar la siembra: ' . $e->getMessage()]);
            exit;
        }
        $errors[] = 'Error al agregar la siembra: ' . $e->getMessage();
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
<div id="messages-container"></div>


<!-- Siembras -->
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
                <!-- Botón para abrir el modal de agregar siembra -->
<button type="button" class="btn btn-success" data-toggle="modal" data-target="#agregarSiembraModal">
    Agregar Nueva Siembra
</button>
                <?php endif; ?>

            
            <!-- Tabla de siembras -->
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>Empleado</th>
                        <th>Terreno</th>
                        <th>Producto</th>
                        <th>Fecha de Siembra</th>
                        <th>Estado</th>
                        <?php if ($_SESSION['rol'] === 'admin'): ?>
                        <th>Acciones</th>
                        <?php endif; ?>
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
                        <?php if ($_SESSION['rol'] === 'admin'): ?>
                        <td>
                            <a href="?action=delete_siembra&id=<?php echo htmlspecialchars($siembra->_id); ?>" class="btn btn-danger btn-sm" onclick="return confirm('¿Estás seguro de que deseas eliminar esta siembra?');">
                                <i class="fas fa-trash"></i> 
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



                    
<!-- Modal Agregar Producto (solo para admin) -->
<?php if ($_SESSION['rol'] === 'admin'): ?>
<!-- Modal para agregar siembra -->
<div class="modal fade" id="agregarSiembraModal" tabindex="-1" role="dialog" aria-labelledby="agregarSiembraModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="agregarSiembraModalLabel">Agregar Nueva Siembra</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form action="" method="POST">
                    <div class="form-group">
                        <label for="empleado">Empleado</label>
                        <select class="form-control" id="empleado" name="empleado_id" required>
                            <?php foreach ($usuariosCollection->find() as $empleado): ?>
                            <option value="<?php echo htmlspecialchars($empleado->_id); ?>">
                                <?php echo htmlspecialchars($empleado->nombre . ' ' . $empleado->apellido); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="terreno">Terreno</label>
                        <select class="form-control" id="terreno" name="terreno_id" required>
                            <?php foreach ($terrenosCollection->find() as $terreno): ?>
                            <option value="<?php echo htmlspecialchars($terreno->_id); ?>">
                                <?php echo htmlspecialchars($terreno->nombre); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="producto">Producto</label>
                        <select class="form-control" id="producto" name="producto_id" required>
                            <?php foreach ($productosCollection->find() as $producto): ?>
                            <option value="<?php echo htmlspecialchars($producto->_id); ?>">
                                <?php echo htmlspecialchars($producto->nombre); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="fecha_siembra">Fecha de Siembra</label>
                        <input type="date" class="form-control" id="fecha_siembra" name="fecha_siembra" required>
                    </div>
                    <div class="form-group">
                        <label for="estado">Estado</label>
                        <select class="form-control" id="estado" name="estado" required>
                            <option value="pendiente">Pendiente</option>
                            <option value="finalizada">Finalizada</option>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-primary">Agregar Siembra</button>
                </form>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Modal Editar Producto -->
<div class="modal fade" id="editarProductoModal" tabindex="-1" role="dialog" aria-labelledby="editarProductoModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editarProductoModalLabel">Editar Producto</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
<form action="productos.php" method="POST" onsubmit="return validarFormulario()">
    <input type="hidden" id="edit_id" name="id">
    <input type="hidden" name="action" value="edit_producto">
    <div class="form-group">
        <label for="edit_nombre">Nombre</label>
        <input type="text" class="form-control" id="edit_nombre" name="nombre" required>
    </div>
    <div class="form-group">
        <label for="edit_descripcion">Descripción</label>
        <textarea class="form-control" id="edit_descripcion" name="descripcion" required></textarea>
    </div>
    <div class="form-group">
        <label for="edit_tipo">Tipo</label>
        <select class="form-control" id="edit_tipo" name="tipo" required>
            <option value="">Seleccione Tipo</option>
            <option value="fruta">Fruta</option>
            <option value="verdura">Verdura</option>
            <option value="semilla">Semilla</option>
            <option value="abono">Abono</option>
        </select>
    </div>
    <div class="form-group">
        <label for="edit_precio_unitario">Precio Unitario</label>
        <input type="number" class="form-control" id="edit_precio_unitario" name="precio_unitario" step="0.01" required>
    </div>
    <div class="form-group">
        <label for="edit_unidad">Unidad</label>
        <select class="form-control" id="edit_unidad" name="unidad" required>
            <option value="">Seleccione Unidad</option>
            <option value="kg">kg</option>
            <option value="unidad">Unidad</option>
            <option value="litro">Litro</option>
        </select>
    </div>
    <button type="submit" class="btn btn-primary">Guardar Cambios</button>
</form>


            </div>
        </div>
    </div>
</div>

<!-- Modal para agregar variedad -->
<div class="modal fade" id="agregarVariedadModal" tabindex="-1" role="dialog" aria-labelledby="agregarVariedadModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
<form action="productos.php" method="POST" id="agregarVariedadForm" onsubmit="return validarFormularioVariedad()">
    <div class="modal-header">
        <h5 class="modal-title" id="agregarVariedadModalLabel">Agregar Variedad</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
            <span aria-hidden="true">&times;</span>
        </button>
    </div>
    <div class="modal-body">
        <input type="hidden" id="product_id" name="product_id">

        <div class="form-group">
            <label for="variedad_nombre">Nombre de la Variedad</label>
            <input type="text" class="form-control" id="variedad_nombre" name="variedad_nombre" required>
        </div>

        <div class="form-group">
            <label for="caracteristicas">Características</label>
            <textarea class="form-control" id="caracteristicas" name="caracteristicas" required></textarea>
        </div>
    </div>
    <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
        <button type="submit" class="btn btn-primary" name="action" value="add_variedad">Agregar Variedad</button>
    </div>
</form>



        </div>
    </div>
</div>



<!-- Modal para ver variedades -->
<div class="modal fade" id="verVariedadesModal" tabindex="-1" role="dialog" aria-labelledby="verVariedadesModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="verVariedadesModalLabel">Variedades</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>Nombre</th>
                            <th>Características</th>
                            <?php if ($_SESSION['rol'] === 'admin'): ?>
                            <th>Acciones</th>
                            <?php endif; ?>
                        </tr>
                    </thead>
                    <tbody id="variedades_table_body">
                        <!-- Las variedades se cargarán aquí con JavaScript -->
                    </tbody>
                </table>

                <?php if ($_SESSION['rol'] === 'admin'): ?>
                <button type="button" class="btn btn-primary" onclick="openAddVariedadModal(document.getElementById('product_id').value)">
                    <i class="fas fa-plus"></i> Agregar Variedad
                </button>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script>
document.getElementById('agregarSiembraForm').addEventListener('submit', function(event) {
    event.preventDefault();
    
    var formData = new FormData(this);

    fetch('ruta_a_tu_archivo_php.php', {
        method: 'POST',
        body: formData,
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Cerrar modal
            $('#agregarSiembraModal').modal('hide');
            
            // Mostrar mensaje de éxito
            var successDiv = document.createElement('div');
            successDiv.className = 'alert alert-success';
            successDiv.innerHTML = data.message;
            document.querySelector('.card-body').prepend(successDiv);
            
            // Actualizar la tabla de siembras
            // Aquí debes añadir la lógica para actualizar la tabla con los nuevos datos
            // Por ejemplo, puedes hacer una solicitud AJAX para obtener la lista actualizada de siembras y renderizarla nuevamente

        } else {
            // Mostrar mensaje de error
            var errorDiv = document.createElement('div');
            errorDiv.className = 'alert alert-danger';
            errorDiv.innerHTML = data.message;
            document.querySelector('.card-body').prepend(errorDiv);
        }
    })
    .catch(error => console.error('Error:', error));
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
