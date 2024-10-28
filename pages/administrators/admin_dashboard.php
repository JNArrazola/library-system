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
    <link rel="stylesheet" href="../../styles/administrators/admin_dashboard.css?v=<?php echo time(); ?>">
</head>
<body>
    <header>
        <h1>Panel de Control - Administrador</h1>
        <div class="user-menu">
            <span><?= htmlspecialchars($_SESSION['nombre']) ?> ▼</span>
            <ul class="dropdown">
                <li><a href="?logout=true">Cerrar sesión</a></li>
            </ul>
        </div>
    </header>
    
    <section class="actions">
        <h2>Acciones del Administrador</h2>
        <ul>
            <li><a href="view_transactions.php">Ver Transacciones</a></li>
            <li><a href="../manage_users.php">Gestionar Usuarios y Permisos</a></li>
        </ul>
    </section>
</body>
</html>
