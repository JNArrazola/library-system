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

$query = "SELECT id, nombre, apellido, correo FROM Usuarios WHERE is_active = 1 AND rol = 'usuario'";
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

    if (empty($pendientes)) {
        $error_message = 'No se encontraron préstamos pendientes para el usuario seleccionado.';
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registrar Devolución</title>
    <link rel="stylesheet" href="../../styles/librarians/return_loan.css?v=<?= time(); ?>">
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

        <h2>Selecciona un Usuario</h2>

        <div class="search-container">
            <input type="text" class="search-input" placeholder="Buscar usuario..." onkeyup="filterUsers(this.value)">
            <div class="results-container" id="resultsContainer">
                <?php foreach ($usuarios as $usuario): ?>
                    <div class="result-item" data-user-info="<?= htmlspecialchars($usuario['nombre'] . ' ' . $usuario['apellido'] . ' ' . $usuario['correo'] . ' ' . $usuario['id']) ?>" onclick="selectUser(<?= htmlspecialchars($usuario['id']) ?>)">
                        <h4><?= htmlspecialchars($usuario['nombre'] . ' ' . $usuario['apellido']) ?></h4>
                        <p>ID: <?= htmlspecialchars($usuario['id']) ?></p>
                        <p>Correo: <?= htmlspecialchars($usuario['correo']) ?></p>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <form action="return_loan.php" method="GET" id="userForm">
            <input type="hidden" name="usuario_id" id="usuario_id">
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

    <script>
        function filterUsers(query) {
            const container = document.getElementById('resultsContainer');
            const items = document.querySelectorAll('.result-item');
            let hasResults = false;

            query = query.toLowerCase();
            items.forEach(item => {
                const userInfo = item.getAttribute('data-user-info').toLowerCase();
                if (userInfo.includes(query)) {
                    item.style.display = 'block';
                    hasResults = true;
                } else {
                    item.style.display = 'none';
                }
            });
            
            container.style.display = hasResults ? 'block' : 'none';
        }

        function selectUser(userId) {
            document.getElementById('usuario_id').value = userId;
            document.getElementById('resultsContainer').style.display = 'none';
            document.querySelector('.search-input').value = 'Usuario seleccionado: ' + userId;
        }

        window.onclick = function(event) {
            if (!event.target.matches('.search-input')) {
                document.getElementById('resultsContainer').style.display = 'none';
            }
        }
    </script>
</body>
</html>
