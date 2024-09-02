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
$mongoUri = "mongodb://localhost:27017";
$mongoClient = new Client($mongoUri);
$collection = $mongoClient->grupo6_agrohub->usuarios;

$errors = [];
$success = [];






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



<?php
$servername = "localhost";
$username = "root";
$password = "marito10";
$dbname = "agrohun_grupo6";

// Crear conexión
$conn = new mysqli($servername, $username, $password, $dbname);

// Verificar conexión
if ($conn->connect_error) {
    die("Conexión fallida: " . $conn->connect_error);
}

// Consulta SQL para obtener el rendimiento por terreno
$sql = "SELECT terrenos.nombre, SUM(cosechas.cantidad) / AVG(terrenos.tamano) AS rendimiento
        FROM cosechas
        JOIN siembras ON cosechas.siembra_id = siembras.ID_Siembra
        JOIN terrenos ON siembras.terreno_id = terrenos.ID_Terrenos
        GROUP BY terrenos.nombre";

$result = $conn->query($sql);

// Arrays para guardar los datos
$nombres = [];
$rendimientos = [];

if ($result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $nombres[] = $row['nombre'];
        $rendimientos[] = $row['rendimiento'];
    }
} else {
    echo "0 resultados";
}








// Consulta para calcular el ingreso estimado por producto
$sql = "SELECT productos.nombre, SUM(cosechas.cantidad * productos.precio_unitario) AS ingreso_estimado
        FROM cosechas
        JOIN siembras ON cosechas.siembra_id = siembras.ID_Siembra
        JOIN productos ON siembras.producto_id = productos.Id_Productos
        GROUP BY productos.nombre";

$result = $conn->query($sql);

$nombres = [];
$ingresos = [];

if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $nombres[] = $row['nombre'];
        $ingresos[] = $row['ingreso_estimado'];
    }
} else {
    echo "0 resultados";
}



// Consulta SQL para obtener el tiempo promedio de crecimiento
$sql = "
SELECT p.nombre,
       AVG(DATEDIFF(c.fecha_cosecha, s.fecha_siembra)) AS TiempoPromedioCrecimiento
FROM productos p
JOIN siembras s ON p.Id_Productos = s.producto_id
JOIN cosechas c ON s.ID_Siembra = c.siembra_id
GROUP BY p.nombre
";

// Ejecutar la consulta
$result = $conn->query($sql);

// Arrays para almacenar los datos
$labels = [];
$data = [];

// Obtener datos
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $labels[] = $row['nombre'];
        $data[] = $row['TiempoPromedioCrecimiento'];
    }
} else {
    echo "0 resultados";
}




// Consulta SQL para obtener la rentabilidad por unidad
$sql = "
SELECT p.nombre,
       SUM(p.precio_unitario) / SUM(c.cantidad) AS RentabilidadPorUnidad
FROM productos p
JOIN siembras s ON p.Id_Productos = s.producto_id
JOIN cosechas c ON s.ID_Siembra = c.siembra_id
GROUP BY p.nombre
";

// Ejecutar la consulta
$result = $conn->query($sql);

// Arrays para almacenar los datos de rentabilidad por unidad
$labels_profit = [];
$data_profit = [];

// Obtener datos
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $labels_profit[] = $row['nombre'];
        $data_profit[] = $row['RentabilidadPorUnidad'];
    }
} else {
    echo "0 resultados para la rentabilidad por unidad";
}

$conn->close();
?>

<?php
// Configuración de la base de datos
$servername = "localhost";
$username = "root";
$password = "marito10";
$dbname = "datawarehouse_agro_hub";

// Crear conexión
$conn = new mysqli($servername, $username, $password, $dbname);

// Comprobar conexión
if ($conn->connect_error) {
    die("Conexión fallida: " . $conn->connect_error);
}

// Consulta SQL para obtener la distribución de productos por tipo
$sql = "
SELECT p.tipo AS tipo_producto,
       COUNT(p.Id_Productos) AS cantidad_productos
FROM dim_productos p
GROUP BY p.tipo
";

// Ejecutar la consulta
$result = $conn->query($sql);

