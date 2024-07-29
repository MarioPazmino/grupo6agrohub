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

// Conexión a MongoDB con la URL proporcionada
$mongoUri = "mongodb://mario1010:marito10@testmongo1.cluster-c9ccw6ywgi5c.us-east-1.docdb.amazonaws.com:27017/?tls=true&tlsCAFile=global-bundle.pem&retryWrites=false";
$mongoClient = new Client($mongoUri);
$collection = $mongoClient->grupo6_agrohub->usuarios;

$errors = [];
$success = [];

// Obtener lista de usuarios
$usuarios = $collection->find()->toArray();

// Agregar nuevo usuario
if (isset($_POST['add'])) {
    $nombre = $_POST['nombre'];
    $apellido = $_POST['apellido'];
    $email = $_POST['email'];
    $telefono = $_POST['telefono'];
    $cedula = $_POST['cedula'];
    $rol = $_POST['rol'];
    $fecha_contratacion = $_POST['fecha_contratacion'];
    $tareas_asignadas = []; // Puedes agregar tareas si es necesario
    $password = $_POST['password'];
    $nombre_usuario = $_POST['nombre_usuario'];

    // Validar datos
    if (empty($nombre) || !preg_match("/^[a-zA-ZáéíóúÁÉÍÓÚñÑ ]*$/", $nombre)) {
        $errors[] = "El nombre es inválido.";
    }
    if (empty($apellido) || !preg_match("/^[a-zA-ZáéíóúÁÉÍÓÚñÑ ]*$/", $apellido)) {
        $errors[] = "El apellido es inválido.";
    }
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "El email es inválido.";
    }
    if (empty($telefono) || !preg_match("/^\d{10}$/", $telefono)) {
        $errors[] = "El teléfono debe tener exactamente 10 dígitos.";
    }
    if (empty($cedula) || !preg_match("/^\d{10}$/", $cedula)) {
        $errors[] = "La cédula debe tener exactamente 10 dígitos.";
    }
    if (empty($password) || strlen($password) < 6) {
        $errors[] = "La contraseña es requerida y debe tener al menos 6 caracteres.";
    }
    if (empty($nombre_usuario)) {
        $errors[] = "El nombre de usuario es requerido.";
    }

    // Validar fecha de contratación
    $fecha = DateTime::createFromFormat('Y-m-d', $fecha_contratacion);
    if (!$fecha || $fecha->format('Y-m-d') !== $fecha_contratacion) {
        $errors[] = "La fecha de contratación es inválida.";
    } else {
        $fecha_contratacion = new MongoDB\BSON\UTCDateTime($fecha->getTimestamp() * 1000);
    }

    // Insertar en la base de datos si no hay errores
    if (empty($errors)) {
        // Verificar si el nombre de usuario ya existe
        $usuarioExistente = $collection->findOne(['nombre_usuario' => $nombre_usuario]);
        if ($usuarioExistente) {
            $errors[] = "El nombre de usuario ya existe.";
        } else {
            $result = $collection->insertOne([
                "nombre" => $nombre,
                "apellido" => $apellido,
                "email" => $email,
                "telefono" => $telefono,
                "cedula" => $cedula,
                "rol" => $rol,
                "fecha_contratacion" => $fecha_contratacion,
                "tareas_asignadas" => $tareas_asignadas,
                "password" => password_hash($password, PASSWORD_DEFAULT),
                "nombre_usuario" => $nombre_usuario
            ]);

            if ($result->getInsertedCount() > 0) {
                $success[] = "Usuario agregado exitosamente.";
            } else {
                $errors[] = "Error al agregar usuario.";
            }
        }
    }
}

