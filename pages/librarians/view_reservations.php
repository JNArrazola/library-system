<?php
session_start();
include('../../config/config.php');

if (!isset($_SESSION['user_id']) || $_SESSION['rol'] !== 'bibliotecario') {
    header('Location: ../../index.php');
    exit();
}

$error_message = '';
$success_message = '';
$usuario_id = isset($_POST['usuario_id']) ? $_POST['usuario_id'] : null;

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['recibir_id']) && isset($_POST['fecha_devolucion'])) {
    $recibir_id = $_POST['recibir_id'];
    $fecha_devolucion = $_POST['fecha_devolucion'];

    if (empty($fecha_devolucion)) {
        $error_message = 'Debes seleccionar una fecha de devolución.';
    } else {
        $query_fecha_reserva = "SELECT fecha_reserva FROM Reservas WHERE id = :recibir_id";
        $stmt_fecha_reserva = $pdo->prepare($query_fecha_reserva);
        $stmt_fecha_reserva->execute(['recibir_id' => $recibir_id]);
        $reserva = $stmt_fecha_reserva->fetch();

        if ($reserva) {
            $fecha_reserva = $reserva['fecha_reserva'];
            $fecha_actual = date('Y-m-d');

            if ($fecha_devolucion <= $fecha_actual || $fecha_devolucion <= $fecha_reserva) {
                $error_message = 'La fecha de devolución debe ser posterior a la fecha actual y a la fecha de reserva.';
            } else {
                $query = "UPDATE Reservas SET fecha_recepcion = CURDATE(), fecha_devolucion = :fecha_devolucion WHERE id = :recibir_id";
                $stmt = $pdo->prepare($query);
                $stmt->execute(['fecha_devolucion' => $fecha_devolucion, 'recibir_id' => $recibir_id]);

                if ($stmt->rowCount()) {
                    $success_message = 'El libro ha sido marcado como recibido y la fecha de devolución ha sido asignada.';
                } else {
                    $error_message = 'No se pudo marcar la recepción del libro.';
                }
            }
        } else {
            $error_message = 'Reserva no encontrada.';
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['usuario_id'])) {
    $usuario_id = $_POST['usuario_id'];

    $query = "SELECT r.id AS reserva_id, u.id AS usuario_id, u.nombre, u.apellido, l.nombre AS libro_nombre, 
                     r.fecha_reserva, r.fecha_recepcion, r.fecha_devolucion
              FROM Reservas r
              JOIN Usuarios u ON r.usuario_id = u.id
              JOIN Libros l ON r.libro_id = l.id
              WHERE u.id = :usuario_id AND r.fecha_recepcion IS NULL";
    $stmt = $pdo->prepare($query);
    $stmt->execute(['usuario_id' => $usuario_id]);
    $reservas = $stmt->fetchAll();
}

$query = "SELECT id, nombre, apellido, correo FROM Usuarios WHERE is_active = 1 AND rol = 'usuario'";
$stmt = $pdo->query($query);
$usuarios = $stmt->fetchAll();

// Almacena el texto de búsqueda del usuario
$searchText = '';
if ($usuario_id) {
    foreach ($usuarios as $usuario) {
        if ($usuario['id'] == $usuario_id) {
            $searchText = htmlspecialchars($usuario['nombre'] . ' ' . $usuario['apellido']);
            break;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ver Reservas</title>
    <link rel="stylesheet" href="../../styles/librarians/view_reservations.css?v=<?php echo time(); ?>">
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
        <h1>Ver Reservas</h1>
        <div class="user-menu">
            <span><?= htmlspecialchars($_SESSION['nombre']) ?> (Bibliotecario)</span>
            <a href="../catalog.php" class="catalog-link">Volver al Catálogo</a>
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
                <div class="search-container">
                    <input type="text" class="search-input" placeholder="Buscar usuario..." onkeyup="filterUsers(this.value)" value="<?= $searchText ?>">
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
                <input type="hidden" name="usuario_id" id="usuario_id" value="<?= htmlspecialchars($usuario_id) ?>" required>
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
                        <th>Fecha de Devolución</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($reservas as $reserva): ?>
                        <tr>
                            <td><?= htmlspecialchars($reserva['usuario_id']) ?></td>
                            <td><?= htmlspecialchars($reserva['nombre'] . ' ' . $reserva['apellido']) ?></td>
                            <td><?= htmlspecialchars($reserva['libro_nombre']) ?></td>
                            <td><?= htmlspecialchars($reserva['fecha_reserva']) ?></td>
                            <td>
                                <form action="view_reservations.php" method="POST">
                                    <input type="hidden" name="recibir_id" value="<?= htmlspecialchars($reserva['reserva_id']) ?>">
                                    <input type="date" name="fecha_devolucion" required>
                            </td>
                            <td>
                                <button type="submit">Entregar libro</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php elseif ($usuario_id): ?>
            <p class="no-results">No se encontraron reservas para este usuario.</p>
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
            const userName = document.querySelector(`.result-item[data-user-info*="${userId}"] h4`).textContent;
            document.querySelector('.search-input').value = userName;
            document.getElementById('resultsContainer').style.display = 'none';
        }

        window.onclick = function(event) {
            if (!event.target.matches('.search-input')) {
                document.getElementById('resultsContainer').style.display = 'none';
            }
        }
    </script>
</body>
</html>
