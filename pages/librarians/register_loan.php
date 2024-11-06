<?php
session_start();
include('../../config/config.php');

if (!isset($_SESSION['user_id']) || $_SESSION['rol'] !== 'bibliotecario') {
    header('Location: ../../index.php');
    exit();
}

$error_message = '';
$success_message = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $usuario_id = $_POST['usuario_id'];
    $libro_id = $_POST['libro_id'];
    $fecha_recepcion = $_POST['fecha_recepcion'];
    $fecha_devolucion = $_POST['fecha_devolucion'];

    if (strtotime($fecha_devolucion) <= strtotime($fecha_recepcion)) {
        $error_message = 'La fecha de devolución debe ser posterior a la fecha de préstamo.';
    } else {
        $query = "SELECT cantidad FROM Libros WHERE id = :libro_id";
        $stmt = $pdo->prepare($query);
        $stmt->execute(['libro_id' => $libro_id]);
        $libro = $stmt->fetch();

        if ($libro && $libro['cantidad'] > 0) {
            $query = "INSERT INTO Reservas (usuario_id, libro_id, fecha_recepcion, fecha_devolucion, B_Entregado) 
                      VALUES (:usuario_id, :libro_id, :fecha_recepcion, :fecha_devolucion, 0)";
            $stmt = $pdo->prepare($query);
            $stmt->execute([
                'usuario_id' => $usuario_id,
                'libro_id' => $libro_id,
                'fecha_recepcion' => $fecha_recepcion,
                'fecha_devolucion' => $fecha_devolucion
            ]);

            $query = "UPDATE Libros SET cantidad = cantidad - 1 WHERE id = :libro_id";
            $stmt = $pdo->prepare($query);
            $stmt->execute(['libro_id' => $libro_id]);

            $success_message = 'Préstamo registrado exitosamente.';
        } else {
            $error_message = 'No hay suficientes copias del libro disponibles.';
        }
    }
}

$query = "SELECT id, nombre, apellido, correo FROM Usuarios WHERE is_active = 1 AND rol = 'usuario'";
$stmt = $pdo->query($query);
$usuarios = $stmt->fetchAll();

$query = "SELECT id, nombre FROM Libros WHERE cantidad > 0";
$stmt = $pdo->query($query);
$libros = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registrar Préstamo</title>
    <link rel="stylesheet" href="../../styles/librarians/register_loan.css?v=<?php echo time(); ?>"> 
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
        <h1>Registrar Préstamo</h1>
        <div class="user-menu">
            <span><?= htmlspecialchars($_SESSION['nombre']) ?> (Bibliotecario)</span>
            <a href="../catalog.php" class="catalog-link">Volver al Catálogo</a>
            <a href="../logout.php" class="logout-button">Cerrar sesión</a>
        </div>
    </header>

    <section class="loan-form">
        <?php if ($error_message): ?>
            <p class="error"><?= htmlspecialchars($error_message) ?></p>
        <?php elseif ($success_message): ?>
            <p class="success"><?= htmlspecialchars($success_message) ?></p>
        <?php endif; ?>

        <form action="register_loan.php" method="POST">
            <div class="form-group">
                <label for="usuario_id">Usuario:</label>
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
                <input type="hidden" name="usuario_id" id="usuario_id" required>
            </div>

            <div class="form-group">
                <label for="libro_id">Libro:</label>
                <select name="libro_id" id="libro_id" class="custom-input" required>
                    <option value="">Selecciona un libro</option>
                    <?php foreach ($libros as $libro): ?>
                        <option value="<?= htmlspecialchars($libro['id']) ?>">
                            <?= htmlspecialchars($libro['nombre']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label for="fecha_recepcion">Fecha de Préstamo:</label>
                <input type="date" name="fecha_recepcion" id="fecha_recepcion" class="custom-input" required>
            </div>
            <div class="form-group">
                <label for="fecha_devolucion">Fecha de Devolución:</label>
                <input type="date" name="fecha_devolucion" id="fecha_devolucion" class="custom-input" required>
            </div>
            <button type="submit">Registrar Préstamo</button>
        </form>
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
