<?php
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['rol'] !== 'bibliotecario') {
    header('Location: ../index.php');
    exit();
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard de Bibliotecario</title>
    <link rel="stylesheet" href="../../styles/librarians/librarian_dashboard.css">
</head>
<body>
    <header>
        <h1>Panel de Control - Bibliotecario</h1>
        <div class="user-menu">
            <span><?= htmlspecialchars($_SESSION['nombre']) ?> (Bibliotecario) </span>
            <a href="../logout.php" class="logout-button">Cerrar sesión</a>
        </div>
    </header>

    <section class="dashboard-menu">
        <ul>
            <li><a href="manage_books.php">Administrar Inventario de Libros</a></li>
            <li><a href="register_loan.php">Registrar Préstamo de Libros</a></li>
            <li><a href="return_books.php">Registrar Devolución de Libros</a></li>
            <li><a href="../manage_users.php">Gestionar Usuarios</a></li>
            <li><a href="view_loans.php">Ver Préstamos Actuales</a></li>
            <li><a href="../main_dashboard.php" class="catalog-link">Volver al Catálogo</a></li> 
        </ul>
    </section>
</body>
</html>