// Arrays para almacenar los datos
$labels_profit1 = [];
$data_profit1 = [];

// Obtener datos
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $labels_profit1[] = $row['tipo_producto'];
        $data_profit1[] = $row['cantidad_productos'];
    }
} else {
    echo "0 resultados";
}

// Cerrar la conexión
$conn->close();
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
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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
                <a class="nav-link" href="dashboard.php">
                    <i class="fas fa-fw fa-tachometer-alt"></i>
                    <span>Dashboard Empresarial D</span></a>
            </li>
                               <!-- Nav Item - Charts -->
                               <li class="nav-item">
                <a class="nav-link" href="dashboardd.php">
                    <i class="fas fa-fw fa-tachometer-alt"></i>
                    <span>Dashboard Empresarial S</span></a>
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

  
    <div class="row">
            <div class="col-xl-8 col-lg-7">

    <!-- Area Chart -->
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Rendimiento de Cosecha por Terreno</h6>
        </div>
        <div class="card-body">
            <div class="chart-area">
                <canvas id="rendimientoTerreno"></canvas>
            </div>
            <hr>
            En el dashboard, una barra más alta indica un mejor rendimiento 
            promedio de cosecha para ese terreno.
        </div>
    </div>

    
</div>
<div class="col-xl-4 col-lg-5">
    <div class="card shadow mb-4">
        <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary">Ingreso Estimado por Producto</h6>
                    </div>
                    <div class="card-body">
                        <div class="chart-area">
                            <canvas id="myPieChart"></canvas>
                        </div>
                        <hr>
                        n el dashboard, una barra más alta indica un mejor rendimiento 
            promedio de cosecha para ese terreno.
                    </div>
                </div>
            </div>
</div>

<div class="row">
    <!-- Primer bloque -->
    <div class="col-xl-4 col-lg-5">
        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">Rentabilidad por Unidad</h6>
            </div>
            <div class="card-body">
                <div class="chart-area">
                    <canvas id="profitPolarChart"></canvas>
                </div>
                <hr>
                En el dashboard, una barra más alta indica un mejor rendimiento promedio de cosecha para ese terreno.
            </div>
        </div>
    </div>

    <!-- Segundo bloque -->
    <div class="col-xl-8 col-lg-7">
        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">Tiempo Promedio de Crecimiento</h6>
            </div>
            <div class="card-body">
                <div class="chart-area">
                    <canvas id="growthBarChartHorizontal"></canvas>
                </div>
                <hr>
                En el dashboard, una barra más alta indica un mejor rendimiento promedio de cosecha para ese terreno.
            </div>
        </div>
    </div>
</div>






<!-- Script para el gráfico de Rentabilidad por Unidad -->
<script>
    const labels_profit = <?php echo json_encode($labels_profit); ?>;
    const data_profit = {
        labels: labels_profit,
        datasets: [{
            label: 'Rentabilidad por Unidad',
            data: <?php echo json_encode($data_profit); ?>,
            backgroundColor: [
                'rgba(255, 99, 132, 0.2)',
                'rgba(54, 162, 235, 0.2)',
                'rgba(255, 206, 86, 0.2)',
                'rgba(75, 192, 192, 0.2)',
                'rgba(153, 102, 255, 0.2)',
                'rgba(255, 159, 64, 0.2)'
            ],
            borderColor: [
                'rgba(255, 99, 132, 1)',
                'rgba(54, 162, 235, 1)',
                'rgba(255, 206, 86, 1)',
                'rgba(75, 192, 192, 1)',
                'rgba(153, 102, 255, 1)',
                'rgba(255, 159, 64, 1)'
            ],
            borderWidth: 1
        }]
    };

    const config_profit = {
        type: 'polarArea',  // Tipo de gráfico: 'polarArea' para gráfico de área polar
        data: data_profit,
        options: {
            responsive: true,
            plugins: {
                legend: {
                    position: 'top',
                },
                tooltip: {
                    callbacks: {
                        label: function(tooltipItem) {
                            return tooltipItem.label + ': $' + tooltipItem.raw.toLocaleString();
                        }
                    }
                }
            }
        }
    };

    // Crear el gráfico de rentabilidad por unidad
    const profitPolarChart = new Chart(
        document.getElementById('profitPolarChart'),
        config_profit
    );
