<?php
session_start();
include('../../config/config.php');

if (!isset($_SESSION['user_id']) || $_SESSION['rol'] !== 'administrador') {
    header('Location: ../../index.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['solicitud_id'])) {
    $solicitud_id = $_POST['solicitud_id'];
    $accion = $_POST['accion'];
    $nuevo_estado = ($accion === 'aprobar') ? 'aprobado' : 'rechazado';

    $query = "UPDATE Solicitudes SET estado = :estado WHERE id = :id";
    $stmt = $pdo->prepare($query);
    $stmt->execute(['estado' => $nuevo_estado, 'id' => $solicitud_id]);
}

$search = isset($_GET['search']) ? $_GET['search'] : '';

try {
    $query = "SELECT s.*, 
                     u.nombre AS solicitante_nombre, u.apellido AS solicitante_apellido, u.correo AS solicitante_correo, u.id AS solicitante_id, u.rol AS solicitante_rol,
                     target.nombre AS objetivo_nombre, target.apellido AS objetivo_apellido, target.correo AS objetivo_correo, target.id AS objetivo_id, target.rol AS objetivo_rol 
              FROM Solicitudes s 
              JOIN Usuarios u ON s.solicitante_id = u.id 
              JOIN Usuarios target ON s.usuario_id = target.id 
              WHERE s.estado = 'pendiente' 
              AND (u.nombre LIKE :search OR u.apellido LIKE :search OR u.correo LIKE :search OR 
                   target.nombre LIKE :search OR target.apellido LIKE :search OR target.correo LIKE :search OR 
                   s.tipo LIKE :search OR DATE(s.fecha_solicitud) LIKE :search)";
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
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        .search-container {
            position: relative;
            width: 100%;
            max-width: 400px;
            margin: 20px 0;
        }
        .search-input {
            width: 100%;
            padding: 10px 15px;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-size: 1em;
            box-sizing: border-box;
        }
        .results-container {
            position: absolute;
            background-color: #f9f9f9;
            border: 1px solid #ddd;
            border-radius: 8px;
            box-shadow: 0 8px 16px rgba(0, 0, 0, 0.1);
            max-height: 200px;
            overflow-y: auto;
            width: 100%;
            z-index: 1000;
            display: none;
        }
        .result-item {
            padding: 10px;
            border-bottom: 1px solid #ddd;
            display: flex;
            flex-direction: column;
            cursor: pointer;
        }
        .result-item:hover {
            background-color: #f0f0f0;
        }
        .result-item h4 {
            margin: 0;
            font-size: 1em;
            color: #333;
        }
        .result-item p {
            margin: 2px 0;
            font-size: 0.9em;
            color: #666;
        }
    </style>
</head>
<body>
    <header>
        <h1>Gestión de Solicitudes</h1>
        <div class="user-menu">
            <span><?= htmlspecialchars($_SESSION['nombre']) ?> (Administrador)</span>
            <a href="../catalog.php" class="catalog-link">Volver al Catálogo</a>
            <a href="../../config/logout.php" class="logout-button">Cerrar sesión</a>
        </div>
    </header>

    <section class="requests-management">
        <h2>Buscar Solicitudes</h2>

        <div class="search-container">
            <input type="text" class="search-input" placeholder="Buscar solicitudes..." id="searchInput">
            <div class="results-container" id="resultsContainer">
                <?php foreach ($solicitudes as $solicitud): ?>
                    <div class="result-item" data-request-id="<?= htmlspecialchars($solicitud['id']) ?>" data-request-info="<?= htmlspecialchars($solicitud['objetivo_nombre'] . ' ' . $solicitud['objetivo_apellido']) ?>" onclick="selectRequest(<?= htmlspecialchars($solicitud['id']) ?>)">
                        <h4><?= htmlspecialchars($solicitud['objetivo_nombre'] . ' ' . $solicitud['objetivo_apellido']) ?></h4>
                        <p>Tipo: <?= htmlspecialchars($solicitud['tipo']) ?></p>
                        <p>Fecha: <?= htmlspecialchars($solicitud['fecha_solicitud']) ?></p>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <table class="styled-table" id="solicitudesTable">
            <thead>
                <tr>
                    <th>ID Solicitud</th>
                    <th>Solicitante</th>
                    <th>Objetivo</th>
                    <th>Tipo</th>
                    <th>Fecha de Solicitud</th>
                    <th>Detalles del Cambio</th>
                    <th>Acción</th>
                </tr>
            </thead>
            <tbody>
                <?php if (count($solicitudes) > 0): ?>
                    <?php foreach ($solicitudes as $solicitud): ?>
                        <tr id="solicitud_<?= $solicitud['id'] ?>">
                            <td><?= htmlspecialchars($solicitud['id']) ?></td>
                            <td>
                                ID: <?= htmlspecialchars($solicitud['solicitante_id']) ?><br>
                                Nombre: <?= htmlspecialchars($solicitud['solicitante_nombre'] . ' ' . $solicitud['solicitante_apellido']) ?><br>
                                Correo: <?= htmlspecialchars($solicitud['solicitante_correo']) ?><br>
                            </td>
                            <td>
                                ID: <?= htmlspecialchars($solicitud['objetivo_id']) ?><br>
                                Nombre: <?= htmlspecialchars($solicitud['objetivo_nombre'] . ' ' . $solicitud['objetivo_apellido']) ?><br>
                                Correo: <?= htmlspecialchars($solicitud['objetivo_correo']) ?><br>
                            </td>
                            <td><?= htmlspecialchars($solicitud['tipo']) ?></td>
                            <td><?= htmlspecialchars($solicitud['fecha_solicitud']) ?></td>
                            <td>
                                <?php 
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
                        <td colspan="7">No se encontraron solicitudes pendientes.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </section>

    <script>
        const searchInput = document.getElementById('searchInput');
        const resultsContainer = document.getElementById('resultsContainer');
        const tableRows = document.querySelectorAll('tr[id^="solicitud_"]');

        searchInput.addEventListener('input', function() {
            filterRequests(this.value);
            resultsContainer.style.display = this.value ? 'block' : 'none';
        });

        searchInput.addEventListener('keydown', function(event) {
            if (event.key === 'Enter') {
                event.preventDefault();
                filterTable(this.value);
                resultsContainer.style.display = 'none';
            }
        });

        function filterRequests(query) {
            const items = document.querySelectorAll('.result-item');
            let hasResults = false;

            query = query.toLowerCase();
            items.forEach(item => {
                const requestInfo = item.getAttribute('data-request-info').toLowerCase();
                if (requestInfo.includes(query)) {
                    item.style.display = 'block';
                    hasResults = true;
                } else {
                    item.style.display = 'none';
                }
            });
            resultsContainer.style.display = hasResults ? 'block' : 'none';
        }

        function selectRequest(requestId) {
            const selectedItem = document.querySelector(`.result-item[data-request-id="${requestId}"]`);
            searchInput.value = selectedItem.querySelector('h4').textContent;
            resultsContainer.style.display = 'none';
            filterTable(searchInput.value);
        }

        function filterTable(query) {
            tableRows.forEach(row => {
                const objetivoInfo = row.querySelectorAll("td")[2].textContent.toLowerCase();
                row.style.display = objetivoInfo.includes(query.toLowerCase()) ? '' : 'none';
            });
        }

        window.onclick = function(event) {
            if (!event.target.matches('.search-input')) {
                resultsContainer.style.display = 'none';
            }
        }
    </script>
</body>
</html>
