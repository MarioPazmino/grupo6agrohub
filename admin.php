<?php
session_start();
if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'admin') {
    header("Location: index.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Página de Administrador</title>
</head>
<body>
    <h1>Bienvenido, <?php echo $_SESSION['nombre_usuario']; ?>!</h1>
    <p>Esta es la página de administrador.</p>
    <a href="logout.php">Cerrar sesión</a>
</body>
</html>
