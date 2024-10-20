<?php
session_start();
include('../../config/config.php');

if (!isset($_SESSION['user_id']) || $_SESSION['rol'] !== 'bibliotecario') {
    header('Location: ../../index.php');
    exit();
}

$error_message = '';
$success_message = '';

$max_file_size = 2 * 1024 * 1024; 

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nombre = $_POST['nombre'];
    $autor = $_POST['autor'];
    $editorial = $_POST['editorial'];
    $cantidad = $_POST['cantidad'];
    $sinopsis = $_POST['sinopsis'];

    if (isset($_FILES['imagen']) && $_FILES['imagen']['error'] == UPLOAD_ERR_OK) {
        if ($_FILES['imagen']['size'] > $max_file_size) {
            $error_message = 'La imagen excede el tamaño máximo permitido de 2 MB.';
        } else {
            $imagen_data = file_get_contents($_FILES['imagen']['tmp_name']);
        }
    } else {
        $error_message = 'Debes subir una imagen válida para registrar el libro.';
    }

    if (!$error_message) {
        $query = "INSERT INTO Libros (nombre, autor, editorial, cantidad, sinopsis, imagen) 
                  VALUES (:nombre, :autor, :editorial, :cantidad, :sinopsis, :imagen)";
        $stmt = $pdo->prepare($query);

        $stmt->execute([
            'nombre' => $nombre,
            'autor' => $autor,
            'editorial' => $editorial,
            'cantidad' => $cantidad,
            'sinopsis' => $sinopsis,
            'imagen' => $imagen_data
        ]);

        if ($stmt) {
            $success_message = 'Libro registrado exitosamente.';
        } else {
            $error_message = 'Error al registrar el libro.';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registrar Nuevo Libro</title>
    <link rel="stylesheet" href="../../styles/librarians/add_book.css">
</head>
<body>
    <header>
        <h1>Registrar Nuevo Libro</h1>
        <div class="user-menu">
            <span><?= htmlspecialchars($_SESSION['nombre']) ?> (Bibliotecario)</span>
            <a href="../main_dashboard.php" class="catalog-link">Volver al Catálogo</a>
            <a href="../logout.php" class="logout-button">Cerrar sesión</a>
        </div>
    </header>

    <section class="add-book-form">
        <?php if ($error_message): ?>
            <p class="error"><?= $error_message ?></p>
        <?php elseif ($success_message): ?>
            <p class="success"><?= $success_message ?></p>
        <?php endif; ?>

        <form action="add_book.php" method="POST" enctype="multipart/form-data">
            <div class="form-group">
                <label for="nombre">Título:</label>
                <input type="text" name="nombre" id="nombre" required>
            </div>
            <div class="form-group">
                <label for="autor">Autor:</label>
                <input type="text" name="autor" id="autor" required>
            </div>
            <div class="form-group">
                <label for="editorial">Editorial:</label>
                <input type="text" name="editorial" id="editorial" required>
            </div>
            <div class="form-group">
                <label for="cantidad">Cantidad:</label>
                <input type="number" name="cantidad" id="cantidad" min="1" required>
            </div>
            <div class="form-group">
                <label for="sinopsis">Sinopsis:</label>
                <textarea name="sinopsis" id="sinopsis" rows="4" required></textarea>
            </div>
            <div class="form-group">
                <label for="imagen">Subir Imagen (máximo 2 MB):</label>
                <input type="file" name="imagen" id="imagen" accept="image/*" required>
            </div>
            <button type="submit">Registrar Libro</button>
        </form>
    </section>
</body>
</html>