// Modificar usuario
if (isset($_POST['update'])) {
    $id = new MongoDB\BSON\ObjectId($_POST['id']);
    $nombre = $_POST['nombre'];
    $apellido = $_POST['apellido'];
    $email = $_POST['email'];
    $telefono = $_POST['telefono'];
    $cedula = $_POST['cedula'];
    $rol = $_POST['rol'];
    $fecha_contratacion = $_POST['fecha_contratacion'];
    $tareas_asignadas = []; // Puedes actualizar tareas si es necesario
    $password = $_POST['password'];
    $nombre_usuario = $_POST['nombre_usuario'];

    // Validar datos
    $errors = [];
    if (empty($nombre) || !preg_match("/^[a-zA-ZáéíóúÁÉÍÓÚñÑ ]*$/", $nombre)) {
        $errors[] = "El nombre es inválido.";
    }
    if (empty($apellido) || !preg_match("/^[a-zA-ZáéíóúÁÉÍÓÚñÑ ]*$/", $apellido)) {
        $errors[] = "El apellido es inválido.";
    }
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "El email es inválido.";
    }
    if (empty($telefono) || !preg_match("/^\d{10}$/", $telefono)) {
        $errors[] = "El teléfono debe tener exactamente 10 dígitos.";
    }
    if (empty($cedula) || !preg_match("/^\d{10}$/", $cedula)) {
        $errors[] = "La cédula debe tener exactamente 10 dígitos.";
    }
    if (empty($password) || strlen($password) < 6) {
        $errors[] = "La contraseña es requerida y debe tener al menos 6 caracteres.";
    }
    if (empty($nombre_usuario)) {
        $errors[] = "El nombre de usuario es requerido.";
    }

    // Validar fecha de contratación
    $fecha = DateTime::createFromFormat('Y-m-d', $fecha_contratacion);
    if (!$fecha || $fecha->format('Y-m-d') !== $fecha_contratacion) {
        $errors[] = "La fecha de contratación es inválida.";
    } else {
        $fecha_contratacion = new MongoDB\BSON\UTCDateTime($fecha->getTimestamp() * 1000);
    }

    if (empty($errors)) {
        $updateData = [
            "nombre" => $nombre,
            "apellido" => $apellido,
            "email" => $email,
            "telefono" => $telefono,
            "cedula" => $cedula,
            "rol" => $rol,
            "fecha_contratacion" => $fecha_contratacion,
            "tareas_asignadas" => $tareas_asignadas,
            "nombre_usuario" => $nombre_usuario
        ];

        if (!empty($password)) {
            $updateData["password"] = password_hash($password, PASSWORD_DEFAULT);
        }

        try {
            $result = $collection->updateOne(
                ['_id' => $id],
                ['$set' => $updateData]
            );

            if ($result->getModifiedCount() > 0) {
                $success[] = "Usuario actualizado exitosamente.";
            } else {
                $errors[] = "Error al actualizar usuario o no se realizaron cambios.";
            }
        } catch (Exception $e) {
            $errors[] = "Error al actualizar usuario: " . $e->getMessage();
        }
    } else {
        // Mostrar errores si los hay
        foreach ($errors as $error) {
            echo "<div class='alert alert-danger'>$error</div>";
        }
    }
}

// Eliminar usuario
if (isset($_POST['delete'])) {
    $id = new MongoDB\BSON\ObjectId($_POST['id']);

    // Eliminar de la base de datos
    $result = $collection->deleteOne(['_id' => $id]);

    if ($result->getDeletedCount() > 0) {
        $success[] = "Usuario eliminado exitosamente.";
    } else {
        $errors[] = "Error al eliminar usuario.";
    }
}

// Mostrar mensajes de éxito
foreach ($success as $message) {
    echo "<div class='alert alert-success'>$message</div>";
}

// Mostrar mensajes de error
foreach ($errors as $message) {
    echo "<div class='alert alert-danger'>$message</div>";
}


