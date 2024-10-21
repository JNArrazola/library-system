<?php
session_start();
include('../../config/config.php');

// Verificar si el usuario ha iniciado sesión y es bibliotecario
if (!isset($_SESSION['user_id']) || $_SESSION['rol'] !== 'bibliotecario') {
    header('Location: ../../index.php');
    exit();
}

$error_message = '';
$success_message = '';
$usuario_id = null;

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['usuario_id'])) {
    $usuario_id = $_POST['usuario_id'];

    $query = "SELECT r.id AS reserva_id, u.id AS usuario_id, u.nombre, u.apellido, l.nombre AS libro_nombre, 
                     r.fecha_reserva, r.fecha_recepcion, r.fecha_devolucion
              FROM Reservas r
              JOIN Usuarios u ON r.usuario_id = u.id
              JOIN Libros l ON r.libro_id = l.id
              WHERE u.id = :usuario_id AND r.fecha_recepcion IS NULL AND r.fecha_devolucion IS NULL"; // Filtrar reservas sin recepción y devolución
    $stmt = $pdo->prepare($query);
    $stmt->execute(['usuario_id' => $usuario_id]);
    $reservas = $stmt->fetchAll();
}

// Obtener todos los usuarios que sean 'usuario' y estén activos
$query = "SELECT id, nombre, apellido, correo FROM Usuarios WHERE is_active = 1 AND rol = 'usuario'";
$stmt = $pdo->query($query);
$usuarios = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ver Reservas</title>
    <link rel="stylesheet" href="../../styles/librarians/view_reservations.css">
</head>
<body>
    <header>
        <h1>Ver Reservas</h1>
        <div class="user-menu">
            <span><?= htmlspecialchars($_SESSION['nombre']) ?> (Bibliotecario)</span>
            <a href="../main_dashboard.php" class="catalog-link">Volver al Catálogo</a>
            <a href="../logout.php" class="logout-button">Cerrar sesión</a>
        </div>
    </header>

    <section class="reservation-view">
        <?php if ($error_message): ?>
            <p class="error"><?= $error_message ?></p>
        <?php elseif ($success_message): ?>
            <p class="success"><?= $success_message ?></p>
        <?php endif; ?>

        <h2>Buscar Reservas por Usuario</h2>

        <form action="view_reservations.php" method="POST">
            <div class="form-group">
                <label for="usuario_id">Buscar Usuario:</label>
                <select name="usuario_id" id="usuario_id" required>
                    <option value="">Selecciona un usuario</option>
                    <?php foreach ($usuarios as $usuario): ?>
                        <option value="<?= htmlspecialchars($usuario['id']) ?>" <?= ($usuario_id == $usuario['id']) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($usuario['id'] . ' - ' . $usuario['nombre'] . ' ' . $usuario['apellido'] . ' (' . $usuario['correo'] . ')') ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <button type="submit">Buscar Reservas</button>
        </form>

        <?php if (!empty($reservas)): ?>
            <h2>Reservas del Usuario</h2>
            <table>
                <thead>
                    <tr>
                        <th>ID Usuario</th>
                        <th>Nombre</th>
                        <th>Libro</th>
                        <th>Fecha de Reserva</th>
                        <th>Fecha de Recepción</th>
                        <th>Fecha de Devolución</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($reservas as $reserva): ?>
                        <tr>
                            <td><?= htmlspecialchars($reserva['usuario_id']) ?></td>
                            <td><?= htmlspecialchars($reserva['nombre'] . ' ' . $reserva['apellido']) ?></td>
                            <td><?= htmlspecialchars($reserva['libro_nombre']) ?></td>
                            <td><?= htmlspecialchars($reserva['fecha_reserva']) ?></td>
                            <td><?= ($reserva['fecha_recepcion']) ? htmlspecialchars($reserva['fecha_recepcion']) : 'N/A' ?></td>
                            <td><?= ($reserva['fecha_devolucion']) ? htmlspecialchars($reserva['fecha_devolucion']) : 'N/A' ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php elseif ($usuario_id): ?>
            <p class="no-results">No se encontraron reservas para este usuario.</p>
        <?php endif; ?>
    </section>
</body>
</html>
