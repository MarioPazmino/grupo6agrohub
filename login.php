<?php

require __DIR__ . '/vendor/autoload.php';
use MongoDB\Client;
use MongoDB\Exception\Exception;

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $emailOrUsername = $_POST['email_or_username'];
    $password = $_POST['password'];

    // URI de conexión a MongoDB (Amazon DocumentDB)
    $mongoUri = "mongodb://localhost:27017";

    // Conexión a MongoDB
    try {
        $mongoClient = new Client($mongoUri);
        $db = $mongoClient->selectDatabase("grupo6_agrohub");
        $collection = $db->selectCollection("usuarios");

        // Buscar usuario por email o nombre de usuario
        $user = $collection->findOne([
            '$or' => [
                ['email' => $emailOrUsername],
                ['nombre_usuario' => $emailOrUsername]
            ]
        ]);

        if ($user) {
            // Verificar la contraseña encriptada
            if (password_verify($password, $user['password'])) {
                session_start();
                $_SESSION['usuario_id'] = (string)$user['_id'];
                $_SESSION['rol'] = $user['rol'];
                $_SESSION['nombre_usuario'] = $user['nombre_usuario'];
                $_SESSION['nombre'] = $user['nombre'];
                $_SESSION['apellido'] = $user['apellido'];
                $_SESSION['email'] = $user['email'];
                $_SESSION['telefono'] = $user['telefono'];
                $_SESSION['cedula'] = $user['cedula'];
                if ($user['rol'] === 'admin') {
                    header("Location: admin.php");
                } else if ($user['rol'] === 'empleado') { // Cambiado 'usuario' a 'empleado' según tu base de datos
                    header("Location: user.php");
                } else {
                    echo "Rol no reconocido.";
                }
                exit();
            } else {
                echo "Contraseña incorrecta.";
                header("Location: index.php");
                exit();
            }

        } else {
            echo "Usuario no encontrado.";
            header("Location: index.php");
            exit();
        }

    } catch (Exception $e) {
        echo "Error: " . $e->getMessage();
        exit();
    }
} else {
    header("Location: index.php");
    exit();
}
?>
