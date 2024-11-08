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

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['confirm_cancel'])) {
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
    <link rel="stylesheet" href="../../styles/users/view_reservations.css?v=<?php echo time(); ?>">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
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
        <h2>Mis Reservas Actuales</h2>

        <?php if ($success_message): ?>
            <script>
                Swal.fire({
                    icon: 'success',
                    title: 'Reserva Cancelada',
                    text: '<?= $success_message ?>',
                    confirmButtonText: 'Aceptar'
                });
            </script>
        <?php elseif ($error_message): ?>
            <script>
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: '<?= $error_message ?>',
                    confirmButtonText: 'Aceptar'
                });
            </script>
        <?php endif; ?>

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
                                <button onclick="confirmCancel(<?= htmlspecialchars($reserva['reserva_id']) ?>, <?= htmlspecialchars($reserva['libro_id']) ?>)" class="cancel-button">Cancelar Reserva</button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p>No tienes reservas activas.</p>
        <?php endif; ?>
    </section>

    <form id="cancelForm" action="view_reservations.php" method="POST" style="display:none;">
        <input type="hidden" name="reserva_id" id="reserva_id">
        <input type="hidden" name="libro_id" id="libro_id">
        <input type="hidden" name="confirm_cancel" value="true">
    </form>

    <script>
        function confirmCancel(reservaId, libroId) {
            Swal.fire({
                title: '¿Estás seguro?',
                text: "¿Deseas cancelar esta reserva?",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'Sí, cancelar',
                cancelButtonText: 'No, volver'
            }).then((result) => {
                if (result.isConfirmed) {
                    document.getElementById('reserva_id').value = reservaId;
                    document.getElementById('libro_id').value = libroId;
                    document.getElementById('cancelForm').submit();
                }
            });
        }
    </script>
</body>
</html>
