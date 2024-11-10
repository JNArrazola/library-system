<?php
session_start();
include('../../config/config.php');

if (!isset($_SESSION['user_id']) || $_SESSION['rol'] !== 'bibliotecario') {
    header('Location: ../../index.php');
    exit();
}

$success_message = '';
$error_message = '';
$max_file_size = 2 * 1024 * 1024;

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['delete_book_id'])) {
        $delete_book_id = $_POST['delete_book_id'];
        $query = "DELETE FROM Libros WHERE id = :id";
        $stmt = $pdo->prepare($query);
        $stmt->execute(['id' => $delete_book_id]);
        $success_message = 'Libro eliminado exitosamente.';
    } elseif (isset($_POST['books'])) {
    foreach ($_POST['books'] as $book_id => $book) {
        $autor = $book['autor'];
        $cantidad = $book['cantidad'];
        $editorial = $book['editorial'];
        $sinopsis = $book['sinopsis'];

        $image_query_part = '';

        if (isset($_FILES['books']['name'][$book_id]['imagen']) && $_FILES['books']['error'][$book_id]['imagen'] == UPLOAD_ERR_OK) {
            if ($_FILES['books']['size'][$book_id]['imagen'] > $max_file_size) {
                $error_message = 'La imagen es demasiado grande. El tamaño máximo permitido es de 2MB.';
            } else {
                $image_data = file_get_contents($_FILES['books']['tmp_name'][$book_id]['imagen']);
                $image_query_part = ', imagen = :imagen';
            }
        }

        if (!$error_message) {
            $query = "UPDATE Libros SET autor = :autor, cantidad = :cantidad, editorial = :editorial, sinopsis = :sinopsis $image_query_part WHERE id = :id";
            $stmt = $pdo->prepare($query);

            $params = [
                'id' => $book_id,
                'autor' => $autor,
                'cantidad' => $cantidad,
                'editorial' => $editorial,
                'sinopsis' => $sinopsis
            ];

            if ($image_query_part) {
                $params['imagen'] = $image_data;
            }

            $stmt->execute($params);
        }
    }
    if (!$error_message) {
        $success_message = 'Libros actualizados exitosamente.';
    }
    }
}

$query = "SELECT * FROM Libros";
$stmt = $pdo->query($query);
$books = $stmt->fetchAll();
?>


<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Administrar Inventario de Libros</title>
    <link rel="stylesheet" href="../../styles/librarians/manage_books.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body>
    <header>
        <h1>Administrar Inventario de Libros</h1>
        <div class="user-menu">
            <span><?= htmlspecialchars($_SESSION['nombre']) ?> (Bibliotecario)</span>
            <a href="../catalog.php" class="catalog-link">Volver al Catálogo</a>
            <a href="../../config/logout.php" class="logout-button">Cerrar sesión</a>
        </div>
    </header>

    <section class="book-management">
        <form action="manage_books.php" method="POST" enctype="multipart/form-data">
            <table class="styled-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Imagen</th>
                        <th>Título</th>
                        <th>Autor</th>
                        <th>Editorial</th>
                        <th>Cantidad</th>
                        <th>Sinopsis</th>
                        <th>Actualizar Imagen</th>
                        <th>Eliminar</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($books as $book): ?>
                        <tr>
                            <td><?= htmlspecialchars($book['id']) ?></td>
                            <td><img src="data:image/jpeg;base64,<?= base64_encode($book['imagen']) ?>" alt="<?= htmlspecialchars($book['nombre']) ?>" class="book-image"></td>
                            <td><?= htmlspecialchars($book['nombre']) ?></td>
                            <td>
                                <input type="text" name="books[<?= htmlspecialchars($book['id']) ?>][autor]" class="custom-input" value="<?= htmlspecialchars($book['autor']) ?>">
                            </td>
                            <td><input type="text" name="books[<?= htmlspecialchars($book['id']) ?>][editorial]" class="custom-input" value="<?= htmlspecialchars($book['editorial']) ?>"></td>
                            <td><input type="number" name="books[<?= htmlspecialchars($book['id']) ?>][cantidad]" class="custom-input" value="<?= htmlspecialchars($book['cantidad']) ?>" min="0"></td>
                            <td><textarea name="books[<?= htmlspecialchars($book['id']) ?>][sinopsis]" class="custom-input"><?= htmlspecialchars($book['sinopsis']) ?></textarea></td>
                            <td>
                                <label for="imagen">Imagen:</label>
                                <input type="file" name="books[<?= htmlspecialchars($book['id']) ?>][imagen]" class="custom-input" accept="image/*">
                            </td>
                            <td>
                                <button type="submit" name="delete_book_id" value="<?= htmlspecialchars($book['id']) ?>" class="delete-button" onclick="return confirm('¿Estás seguro de que deseas eliminar este libro?');">Eliminar</button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <br>
            <button type="submit" class="add-book-button">Aplicar cambios</button>
        </form>
        <br>
        <div class="add-book">
            <a href="add_book.php" class="add-book-button">Registrar Nuevo Libro</a>
        </div>
    </section>

    <script>
        <?php if ($success_message): ?>
            Swal.fire({
                icon: 'success',
                title: 'Éxito',
                text: '<?= $success_message ?>',
                confirmButtonText: 'Aceptar'
            });
        <?php elseif ($error_message): ?>
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: '<?= $error_message ?>',
                confirmButtonText: 'Aceptar'
            });
        <?php endif; ?>
    </script>
</body>
</html>