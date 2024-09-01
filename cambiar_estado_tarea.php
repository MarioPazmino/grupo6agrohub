<?php
session_start();

require __DIR__ . '/vendor/autoload.php';

use MongoDB\Client;
use MongoDB\Exception\Exception;

// Verificar si el usuario está autenticado y tiene el rol de 'empleado'
if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'empleado') {
    header("Location: index.php");
    exit();
}

// Conexión a MongoDB con la URL actualizada
$mongoUri = "mongodb://localhost:27017";
$mongoClient = new Client($mongoUri);
$db = $mongoClient->selectDatabase("grupo6_agrohub");
$collection = $db->usuarios; // Nombre de la colección en MongoDB

$usuario_id = new MongoDB\BSON\ObjectId($_SESSION['usuario_id']);

// Procesar el formulario de actualización del estado de la tarea
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Validar que los datos del formulario están presentes
    if (isset($_POST['tarea_id']) && isset($_POST['estado'])) {
        $tarea_id = (int)$_POST['tarea_index']; // Usar índice entero para localizar la tarea
        $nuevo_estado = $_POST['estado'];

        // Construir el filtro para encontrar el usuario por ID
        $filter = ['_id' => $usuario_id];

        // Construir el objeto de actualización utilizando el índice de la tarea
        $update = [
            '$set' => [
                "tareas_asignadas.$tarea_id.estado" => $nuevo_estado
            ]
        ];

        // Actualizar el documento del usuario en MongoDB
        try {
            $result = $collection->updateOne($filter, $update);

            if ($result->getModifiedCount() > 0) {
                // Éxito: redirigir de nuevo al dashboard con un mensaje de éxito
                $_SESSION['message'] = "Estado de la tarea actualizado correctamente.";
            } else {
                // Error: redirigir de nuevo al dashboard con un mensaje de error
                $_SESSION['message'] = "No se pudo actualizar el estado de la tarea.";
            }
        } catch (Exception $e) {
            // Error: redirigir de nuevo al dashboard con un mensaje de error
            $_SESSION['message'] = "Error al actualizar el estado de la tarea: " . $e->getMessage();
        }

        // Redirigir de nuevo a la página de usuario
        header("Location: user.php");
        exit();
    } else {
        // Si los datos del formulario no están presentes, redirigir de nuevo a la página de usuario
        $_SESSION['message'] = "Datos del formulario inválidos.";
        header("Location: user.php");
        exit();
    }
} else {
    // Si el método de solicitud no es POST, redirigir de nuevo a la página de usuario
    header("Location: user.php");
    exit();
}
?>
