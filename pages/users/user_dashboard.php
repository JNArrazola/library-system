<?php
session_start();
include('../../config/config.php');

if (!isset($_SESSION['user_id']) || $_SESSION['rol'] !== 'usuario') {
    header('Location: ../../index.php');
    exit();
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mi Panel - Usuario</title>
    <link rel="stylesheet" href="../../styles/users/user_dashboard.css?v=<?php echo time(); ?>">
</head>
<body>
    <header>
        <h1>Mi Panel - Usuario</h1>
        <div class="user-menu">
            <span><?= htmlspecialchars($_SESSION['nombre']) ?></span>
            <a href="../../config/logout.php" class="logout-button">Cerrar sesión</a>
        </div>
    </header>

    <section class="dashboard">
        <?php if (isset($_GET['success'])): ?>
            <p class="success-message"><?= htmlspecialchars($_GET['success']) ?></p>
        <?php endif; ?>

        <div class="options-menu">
            <h2>Opciones</h2>
            <ul>
                <li><a href="view_reservations.php">Ver Mis Reservas</a></li>
                <li><a href="view_loans.php">Ver Mis Préstamos</a></li>
                <li><a href="../user_edit.php">Modificar cuenta</a></li>
                <li><a href="../catalog.php">Volver al Catálogo</a></li> 
            </ul>
        </div>
    </section>
</body>
</html>
