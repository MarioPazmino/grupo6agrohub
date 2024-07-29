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
            echo "<div class='alert alert-danger'>Error al actualizar los datos: " . $e->getMessage() . "</div>";
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
    <link
        href="https://fonts.googleapis.com/css?family=Nunito:200,200i,300,300i,400,400i,600,600i,700,700i,800,800i,900,900i"
        rel="stylesheet">

    <!-- Custom styles for this template-->
    <link href="css/user/sb-admin-2.min.css" rel="stylesheet">



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
                <a class="nav-link" href="user.php">
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
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">Dashboard</h1>
        
    </div>

    <!-- HTML y PHP para mostrar y editar los datos del usuario -->
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card shadow mb-4">
                <!-- Card Header - User Info -->
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Información del Usuario</h6>
                </div>
                <!-- Card Body - User Info -->
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-4 text-center">
                            <img class="img-fluid rounded-circle" style="width: 150px;" src="assets/images/undraw_profile.svg" alt="Profile Image">
                        </div>


                        <div class="col-md-8">
    <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" onsubmit="return validateForm();">
        <div class="form-group">
            <label for="nombre_usuario">Nombre de Usuario:</label>
            <input type="text" class="form-control" id="nombre_usuario" name="nombre_usuario" value="<?php echo $_SESSION['nombre_usuario']; ?>" readonly>
        </div>
        <div class="form-group">
            <label for="nombre">Nombre:</label>
            <input type="text" class="form-control" id="nombre" name="nombre" value="<?php echo $_SESSION['nombre']; ?>">
        </div>
        <div class="form-group">
            <label for="apellido">Apellido:</label>
            <input type="text" class="form-control" id="apellido" name="apellido" value="<?php echo $_SESSION['apellido']; ?>">
        </div>
        <div class="form-group">
            <label for="email">Email:</label>
            <input type="email" class="form-control" id="email" name="email" value="<?php echo $_SESSION['email']; ?>">
        </div>
        <div class="form-group">
            <label for="telefono">Teléfono:</label>
            <input type="text" class="form-control" id="telefono" name="telefono" value="<?php echo $_SESSION['telefono']; ?>">
        </div>
        <div class="form-group">
            <label for="cedula">Cédula:</label>
            <input type="text" class="form-control" id="cedula" name="cedula" value="<?php echo $_SESSION['cedula']; ?>">
        </div>
        <button type="submit" class="btn btn-primary">Guardar Cambios</button>
    </form>
</div>
                        

                    </div>
                </div>
            </div>

         <!-- Tareas Asignadas -->
<div class="container-fluid">
    <!-- Page Heading -->
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">Tareas Asignadas</h1>
    </div>
    <div class="row">
        <!-- Display tasks horizontally -->
        <?php if (!empty($empleado['tareas_asignadas'])) : ?>
            <?php foreach ($empleado['tareas_asignadas'] as $index => $tarea) : ?>
                <div class="col-md-4 task-card">
                    <div class="card shadow mb-4 task-card-body">
                        <div class="card-body">
                            <h5 class="card-title"><?= $tarea['descripcion']; ?></h5>
                            <p class="card-text task-status-<?= $tarea['estado']; ?>">Estado: <?= ucfirst($tarea['estado']); ?></p>
                            <form method="POST" action="cambiar_estado_tarea.php">
                                <input type="hidden" name="tarea_id" value="<?= $index; ?>">
                                <select name="nuevo_estado" class="form-control mb-2">
                                    <option value="pendiente" <?= $tarea['estado'] === 'pendiente' ? 'selected' : ''; ?>>Pendiente</option>
                                    <option value="en progreso" <?= $tarea['estado'] === 'en progreso' ? 'selected' : ''; ?>>En progreso</option>
                                    <option value="completada" <?= $tarea['estado'] === 'completada' ? 'selected' : ''; ?>>Completada</option>
                                </select>
                                <button type="submit" class="btn btn-primary">Cambiar Estado</button>
                            </form>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else : ?>
            <div class="col-12">
                <div class="alert alert-info" role="alert">No hay tareas asignadas.</div>
            </div>
        <?php endif; ?>
    </div>
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
