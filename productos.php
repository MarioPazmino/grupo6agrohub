<?php
session_start();

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

// Manejo de la eliminación de productos
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $id = $_GET['id'];
    error_log("Intentando eliminar producto con ID: " . $id);
    
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

    // Redireccionar después de procesar la eliminación
    header("Location: productos.php");
    exit();
}

// Manejo de la actualización y agregación de productos
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    error_log('POST data: ' . print_r($_POST, true));

    if (isset($_POST['id'])) {
        // Actualizar producto
        try {
            $variedades = json_decode($_POST['variedades'], true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new Exception('El formato JSON para variedades no es válido.');
            }

            $result = $productosCollection->updateOne(
                ['_id' => new ObjectId($_POST['id'])],
                ['$set' => [
                    'nombre' => $_POST['nombre'],
                    'descripcion' => $_POST['descripcion'],
                    'tipo' => $_POST['tipo'],
                    'precio_unitario' => floatval($_POST['precio_unitario']),
                    'unidad' => $_POST['unidad'],
                    'variedades' => $variedades
                ]]
            );
            if ($result->getModifiedCount() > 0) {
                $success[] = 'Producto actualizado exitosamente.';
            } else {
                $errors[] = 'No se encontró el producto para actualizar o no hubo cambios.';
            }
        } catch (Exception $e) {
            $errors[] = 'Error al actualizar el producto: ' . $e->getMessage();
        }
    } else {
        // Agregar producto
        try {
            $variedades = json_decode($_POST['variedades'], true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new Exception('El formato JSON para variedades no es válido.');
            }

            $result = $productosCollection->insertOne([
                'nombre' => $_POST['nombre'],
                'descripcion' => $_POST['descripcion'],
                'tipo' => $_POST['tipo'],
                'precio_unitario' => floatval($_POST['precio_unitario']),
                'unidad' => $_POST['unidad'],
                'variedades' => $variedades
            ]);
            if ($result->getInsertedCount() > 0) {
                $success[] = 'Producto agregado exitosamente.';
            } else {
                $errors[] = 'Error al agregar el producto.';
            }
        } catch (Exception $e) {
            $errors[] = 'Error al agregar el producto: ' . $e->getMessage();
        }
    }
}

// Obtener productos para mostrar en la tabla
$productos = $productosCollection->find()->toArray();

