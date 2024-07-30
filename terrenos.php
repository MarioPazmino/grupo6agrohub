<?php
session_start();

// Verificar si el usuario está autenticado
if (!isset($_SESSION['rol'])) {
    header("Location: index.php");
    exit();
}

require __DIR__ . '/vendor/autoload.php';

use MongoDB\Client;
use MongoDB\Exception\Exception;

// Conexión a MongoDB con la URL proporcionada
$mongoUri = "mongodb://mario1010:marito10@testmongo1.cluster-c9ccw6ywgi5c.us-east-1.docdb.amazonaws.com:27017/?tls=true&tlsCAFile=global-bundle.pem&retryWrites=false";
$mongoClient = new Client($mongoUri);
$collection = $mongoClient->grupo6_agrohub->terrenos;

// Variables para mensajes de éxito y error
$success = [];
$errors = [];

// Manejo del formulario de inserción de terreno para administradores
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion']) && $_SESSION['rol'] === 'admin') {
    try {
        if ($_POST['accion'] === 'insertar') {
            $nombre = $_POST['nombre'];
            $ubicacion = $_POST['ubicacion'];
            $tamano = (int)$_POST['tamano'];
            $estado = $_POST['estado'];
            $descripcion = $_POST['descripcion'];

            $result = $collection->insertOne([
                "nombre" => $nombre,
                "ubicacion" => $ubicacion,
                "tamano" => $tamano,
                "estado" => $estado,
                "descripcion" => $descripcion
            ]);

            $success[] = "Terreno agregado exitosamente.";
        }
    } catch (Exception $e) {
        $errors[] = "Error al agregar terreno: " . $e->getMessage();
    }
}

// Leer terrenos
$terrenos = [];
if ($_SESSION['rol'] === 'admin') {
    $terrenos = $collection->find()->toArray();
} else if ($_SESSION['rol'] === 'empleado') {
    // Opcional: Filtrar terrenos específicos para empleados, si es necesario
    $terrenos = $collection->find()->toArray();
}

// Contar el número total de empleados y tareas si el usuario es admin
$total_empleados = 0;
$total_tareas_pendientes = 0;
$total_tareas_proceso = 0;
$total_tareas_completadas = 0;

if ($_SESSION['rol'] === 'admin') {
    $empleadosCollection = $mongoClient->grupo6_agrohub->empleados;

    // Contar el número total de empleados
    $total_empleados = $empleadosCollection->countDocuments(['rol' => 'empleado']);

    // Contar el número de tareas pendientes, en proceso y completadas
    $total_tareas_pendientes = $empleadosCollection->countDocuments([
        'tareas_asignadas.estado' => 'pendiente'
    ]);

    $total_tareas_proceso = $empleadosCollection->countDocuments([
        'tareas_asignadas.estado' => 'en_proceso'
    ]);

    $total_tareas_completadas = $empleadosCollection->countDocuments([
        'tareas_asignadas.estado' => 'completada'
    ]);
}

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
   
<h1 class="h3 mb-4 text-gray-800">
    <?php echo ($_SESSION['rol'] === 'admin') ? 'Perfil Administrador' : 'Perfil de Empleado'; ?>
</h1>

<!-- Mostrar mensajes de error -->
<?php if (!empty($errors)): ?>
    <div class="alert alert-danger notification">
        <?php foreach ($errors as $error): ?>
            <p><?php echo $error; ?></p>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<!-- Mostrar mensajes de éxito -->
<?php if (!empty($success)): ?>
    <div class="alert alert-success notification">
        <?php foreach ($success as $msg): ?>
            <p><?php echo $msg; ?></p>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

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
                                Total de Empleados
                            </div>
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
                                Tareas Pendientes
                            </div>
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
                                Tareas En Proceso
                            </div>
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
                                Tareas Completadas
                            </div>
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
<?php endif; ?>






    <?php if ($_SESSION['rol'] === 'admin'): ?>
    <!-- Formulario para agregar terrenos -->
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Agregar Terreno</h6>
        </div>
        <div class="card-body">
            <form method="post" action="">
                <input type="hidden" name="accion" value="insertar">
                <div class="form-group">
                    <label for="nombre">Nombre:</label>
                    <input type="text" class="form-control" id="nombre" name="nombre" required>
                </div>
                <div class="form-group">
                    <label for="ubicacion">Ubicación:</label>
                    <input type="text" class="form-control" id="ubicacion" name="ubicacion" required>
                </div>
                <div class="form-group">
                    <label for="tamano">Tamaño (hectáreas):</label>
                    <input type="number" class="form-control" id="tamano" name="tamano" required>
                </div>
                <div class="form-group">
                    <label for="estado">Estado:</label>
                    <select class="form-control" id="estado" name="estado" required>
                        <option value="disponible">Disponible</option>
                        <option value="ocupado">Ocupado</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="descripcion">Descripción:</label>
                    <textarea class="form-control" id="descripcion" name="descripcion" rows="3"></textarea>
                </div>
                <button type="submit" class="btn btn-primary">Agregar Terreno</button>
            </form>
        </div>
    </div>

    <!-- Mostrar terrenos -->
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Terrenos Registrados</h6>
        </div>
        <div class="card-body">
            <?php if (!empty($terrenos)): ?>
                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th>Nombre</th>
                            <th>Ubicación</th>
                            <th>Tamaño</th>
                            <th>Estado</th>
                            <th>Descripción</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($terrenos as $terreno): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($terreno->nombre); ?></td>
                                <td><?php echo htmlspecialchars($terreno->ubicacion); ?></td>
                                <td><?php echo htmlspecialchars($terreno->tamano); ?></td>
                                <td><?php echo htmlspecialchars($terreno->estado); ?></td>
                                <td><?php echo htmlspecialchars($terreno->descripcion); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p>No hay terrenos registrados.</p>
            <?php endif; ?>
        </div>
    </div>

<?php else: ?>
    <!-- Mostrar terrenos para empleados sin la opción de modificar -->
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Terrenos Disponibles</h6>
        </div>
        <div class="card-body">
            <?php if (!empty($terrenos)): ?>
                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th>Nombre</th>
                            <th>Ubicación</th>
                            <th>Tamaño</th>
                            <th>Estado</th>
                            <th>Descripción</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($terrenos as $terreno): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($terreno->nombre); ?></td>
                                <td><?php echo htmlspecialchars($terreno->ubicacion); ?></td>
                                <td><?php echo htmlspecialchars($terreno->tamano); ?></td>
                                <td><?php echo htmlspecialchars($terreno->estado); ?></td>
                                <td><?php echo htmlspecialchars($terreno->descripcion); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p>No hay terrenos registrados.</p>
            <?php endif; ?>
        </div>
    </div>

<?php endif; ?>


                                    
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
