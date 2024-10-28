<?php
session_start();
include('../../config/config.php');

if (!isset($_SESSION['user_id']) || $_SESSION['rol'] !== 'bibliotecario') {
    header('Location: ../../index.php');
    exit();
}

$error_message = '';
$success_message = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['reserva_id'])) {
    $reserva_id = $_POST['reserva_id'];
    $fecha_actual = date('Y-m-d');

    $query = "UPDATE Reservas SET fecha_devolucion = :fecha_devolucion, B_Entregado = 1 WHERE id = :reserva_id";
    $stmt = $pdo->prepare($query);
    $stmt->execute([
        'fecha_devolucion' => $fecha_actual,
        'reserva_id' => $reserva_id
    ]);

    $query = "SELECT libro_id FROM Reservas WHERE id = :reserva_id";
    $stmt = $pdo->prepare($query);
    $stmt->execute(['reserva_id' => $reserva_id]);
    $libro = $stmt->fetch();

    $query = "UPDATE Libros SET cantidad = cantidad + 1 WHERE id = :libro_id";
    $stmt = $pdo->prepare($query);
    $stmt->execute(['libro_id' => $libro['libro_id']]);

    $success_message = 'Devolución registrada exitosamente.';
}

$query = "SELECT id, nombre, apellido FROM Usuarios WHERE is_active = 1 AND rol = 'usuario'";
$stmt = $pdo->query($query);
$usuarios = $stmt->fetchAll();

$pendientes = [];
if ($_SERVER['REQUEST_METHOD'] == 'GET' && !empty($_GET['usuario_id'])) {
    $usuario_id = $_GET['usuario_id'];

    $query = "SELECT r.id, l.nombre AS libro_nombre, r.fecha_recepcion, u.nombre AS usuario_nombre, u.apellido AS usuario_apellido 
              FROM Reservas r
              JOIN Libros l ON r.libro_id = l.id
              JOIN Usuarios u ON r.usuario_id = u.id
              WHERE r.usuario_id = :usuario_id AND r.B_Entregado = 0 AND r.fecha_recepcion IS NOT NULL";
    $stmt = $pdo->prepare($query);
    $stmt->execute(['usuario_id' => $usuario_id]);
    $pendientes = $stmt->fetchAll();
} elseif ($_SERVER['REQUEST_METHOD'] == 'GET' && empty($_GET['usuario_id'])) {
    // Muestra todos los préstamos pendientes si la búsqueda está vacía
    $query = "SELECT r.id, l.nombre AS libro_nombre, r.fecha_recepcion, u.nombre AS usuario_nombre, u.apellido AS usuario_apellido 
              FROM Reservas r
              JOIN Libros l ON r.libro_id = l.id
              JOIN Usuarios u ON r.usuario_id = u.id
              WHERE r.B_Entregado = 0 AND r.fecha_recepcion IS NOT NULL";
    $stmt = $pdo->query($query);
    $pendientes = $stmt->fetchAll();
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registrar Devolución</title>
    <link rel="stylesheet" href="../../styles/librarians/return_loan.css?v=<?= time(); ?>">
</head>
<body>
    <header>
        <h1>Registrar Devolución</h1>
        <div class="user-menu">
            <span><?= htmlspecialchars($_SESSION['nombre']) ?> (Bibliotecario)</span>
            <a href="../catalog.php" class="catalog-link">Volver al Catálogo</a>
            <a href="../logout.php" class="logout-button">Cerrar sesión</a>
        </div>
    </header>

    <section class="return-form">
        <?php if ($error_message): ?>
            <p class="error"><?= $error_message ?></p>
        <?php elseif ($success_message): ?>
            <p class="success"><?= $success_message ?></p>
        <?php endif; ?>

        <form action="return_loan.php" method="GET">
            <div class="form-group">
                <label for="usuario_id">Buscar Usuario:</label>
                <select name="usuario_id" id="usuario_id" required>
                    <option value="">Selecciona un usuario</option>
                    <?php foreach ($usuarios as $usuario): ?>
                        <option value="<?= htmlspecialchars($usuario['id']) ?>">
                            <?= htmlspecialchars($usuario['nombre'] . ' ' . $usuario['apellido']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <button type="submit">Buscar Préstamos Pendientes</button>
        </form>

        <?php if (!empty($pendientes)): ?>
            <h2>Préstamos Pendientes de Devolución</h2>
            <table>
                <thead>
                    <tr>
                        <th>Libro</th>
                        <th>Usuario</th>
                        <th>Fecha de Préstamo</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($pendientes as $pendiente): ?>
                        <tr>
                            <td><?= htmlspecialchars($pendiente['libro_nombre']) ?></td>
                            <td><?= htmlspecialchars($pendiente['usuario_nombre'] . ' ' . $pendiente['usuario_apellido']) ?></td>
                            <td><?= htmlspecialchars($pendiente['fecha_recepcion']) ?></td>
                            <td>
                                <form action="return_loan.php" method="POST">
                                    <input type="hidden" name="reserva_id" value="<?= htmlspecialchars($pendiente['id']) ?>">
                                    <button type="submit" onclick="return confirm('¿Confirmar devolución de este libro?')">Confirmar Devolución</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </section>
</body>
</html>