// Asignar tarea
// Asignar tarea
if (isset($_POST['assign_task'])) {
    $user_id = new MongoDB\BSON\ObjectId($_POST['user_id']);
    $tarea_descripcion = $_POST['tarea_descripcion'];
    $tarea_estado = $_POST['tarea_estado'];

    $nueva_tarea = [
        "tarea_id" => new MongoDB\BSON\ObjectId(),
        "descripcion" => $tarea_descripcion,
        "estado" => $tarea_estado,
        // Eliminamos la línea de fecha_asignacion si no existe
    ];

    try {
        $result = $collection->updateOne(
            ['_id' => $user_id],
            ['$push' => ['tareas_asignadas' => $nueva_tarea]]
        );

        if ($result->getModifiedCount() > 0) {
            $success[] = "Tarea asignada exitosamente.";
        } else {
            $errors[] = "Error al asignar la tarea. No se modificó ningún documento.";
        }
    } catch (Exception $e) {
        $errors[] = "Error al asignar la tarea: " . $e->getMessage();
    }
}

// Asegúrate de que $usuarios se actualice después de asignar una tarea
$usuarios = $collection->find()->toArray();




// Inicializar un array para todas las tareas
$tareas = [];

// Iterar sobre cada usuario y sus tareas
foreach ($usuarios as $usuario) {
    if (!empty($usuario['tareas_asignadas'])) {
        foreach ($usuario['tareas_asignadas'] as $tarea) {
            // Añadir detalles del empleado a la tarea
            $tareas[] = [
                'empleado_nombre' => $usuario['nombre'] . ' ' . $usuario['apellido'],
                'descripcion' => $tarea['descripcion'],
                'estado' => $tarea['estado'],
                'tarea_id' => (string)$tarea['tarea_id'] // Convertir ObjectId a string para manejarlo en PHP
            ];
        }
    }
}






if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_task'])) {
    $tarea_id = $_POST['tarea_id'];
    
   
    
    // Eliminar la tarea
    $result = $collection->updateMany(
        [],
        ['$pull' => ['tareas_asignadas' => ['tarea_id' => new MongoDB\BSON\ObjectId($tarea_id)]]]
    );
    
    // Luego, redirige o actualiza la página
    header('Location: ' . $_SERVER['PHP_SELF']);
    exit;
}










// Contar el número total de empleados
$total_empleados = $collection->countDocuments(['rol' => 'empleado']);

// Contar el número de tareas pendientes, en proceso y completadas
$total_tareas_pendientes = $collection->countDocuments([
    'tareas_asignadas.estado' => 'pendiente'
]);

$total_tareas_proceso = $collection->countDocuments([
    'tareas_asignadas.estado' => 'en_proceso'
]);

$total_tareas_completadas = $collection->countDocuments([
    'tareas_asignadas.estado' => 'completada'
]);

