<?php
session_start();

// Verificar si el usuario está autenticado y tiene el rol de 'empleado'
if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'empleado') {
    header("Location: index.php");
    exit();
}

require __DIR__ . '/vendor/autoload.php';

use MongoDB\Client;
use MongoDB\Exception\Exception;

// Conexión a MongoDB con la URL actualizada
$mongoUri = "mongodb://mario1010:marito10@testmongo1.cluster-c9ccw6ywgi5c.us-east-1.docdb.amazonaws.com:27017/?tls=true&tlsCAFile=global-bundle.pem&retryWrites=false";
$mongoClient = new Client($mongoUri);
$db = $mongoClient->selectDatabase("grupo6_agrohub");
$collection = $db->usuarios; // Nombre de la colección en MongoDB

$usuario_id = new MongoDB\BSON\ObjectId($_SESSION['usuario_id']);
$usuario = $collection->findOne(['_id' => $usuario_id]);

// Inicializar el array de errores
$errors = array();

// Procesar el formulario de actualización
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Obtener los datos del formulario
    $nombre = $_POST['nombre'];
    $apellido = $_POST['apellido'];
    $email = $_POST['email'];
    $telefono = $_POST['telefono'];
    $cedula = $_POST['cedula']; // Nuevo campo: cedula

    // Validar los campos del formulario
    if (empty($nombre)) {
        $errors['nombre'] = "El nombre es requerido.";
    } elseif (!preg_match("/^[a-zA-ZáéíóúÁÉÍÓÚñÑ ]*$/", $nombre)) {
        $errors['nombre'] = "Solo se permiten letras, espacios y tildes en el nombre.";
    }

    if (empty($apellido)) {
        $errors['apellido'] = "El apellido es requerido.";
    } elseif (!preg_match("/^[a-zA-ZáéíóúÁÉÍÓÚñÑ ]*$/", $apellido)) {
        $errors['apellido'] = "Solo se permiten letras, espacios y tildes en el apellido.";
    }

    if (empty($email)) {
        $errors['email'] = "El email es requerido.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = "Formato de email inválido.";
    }

    if (empty($telefono)) {
        $errors['telefono'] = "El teléfono es requerido.";
    } elseif (!preg_match("/^0[0-9]{9}$/", $telefono)) {
        $errors['telefono'] = "El teléfono debe tener 10 números y comenzar con 0.";
    }

    if (empty($cedula)) {
        $errors['cedula'] = "La cédula es requerida.";
    } elseif (!preg_match("/^[0-9]{10}$/", $cedula)) {
        $errors['cedula'] = "La cédula debe tener 10 números.";
    }

    // Si no hay errores, actualizar los datos en la base de datos
    if (empty($errors)) {
        // Construir el filtro para encontrar el usuario por ID
        $filter = ['_id' => $usuario_id];

        // Construir el objeto de actualización
        $update = [
            '$set' => [
                'nombre' => $nombre,
                'apellido' => $apellido,
                'email' => $email,
                'telefono' => $telefono,
                'cedula' => $cedula, // Agregar cedula al documento
                'fecha_actualizacion' => new MongoDB\BSON\UTCDateTime(), // Fecha de actualización
                'rol' => 'empleado' // Asegúrate de que el rol sea 'empleado'
            ]
        ];

        // Actualizar el documento del usuario en MongoDB
        try {
            $result = $collection->updateOne($filter, $update);

            if ($result->getModifiedCount() > 0) {
                // Éxito: mostrar mensaje y actualizar datos en sesión
                echo "<div class='alert alert-success'>Datos actualizados correctamente.</div>";
                // Actualizar los datos en la sesión también para reflejar los cambios
                $_SESSION['nombre'] = $nombre;
                $_SESSION['apellido'] = $apellido;
                $_SESSION['email'] = $email;
                $_SESSION['telefono'] = $telefono;
                $_SESSION['cedula'] = $cedula; // Actualizar cedula en la sesión

                // Actualizar la variable $usuario con los nuevos datos
                $usuario = $collection->findOne(['_id' => $usuario_id]);
            } else {
                echo "<div class='alert alert-danger'>No se pudo actualizar los datos.</div>";
            }
        } catch (Exception $e) {
            echo "<div class='alert alert-danger'>Error al actualizar los datos: " . htmlspecialchars($e->getMessage()) . "</div>";
        }
    }
}

