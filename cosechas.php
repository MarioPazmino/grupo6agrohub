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

// Variables para mensajes de éxito y error
$success = [];
$errors = [];

// Obtener siembras para mostrar en el formulario de cosechas
$siembrasCollection = $mongoClient->grupo6_agrohub->siembras;
try {
    $siembrasCursor = $siembrasCollection->find();
    $siembras = iterator_to_array($siembrasCursor);
} catch (Exception $e) {
    $errors[] = 'Error al obtener las siembras: ' . $e->getMessage();
}





// Obtener siembras y la información del empleado encargado
try {
    $siembrasCursor = $siembrasCollection->aggregate([
        [
            '$lookup' => [
                'from' => 'usuarios',
                'localField' => 'empleado_id',
                'foreignField' => '_id',
                'as' => 'empleado_info'
            ]
        ],
        [
            '$unwind' => '$empleado_info'
        ],
        [
            '$project' => [
                '_id' => 1,
                'empleado_id' => 1,
                'terreno_id' => 1,
                'producto_id' => 1,
                'fecha_siembra' => 1,
                'estado' => 1,
                'empleado_nombre' => ['$concat' => ['$empleado_info.nombre', ' ', '$empleado_info.apellido']]
            ]
        ]
    ]);

    $siembras = iterator_to_array($siembrasCursor);
} catch (Exception $e) {
    $errors[] = 'Error al obtener las siembras: ' . $e->getMessage();
}

