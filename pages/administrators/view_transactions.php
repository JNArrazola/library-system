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
    <!-- <link rel="stylesheet" href="../../styles/administrators/admin_dashboard.css?v=<?php echo time(); ?>"> -->
    <link rel="stylesheet" href="../../styles/librarians/manage_books.css">
    <link rel="stylesheet" href="../../styles/uriegas/buttons.css">
    <link rel="stylesheet" href="../../styles/uriegas/unorderlist.css">
    <link rel="stylesheet" href="../../styles/uriegas/main.css">
</head>
<body>
    <header>
        <h1>Transacciones</h1>
        <div class="user-menu">
            <span><?= htmlspecialchars($_SESSION['nombre']) ?> ▼</span>
            <a href=".../../config/logout.php" class="logout-button">Cerrar sesión</a>
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
