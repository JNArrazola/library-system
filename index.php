<?php
session_start();
include('config/config.php'); 

$error_message = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $correo = $_POST['correo'];
    $password = $_POST['password'];

    $query = "SELECT * FROM Usuarios WHERE correo = :correo AND is_active = 1";
    $stmt = $pdo->prepare($query);
    $stmt->execute(['correo' => $correo]);
    $user = $stmt->fetch();

    if ($user) {
        if (password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['nombre'] = $user['nombre'];
            $_SESSION['rol'] = $user['rol'];

            if ($user['rol'] === 'usuario') {
                header('Location: pages/users/user_dashboard.php');
            } else if($user['rol'] === 'administrador') { 
                header('Location: pages/administrators/admin_dashboard.php'); 
            } else {
                header('Location: pages/main_dashboard.php');
            }
            exit();
        } else {
            $error_message = 'Contraseña incorrecta.';
        }
    } else {
        $error_message = 'Correo no encontrado o cuenta no activada.';
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="styles/index.css">
    <title>Inicio de Sesión</title>
</head>
<body>
    <div class="login-container">
        <h2>Iniciar Sesión</h2>
        <?php if ($error_message): ?>
            <p class="error"><?= $error_message ?></p>
        <?php endif; ?>
        <form action="index.php" method="POST">
            <label for="correo">Correo electrónico:</label>
            <input type="email" name="correo" id="correo" required>
            <label for="password">Contraseña:</label>
            <input type="password" name="password" id="password" required>
            <button type="submit">Ingresar</button>
        </form>
        <p>¿No tienes cuenta? <a href="pages/register.php">Regístrate aquí</a></p>
    </div>
</body>
</html>
