<?php
session_start();
include('../config/config.php');

if (!isset($_SESSION['user_id'])) {
    header('Location: ../index.php');
    exit();
}

$error_message = '';
$success_message = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $correo = $_POST['correo'];
    $direccion = $_POST['direccion'];
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    if ($password) {
        if ($password !== $confirm_password) {
            $error_message = 'Las contraseñas no coinciden.';
        } elseif (strlen($password) < 8 || !preg_match('/[A-Z]/', $password) || !preg_match('/[0-9]/', $password)) {
            $error_message = 'La contraseña debe tener al menos 8 caracteres, una letra mayúscula y un número.';
        } else {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        }
    }

    if (!$error_message) {
        $query = "UPDATE Usuarios SET correo = :correo, direccion = :direccion";
        if (isset($hashed_password)) {
            $query .= ", password = :password";
        }
        $query .= " WHERE id = :id";

        $stmt = $pdo->prepare($query);
        $params = [
            'correo' => $correo,
            'direccion' => $direccion,
            'id' => $_SESSION['user_id']
        ];
        if (isset($hashed_password)) {
            $params['password'] = $hashed_password;
        }

        if ($stmt->execute($params)) {
            $success_message = 'Datos actualizados correctamente.';
        } else {
            $error_message = 'Error al actualizar los datos.';
        }
    }
}

$stmt = $pdo->prepare("SELECT * FROM Usuarios WHERE id = :id");
$stmt->execute(['id' => $_SESSION['user_id']]);
$user = $stmt->fetch();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Modificar Datos de Usuario</title>
    <link rel="stylesheet" href="../styles/user_edit.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body>
    <header>
        <h1>Modificar Información de Usuario</h1>
        <div class="user-menu">
            <span><?= htmlspecialchars($_SESSION['nombre']) ?> (Usuario)</span>
            <a href="../index.php" class="nav-link">Inicio</a>
            <a href="../pages/catalog.php" class="nav-link">Catálogo</a>
            <a href="../config/logout.php" class="logout-button">Cerrar sesión</a>
        </div>
    </header>

    <div class="loan-form">
        <h2>Modificar Información de Usuario</h2>

        <?php if ($error_message): ?>
            <script>
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: '<?= $error_message ?>',
                    confirmButtonText: 'Aceptar'
                });
            </script>
        <?php elseif ($success_message): ?>
            <script>
                Swal.fire({
                    icon: 'success',
                    title: 'Actualización Exitosa',
                    text: '<?= $success_message ?>',
                    confirmButtonText: 'Aceptar'
                });
            </script>
        <?php endif; ?>

        <form action="user_edit.php" method="POST">
            <div class="form-group">
                <label for="correo">Correo Electrónico</label>
                <input type="email" name="correo" id="correo" value="<?= htmlspecialchars($user['correo']) ?>" required>
            </div>

            <div class="form-group">
                <label for="direccion">Dirección</label>
                <input type="text" name="direccion" id="direccion" value="<?= htmlspecialchars($user['direccion']) ?>" required>
            </div>

            <div class="form-group">
                <label for="password">Nueva Contraseña</label>
                <input type="password" name="password" id="password" placeholder="Dejar en blanco para no cambiar">
            </div>

            <div class="form-group">
                <label for="confirm_password">Confirmar Nueva Contraseña</label>
                <input type="password" name="confirm_password" id="confirm_password" placeholder="Dejar en blanco para no cambiar">
            </div>

            <button type="submit">Guardar Cambios</button>
        </form>
    </div>
</body>
</html>