</script>



<!-- Script para el gráfico de Tiempo Promedio de Crecimiento -->
<script>
        const labels = <?php echo json_encode($labels); ?>;
        const data = {
            labels: labels,
            datasets: [{
                label: 'Tiempo Promedio de Crecimiento (días)',
                data: <?php echo json_encode($data); ?>,
                backgroundColor: 'rgba(75, 192, 192, 0.2)',
                borderColor: 'rgba(75, 192, 192, 1)',
                borderWidth: 1
            }]
        };

        const config = {
            type: 'bar',  // Tipo de gráfico: 'bar' para gráfico de barras
            data: data,
            options: {
                responsive: true,
                indexAxis: 'y',  // Cambia el eje X a eje Y para gráfico de barras horizontales
                scales: {
                    x: {
                        beginAtZero: true,
                        title: {
                            display: true,
                            text: 'Tiempo Promedio de Crecimiento (días)'
                        }
                    },
                    y: {
                        beginAtZero: true,
                        title: {
                            display: true,
                            text: 'Variedad de Producto'
                        }
                    }
                },
                plugins: {
                    legend: {
                        position: 'top',
                    },
                    tooltip: {
                        callbacks: {
                            label: function(tooltipItem) {
                                return tooltipItem.label + ': ' + tooltipItem.raw + ' días';
                            }
                        }
                    }
                }
            }
        };

        // Crear el gráfico de barras horizontales
        const growthBarChartHorizontal = new Chart(
            document.getElementById('growthBarChartHorizontal'),
            config
        );
    </script>

    
<script>
        const labelsRendimiento = <?php echo json_encode($nombres); ?>;
        const dataRendimiento = {
            labels: labelsRendimiento,
            datasets: [{
                label: 'Rendimiento por Terreno',
                data: <?php echo json_encode($rendimientos); ?>,
                backgroundColor: 'rgba(54, 162, 235, 0.2)',
                borderColor: 'rgba(54, 162, 235, 1)',
                borderWidth: 1
            }]
        };

        const configRendimiento = {
            type: 'bar',  // Tipo de gráfico: 'bar', 'line', 'pie', etc.
            data: dataRendimiento,
            options: {
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        };

        // Crear el gráfico de Rendimiento por Terreno
        const rendimientoTerreno = new Chart(
            document.getElementById('rendimientoTerreno'),
            configRendimiento
        );
    </script>

    <!-- Script para el gráfico de Ingreso Estimado -->
    <script>
        const labelsIngreso = <?php echo json_encode($nombres); ?>;
        const dataIngreso = {
            labels: labelsIngreso,
            datasets: [{
                label: 'Ingreso Estimado',
                data: <?php echo json_encode($ingresos); ?>,
                backgroundColor: [
                    'rgba(255, 99, 132, 0.2)',
                    'rgba(54, 162, 235, 0.2)',
                    'rgba(255, 206, 86, 0.2)',
                    'rgba(75, 192, 192, 0.2)',
                    'rgba(153, 102, 255, 0.2)',
                    'rgba(255, 159, 64, 0.2)'
                ],
                borderColor: [
                    'rgba(255, 99, 132, 1)',
                    'rgba(54, 162, 235, 1)',
                    'rgba(255, 206, 86, 1)',
                    'rgba(75, 192, 192, 1)',
                    'rgba(153, 102, 255, 1)',
                    'rgba(255, 159, 64, 1)'
                ],
                borderWidth: 1
            }]
        };

        const configIngreso = {
            type: 'doughnut',  // Cambia a 'pie' si prefieres un gráfico de pastel
            data: dataIngreso,
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'top',
                    },
                    tooltip: {
                        callbacks: {
                            label: function(tooltipItem) {
                                return tooltipItem.label + ': $' + tooltipItem.raw.toLocaleString();
                            }
                        }
                    }
                }
            }
        };

        // Crear el gráfico de Ingreso Estimado
        const myPieChart = new Chart(
            document.getElementById('myPieChart'),
            configIngreso
        );
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
