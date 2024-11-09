<?php
session_start();
include('../../config/config.php');

if (!isset($_SESSION['user_id']) || $_SESSION['rol'] !== 'administrador') {
    header('Location: ../../index.php');
    exit();
}

if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: ../../index.php');
    exit();
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel de Administrador</title>
    <link rel="stylesheet" href="../../styles/administrators/admin_dashboard.css?v=<?= time(); ?>">
</head>
<body>
    <header>
        <h1>Panel de Control - Administrador</h1>
        <div class="user-menu">
            <span><?= htmlspecialchars($_SESSION['nombre']) ?> (Administrador)</span>
            <a href="../../config/logout.php" class="logout-button">Cerrar sesión</a>
        </div>
    </header>

    <section class="dashboard-menu">
        <ul>
<!--        <li><a href="view_transactions.php">Ver Transacciones</a></li>-->            
            <li><a href="../manage_users.php">Gestionar Usuarios y Permisos</a></li>
            <li><a href="request.php">Solicitudes</a></li>
            <li><a href="../user_edit.php">Editar perfil</a></li>
            <li><a href="../catalog.php" class="catalog-link">Volver al Catálogo</a></li>
        </ul>
    </section>
</body>
</html>
