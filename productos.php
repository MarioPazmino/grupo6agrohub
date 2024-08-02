
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

// Obtener tipos de productos para mostrar en el formulario
$tiposProductosCollection = $mongoClient->grupo6_agrohub->productos;
try {
    $tiposProductosCursor = $tiposProductosCollection->find();
    $tiposProductos = iterator_to_array($tiposProductosCursor);
} catch (Exception $e) {
    $errors[] = 'Error al obtener los tipos de productos: ' . $e->getMessage();
}

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

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_POST['action'])) {
    try {
        // Verificar si variedades está presente y no es null
        $variedades = isset($_POST['variedades']) ? json_decode($_POST['variedades'], true) : [];

        // Verificar si la decodificación fue exitosa
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

// Modifica la parte de eliminación de variedades
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

    echo json_encode(['success' => $success, 'errors' => $errors]);
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
        // Validar el ID del producto
        if (strlen($product_id) === 24 && ctype_xdigit($product_id)) {
            $result = $productosCollection->updateOne(
                ['_id' => new ObjectId($product_id)],
                ['$push' => ['variedades' => $variedad]]
            );
            if ($result->getModifiedCount() > 0) {
                $success[] = 'Variedad agregada exitosamente.';
            } else {
                $errors[] = 'No se pudo agregar la variedad. Verifique que el producto exista.';
            }
        } else {
            $errors[] = 'ID de producto inválido.';
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



// Procesar la acción (agregar, eliminar, etc.)
$response = ['success' => [], 'errors' => []];

if ($action === 'delete_variedad') {
    // Código para eliminar variedad
    if ($eliminadoConExito) {
        $response['success'][] = 'Variedad eliminada exitosamente.';
    } else {
        $response['errors'][] = 'Error al eliminar la variedad.';
    }
}

// Enviar respuesta JSON
header('Content-Type: application/json');
echo json_encode($response);
exit;





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
<div id="messages">
    <!-- Los mensajes de éxito o error se mostrarán aquí -->
</div>

    <!-- Productos -->
    <div class="col-lg-12">
        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">Productos</h6>
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

                <!-- Botón de agregar producto (solo para admin) -->
                <?php if ($_SESSION['rol'] === 'admin'): ?>
                <button type="button" class="btn btn-primary mb-2" data-toggle="modal" data-target="#agregarProductoModal">
                    <i class="fas fa-plus"></i> Agregar Producto
                </button>
                <?php endif; ?>

                                <!-- Tabla de productos -->
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>Nombre</th>
                            <th>Descripción</th>
                            <th>Tipo</th>
                            <th>Precio Unitario</th>
                            <th>Unidad</th>
                            <th>Variedades</th>
                            <?php if ($_SESSION['rol'] === 'admin'): ?>
                            <th>Acciones</th>
                            <?php endif; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($productos as $producto): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($producto->nombre); ?></td>
                            <td><?php echo htmlspecialchars($producto->descripcion); ?></td>
                            <td><?php echo htmlspecialchars($producto->tipo); ?></td>
                            <td><?php echo htmlspecialchars($producto->precio_unitario); ?></td>
                            <td><?php echo htmlspecialchars($producto->unidad); ?></td>
                            <td>
                                <button type="button" class="btn btn-info btn-sm" onclick="showVariedades(<?php echo htmlspecialchars(json_encode($producto->variedades), ENT_QUOTES, 'UTF-8'); ?>, '<?php echo htmlspecialchars($producto->_id); ?>')">
                                    <i class="fas fa-eye"></i> Ver Variedades
                                </button>
                            </td>
                            <?php if ($_SESSION['rol'] === 'admin'): ?>
                            <td>
                                <a href="?action=delete&id=<?php echo htmlspecialchars($producto->_id); ?>" class="btn btn-danger btn-sm" onclick="return confirm('¿Estás seguro de que deseas eliminar este producto?');">
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

<!-- Modal para agregar producto -->
<div class="modal fade" id="agregarProductoModal" tabindex="-1" role="dialog" aria-labelledby="agregarProductoModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <form action="productos.php" method="POST">
                <div class="modal-header">
                    <h5 class="modal-title" id="agregarProductoModalLabel">Agregar/Editar Producto</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <input type="hidden" id="id" name="id">

                    <div class="form-group">
                        <label for="nombre">Nombre</label>
                        <input type="text" class="form-control" id="nombre" name="nombre" required>
                    </div>

                    <div class="form-group">
                        <label for="descripcion">Descripción</label>
                        <textarea class="form-control" id="descripcion" name="descripcion" required></textarea>
                    </div>

                    <div class="form-group">
                        <label for="tipo">Tipo</label>
                        <select class="form-control" id="tipo" name="tipo" required>
                            <?php foreach ($tiposProductos as $tipoProducto): ?>
                            <option value="<?php echo htmlspecialchars($tipoProducto->nombre); ?>">
                                <?php echo htmlspecialchars($tipoProducto->nombre); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="precio_unitario">Precio Unitario</label>
                        <input type="number" step="0.01" class="form-control" id="precio_unitario" name="precio_unitario" required>
                    </div>

                    <div class="form-group">
                        <label for="unidad">Unidad</label>
                        <input type="text" class="form-control" id="unidad" name="unidad" required>
                    </div>

                    <div class="form-group">
                        <label for="variedades">Variedades (JSON)</label>
                        <textarea class="form-control" id="variedades" name="variedades" rows="4"></textarea>
                        <small class="form-text text-muted">Introduce un array JSON de variedades, por ejemplo: [{"nombre_variedad": "Variedad 1", "caracteristicas": "Características 1"}]</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Guardar Producto</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal para agregar variedad -->
<div class="modal fade" id="agregarVariedadModal" tabindex="-1" role="dialog" aria-labelledby="agregarVariedadModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <form action="productos.php" method="POST" id="agregarVariedadForm">
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
                // Llama a una función para recargar los datos de la tabla
                actualizarTablaVariedades(); // Asume que tienes esta función definida
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error al eliminar variedad');
        });
    }
}


    // Configurar el modal de edición
    $('#editarProductoModal').on('show.bs.modal', function (event) {
        const button = $(event.relatedTarget);
        const id = button.data('id');
        const nombre = button.data('nombre');
        const descripcion = button.data('descripcion');
        const tipo = button.data('tipo');
        const precioUnitario = button.data('precio_unitario');
        const unidad = button.data('unidad');

        const modal = $(this);
        modal.find('#edit_id').val(id);
        modal.find('#edit_nombre').val(nombre);
        modal.find('#edit_descripcion').val(descripcion);
        modal.find('#edit_tipo').val(tipo);
        modal.find('#edit_precio_unitario').val(precioUnitario);
        modal.find('#edit_unidad').val(unidad);
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
