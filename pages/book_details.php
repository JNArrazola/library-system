<?php
session_start();
include('../config/config.php');

if (!isset($_GET['id'])) {
    header('Location: catalog.php');
    exit();
}

$book_id = $_GET['id'];

$query = "SELECT * FROM Libros WHERE id = :book_id";
$stmt = $pdo->prepare($query);
$stmt->execute(['book_id' => $book_id]);
$book = $stmt->fetch();

if (!$book) {
    header('Location: catalog.php');
    exit();
}

$success_message = isset($_GET['success']) ? $_GET['success'] : '';
$error_message = isset($_GET['error']) ? $_GET['error'] : '';

$nav_options = '<a href="../index.php" class="nav-link">Inicio</a>';
$nav_options .= '<a href="catalog.php" class="nav-link">Catálogo</a>';

if (isset($_SESSION['user_id'])) {
    if ($_SESSION['rol'] === 'administrador') {
        $nav_options .= '<a href="administrators/admin_dashboard.php" class="admin-link">Panel de Administrador</a>';
    } elseif ($_SESSION['rol'] === 'bibliotecario') {
        $nav_options .= '<a href="librarians/librarian_dashboard.php" class="bibliotecario-link">Opciones de Bibliotecario</a>';
    } elseif ($_SESSION['rol'] === 'usuario') {
        $nav_options .= '<a href="users/user_dashboard.php" class="usuario-link">Mi Panel</a>';
    }
    $nav_options .= '<a href="../config/logout.php" class="logout-button">Cerrar sesión</a>';
} else {
    $nav_options .= '<a href="login.php" class="login-link">Iniciar Sesión</a>';
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detalles del Libro</title>
    <link rel="stylesheet" href="../styles/book_details.css?v=<?= time(); ?>">
</head>
<body>
    <header>
        <div class="logo">
            <h1>Biblioteca</h1>
        </div>
        <div class="user-menu">
            <?= $nav_options ?>
        </div>
    </header>

    <?php if ($success_message): ?>
        <div class="success-message">
            <?= htmlspecialchars($success_message) ?>
        </div>
    <?php elseif ($error_message): ?>
        <div class="error-message">
            <?= htmlspecialchars($error_message) ?>
        </div>
    <?php endif; ?>

    <div class="back-button-container">
        <a href="catalog.php" class="back-button">Volver al catálogo</a>
    </div>

    <div class="book-details-container">
        <!-- Sección de la imagen del libro -->
        <div class="book-image">
            <img src="../styles/img/background.jpg" alt="Portada de Ejemplo">
        </div>
        
        <!-- Sección de la información del libro -->
        <div class="book-info">
            <h2 class="book-title"><?= htmlspecialchars($book['nombre']) ?></h2>
            <hr>
            <h3>Sinopsis</h3>
            <p class="synopsis"><?= htmlspecialchars($book['sinopsis']) ?></p>
            <p><strong>Autor:</strong> <?= htmlspecialchars($book['autor']) ?></p>
            <p><strong>Editorial:</strong> <?= htmlspecialchars($book['editorial']) ?></p>
            <p><strong>Fecha de Publicación:</strong> <?= htmlspecialchars($book['fecha_publicacion']) ?></p>
            <p><strong>Disponibilidad:</strong> <?= ($book['cantidad'] > 0) ? 'Disponible' : 'No disponible' ?></p>

            <?php if (isset($_SESSION['rol']) && $_SESSION['rol'] === 'usuario' && $book['cantidad'] > 0): ?>
                <a href="users/reserve_book.php?id=<?= $book['id'] ?>" 
                class="reserve-button" 
                onclick="return confirm('¿Estás seguro que deseas reservar el libro <?= htmlspecialchars($book['nombre']) ?>?');">Reservar</a>
            <?php endif; ?>
        </div>
    </div>

    <footer>
        <p>Biblioteca - 2024</p>
    </footer>
</body>
</html>