// Obtener cosechas y mapear la información del empleado encargado
try {
    $cosechasCursor = $cosechasCollection->find();
    $cosechas = [];

    foreach ($cosechasCursor as $cosecha) {
        // Encontrar la siembra correspondiente para obtener el nombre del empleado encargado
        $siembra = array_filter($siembras, function($s) use ($cosecha) {
            return (string)$s['_id'] === (string)$cosecha['siembra_id'];
        });

        if ($siembra) {
            $siembra = array_values($siembra)[0]; // Obtener el primer elemento
            $cosecha['nombre_empleado'] = $siembra['empleado_nombre'];
        } else {
            $cosecha['nombre_empleado'] = 'Desconocido';
        }

        $cosechas[] = $cosecha;
    }
} catch (Exception $e) {
    $errors[] = 'Error al obtener las cosechas: ' . $e->getMessage();
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

// Manejo de la actualización y agregación de cosechas
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        if ($_POST['action'] === 'add_cosecha') {
            try {
                $cosechaData = [
                    'siembra_id' => new ObjectId($_POST['siembra_id']),
                    'fecha_cosecha' => new MongoDB\BSON\UTCDateTime(new DateTime($_POST['fecha_cosecha'])),
                    'cantidad' => intval($_POST['cantidad']),
                    'unidad' => $_POST['unidad'],
                    'detalles_cosecha' => [
                        [
                            'cantidad_recolectada' => intval($_POST['cantidad_recolectada_1']),
                            'calidad' => $_POST['calidad_1']
                        ],
                        [
                            'cantidad_recolectada' => intval($_POST['cantidad_recolectada_2']),
                            'calidad' => $_POST['calidad_2']
                        ]
                    ]
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
                        'detalles_cosecha' => [
                            [
                                'cantidad_recolectada' => intval($_POST['cantidad_recolectada_1']),
                                'calidad' => $_POST['calidad_1']
                            ],
                            [
                                'cantidad_recolectada' => intval($_POST['cantidad_recolectada_2']),
                                'calidad' => $_POST['calidad_2']
                            ]
                        ]
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


// Modifica la parte de eliminación de detalles de cosecha
if (isset($_GET['action']) && $_GET['action'] === 'delete_detalle' && isset($_GET['cosecha_id']) && isset($_GET['detalle_index'])) {
    $cosecha_id = $_GET['cosecha_id'];
    $detalle_index = intval($_GET['detalle_index']);  // Convertir el índice a entero

    $success = [];
    $errors = [];

    try {
        $result = $productosCollection->updateOne(
            ['_id' => new ObjectId($cosecha_id)],
            ['$unset' => ['detalles_cosecha.' . $detalle_index => '']]
        );

        if ($result->getModifiedCount() > 0) {
            $success[] = 'Detalle de cosecha eliminado exitosamente.';
        } else {
            $errors[] = 'No se pudo eliminar el detalle de cosecha.';
        }
    } catch (Exception $e) {
        $errors[] = 'Error al eliminar el detalle de cosecha: ' . $e->getMessage();
    }

    // Redirigir a la página de cosechas después de la eliminación
    header("Location: cosechas.php");
    exit();
}



// Manejo de la agregación de detalles de cosecha
if (isset($_POST['action']) && $_POST['action'] === 'add_detalle' && isset($_POST['cosecha_id']) && isset($_POST['cantidad_recolectada']) && isset($_POST['calidad'])) {
    $cosecha_id = $_POST['cosecha_id'];
    $detalle = [
        'cantidad_recolectada' => intval($_POST['cantidad_recolectada']),
        'calidad' => $_POST['calidad']
    ];

    try {
        // Validar el ID de la cosecha
        if (strlen($cosecha_id) === 24 && ctype_xdigit($cosecha_id)) {
            $result = $productosCollection->updateOne(
                ['_id' => new ObjectId($cosecha_id)],
                ['$push' => ['detalles_cosecha' => $detalle]]
            );
            if ($result->getModifiedCount() > 0) {
                $success[] = 'Detalle de cosecha agregado exitosamente.';
            } else {
                $errors[] = 'No se pudo agregar el detalle de cosecha. Verifique que la cosecha exista.';
            }
        } else {
            $errors[] = 'ID de cosecha inválido.';
        }
    } catch (Exception $e) {
        $errors[] = 'Error al agregar el detalle de cosecha: ' . $e->getMessage();
    }

    header('Location: cosechas.php');
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





                    <!-- Content Row -->
<div class="row">
    <div id="messages-container"></div>

    <!-- Cosechas -->
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
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>Empleado Encargado</th>
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
                            <td><?php echo htmlspecialchars($cosecha->nombre_empleado ?? ''); ?></td>
                            <td><?php echo htmlspecialchars(date('Y-m-d', strtotime($cosecha->fecha_cosecha ?? ''))); ?></td>
                            <td>
                                <?php 
                                $totalCantidadRecolectada = 0;
                                if (isset($cosecha->detalles_cosecha)) {
                                    foreach ($cosecha->detalles_cosecha as $detalle) {
                                        $totalCantidadRecolectada += $detalle['cantidad_recolectada'] ?? 0;
                                    }
                                }
                                echo htmlspecialchars($totalCantidadRecolectada);
                                ?>
                            </td>
                            <td>
                                <?php
                                $calidades = [];
                                if (isset($cosecha->detalles_cosecha)) {
                                    foreach ($cosecha->detalles_cosecha as $detalle) {
                                        $calidades[] = htmlspecialchars($detalle['calidad'] ?? 'N/A');
                                    }
                                }
                                echo implode(', ', $calidades);
                                ?>
                            </td>
                            <?php if ($_SESSION['rol'] === 'admin'): ?>
                            <td>
                                <button type="button" class="btn btn-info btn-sm" onclick="showDetallesCosecha(<?php echo htmlspecialchars(json_encode($cosecha->detalles_cosecha), ENT_QUOTES, 'UTF-8'); ?>, '<?php echo htmlspecialchars($cosecha->_id); ?>')">
                                    <i class="fas fa-eye"></i> Ver Detalles
                                </button>
                                <button type="button" class="btn btn-warning btn-sm" title="Editar" onclick="openEditModal('<?php echo $cosecha->_id; ?>')">
                                    <i class="fas fa-pencil-alt"></i>
                                </button>
                                <a href="?action=delete&id=<?php echo htmlspecialchars($cosecha->_id); ?>" class="btn btn-danger btn-sm" onclick="return confirm('¿Estás seguro de que deseas eliminar esta cosecha?');" title="Eliminar">
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



<!-- Modal Agregar Cosecha (solo para admin) -->
<?php if ($_SESSION['rol'] === 'admin'): ?>
<!-- Modal para agregar detalles de cosecha -->
<div class="modal fade" id="agregarDetalleCosechaModal" tabindex="-1" role="dialog" aria-labelledby="agregarDetalleCosechaModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <form action="productos.php" method="POST" id="agregarDetalleCosechaForm" onsubmit="return validarFormularioDetalleCosecha()">
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
    let detalleCount = 0;

    function agregarDetalleCosecha() {
        detalleCount++;
        const detallesContainer = document.getElementById('detalles_cosecha_container');
        const detalleHtml = `
            <div class="detalle-cosecha" id="detalle_${detalleCount}">
                <h6>Detalle ${detalleCount}</h6>
                <div class="form-group">
                    <label for="cantidad_recolectada_${detalleCount}">Cantidad Recolectada</label>
                    <input type="number" class="form-control" id="cantidad_recolectada_${detalleCount}" name="detalles_cosecha[${detalleCount}][cantidad_recolectada]" required>
                </div>
                <div class="form-group">
                    <label for="calidad_${detalleCount}">Calidad</label>
                    <select class="form-control" id="calidad_${detalleCount}" name="detalles_cosecha[${detalleCount}][calidad]" required>
                        <option value="alta">Alta</option>
                        <option value="media">Media</option>
                        <option value="baja">Baja</option>
                    </select>
                </div>
                <button type="button" class="btn btn-danger" onclick="eliminarDetalleCosecha(${detalleCount})">Eliminar Detalle</button>
            </div>`;
        detallesContainer.insertAdjacentHTML('beforeend', detalleHtml);
    }

    function eliminarDetalleCosecha(detalleId) {
        const detalleElement = document.getElementById(`detalle_${detalleId}`);
        if (detalleElement) {
            detalleElement.remove();
        }
    }

    function validarFormularioDetalleCosecha() {
        const cantidad = document.getElementById('cantidad').value;
        const unidad = document.getElementById('unidad').value;
        const fechaCosecha = document.getElementById('fecha_cosecha').value;

        if (!cantidad || !unidad || !fechaCosecha) {
            alert('Por favor, complete todos los campos requeridos.');
            return false;
        }

        return true;
    }

    // Función para abrir el modal de agregar detalle de cosecha
    function openAddDetalleCosechaModal(siembraId) {
        document.getElementById('siembra_id').value = siembraId;
        $('#agregarDetalleCosechaModal').modal('show');
    }
</script>
















<script>
    // Función para mostrar el modal de agregar variedad
    function showVariedades(variedades, productId) {
        document.getElementById('product_id').value = productId;

        // Mostrar variedades en el modal
        let variedadesHtml = '';
        variedades.forEach(variedad => {
            variedadesHtml += `<tr>
                <td>${variedad.nombre_variedad}</td>
                <td>${variedad.caracteristicas}</td>
                <?php if ($_SESSION['rol'] === 'admin'): ?>
                <td>
                    <a href="?action=delete_variedad&product_id=${productId}&variedad_nombre=${encodeURIComponent(variedad.nombre_variedad)}" class="btn btn-danger btn-sm" onclick="return confirm('¿Estás seguro de que deseas eliminar esta variedad?');">
                        <i class="fas fa-trash"></i> Eliminar
                    </a>
                </td>
                <?php endif; ?>
            </tr>`;
        });

        document.getElementById('variedades_table_body').innerHTML = variedadesHtml;
        $('#verVariedadesModal').modal('show');
    }

    // Función para abrir el modal de agregar variedad
    function openAddVariedadModal(productId) {
        document.getElementById('product_id').value = productId;
        $('#agregarVariedadModal').modal('show');
    }
</script>

                    
<script>
$(document).ready(function() {
    // Configura el modal de agregar variedad con el ID del producto
    $('#agregarVariedadModal').on('show.bs.modal', function(event) {
        var button = $(event.relatedTarget); // Botón que abrió el modal
        var productId = button.data('product-id'); // Extrae el ID del producto
        var modal = $(this);
        modal.find('#product_id').val(productId);
    });

    // Maneja el envío del formulario de agregar variedad
    $('#agregarVariedadForm').on('submit', function(e) {
        e.preventDefault(); // Evita el envío normal del formulario
        var formData = $(this).serialize(); // Serializa los datos del formulario

        $.ajax({
            url: 'productos.php',
            type: 'POST',
            data: formData + '&action=add_variedad', // Agrega la acción al formulario
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    alert(response.success[0]);
                    $('#agregarVariedadModal').modal('hide');
                    // Recargar la tabla de variedades
                    showVariedades(response.variedades, $('#product_id').val());
                } else if (response.errors) {
                    alert(response.errors[0]);
                }
            },
            error: function() {
                alert('Error al agregar la variedad.');
            }
        });
    });
});

    function eliminarVariedad(productoId, nombreVariedad) {
        if (confirm('¿Estás seguro de que deseas eliminar esta variedad?')) {
            fetch(`productos.php?action=delete_variedad&product_id=${productoId}&variedad_nombre=${encodeURIComponent(nombreVariedad)}`, {
                method: 'GET'
            })
            .then(response => response.json())
            .then(data => {
                let messageHTML = '';
                // Limpiar el contenedor de mensajes antes de agregar nuevos
                const messagesContainer = document.getElementById('messages-container');
                messagesContainer.innerHTML = '';

                if (data.success && data.success.length > 0) {
                    messageHTML += '<div class="alert alert-success" role="alert">';
                    data.success.forEach(message => {
                        messageHTML += `${message}<br>`;
                    });
                    messageHTML += '</div>';
                }
                if (data.errors && data.errors.length > 0) {
                    messageHTML += '<div class="alert alert-danger" role="alert">';
                    data.errors.forEach(message => {
                        messageHTML += `${message}<br>`;
                    });
                    messageHTML += '</div>';
                }
                
                // Insertar los mensajes en el DOM solo si hay mensajes que mostrar
                if (messageHTML !== '') {
                    messagesContainer.innerHTML = messageHTML;
                    // Hacer scroll hacia los mensajes
                    messagesContainer.scrollIntoView({ behavior: "smooth" });
                }
                
                // Actualizar la tabla de variedades en lugar de recargar la página
                if (data.success && data.success.length > 0) {
                    actualizarTablaVariedades(); // Asegúrate de definir esta función para actualizar la tabla de variedades
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error al eliminar variedad');
            });
        }
    }


</script>


                    <script>
function validarFormularioVariedad() {
    var nombreVariedad = document.getElementById('variedad_nombre').value;
    var caracteristicas = document.getElementById('caracteristicas').value;
    var regex = /\d/; // Expresión regular para detectar números

    if (regex.test(nombreVariedad)) {
        alert('El campo "Nombre de la Variedad" no debe contener números.');
        return false;
    }

    if (caracteristicas && regex.test(caracteristicas)) {
        alert('El campo "Características" no debe contener números.');
        return false;
    }

    return true; // Si todas las validaciones son correctas
}
</script>

<script>
function openEditModal(id) {
    console.log("ID recibido:", id); // Para depuración
    fetch('cosechas.php?id=' + id)
        .then(response => response.json())
        .then(data => {
            if (data.error) {
                alert(data.error);
            } else {
                console.log("Datos recibidos:", data); // Para depuración
                document.getElementById('edit_id').value = id; // Usa el ID original
                document.getElementById('edit_nombre').value = data.nombre;
                document.getElementById('edit_descripcion').value = data.descripcion;
                document.getElementById('edit_fecha').value = data.fecha;
                document.getElementById('edit_cantidad_recolectada').value = data.cantidad_recolectada;
                document.getElementById('edit_calidad').value = data.calidad;

                $('#editarCosechaModal').modal('show');
            }
        })
        .catch(error => {
            console.error('Error:', error); // Para depuración
            alert('Error al obtener los datos de la cosecha.');
        });
}
</script>

<script>
function validarFormulario() {
    var nombre = document.getElementById('edit_nombre').value;
    var descripcion = document.getElementById('edit_descripcion').value;
    var regex = /\d/; // Expresión regular para detectar números

    if (regex.test(nombre)) {
        alert('El campo "Nombre" no debe contener números.');
        return false;
    }

    if (regex.test(descripcion)) {
        alert('El campo "Descripción" no debe contener números.');
        return false;
    }

    return true; // Si todas las validaciones son correctas
}
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