// Obtener el empleado con las tareas asignadas
$empleado = $collection->findOne(['_id' => $usuario_id]);
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
            <a class="sidebar-brand d-flex align-items-center justify-content-center" href="index.php">
                <div class="sidebar-brand-text mx-3">Dashboard</div>
            </a>

            <hr class="sidebar-divider my-0">
            <li class="nav-item active">
                <a class="nav-link" href="index.php">
                    <i class="fas fa-fw fa-tachometer-alt"></i>
                    <span>Dashboard</span></a>
            </li>

            <hr class="sidebar-divider">

            <div class="sidebar-heading">
                Interface
            </div>

            <li class="nav-item">
                <a class="nav-link" href="profile.php">
                    <i class="fas fa-fw fa-user"></i>
                    <span>Perfil</span>
                </a>
            </li>

            <hr class="sidebar-divider d-none d-md-block">
        </ul>
        <!-- End of Sidebar -->

        <!-- Content Wrapper -->
        <div id="content-wrapper" class="d-flex flex-column">
            <!-- Main Content -->
            <div id="content">
                <!-- Topbar -->
                <nav class="navbar navbar-expand navbar-light bg-white topbar mb-4 static-top shadow">
                    <button id="sidebarToggleTop" class="btn btn-link d-md-none rounded-circle mr-3">
                        <i class="fa fa-bars"></i>
                    </button>

                    <ul class="navbar-nav ml-auto">
                        <!-- Nav Item - User Information -->
                        <li class="nav-item dropdown no-arrow">
                            <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                <span class="mr-2 d-none d-lg-inline text-gray-600 small"><?php echo htmlspecialchars($_SESSION['nombre'] . ' ' . $_SESSION['apellido']); ?></span>
                                <img class="img-profile rounded-circle" src="img/profile.jpg">
                            </a>
                            <!-- Dropdown - User Information -->
                            <div class="dropdown-menu dropdown-menu-right shadow animated--grow-in" aria-labelledby="userDropdown">
                                <a class="dropdown-item" href="profile.php">
                                    <i class="fas fa-user fa-sm fa-fw mr-2 text-gray-400"></i>
                                    Perfil
                                </a>
                                <a class="dropdown-item" href="logout.php">
                                    <i class="fas fa-sign-out-alt fa-sm fa-fw mr-2 text-gray-400"></i>
                                    Cerrar sesión
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
                        <h1 class="h3 mb-0 text-gray-800">Perfil del Empleado</h1>
                    </div>

                    <!-- Update Profile Form -->
                    <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="POST">
                        <div class="form-group row">
                            <label for="nombre" class="col-sm-2 col-form-label">Nombre</label>
                            <div class="col-sm-10">
                                <input type="text" class="form-control" id="nombre" name="nombre" value="<?php echo htmlspecialchars($usuario->nombre); ?>">
                                <?php if (isset($errors['nombre'])): ?>
                                    <div class="text-danger"><?php echo htmlspecialchars($errors['nombre']); ?></div>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="form-group row">
                            <label for="apellido" class="col-sm-2 col-form-label">Apellido</label>
                            <div class="col-sm-10">
                                <input type="text" class="form-control" id="apellido" name="apellido" value="<?php echo htmlspecialchars($usuario->apellido); ?>">
                                <?php if (isset($errors['apellido'])): ?>
                                    <div class="text-danger"><?php echo htmlspecialchars($errors['apellido']); ?></div>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="form-group row">
                            <label for="email" class="col-sm-2 col-form-label">Email</label>
                            <div class="col-sm-10">
                                <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($usuario->email); ?>">
                                <?php if (isset($errors['email'])): ?>
                                    <div class="text-danger"><?php echo htmlspecialchars($errors['email']); ?></div>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="form-group row">
                            <label for="telefono" class="col-sm-2 col-form-label">Teléfono</label>
                            <div class="col-sm-10">
                                <input type="text" class="form-control" id="telefono" name="telefono" value="<?php echo htmlspecialchars($usuario->telefono); ?>">
                                <?php if (isset($errors['telefono'])): ?>
                                    <div class="text-danger"><?php echo htmlspecialchars($errors['telefono']); ?></div>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="form-group row">
                            <label for="cedula" class="col-sm-2 col-form-label">Cédula</label>
                            <div class="col-sm-10">
                                <input type="text" class="form-control" id="cedula" name="cedula" value="<?php echo htmlspecialchars($usuario->cedula); ?>">
                                <?php if (isset($errors['cedula'])): ?>
                                    <div class="text-danger"><?php echo htmlspecialchars($errors['cedula']); ?></div>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="form-group row">
                            <div class="col-sm-10">
                                <button type="submit" class="btn btn-primary">Actualizar Perfil</button>
                            </div>
                        </div>
                    </form>

                    <!-- Tareas Asignadas -->
                    <h3 class="mt-4">Tareas Asignadas</h3>
                    <?php if (!empty($empleado->tareas_asignadas)): ?>
                        <?php foreach ($empleado->tareas_asignadas as $tarea): ?>
                            <div class="task-card card">
                                <div class="card-body">
                                    <p class="card-text"><?php echo htmlspecialchars($tarea->descripcion); ?></p>
                                    <?php if ($tarea->estado === 'pendiente'): ?>
                                        <span class="task-status-pendiente">Pendiente</span>
                                    <?php elseif ($tarea->estado === 'en_progreso'): ?>
                                        <span class="task-status-en-progreso">En Progreso</span>
                                    <?php else: ?>
                                        <span class="task-status-completada">Completada</span>
                                    <?php endif; ?>
                                    <form action="cambiar_estado_tarea.php" method="POST" class="mt-2">
                                        <input type="hidden" name="tarea_id" value="<?php echo htmlspecialchars($tarea->_id); ?>">
                                        <button type="submit" class="btn btn-primary">Cambiar Estado</button>
                                    </form>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p>No tienes tareas asignadas.</p>
                    <?php endif; ?>

                </div>
                <!-- /.container-fluid -->

            </div>
            <!-- End of Main Content -->

            <!-- Footer -->
            <footer class="sticky-footer bg-white">
                <div class="container my-auto">
                    <div class="copyright text-center my-auto">
                        <span>© 2024 Tu Empresa</span>
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

    <!-- Bootstrap core JavaScript-->
    <script src="vendor/jquery/jquery.min.js"></script>
    <script src="vendor/bootstrap/js/bootstrap.bundle.min.js"></script>

    <!-- Core plugin JavaScript-->
    <script src="vendor/jquery-easing/jquery.easing.min.js"></script>

    <!-- Custom scripts for all pages-->
    <script src="js/sb-admin-2.min.js"></script>

</body>
</html>
