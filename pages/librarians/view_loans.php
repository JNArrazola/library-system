<?php
session_start();
include('../../config/config.php');

if (!isset($_SESSION['user_id']) || $_SESSION['rol'] !== 'bibliotecario') {
    header('Location: ../../index.php');
    exit();
}

$error_message = '';
$success_message = '';
$usuario_id = null;
$b_entregado = null; 

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

$query = "SELECT id, nombre, apellido, correo FROM Usuarios WHERE is_active = 1 AND rol = 'usuario'";
$stmt = $pdo->query($query);
$usuarios = $stmt->fetchAll();

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
    <style>
        table {
            width: 100%;
            border-collapse: collapse;
        }

        th, td {
            padding: 10px;
            text-align: center;
            border: 1px solid #ddd;
        }

        input[type="date"] {
            width: 100%;
            padding: 5px;
            box-sizing: border-box;
            text-align: center;
        }

        .form-group {
            margin-bottom: 15px;
        }

        button {
            padding: 5px 10px;
            background-color: #333;
            color: white;
            border: none;
            cursor: pointer;
        }

        button:hover {
            background-color: #555;
        }
    </style>
</head>
<body>
    <header>
        <h1>Ver Préstamos</h1>
        <div class="user-menu">
            <span><?= htmlspecialchars($_SESSION['nombre']) ?> (Bibliotecario)</span>
            <a href="../main_dashboard.php" class="catalog-link">Volver al Catálogo</a>
            <a href="../logout.php" class="logout-button">Cerrar sesión</a>
        </div>
    </header>

    <section class="loan-view">
        <?php if ($error_message): ?>
            <p class="error"><?= $error_message ?></p>
        <?php elseif ($success_message): ?>
            <p class="success"><?= $success_message ?></p>
        <?php endif; ?>

        <h2>Buscar Préstamos por Usuario</h2>

        <form action="view_loans.php" method="POST">
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
</body>
</html>
