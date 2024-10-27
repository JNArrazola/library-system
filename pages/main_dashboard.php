<?php
session_start();
include('../config/config.php');

if (!isset($_SESSION['user_id'])) {
    header('Location: ../index.php');
    exit();
}

$search = '';
if (isset($_GET['search'])) {
    $search = $_GET['search'];
    $query = "SELECT * FROM Libros WHERE nombre LIKE :search OR autor LIKE :search";
    $stmt = $pdo->prepare($query);
    $stmt->execute(['search' => '%' . $search . '%']);
} else {
    $query = "SELECT * FROM Libros";
    $stmt = $pdo->query($query);
}

$books = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Biblioteca - Libros</title>
    <link rel="stylesheet" href="../styles/main_dashboard.css?v=<?= time(); ?>">
</head>
<body>
    <header>
        <h1>Biblioteca</h1>
        <div class="user-menu">
            <span><?= htmlspecialchars($_SESSION['nombre']) ?> 
                <?php if ($_SESSION['rol'] === 'bibliotecario'): ?>
                    <a href="librarians/librarian_dashboard.php" class="bibliotecario-link">(Opciones de Bibliotecario)</a>
                <?php else: ?>
                    <a href="users/user_dashboard.php" class="usuario-link">(Mi Panel)</a>
                <?php endif; ?>
            </span>
            <a href="../config/logout.php" class="logout-button">Cerrar sesión</a>
        </div>
    </header>

    <div class="search-bar">
        <form action="main_dashboard.php" method="GET">
            <input type="text" name="search" placeholder="Buscar por título o autor..." value="<?= htmlspecialchars($search) ?>">
            <button type="submit">Buscar</button>
        </form>
    </div>

    <div class="book-list">
        <?php if (count($books) > 0): ?>
            <?php foreach ($books as $book): ?>
                <div class="book-item" onclick="window.location.href='book_details.php?id=<?= $book['id'] ?>'">
                    <img src="data:image/jpeg;base64,<?= base64_encode($book['imagen']) ?>" alt="<?= htmlspecialchars($book['nombre']) ?>">
                    <h3><?= htmlspecialchars($book['nombre']) ?></h3>
                    <p><strong>Autor:</strong> <?= htmlspecialchars($book['autor']) ?></p>
                    <p><strong>Sinopsis:</strong> <?= substr(htmlspecialchars($book['sinopsis']), 0, 150) ?>...</p>
                    <p><strong>Disponibilidad:</strong> <?= ($book['cantidad'] > 0) ? 'Disponible' : 'No disponible' ?></p>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <p>No se encontraron libros.</p>
        <?php endif; ?>
    </div>
</body>
</html>
