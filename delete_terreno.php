<?php
session_start();

// Incluir el autoload de Composer
require __DIR__ . '/vendor/autoload.php';

use MongoDB\Client;
use MongoDB\Exception\Exception;

// Configuración de MongoDB
$mongoUri = "mongodb://localhost:27017";
$mongoClient = new Client($mongoUri);
$database = $mongoClient->grupo6_agrohub; // Nombre de la base de datos
$collection = $database->terrenos; // Nombre de la colección

// Verificar si el usuario está autenticado y es administrador
if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'admin') {
    header('Location: index.php');
    exit();
}

// Verificar que el ID del terreno esté presente en la URL
if (isset($_GET['id'])) {
    $id = $_GET['id'];

    try {
        // Intentar eliminar el terreno
        $deleteResult = $collection->deleteOne(['_id' => new MongoDB\BSON\ObjectId($id)]);

        if ($deleteResult->getDeletedCount() === 1) {
            echo "Terreno eliminado con éxito.";
        } else {
            echo "No se encontró ningún terreno con el ID proporcionado.";
        }
    } catch (Exception $e) {
        echo "Error al eliminar el terreno: " . $e->getMessage();
    }
} else {
    echo "ID del terreno no proporcionado.";
}
?>