// Debug: Imprimir IDs de productos
foreach ($productos as $producto) {
    error_log("Producto ID: " . $producto->_id);
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

// Aquí comienza el HTML
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
            <h1 class="h3 mb-0 text-gray-800">Productos</h1>
        </div>

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

        <!-- Content Row -->
        <div class="row">
            <!-- Productos -->
            <div class="col-lg-12">
                <div class="card shadow mb-4">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary">Productos</h6>
                    </div>
                    <div class="card-body">
                        <!-- Botón de agregar producto -->
                        <?php if ($_SESSION['rol'] === 'admin'): ?>
                            <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#agregarProductoModal">
                                Agregar Producto
                            </button>
                        <?php endif; ?>

                        <!-- Tabla de productos -->
                        <div class="table-responsive mt-4">
                            <table class="table table-bordered" id="dataTable" width="100%" cellspacing="0">
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
                                                <?php foreach ($producto->variedades as $variedad): ?>
                                                    <div><?php echo htmlspecialchars($variedad->nombre_variedad) . ': ' . htmlspecialchars($variedad->caracteristicas); ?></div>
                                                <?php endforeach; ?>
                                            </td>
                                            <?php if ($_SESSION['rol'] === 'admin'): ?>
                                                <td>
                                                    <button type="button" class="btn btn-warning btn-sm" data-toggle="modal" data-target="#editarProductoModal"
                                                            data-id="<?php echo $producto->_id; ?>"
                                                            data-nombre="<?php echo htmlspecialchars($producto->nombre); ?>"
                                                            data-descripcion="<?php echo htmlspecialchars($producto->descripcion); ?>"
                                                            data-tipo="<?php echo htmlspecialchars($producto->tipo); ?>"
                                                            data-precio="<?php echo htmlspecialchars($producto->precio_unitario); ?>"
                                                            data-unidad="<?php echo htmlspecialchars($producto->unidad); ?>"
                                                            data-variedades='<?php echo json_encode($producto->variedades); ?>'>
                                                        Editar
                                                    </button>
                                                    <a href="?action=delete&id=<?php echo $producto->_id; ?>" class="btn btn-danger btn-sm" onclick="return confirm('¿Estás seguro de que deseas eliminar este producto?');">
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
    <!-- /.container-fluid -->

    <!-- Modal Agregar Producto -->
    <div class="modal fade" id="agregarProductoModal" tabindex="-1" role="dialog" aria-labelledby="agregarProductoModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="agregarProductoModalLabel">Agregar Producto</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <form action="productos.php" method="POST">
                        <div class="form-group">
                            <label for="nombre">Nombre</label>
                            <input type="text" class="form-control" id="nombre" name="nombre" required>
                        </div>
                        <div class="form-group">
                            <label for="descripcion">Descripción</label>
                            <textarea class="form-control" id="descripcion" name="descripcion"></textarea>
                        </div>
                        <div class="form-group">
                            <label for="tipo">Tipo</label>
                            <input type="text" class="form-control" id="tipo" name="tipo" required>
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
                            <textarea class="form-control" id="variedades" name="variedades"></textarea>
                            <small id="variedadesHelp" class="form-text text-muted">Formato JSON para las variedades.</small>
                        </div>
                        <button type="submit" class="btn btn-primary">Agregar</button>
                    </form>
                </div>
            </div>
        </div>
    </div>



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
                    <form action="productos.php" method="POST">
                        <input type="hidden" id="edit_id" name="id">
                        <div class="form-group">
                            <label for="edit_nombre">Nombre</label>
                            <input type="text" class="form-control" id="edit_nombre" name="nombre" required>
                        </div>
                        <div class="form-group">
                            <label for="edit_descripcion">Descripción</label>
                            <textarea class="form-control" id="edit_descripcion" name="descripcion"></textarea>
                        </div>
                        <div class="form-group">
                            <label for="edit_tipo">Tipo</label>
                            <input type="text" class="form-control" id="edit_tipo" name="tipo" required>
                        </div>
                        <div class="form-group">
                            <label for="edit_precio">Precio Unitario</label>
                            <input type="number" step="0.01" class="form-control" id="edit_precio" name="precio" required>
                        </div>
                        <div class="form-group">
                            <label for="edit_unidad">Unidad</label>
                            <input type="text" class="form-control" id="edit_unidad" name="unidad" required>
                        </div>
                        <div class="form-group">
                            <label for="edit_variedades">Variedades (JSON)</label>
                            <textarea class="form-control" id="edit_variedades" name="variedades"></textarea>
                            <small id="edit_variedadesHelp" class="form-text text-muted">Formato JSON para las variedades.</small>
                        </div>
                        <button type="submit" class="btn btn-primary">Actualizar</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

</div>

<script>
    // Script para llenar el modal de edición con los datos del producto
    $('#editarProductoModal').on('show.bs.modal', function (event) {
        var button = $(event.relatedTarget);
        var id = button.data('id');
        var nombre = button.data('nombre');
        var descripcion = button.data('descripcion');
        var tipo = button.data('tipo');
        var precio = button.data('precio');
        var unidad = button.data('unidad');
        var variedades = button.data('variedades');

        var modal = $(this);
        modal.find('#edit_id').val(id);
        modal.find('#edit_nombre').val(nombre);
        modal.find('#edit_descripcion').val(descripcion);
        modal.find('#edit_tipo').val(tipo);
        modal.find('#edit_precio').val(precio);
        modal.find('#edit_unidad').val(unidad);
        modal.find('#edit_variedades').val(variedades);
    });
</script>


    </body>

</html>
