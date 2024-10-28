<?php
session_start();
include('../../config/config.php');

if (!isset($_SESSION['user_id']) || $_SESSION['rol'] !== 'usuario') {
    header('Location: ../../index.php');
    exit();
}

$usuario_id = $_SESSION['user_id'];

$query_prestamos = "SELECT r.id AS reserva_id, l.nombre AS libro_nombre, r.fecha_recepcion, r.fecha_devolucion
                    FROM Reservas r
                    JOIN Libros l ON r.libro_id = l.id
                    WHERE r.usuario_id = :usuario_id AND r.fecha_recepcion IS NOT NULL AND r.B_Entregado = 0";
$stmt_prestamos = $pdo->prepare($query_prestamos);
$stmt_prestamos->execute(['usuario_id' => $usuario_id]);
$prestamos = $stmt_prestamos->fetchAll();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mis Préstamos</title>
    <link rel="stylesheet" href="../../styles/users/view_loans.css?v=<?php echo time(); ?>">
</head>
<body>
    <header>
        <h1>Mis Préstamos</h1>
        <div class="user-menu">
            <span><?= htmlspecialchars($_SESSION['nombre']) ?></span>
            <a href="user_dashboard.php" class="dashboard-link">Volver a Mi Panel</a>
            <a href="../config/logout.php" class="logout-button">Cerrar sesión</a>
        </div>
    </header>

    <section class="loans-section">
        <h2>Préstamos Activos</h2>

        <?php if (!empty($prestamos)): ?>
            <table>
                <thead>
                    <tr>
                        <th>Libro</th>
                        <th>Fecha de Inicio</th>
                        <th>Fecha de Devolución</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($prestamos as $prestamo): ?>
                        <tr>
                            <td><?= htmlspecialchars($prestamo['libro_nombre']) ?></td>
                            <td><?= htmlspecialchars($prestamo['fecha_recepcion']) ?></td>
                            <td><?= ($prestamo['fecha_devolucion']) ? htmlspecialchars($prestamo['fecha_devolucion']) : 'Pendiente' ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p>No tienes préstamos en curso.</p>
        <?php endif; ?>
    </section>
</body>
</html>
