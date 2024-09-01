<?php
session_start(); // Iniciar la sesión al principio de la página
require __DIR__ . '/vendor/autoload.php';

use MongoDB\Client;
use MongoDB\Exception\Exception;

function registrarUsuario($datos)
{
    // Validación básica
    $campos = ['nombre', 'apellido', 'nombre_usuario', 'telefono', 'email', 'password', 'confirmPassword', 'direccion', 'cedula'];
    foreach ($campos as $campo) {
        if (empty($datos[$campo])) {
            $_SESSION['error_message'] = "Por favor, completa todos los campos.";
            return false;
        }
    }

    // Validación de contraseña
    if ($datos['password'] != $datos['confirmPassword']) {
        $_SESSION['error_message'] = "Las contraseñas no coinciden. Por favor, inténtelo de nuevo.";
        return false;
    }

    // Validación de longitud del teléfono
    if (strlen($datos['telefono']) !== 10) {
        $_SESSION['error_message'] = "El número de teléfono debe tener exactamente 10 dígitos.";
        return false;
    }

    // Validación de formato de correo electrónico
    if (!filter_var($datos['email'], FILTER_VALIDATE_EMAIL)) {
        $_SESSION['error_message'] = "El formato del correo electrónico es inválido.";
        return false;
    }

    // URI de conexión a MongoDB (Amazon DocumentDB)
    $mongoUri = "mongodb://localhost:27017";

    // Conexión a MongoDB y registro de usuario
    try {
        $mongoClient = new MongoDB\Client($mongoUri);
        $db = $mongoClient->selectDatabase("grupo6_agrohub");
        $collection = $db->selectCollection("usuarios");

        // Verificar si ya existe un usuario con el mismo correo electrónico o nombre de usuario
        $existingUserEmail = $collection->findOne(['email' => $datos['email']]);
        $existingUserName = $collection->findOne(['nombre_usuario' => $datos['nombre_usuario']]);
        
        if ($existingUserEmail) {
            $_SESSION['error_message'] = "Ya existe un usuario registrado con este correo electrónico. Por favor, utiliza otro correo.";
            return false;
        }

        if ($existingUserName) {
            $_SESSION['error_message'] = "El nombre de usuario ya está en uso. Por favor, elige otro nombre de usuario.";
            return false;
        }

        // Crear documento para insertar en la colección
        $documento = [
            'nombre' => $datos['nombre'],
            'apellido' => $datos['apellido'],
            'nombre_usuario' => $datos['nombre_usuario'],
            'telefono' => $datos['telefono'],
            'email' => $datos['email'],
            'password' => password_hash($datos['password'], PASSWORD_BCRYPT), // Encriptar la contraseña
            'direccion' => $datos['direccion'],
            'rol' => 'empleado', // Asignar el rol como empleado
            'cedula' => $datos['cedula'],
            'fecha_contratacion' => new MongoDB\BSON\UTCDateTime(), // Usar la fecha actual
            'tareas_asignadas' => [] // Inicialmente vacío
        ];

        // Insertar documento en la colección
        $insertOneResult = $collection->insertOne($documento);

        // Verificar si se insertó correctamente
        if ($insertOneResult->getInsertedCount() > 0) {
            $_SESSION['success_message'] = "¡Registro exitoso! Bienvenido a AgroCultura.";
            return true;
        } else {
            $_SESSION['error_message'] = "Hubo un problema al registrar el usuario.";
            return false;
        }

    } catch (MongoDB\Exception\Exception $e) {
        $_SESSION['error_message'] = "Error al conectar con la base de datos MongoDB: " . $e->getMessage();
        return false;
    }
}

// Verificar si se recibieron los datos del formulario de registro
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['registro'])) {
    // Obtener datos del formulario y procesar el registro del usuario
    $registroExitoso = registrarUsuario($_POST);
    if ($registroExitoso) {
        // Redirigir al usuario al index.php con mensaje de éxito
        header("Location: index.php?registro=exitoso");
        exit();
    } else {
        // Error durante el registro, ya se estableció $_SESSION['error_message']
        header("Location: index.php?registro=error");
        exit();
    }
}
?>




