<?php
session_start();
include('../../config/config.php');

if (!isset($_SESSION['user_id']) || $_SESSION['rol'] !== 'administrador') {
    header('Location: ../index.php');
    exit();
}

// Procesamiento de la aprobación o rechazo de solicitud
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['solicitud_id'])) {
    $solicitud_id = $_POST['solicitud_id'];
    $accion = $_POST['accion'];

    $nuevo_estado = ($accion === 'aprobar') ? 'aprobado' : 'rechazado';

    $query = "UPDATE Solicitudes SET estado = :estado WHERE id = :id";
    $stmt = $pdo->prepare($query);
    $stmt->execute(['estado' => $nuevo_estado, 'id' => $solicitud_id]);
}

// Barra de búsqueda: capturando y aplicando el término de búsqueda
$search = isset($_GET['search']) ? $_GET['search'] : '';

try {
    $query = "SELECT s.*, u.nombre AS usuario_nombre, u.apellido AS usuario_apellido 
              FROM Solicitudes s 
              JOIN Usuarios u ON s.solicitante_id = u.id 
              WHERE s.estado = 'pendiente' 
              AND (u.nombre LIKE :search OR u.apellido LIKE :search OR s.tipo LIKE :search OR DATE(s.fecha_solicitud) LIKE :search)";
    $stmt = $pdo->prepare($query);
    $stmt->execute(['search' => '%' . $search . '%']);
    $solicitudes = $stmt->fetchAll();
} catch (PDOException $e) {
    echo "Error en la búsqueda: " . $e->getMessage();
}

?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Solicitudes</title>
    <link rel="stylesheet" href="../../styles/manage_users.css">
</head>
<body>
    <header>
        <h1>Gestión de Solicitudes</h1>
        <div class="user-menu">
            <span><?= htmlspecialchars($_SESSION['nombre']) ?> (Administrador)</span>
            <a href="../catalog.php" class="catalog-link">Volver al Catálogo</a>
            <a href="../logout.php" class="logout-button">Cerrar sesión</a>
        </div>
    </header>

    <section class="requests-management">
        <!-- Barra de búsqueda -->
        <form method="GET" class="search-form">
            <input type="text" name="search" placeholder="Buscar solicitudes..." value="<?= htmlspecialchars($search) ?>" class="search-input">
            <button type="submit" class="search-button">Buscar</button>
        </form>

        <!-- Tabla de solicitudes -->
        <table class="styled-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Solicitante</th>
                    <th>Tipo</th>
                    <th>Fecha de Solicitud</th>
                    <th>Detalles del Cambio</th>
                    <th>Acción</th>
                </tr>
            </thead>
            <tbody>
                <?php if (count($solicitudes) > 0): ?>
                    <?php foreach ($solicitudes as $solicitud): ?>
                        <tr>
                            <td><?= htmlspecialchars($solicitud['id']) ?></td>
                            <td><?= htmlspecialchars($solicitud['usuario_nombre'] . ' ' . $solicitud['usuario_apellido']) ?></td>
                            <td><?= htmlspecialchars($solicitud['tipo']) ?></td>
                            <td><?= htmlspecialchars($solicitud['fecha_solicitud']) ?></td>
                            <td>
                                <?php 
                                // Decodificar el JSON y mostrarlo de forma más legible
                                $detalles_cambio = json_decode($solicitud['detalles_cambio'], true);
                                if (is_array($detalles_cambio)) {
                                    echo '<ul>';
                                    foreach ($detalles_cambio as $clave => $valor) {
                                        echo '<li>' . htmlspecialchars($clave) . ': ' . htmlspecialchars($valor) . '</li>';
                                    }
                                    echo '</ul>';
                                } else {
                                    echo 'Sin detalles disponibles';
                                }
                                ?>
                            </td>
                            <td>
                                <form action="manage_requests.php" method="POST" class="action-form">
                                    <input type="hidden" name="solicitud_id" value="<?= htmlspecialchars($solicitud['id']) ?>">
                                    <button type="submit" name="accion" value="aprobar" class="approve-button">Aprobar</button>
                                    <button type="submit" name="accion" value="rechazar" class="reject-button">Rechazar</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="6">No se encontraron solicitudes pendientes.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </section>
</body>
</html>
