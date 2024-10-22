<?php
session_start();
include('../../config/config.php');

if (!isset($_SESSION['user_id']) || $_SESSION['rol'] !== 'usuario') {
    header('Location: ../../index.php');
    exit();
}

$usuario_id = $_SESSION['user_id'];
$success_message = '';
$error_message = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['cancel_reserva'])) {
    $reserva_id = $_POST['reserva_id'];
    $libro_id = $_POST['libro_id'];

    $query_cancel_reserva = "DELETE FROM Reservas WHERE id = :reserva_id AND usuario_id = :usuario_id";
    $stmt_cancel = $pdo->prepare($query_cancel_reserva);
    $stmt_cancel->execute(['reserva_id' => $reserva_id, 'usuario_id' => $usuario_id]);

    if ($stmt_cancel->rowCount() > 0) {
        $query_update_libro = "UPDATE Libros SET cantidad = cantidad + 1 WHERE id = :libro_id";
        $stmt_update_libro = $pdo->prepare($query_update_libro);
        $stmt_update_libro->execute(['libro_id' => $libro_id]);

        $success_message = 'Reserva cancelada con éxito.';
    } else {
        $error_message = 'No se pudo cancelar la reserva. Intenta nuevamente.';
    }
}

$query_reservas = "SELECT r.id AS reserva_id, l.id AS libro_id, l.nombre AS libro_nombre, r.fecha_reserva 
                   FROM Reservas r
                   JOIN Libros l ON r.libro_id = l.id
                   WHERE r.usuario_id = :usuario_id AND r.fecha_recepcion IS NULL AND r.fecha_devolucion IS NULL";
$stmt_reservas = $pdo->prepare($query_reservas);
$stmt_reservas->execute(['usuario_id' => $usuario_id]);
$reservas = $stmt_reservas->fetchAll();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mis Reservas</title>
    <link rel="stylesheet" href="../../styles/users/view_reservations.css">
</head>
<body>
    <header>
        <h1>Mis Reservas</h1>
        <div class="user-menu">
            <span><?= htmlspecialchars($_SESSION['nombre']) ?></span>
            <a href="user_dashboard.php" class="dashboard-link">Volver a Mi Panel</a>
            <a href="../../config/logout.php" class="logout-button">Cerrar sesión</a>
        </div>
    </header>

    <section class="reservations-section">
        <?php if ($success_message): ?>
            <p class="success-message"><?= htmlspecialchars($success_message) ?></p>
        <?php elseif ($error_message): ?>
            <p class="error-message"><?= htmlspecialchars($error_message) ?></p>
        <?php endif; ?>

        <h2>Mis Reservas Actuales</h2>

        <?php if (!empty($reservas)): ?>
            <table>
                <thead>
                    <tr>
                        <th>Libro</th>
                        <th>Fecha de Reserva</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($reservas as $reserva): ?>
                        <tr>
                            <td><?= htmlspecialchars($reserva['libro_nombre']) ?></td>
                            <td><?= htmlspecialchars($reserva['fecha_reserva']) ?></td>
                            <td>
                                <form action="view_reservations.php" method="POST">
                                    <input type="hidden" name="reserva_id" value="<?= htmlspecialchars($reserva['reserva_id']) ?>">
                                    <input type="hidden" name="libro_id" value="<?= htmlspecialchars($reserva['libro_id']) ?>">
                                    <button type="submit" name="cancel_reserva" class="cancel-button">Cancelar Reserva</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p>No tienes reservas activas.</p>
        <?php endif; ?>
    </section>
</body>
</html>
