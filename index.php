<?php
session_start();
include('config/config.php');

$query = "SELECT * FROM Libros ORDER BY RAND() LIMIT 3";
$stmt = $pdo->query($query);
$featured_books = $stmt->fetchAll();

$nav_options = '<a href="index.php" class="nav-link">Inicio</a> <a href="pages/catalog.php" class="nav-link">Catálogo</a>';

if (isset($_SESSION['user_id'])) {
    if ($_SESSION['rol'] === 'administrador') {
        $nav_options .= '<a href="pages/administrators/admin_dashboard.php" class="admin-link">Panel de Administrador</a>';
    } elseif ($_SESSION['rol'] === 'admin_general') {
        $nav_options .= '<a href="pages/administrators/general_dashboard.php" class="admin-general-link">Panel Administrador</a>';
    } elseif ($_SESSION['rol'] === 'bibliotecario') {
        $nav_options .= '<a href="pages/librarians/librarian_dashboard.php" class="bibliotecario-link">Opciones de Bibliotecario</a>';
    } elseif ($_SESSION['rol'] === 'usuario') {
        $nav_options .= '<a href="pages/users/user_dashboard.php" class="usuario-link">Mi Panel</a>';
    }
    $nav_options .= ' <a href="config/logout.php" class="logout-button">Cerrar sesión</a>';
} else {
    $nav_options .= '<a href="pages/login.php" class="usuario-link">Iniciar Sesión</a>';
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bienvenido a la Biblioteca</title>
    <link rel="stylesheet" href="styles/index.css">
</head>
<body>
    <header>
        <h1>Bienvenido a la Biblioteca</h1>
        <div class="user-menu">
            <?= $nav_options ?>
        </div>
    </header>

    <section class="welcome-section">
        <div class="intro-text">
            <h2>Descubre los Mejores Libros</h2>
            <p>Explora nuestra colección y encuentra el libro perfecto para ti. Visita nuestro catálogo o revisa algunos de nuestros destacados.</p>
            <a href="pages/catalog.php" class="catalog-link">Ir al Catálogo Completo</a>
        </div>

        <div class="featured-books">
            <h2>Libros Destacados</h2>
            <div class="book-list">
                <?php foreach ($featured_books as $book): ?>
                    <div class="book-item" onclick="window.location.href='pages/book_details.php?id=<?= $book['id'] ?>'">
                        <div class="flip-card-inner">
                            <div class="flip-card-front">
                                <img src="data:image/jpeg;base64,<?= base64_encode($book['imagen']) ?>" alt="<?= htmlspecialchars($book['nombre']) ?>">
                                <h3><?= htmlspecialchars($book['nombre']) ?></h3>
                            </div>
                            <div class="flip-card-back">
                                <p><strong>Autor:</strong> <?= htmlspecialchars($book['autor']) ?></p>
                                <p><strong>Sinopsis:</strong> <?= substr(htmlspecialchars($book['sinopsis']), 0, 150) ?>.</p>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>
</body>
</html>