// Pasa estos valores a tu vista


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
            <a class="sidebar-brand d-flex align-items-center justify-content-center" href="user.php">
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
    <h1 class="h3 mb-4 text-gray-800">Perfil Administrador</h1>

    <?php if ($_SESSION['rol'] === 'admin'): ?>
        <!-- Content Row -->
        <div class="row">

            <!-- Total Empleados Card -->
            <div class="col-xl-3 col-md-6 mb-4">
                <div class="card border-left-primary shadow h-100 py-2">
                    <div class="card-body">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-2">
                                <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                    Total de Empleados</div>
                                <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $total_empleados; ?></div>
                            </div>
                            <div class="col-auto">
                                <i class="fas fa-users fa-2x text-gray-300"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tareas Pendientes Card -->
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

            <!-- Tareas En Proceso Card -->
            <div class="col-xl-3 col-md-6 mb-4">
                <div class="card border-left-info shadow h-100 py-2">
                    <div class="card-body">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-2">
                                <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                                    Tareas En Proceso</div>
                                <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $total_tareas_proceso; ?></div>
                            </div>
                            <div class="col-auto">
                                <i class="fas fa-spinner fa-2x text-gray-300"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tareas Completadas Card -->
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
                                <i class="fas fa-check fa-2x text-gray-300"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    <?php else: ?>
        <!-- Perfil del Usuario (Visible para todos los roles, excepto admin) -->
        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">Datos del Perfil</h6>
            </div>
            <!-- Card Body - User Info -->
            <div class="card-body">
                <div class="row">
                    <div class="col-md-4 text-center">
                        <img class="img-fluid rounded-circle" style="width: 150px;" src="assets/images/undraw_profile.svg" alt="Profile Image">
                    </div>
                    <div class="col-md-8">
                        <form method="post" action="">
                            <?php
                            // Mostrar errores si existen
                            if (!empty($errors)) {
                                echo '<div class="alert alert-danger">';
                                echo '<ul>';
                                foreach ($errors as $error) {
                                    echo "<li>$error</li>";
                                }
                                echo '</ul>';
                                echo '</div>';
                            }
                            ?>
                            <div class="form-group">
                                <label for="nombre_usuario">Nombre de Usuario:</label>
                                <input type="text" class="form-control" id="nombre_usuario" name="nombre_usuario" value="<?php echo htmlspecialchars($_SESSION['nombre_usuario']); ?>" readonly>
                            </div>
                            <div class="form-group">
                                <label for="nombre">Nombre:</label>
                                <input type="text" class="form-control" id="nombre" name="nombre" value="<?php echo htmlspecialchars($usuario['nombre']); ?>">
                            </div>
                            <div class="form-group">
                                <label for="apellido">Apellido:</label>
                                <input type="text" class="form-control" id="apellido" name="apellido" value="<?php echo htmlspecialchars($usuario['apellido']); ?>">
                            </div>
                            <div class="form-group">
                                <label for="email">Email:</label>
                                <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($usuario['email']); ?>">
                            </div>
                            <div class="form-group">
                                <label for="telefono">Teléfono:</label>
                                <input type="text" class="form-control" id="telefono" name="telefono" value="<?php echo htmlspecialchars($usuario['telefono']); ?>">
                            </div>
                            <div class="form-group">
                                <label for="cedula">Cédula:</label>
                                <input type="text" class="form-control" id="cedula" name="cedula" value="<?php echo htmlspecialchars($usuario['cedula']); ?>">
                            </div>
                            <button type="submit" class="btn btn-primary">Actualizar Perfil</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>