<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AgroCultura</title>
    <link rel="stylesheet" href="css/index/styles.css">
    <script defer src="components/index/scripts.js"></script>
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</head>
<body>
<header>
    <div class="logo">AgroCultura</div>
    <nav>
        <ul>
            <li><a href="index.php">Hogar</a></li>
        </ul>
    </nav>
</header>
<main>
    <div class="hero">
        <h1>AgroCultura</h1>
        <p>Su producto Nuestro mercado</p>
        <div class="buttons">
            <a href="#" class="btn" id="open-login">Iniciar sesión</a>
            <a href="#" class="btn" id="open-register">Registro</a>
        </div>
    </div>
</main>

<!-- Modal de Iniciar Sesión -->
<div class="modal" id="login-modal">
    <div class="modal-content">
        <span class="close" id="close-login">&times;</span>
        <h2>Iniciar sesión</h2>
        <form action="login.php" method="post">
            <div class="user-box">
                <input type="text" id="email_or_username" name="email_or_username" required placeholder="Nombre de usuario o correo electrónico">
            </div>
            <div class="user-box">
                <input type="password" id="password" name="password" required placeholder="Contraseña">
            </div>
            <button type="submit">
                <span></span>
                <span></span>
                <span></span>
                <span></span>
                Iniciar sesión
            </button>
        </form>
        <?php if (isset($_SESSION['error_message'])): ?>
            <p class="text-danger"><?php echo $_SESSION['error_message']; ?></p>
            <?php unset($_SESSION['error_message']); // Limpiar mensaje de error después de mostrarlo ?>
        <?php endif; ?>
    </div>
</div>

<!-- Modal de Registro -->
<div class="modal" id="register-modal">
    <div class="modal-content">
        <span class="close" id="close-register">&times;</span>
        <h2>Registro</h2>
        <div class="container mt-2">
            <form action="#" method="post" id="registro-form">
                <div class="form-group">
                    <input type="text" class="form-control" name="nombre" required placeholder="Nombre">
                </div>
                <div class="form-group">
                    <input type="text" class="form-control" name="apellido" required placeholder="Apellido">
                </div>
                <div class="form-group">
                    <input type="text" class="form-control" name="nombre_usuario" pattern="[A-Za-z0-9áéíóúÁÉÍÓÚüÜñÑ_.-]+" required placeholder="Nombre de usuario (solo letras, números y caracteres especiales)">
                </div>
                <div class="form-group">
                    <input type="tel" class="form-control" name="telefono" pattern="[0-9]{10}" required placeholder="Número de teléfono móvil (10 dígitos)">
                </div>
                <div class="form-group">
                    <input type="email" class="form-control" name="email" required placeholder="Correo electrónico">
                </div>
                <div class="form-group">
                    <input type="password" class="form-control" name="password" required placeholder="Contraseña">
                </div>
                <div class="form-group">
                    <input type="password" class="form-control" name="confirmPassword" required placeholder="Confirmar contraseña">
                </div>
                <div class="form-group">
                    <input type="text" class="form-control" name="direccion" required placeholder="Dirección">
                </div>
                <div class="form-group">
                    <input type="text" class="form-control" name="cedula" required placeholder="Cédula">
                </div>
                <input type="hidden" name="registro" value="1">
                <button type="submit" class="btn btn-primary">Registrar</button>
            </form>
            <?php if (isset($_SESSION['success_message'])): ?>
                <p class="text-success"><?php echo $_SESSION['success_message']; ?></p>
                <?php unset($_SESSION['success_message']); // Limpiar mensaje de éxito después de mostrarlo ?>
            <?php endif; ?>
            <?php if (isset($_SESSION['error_message'])): ?>
                <p class="text-danger"><?php echo $_SESSION['error_message']; ?></p>
                <?php unset($_SESSION['error_message']); // Limpiar mensaje de error después de mostrarlo ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
document.getElementById('open-register').addEventListener('click', function() {
    document.getElementById('register-modal').style.display = 'block';
});

document.getElementById('close-register').addEventListener('click', function() {
    document.getElementById('register-modal').style.display = 'none';
});

document.getElementById('open-login').addEventListener('click', function() {
    document.getElementById('login-modal').style.display = 'block';
});

document.getElementById('close-login').addEventListener('click', function() {
    document.getElementById('login-modal').style.display = 'none';
});
</script>
</body>
</html>
