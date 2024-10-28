<?php
session_start();
include('../config/config.php');

if (!isset($_SESSION['user_id']) || ($_SESSION['rol'] !== 'administrador' && $_SESSION['rol'] !== 'bibliotecario')) {
    header('Location: ../index.php');
    exit();
}

$error_message = '';
$success_message = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nombre = $_POST['nombre'];
    $apellido = $_POST['apellido'];
    $correo = $_POST['correo'];
    $direccion = $_POST['direccion'];
    $rol = $_POST['rol'];
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    if ($password !== $confirm_password) {
        $error_message = 'Las contraseñas no coinciden.';
    } else {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $activation_code = md5(rand());  

        $query = "INSERT INTO Usuarios (nombre, apellido, correo, direccion, rol, password, activation_code, is_active) 
                  VALUES (:nombre, :apellido, :correo, :direccion, :rol, :password, :activation_code, 1)";
        $stmt = $pdo->prepare($query);
        $result = $stmt->execute([
            'nombre' => $nombre,
            'apellido' => $apellido,
            'correo' => $correo,
            'direccion' => $direccion,
            'rol' => $rol,
            'password' => $hashed_password,
            'activation_code' => $activation_code
        ]);

        if ($result) {
            $success_message = 'Usuario agregado exitosamente.';
        } else {
            $error_message = 'Hubo un error al agregar el usuario.';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Agregar Usuario</title>
    <link rel="stylesheet" href="../styles/add_user.css?v=<?php echo time(); ?>">
</head>
<body>
    <h1>Agregar Usuario</h1>

    <?php if ($error_message): ?>
        <p class="error"><?= $error_message ?></p>
    <?php elseif ($success_message): ?>
        <p class="success"><?= $success_message ?></p>
    <?php endif; ?>

    <form action="add_user.php" method="POST">
        <label for="nombre">Nombre:</label>
        <input type="text" name="nombre" id="nombre" required>

        <label for="apellido">Apellido:</label>
        <input type="text" name="apellido" id="apellido" required>

        <label for="correo">Correo Electrónico:</label>
        <input type="email" name="correo" id="correo" required>

        <label for="direccion">Dirección:</label>
        <input type="text" name="direccion" id="direccion" required>

        <label for="rol">Rol:</label>
        <select name="rol" id="rol" required>
            <?php if ($_SESSION['rol'] === 'administrador'): ?>
                <option value="usuario">Usuario</option>
                <option value="bibliotecario">Bibliotecario</option>
                <option value="administrador">Administrador</option>
            <?php elseif ($_SESSION['rol'] === 'bibliotecario'): ?>
                <option value="usuario">Usuario</option>
                <option value="bibliotecario">Bibliotecario</option>
            <?php endif; ?>
        </select>

        <label for="password">Contraseña:</label>
        <input type="password" name="password" id="password" required>

        <label for="confirm_password">Confirmar Contraseña:</label>
        <input type="password" name="confirm_password" id="confirm_password" required>

        <button type="submit">Agregar Usuario</button>
    </form>

    <p><a href="manage_users.php">Volver a la gestión de usuarios</a></p>
</body>
</html>
