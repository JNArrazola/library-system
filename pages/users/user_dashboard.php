<?php
session_start();
include('../../config/config.php');

if (!isset($_SESSION['user_id']) || $_SESSION['rol'] !== 'usuario') {
    header('Location: ../index.php');
    exit();
}

if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: ../index.php');
    exit();
}

$search_query = '';
if (isset($_GET['search'])) {
    $search_query = $_GET['search'];
    $query = "SELECT * FROM Libros WHERE nombre LIKE :search OR autor LIKE :search OR editorial LIKE :search";
    $stmt = $pdo->prepare($query);
    $stmt->execute(['search' => '%' . $search_query . '%']);
} else {
    $query = "SELECT * FROM Libros LIMIT 10"; 
    $stmt = $pdo->query($query);
}

$books = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Biblioteca - Usuario</title>
    <link rel="stylesheet" href="../../styles/users/user_dashboard.css">
</head>
<body>
    <header>
        <h1>Bienvenido, <?= htmlspecialchars($_SESSION['nombre']) ?></h1>
        <div class="user-menu">
            <span><?= htmlspecialchars($_SESSION['nombre']) ?> ▼</span>
            <ul class="dropdown">
                <li><a href="../../index.php">Cerrar sesión</a></li>
            </ul>
        </div>
    </header>
    
    <section>
        <form method="GET" action="user_dashboard.php">
            <input type="text" name="search" placeholder="Buscar libros..." value="<?= htmlspecialchars($search_query) ?>">
            <button type="submit">Buscar</button>
        </form>
    </section>

    <section class="book-list">
        <h2>Libros disponibles</h2>
        <?php if ($books): ?>
            <ul>
                <?php foreach ($books as $book): ?>
                    <li>
                        <h3><?= htmlspecialchars($book['nombre']) ?></h3>
                        <p>Autor: <?= htmlspecialchars($book['autor']) ?></p>
                        <p>Editorial: <?= htmlspecialchars($book['editorial']) ?></p>
                        <p>Cantidad disponible: <?= htmlspecialchars($book['cantidad']) ?></p>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php else: ?>
            <p>No se encontraron libros.</p>
        <?php endif; ?>
    </section>
</body>
</html>
