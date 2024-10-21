<?php
session_start();
include('../config/config.php');

if (!isset($_SESSION['user_id'])) {
    header('Location: ../index.php');
    exit();
}

if (!isset($_GET['id'])) {
    header('Location: main_dashboard.php');
    exit();
}

$book_id = $_GET['id'];

$query = "SELECT * FROM Libros WHERE id = :book_id";
$stmt = $pdo->prepare($query);
$stmt->execute(['book_id' => $book_id]);
$book = $stmt->fetch();

if (!$book) {
    header('Location: main_dashboard.php');
    exit();
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detalles del Libro</title>
    <link rel="stylesheet" href="../styles/book_details.css">
</head>
<body>
    <header>
        <h1><?= htmlspecialchars($book['nombre']) ?></h1>
        <div class="user-menu">
            <span><?= htmlspecialchars($_SESSION['nombre']) ?> <?= $_SESSION['rol'] === 'bibliotecario' ? '<a href="librarians/librarian_dashboard.php" class="bibliotecario-link">(Opciones de Bibliotecario)</a>' : '' ?></span>
            <a href="../config/logout.php" class="logout-button">Cerrar sesión</a>
        </div>
    </header>

    <div class="book-details-container">
        <div class="book-image">
            <img src="data:image/jpeg;base64,<?= base64_encode($book['imagen']) ?>" alt="<?= htmlspecialchars($book['nombre']) ?>">
        </div>
        <div class="book-info">
            <h2><?= htmlspecialchars($book['nombre']) ?></h2>
            <p><strong>Autor:</strong> <?= htmlspecialchars($book['autor']) ?></p>
            <p><strong>Editorial:</strong> <?= htmlspecialchars($book['editorial']) ?></p>
            <p><strong>Fecha de Publicación:</strong> <?= htmlspecialchars($book['fecha_publicacion']) ?></p>
            <p><strong>Sinopsis:</strong> <?= htmlspecialchars($book['sinopsis']) ?></p>
            <p><strong>Disponibilidad:</strong> <?= ($book['cantidad'] > 0) ? 'Disponible' : 'No disponible' ?></p>

            <?php if ($_SESSION['rol'] === 'usuario' && $book['cantidad'] > 0): ?>
                <a href="reserve_book.php?id=<?= $book['id'] ?>" class="reserve-button">Reservar</a>
            <?php endif; ?>
        </div>
    </div>

    <footer>
        <p>Biblioteca - 2024</p>
    </footer>
</body>
</html>