<!-- Listado de Usuarios -->
<div class="card shadow mb-4">
    <div class="card-header py-3">
        <h6 class="m-0 font-weight-bold text-primary">Lista de Usuarios</h6>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th>Nombre</th>
                        <th>Apellido</th>
                        <th>Email</th>
                        <th>Teléfono</th>
                        <th>Cédula</th>
                        <th>Rol</th>
                        <th>Fecha de Contratación</th>
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
                            <td><?php echo htmlspecialchars($usuario['fecha_contratacion']->toDateTime()->format('Y-m-d')); ?></td>
                            <td>
                                <!-- Botones para modificar, eliminar y asignar tareas -->
                                <div class="d-flex">
                                    <form method="post" action="" class="mr-2">
                                        <input type="hidden" name="id" value="<?php echo $usuario['_id']; ?>">
                                        <button type="submit" name="delete" class="btn btn-danger btn-sm" title="Eliminar">
                                            <i class="fas fa-trash-alt"></i>
                                        </button>
                                    </form>
                                    <button class="btn btn-primary btn-sm mr-2" data-toggle="modal" data-target="#editModal<?php echo $usuario['_id']; ?>" title="Modificar">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <?php if ($usuario['rol'] === 'empleado'): ?>
                                        <button class="btn btn-success btn-sm" data-toggle="modal" data-target="#taskModal<?php echo $usuario['_id']; ?>" title="Asignar Tarea">
                                            <i class="fas fa-tasks"></i>
                                        </button>
                                    <?php endif; ?>
                                </div>

                                <!-- Modal para modificar usuario -->
                                <div class="modal fade" id="editModal<?php echo $usuario['_id']; ?>" tabindex="-1" role="dialog" aria-labelledby="editModalLabel" aria-hidden="true">
                                    <div class="modal-dialog" role="document">
                                        <div class="modal-content">
                                            <div class="modal-header">
                                                <h5 class="modal-title" id="editModalLabel">Modificar Usuario</h5>
                                                <button class="close" type="button" data-dismiss="modal" aria-label="Close">
                                                    <span aria-hidden="true">×</span>
                                                </button>
                                            </div>
                                            <form method="post" action="" onsubmit="return validateForm(this);">
                                                <div class="modal-body">
                                                    <input type="hidden" name="id" value="<?php echo $usuario['_id']; ?>">
                                                    <!-- Formulario para editar usuario -->
                                                    <div class="form-group">
                                                        <label for="nombre">Nombre:</label>
                                                        <input type="text" class="form-control" id="nombre" name="nombre" value="<?php echo htmlspecialchars($usuario['nombre']); ?>" required>
                                                    </div>
                                                    <div class="form-group">
                                                        <label for="apellido">Apellido:</label>
                                                        <input type="text" class="form-control" id="apellido" name="apellido" value="<?php echo htmlspecialchars($usuario['apellido']); ?>" required>
                                                    </div>
                                                    <div class="form-group">
                                                        <label for="email">Email:</label>
                                                        <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($usuario['email']); ?>" required>
                                                    </div>
                                                    <div class="form-group">
                                                        <label for="telefono">Teléfono:</label>
                                                        <input type="text" class="form-control" id="telefono" name="telefono" value="<?php echo htmlspecialchars($usuario['telefono']); ?>" pattern="\d{10}" title="El teléfono debe tener exactamente 10 dígitos." required>
                                                    </div>
                                                    <div class="form-group">
                                                        <label for="cedula">Cédula:</label>
                                                        <input type="text" class="form-control" id="cedula" name="cedula" value="<?php echo htmlspecialchars($usuario['cedula']); ?>" pattern="\d{10}" title="La cédula debe tener exactamente 10 dígitos." required>
                                                    </div>
                                                    <div class="form-group">
                                                        <label for="rol">Rol:</label>
                                                        <select class="form-control" id="rol" name="rol" required>
                                                            <option value="empleado" <?php if ($usuario['rol'] == 'empleado') echo 'selected'; ?>>Empleado</option>
                                                            <option value="admin" <?php if ($usuario['rol'] == 'admin') echo 'selected'; ?>>Admin</option>
                                                        </select>
                                                    </div>
                                                    <div class="form-group">
                                                        <label for="fecha_contratacion">Fecha de Contratación:</label>
                                                        <input type="date" class="form-control" id="fecha_contratacion" name="fecha_contratacion" value="<?php echo $usuario['fecha_contratacion']->toDateTime()->format('Y-m-d'); ?>" required>
                                                    </div>
                                                    <div class="form-group">
                                                        <label for="password">Contraseña:</label>
                                                        <input type="password" class="form-control" id="password" name="password" value="<?php echo htmlspecialchars($usuario['password']); ?>">
                                                    </div>
                                                    <div class="form-group">
                                                        <label for="nombre_usuario">Nombre de Usuario:</label>
                                                        <input type="text" class="form-control" id="nombre_usuario" name="nombre_usuario" value="<?php echo htmlspecialchars($usuario['nombre_usuario']); ?>" required>
                                                    </div>
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                                                    <button type="submit" name="update" class="btn btn-primary">Actualizar</button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>

                                <?php if ($usuario['rol'] === 'empleado'): ?>
                                    <!-- Modal para asignar tarea -->
                                    <div class="modal fade" id="taskModal<?php echo $usuario['_id']; ?>" tabindex="-1" role="dialog" aria-labelledby="taskModalLabel" aria-hidden="true">
                                        <div class="modal-dialog" role="document">
                                            <div class="modal-content">
                                                <div class="modal-header">
                                                    <h5 class="modal-title" id="taskModalLabel">Asignar Tarea a <?php echo htmlspecialchars($usuario['nombre'] . ' ' . $usuario['apellido']); ?></h5>
                                                    <button class="close" type="button" data-dismiss="modal" aria-label="Close">
                                                        <span aria-hidden="true">×</span>
                                                    </button>
                                                </div>
                                                <form method="post" action="">
                                                    <div class="modal-body">
                                                        <input type="hidden" name="user_id" value="<?php echo $usuario['_id']; ?>">
                                                        <div class="form-group">
                                                            <label for="tarea_descripcion">Descripción de la Tarea:</label>
                                                            <textarea class="form-control" id="tarea_descripcion" name="tarea_descripcion" required></textarea>
                                                        </div>
                                                        <div class="form-group">
                                                            <label for="tarea_estado">Estado:</label>
                                                            <select class="form-control" id="tarea_estado" name="tarea_estado">
                                                                <option value="pendiente">Pendiente</option>
                                                                <option value="en_proceso">En Proceso</option>
                                                                <option value="completada">Completada</option>
                                                            </select>
                                                        </div>
                                                    </div>
                                                    <div class="modal-footer">
                                                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                                                        <button type="submit" name="assign_task" class="btn btn-success">Asignar Tarea</button>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
