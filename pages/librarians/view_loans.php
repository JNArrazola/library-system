<?php
session_start();
include('../../config/config.php');

if (!isset($_SESSION['user_id']) || $_SESSION['rol'] === 'usuario') {
    header('Location: ../../index.php');
    exit();
}

$error_message = '';
$success_message = '';
$usuario_id = isset($_POST['usuario_id']) ? $_POST['usuario_id'] : null;
$b_entregado = isset($_POST['b_entregado']) ? $_POST['b_entregado'] : '0';

$query = "SELECT id, nombre, apellido, correo FROM Usuarios WHERE is_active = 1 AND rol = 'usuario'";
$stmt = $pdo->query($query);
$usuarios = $stmt->fetchAll();

$searchText = '';
if ($usuario_id) {
    foreach ($usuarios as $usuario) {
        if ($usuario['id'] == $usuario_id) {
            $searchText = htmlspecialchars($usuario['nombre'] . ' ' . $usuario['apellido']);
            break;
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['loan_id'])) {
    $loan_id = $_POST['loan_id'];
    $fecha_devolucion = $_POST['fecha_devolucion'];

    $query = "SELECT fecha_recepcion, B_Entregado FROM Reservas WHERE id = :loan_id";
    $stmt = $pdo->prepare($query);
    $stmt->execute(['loan_id' => $loan_id]);
    $loan = $stmt->fetch();

    if ($loan['B_Entregado'] == 1) {
        $error_message = 'El préstamo ya ha sido entregado, no se puede modificar la fecha de devolución.';
    } elseif ($fecha_devolucion > $loan['fecha_recepcion']) {
        $query = "UPDATE Reservas SET fecha_devolucion = :fecha_devolucion WHERE id = :loan_id";
        $stmt = $pdo->prepare($query);
        $stmt->execute([
            'loan_id' => $loan_id,
            'fecha_devolucion' => $fecha_devolucion
        ]);

        if ($stmt->rowCount()) {
            $success_message = 'Fecha de devolución actualizada exitosamente.';
        } else {
            $error_message = 'No se pudo actualizar la fecha de devolución.';
        }
    } else {
        $error_message = 'La fecha de devolución debe ser posterior a la fecha de préstamo.';
    }
}

$prestamos = [];

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['usuario_id'])) {
    $usuario_id = $_POST['usuario_id'];
    $b_entregado = $_POST['b_entregado'];  

    $query = "SELECT r.id AS reserva_id, u.id AS usuario_id, u.nombre, u.apellido, u.correo, l.nombre AS libro_nombre, 
                     r.fecha_recepcion, r.fecha_devolucion, r.B_Entregado
              FROM Reservas r
              JOIN Usuarios u ON r.usuario_id = u.id
              JOIN Libros l ON r.libro_id = l.id
              WHERE u.id = :usuario_id AND r.B_Entregado = :b_entregado AND r.fecha_recepcion IS NOT NULL"; 
    $stmt = $pdo->prepare($query);
    $stmt->execute([
        'usuario_id' => $usuario_id,
        'b_entregado' => $b_entregado
    ]);
    $prestamos = $stmt->fetchAll();
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ver Préstamos</title>
    <link rel="stylesheet" href="../../styles/librarians/view_loans.css?v=<?php echo time(); ?>">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" crossorigin="anonymous">
</head>
<body>
    <header>
        <h1>Ver Préstamos</h1>
        <div class="user-menu">
            <span><?= htmlspecialchars($_SESSION['nombre']) ?> (Bibliotecario)</span>
            <a href="../catalog.php" class="catalog-link">Volver al Catálogo</a>
            <a href="../../config/logout.php" class="logout-button">Cerrar sesión</a>
        </div>
    </header>

    <section class="loan-view">
        <h2>Buscar Préstamos por Usuario</h2>

        <form action="view_loans.php" method="POST">
            <div class="form-group">
                <label for="usuario_id">Buscar Usuario:</label>
                <div class="search-container-wrapper">
                    <div class="search-container">
                        <input type="text" class="custom-input" placeholder="Buscar usuario..." onkeyup="filterUsers(this.value)" value="<?= $searchText ?>">
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
                <input type="hidden" name="usuario_id" id="usuario_id" value="<?= htmlspecialchars($usuario_id) ?>" required>
            </div>

            <div class="form-group">
                <label for="b_entregado">Filtrar por:</label>
                <select name="b_entregado" id="b_entregado" required>
                    <option value="0" <?= ($b_entregado === '0') ? 'selected' : '' ?>>No Entregados</option>
                    <option value="1" <?= ($b_entregado === '1') ? 'selected' : '' ?>>Entregados</option>
                </select>
            </div>

            <button type="submit">Buscar Préstamos</button>
        </form>

        <?php if (!empty($prestamos)): ?>
            <h2>Préstamos del Usuario</h2>
            <table>
                <thead>
                    <tr>
                        <th>ID Usuario</th>
                        <th>Nombre</th>
                        <th>Libro</th>
                        <th>Fecha de Préstamo</th>
                        <th>Fecha de Devolución</th>
                        <th>Estado</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($prestamos as $prestamo): ?>
                        <tr>
                            <td><?= htmlspecialchars($prestamo['usuario_id']) ?></td>
                            <td><?= htmlspecialchars($prestamo['nombre'] . ' ' . $prestamo['apellido']) ?></td>
                            <td><?= htmlspecialchars($prestamo['libro_nombre']) ?></td>
                            <td><?= htmlspecialchars($prestamo['fecha_recepcion']) ?></td>
                            <td>
                                <?php if ($prestamo['B_Entregado'] == 0): ?>
                                    <form action="view_loans.php" method="POST">
                                        <input type="date" name="fecha_devolucion" value="<?= htmlspecialchars($prestamo['fecha_devolucion']) ?>" required>
                                <?php else: ?>
                                    <?= htmlspecialchars($prestamo['fecha_devolucion']) ?>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?= ($prestamo['B_Entregado'] == 1) ? 'Entregado' : 'No Entregado' ?>
                            </td>
                            <td>
                                <?php if ($prestamo['B_Entregado'] == 0): ?>
                                    <input type="hidden" name="loan_id" value="<?= htmlspecialchars($prestamo['reserva_id']) ?>">
                                    <button type="submit">Actualizar</button>
                                    </form>
                                <?php else: ?>
                                    No disponible
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php elseif ($usuario_id): ?>
            <p class="no-results">No se encontraron préstamos para este usuario.</p>
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
            document.querySelector('.custom-input').value = userName;
            document.getElementById('resultsContainer').style.display = 'none';
        }

        function showSearchInfo() {
            Swal.fire({
                icon: 'info',
                title: 'Información de Búsqueda',
                text: 'Puedes buscar usuarios por ID, nombre o correo.',
                confirmButtonText: 'Entendido'
            });
        }

        window.onclick = function(event) {
            if (!event.target.matches('.custom-input')) {
                document.getElementById('resultsContainer').style.display = 'none';
            }
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

