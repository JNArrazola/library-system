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

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['book_id'])) {
    $book_id = $_POST['book_id'];
    $autor = $_POST['autor'];
    $cantidad = $_POST['cantidad'];
    $editorial = $_POST['editorial'];
    $sinopsis = $_POST['sinopsis'];

    $image_query_part = '';

    if (isset($_FILES['imagen']) && $_FILES['imagen']['error'] == UPLOAD_ERR_OK) {
        if ($_FILES['imagen']['size'] > $max_file_size) {
            $error_message = 'La imagen es demasiado grande. El tamaño máximo permitido es de 2MB.';
        } else {
            $image_data = file_get_contents($_FILES['imagen']['tmp_name']);
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
        $success_message = 'Libro actualizado exitosamente.';
    }
}

if (isset($_POST['delete_book_id'])) {
    $delete_book_id = $_POST['delete_book_id'];

    $query = "SELECT COUNT(*) FROM Reservas WHERE libro_id = :libro_id AND B_Entregado = 0";
    $stmt = $pdo->prepare($query);
    $stmt->execute(['libro_id' => $delete_book_id]);
    $active_loans = $stmt->fetchColumn();

    if ($active_loans == 0) {
        $query = "DELETE FROM Libros WHERE id = :id";
        $stmt = $pdo->prepare($query);
        $stmt->execute(['id' => $delete_book_id]);
        $success_message = 'Libro eliminado exitosamente.';
    } else {
        $error_message = 'No se puede borrar el libro porque tiene préstamos activos';
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
            <a href="../logout.php" class="logout-button">Cerrar sesión</a>
        </div>
    </header>

    <section class="book-management">
        <div class="add-book">
            <a href="add_book.php" class="add-book-button">Registrar Nuevo Libro</a>
        </div>

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
                    <th>Actualizar Detalles</th>
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
                            <form action="manage_books.php" method="POST" enctype="multipart/form-data">
                                <input type="text" name="autor" class="custom-input" value="<?= htmlspecialchars($book['autor']) ?>">
                        </td>
                        <td><input type="text" name="editorial" class="custom-input" value="<?= htmlspecialchars($book['editorial']) ?>"></td>
                        <td><input type="number" name="cantidad" class="custom-input" value="<?= htmlspecialchars($book['cantidad']) ?>" min="0"></td>
                        <td><textarea name="sinopsis"><?= htmlspecialchars($book['sinopsis']) ?></textarea></td>
                        <td>
                            <label for="imagen">Imagen:</label>
                            <input type="file" name="imagen" class="custom-input" accept="image/*">
                        </td>
                        <td>
                            <input type="hidden" name="book_id" value="<?= htmlspecialchars($book['id']) ?>">
                            <button type="submit" class="update-button">Actualizar</button>
                            </form>
                        </td>
                        <td>
                            <form action="manage_books.php" method="POST" onsubmit="return confirm('¿Estás seguro de que deseas borrar este libro?')">
                                <input type="hidden" name="delete_book_id" value="<?= htmlspecialchars($book['id']) ?>">
                                <button type="submit" class="delete-button">Eliminar</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
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
