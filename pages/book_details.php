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
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body>
    <header>
        <h1>Detalles del Libro</h1>
        <div class="user-menu">
            <?= $nav_options ?>
        </div>
    </header>

    <div class="back-button-container">
        <a href="catalog.php" class="back-button">Volver al catálogo</a>
    </div>

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

            <?php if (isset($_SESSION['rol']) && $_SESSION['rol'] === 'usuario' && $book['cantidad'] > 0): ?>
                <button class="reserve-button" onclick="confirmReservation('<?= htmlspecialchars($book['id']) ?>', '<?= htmlspecialchars($book['nombre']) ?>')">Reservar</button>
            <?php endif; ?>
        </div>
    </div>

    <footer>
        <p>Biblioteca - 2024</p>
    </footer>

    <script>
        function confirmReservation(bookId, bookName) {
            Swal.fire({
                title: `¿Estás seguro que deseas reservar el libro "${bookName}"?`,
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: 'Reservar',
                cancelButtonText: 'Cancelar'
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = `users/reserve_book.php?id=${bookId}`;
                }
            });
        }

        <?php if ($success_message): ?>
            Swal.fire({
                icon: 'success',
                title: 'Éxito',
                text: '<?= htmlspecialchars($success_message) ?>',
                confirmButtonText: 'Aceptar'
            });
        <?php elseif ($error_message): ?>
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: '<?= htmlspecialchars($error_message) ?>',
                confirmButtonText: 'Aceptar'
            });
        <?php endif; ?>
    </script>
</body>
</html>
