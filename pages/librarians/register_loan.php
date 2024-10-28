<?php
session_start();
include('../../config/config.php');

if (!isset($_SESSION['user_id']) || $_SESSION['rol'] !== 'bibliotecario') {
    header('Location: ../../index.php');
    exit();
}

$error_message = '';
$success_message = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $usuario_id = $_POST['usuario_id'];
    $libro_id = $_POST['libro_id'];
    $fecha_recepcion = $_POST['fecha_recepcion'];
    $fecha_devolucion = $_POST['fecha_devolucion'];

    if (strtotime($fecha_devolucion) <= strtotime($fecha_recepcion)) {
        $error_message = 'La fecha de devolución debe ser posterior a la fecha de préstamo.';
    } else {
        $query = "SELECT cantidad FROM Libros WHERE id = :libro_id";
        $stmt = $pdo->prepare($query);
        $stmt->execute(['libro_id' => $libro_id]);
        $libro = $stmt->fetch();

        if ($libro && $libro['cantidad'] > 0) {
            $query = "INSERT INTO Reservas (usuario_id, libro_id, fecha_recepcion, fecha_devolucion, B_Entregado) 
                      VALUES (:usuario_id, :libro_id, :fecha_recepcion, :fecha_devolucion, 0)";
            $stmt = $pdo->prepare($query);
            $stmt->execute([
                'usuario_id' => $usuario_id,
                'libro_id' => $libro_id,
                'fecha_recepcion' => $fecha_recepcion,
                'fecha_devolucion' => $fecha_devolucion
            ]);

            $query = "UPDATE Libros SET cantidad = cantidad - 1 WHERE id = :libro_id";
            $stmt = $pdo->prepare($query);
            $stmt->execute(['libro_id' => $libro_id]);

            $success_message = 'Préstamo registrado exitosamente.';
        } else {
            $error_message = 'No hay suficientes copias del libro disponibles.';
        }
    }
}

$query = "SELECT id, nombre, apellido FROM Usuarios WHERE is_active = 1 AND rol = 'usuario'";
$stmt = $pdo->query($query);
$usuarios = $stmt->fetchAll();

$query = "SELECT id, nombre FROM Libros WHERE cantidad > 0";
$stmt = $pdo->query($query);
$libros = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registrar Préstamo</title>
    <link rel="stylesheet" href="../../styles/librarians/register_loan.css?v=<?php echo time(); ?>"> <!-- Evita el caché -->
</head>
<body>
    <header>
        <h1>Registrar Préstamo</h1>
        <div class="user-menu">
            <span><?= htmlspecialchars($_SESSION['nombre']) ?> (Bibliotecario)</span>
            <a href="../catalog.php" class="catalog-link">Volver al Catálogo</a>
            <a href="../logout.php" class="logout-button">Cerrar sesión</a>
        </div>
    </header>

    <section class="loan-form">
        <?php if ($error_message): ?>
            <p class="error"><?= htmlspecialchars($error_message) ?></p>
        <?php elseif ($success_message): ?>
            <p class="success"><?= htmlspecialchars($success_message) ?></p>
        <?php endif; ?>

        <form action="register_loan.php" method="POST">
            <div class="form-group">
                <label for="usuario_id">Usuario:</label>
                <select name="usuario_id" id="usuario_id" class="custom-input" required>
                    <option value="">Selecciona un usuario</option>
                    <?php foreach ($usuarios as $usuario): ?>
                        <option value="<?= htmlspecialchars($usuario['id']) ?>">
                            <?= htmlspecialchars($usuario['nombre'] . ' ' . $usuario['apellido']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label for="libro_id">Libro:</label>
                <select name="libro_id" id="libro_id" class="custom-input" required>
                    <option value="">Selecciona un libro</option>
                    <?php foreach ($libros as $libro): ?>
                        <option value="<?= htmlspecialchars($libro['id']) ?>">
                            <?= htmlspecialchars($libro['nombre']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label for="fecha_recepcion">Fecha de Préstamo:</label>
                <input type="date" name="fecha_recepcion" id="fecha_recepcion" class="custom-input" required>
            </div>
            <div class="form-group">
                <label for="fecha_devolucion">Fecha de Devolución:</label>
                <input type="date" name="fecha_devolucion" id="fecha_devolucion" class="custom-input" required>
            </div>
            <button type="submit">Registrar Préstamo</button>
        </form>
    </section>
</body>
</html>