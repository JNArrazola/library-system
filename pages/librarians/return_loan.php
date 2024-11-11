<?php
session_start();
include('../../config/config.php');

if (!isset($_SESSION['user_id']) || $_SESSION['rol'] === 'usuario') {
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
if ($_SERVER['REQUEST_METHOD'] == 'GET') {
    if (!empty($_GET['usuario_id'])) {
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
    } elseif (!empty($_GET['libro_id'])) {
        $libro_id = $_GET['libro_id'];
        
        $query = "SELECT r.id, l.nombre AS libro_nombre, r.fecha_recepcion, u.nombre AS usuario_nombre, u.apellido AS usuario_apellido 
                  FROM Reservas r
                  JOIN Libros l ON r.libro_id = l.id
                  JOIN Usuarios u ON r.usuario_id = u.id
                  WHERE r.libro_id = :libro_id AND r.B_Entregado = 0 AND r.fecha_recepcion IS NOT NULL";
        $stmt = $pdo->prepare($query);
        $stmt->execute(['libro_id' => $libro_id]);
        $pendientes = $stmt->fetchAll();
        
        if (empty($pendientes)) {
            $error_message = 'No se encontraron préstamos pendientes para el libro especificado.';
        }
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
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" crossorigin="anonymous">
</head>
<body>
    <header>
        <h1>Registrar Devolución</h1>
        <div class="user-menu">
            <span><?= htmlspecialchars($_SESSION['nombre']) ?> (Bibliotecario)</span>
            <a href="../catalog.php" class="catalog-link">Volver al Catálogo</a>
            <a href="../../config/logout.php" class="logout-button">Cerrar sesión</a>
        </div>
    </header>

    <section class="return-form">
        <label>Selecciona un Usuario o Libro por ID: </label>

        <div class="search-container-wrapper">
            <div class="search-container">
                <input type="text" class="custom-input" placeholder="Buscar usuario o libro..." onkeyup="filterUsers(this.value)">
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
            <div class="info-button" onclick="showSearchInfo()">
                <i class="fas fa-info-circle"></i>
            </div>
        </div>

        <form action="return_loan.php" method="GET" id="userForm">
            <input type="hidden" name="usuario_id" id="usuario_id">
            <input type="hidden" name="libro_id" id="libro_id">
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

            // Check if query is a number, indicating a possible ID of libro
            if (!isNaN(query) && query.trim() !== "") {
                document.getElementById('usuario_id').value = ''; // Clear user ID field
                document.getElementById('libro_id').value = query; // Set libro ID
                document.getElementById('userForm').submit();
            }
        }

        function selectUser(userId) {
            document.getElementById('usuario_id').value = userId;
            document.getElementById('libro_id').value = ''; // Clear libro ID field
            const userName = document.querySelector(`.result-item[data-user-info*="${userId}"] h4`).textContent;
            document.querySelector('.custom-input').value = userName;
            document.getElementById('resultsContainer').style.display = 'none';
        }

        function showSearchInfo() {
            Swal.fire({
                icon: 'info',
                title: 'Información de Búsqueda',
                text: 'Puedes buscar usuarios por nombre o correo, o libros por ID/Código de Barras.',
                confirmButtonText: 'Entendido'
            });
        }

        <?php if ($success_message): ?>
            Swal.fire({
                icon: 'success',
                title: 'Éxito',
                text: '<?= $success_message ?>',
                confirmButtonText: 'Aceptar'
            });
        <?php elseif ($error_message): ?>
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: '<?= $error_message ?>',
                confirmButtonText: 'Aceptar'
            });
        <?php endif; ?>
    </script>
</body>
</html>