function validateForm(form) {
    const fechaContratacion = new Date(form.fecha_contratacion.value);
    const hoy = new Date();
    
    // Verificar que la fecha de contratación no sea futura
    if (fechaContratacion > hoy) {
        alert("La fecha de contratación no puede ser futura.");
        return false;
    }
    
    return true;
}
</script>




<!-- Formulario para agregar nuevo usuario -->
<div class="card shadow mb-4">
    <div class="card-header py-3">
        <h6 class="m-0 font-weight-bold text-primary">Agregar Usuario</h6>
    </div>
    <div class="card-body">
        <form method="post" action="" onsubmit="return validateForm(this);">
            <div class="row">
                <!-- Columna 1 -->
                <div class="col-md-4">
                    <div class="form-group">
                        <label for="nombre">Nombre:</label>
                        <input type="text" class="form-control" id="nombre" name="nombre" required>
                    </div>
                    <div class="form-group">
                        <label for="apellido">Apellido:</label>
                        <input type="text" class="form-control" id="apellido" name="apellido" required>
                    </div>
                    <div class="form-group">
                        <label for="email">Email:</label>
                        <input type="email" class="form-control" id="email" name="email" required>
                    </div>
                </div>
                <!-- Columna 2 -->
                <div class="col-md-4">
                    <div class="form-group">
                        <label for="telefono">Teléfono:</label>
                        <input type="text" class="form-control" id="telefono" name="telefono" pattern="\d{10}" title="El teléfono debe tener exactamente 10 dígitos." required>
                    </div>
                    <div class="form-group">
                        <label for="cedula">Cédula:</label>
                        <input type="text" class="form-control" id="cedula" name="cedula" pattern="\d{10}" title="La cédula debe tener exactamente 10 dígitos." required>
                    </div>
                    <div class="form-group">
                        <label for="rol">Rol:</label>
                        <select class="form-control" id="rol" name="rol" required>
                            <option value="empleado">Empleado</option>
                            <option value="admin">Admin</option>
                        </select>
                    </div>
                </div>
                <!-- Columna 3 -->
                <div class="col-md-4">
                    <div class="form-group">
                        <label for="fecha_contratacion">Fecha de Contratación:</label>
                        <input type="date" class="form-control" id="fecha_contratacion" name="fecha_contratacion" required>
                    </div>
                    <div class="form-group">
                        <label for="password">Contraseña:</label>
                        <input type="password" class="form-control" id="password" name="password" required>
                    </div>
                    <div class="form-group">
                        <label for="nombre_usuario">Nombre de Usuario:</label>
                        <input type="text" class="form-control" id="nombre_usuario" name="nombre_usuario" required>
                    </div>
                </div>
            </div>
            <button type="submit" name="add" class="btn btn-primary">Agregar</button>
        </form>
    </div>
</div>

<script>
function validateForm(form) {
    const fechaContratacion = new Date(form.fecha_contratacion.value);
    const hoy = new Date();
    
    // Verificar que la fecha de contratación no sea futura
    if (fechaContratacion > hoy) {
        alert("La fecha de contratación no puede ser futura.");
        return false;
    }
    
    return true;
}
</script>



<!-- Listado de Tareas Asignadas -->
<div class="card shadow mb-4">
    <div class="card-header py-3">
        <h6 class="m-0 font-weight-bold text-primary">Tareas Asignadas</h6>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-bordered table-hover">
                <thead class="thead-dark">
                    <tr>
                        <th>Empleado</th>
                        <th>Descripción</th>
                        <th>Estado</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($tareas)): ?>
                        <?php foreach ($tareas as $tarea): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($tarea['empleado_nombre']); ?></td>
                                <td><?php echo htmlspecialchars($tarea['descripcion']); ?></td>
                                <td>
                                    <?php 
                                    $estado = htmlspecialchars($tarea['estado']);
                                    // Aplicar clases de Bootstrap según el estado
                                    if ($estado === 'pendiente') {
                                        echo '<span class="badge badge-warning">Pendiente</span>';
                                    } elseif ($estado === 'en_proceso') {
                                        echo '<span class="badge badge-info">En Proceso</span>';
                                    } elseif ($estado === 'completada') {
                                        echo '<span class="badge badge-success">Completada</span>';
                                    }
                                    ?>
                                </td>
                                <td>
                                    <!-- Botón de eliminar -->
                                    <form method="post" action="" class="d-inline">
                                        <input type="hidden" name="tarea_id" value="<?php echo htmlspecialchars($tarea['tarea_id']); ?>">
                                        <button type="submit" name="delete_task" class="btn btn-danger btn-sm" title="Eliminar">
                                            <i class="fas fa-trash-alt"></i>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="4" class="text-center text-muted">No hay tareas asignadas.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
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
    <script>
        function validateForm() {
            let errors = [];
            let nombre = document.getElementById('nombre').value;
            let apellido = document.getElementById('apellido').value;
            let email = document.getElementById('email').value;
            let telefono = document.getElementById('telefono').value;
            let cedula = document.getElementById('cedula').value;

            // Validar nombre
            if (nombre === "") {
                errors.push("El nombre es requerido.");
            } else if (!/^[a-zA-ZáéíóúÁÉÍÓÚñÑ ]*$/.test(nombre)) {
                errors.push("Solo se permiten letras, espacios y tildes en el nombre.");
            }

            // Validar apellido
            if (apellido === "") {
                errors.push("El apellido es requerido.");
            } else if (!/^[a-zA-ZáéíóúÁÉÍÓÚñÑ ]*$/.test(apellido)) {
                errors.push("Solo se permiten letras, espacios y tildes en el apellido.");
            }

            // Validar email
            if (email === "") {
                errors.push("El email es requerido.");
            } else if (!/^\S+@\S+\.\S+$/.test(email)) {
                errors.push("Formato de email inválido.");
            }

            // Validar teléfono
            if (telefono === "") {
                errors.push("El teléfono es requerido.");
            } else if (!/^0[0-9]{9}$/.test(telefono)) {
                errors.push("El teléfono debe tener 10 números y comenzar con 0.");
            }

            // Validar cédula
            if (cedula === "") {
                errors.push("La cédula es requerida.");
            } else if (!/^[0-9]{10}$/.test(cedula)) {
                errors.push("La cédula debe tener 10 números.");
            }

            if (errors.length > 0) {
                alert(errors.join("\n"));
                return false;
            }

            return true;
        }
    </script>
</body>

</html> 
