<?php
session_start();
include('../config/config.php'); 

$error_message = '';
$lock_time = [30, 60, 120, 240, 480]; 

if (isset($_SESSION['login_attempts']) && $_SESSION['login_attempts'] >= 3) {
    $attempt_count = min($_SESSION['login_attempts'] - 3, count($lock_time) - 1);
    $wait_time = $lock_time[$attempt_count];
    $remaining_time = $_SESSION['last_attempt'] + $wait_time - time();

    if ($remaining_time > 0) {
        $error_message = "Demasiados intentos fallidos. Intenta de nuevo en $remaining_time segundos.";
    } else {
        unset($_SESSION['login_attempts'], $_SESSION['last_attempt']);
    }
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && empty($error_message)) {
    $correo = $_POST['correo'];
    $password = $_POST['password'];

    $query = "SELECT * FROM Usuarios WHERE correo = :correo";
    $stmt = $pdo->prepare($query);
    $stmt->execute(['correo' => $correo]);
    $user = $stmt->fetch();

    if ($user) {
        if ($user['is_active']) {
            if (password_verify($password, $user['password'])) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['nombre'] = $user['nombre'];
                $_SESSION['rol'] = $user['rol'];
                unset($_SESSION['login_attempts'], $_SESSION['last_attempt']);
                
                header('Location: ../index.php');
                exit();
            } else {
                $error_message = 'Contraseña incorrecta.';
            }
        } else {
            $error_message = 'Cuenta no activada. Por favor, revisa tu correo para activarla.';
        }
    } else {
        $error_message = 'Correo no encontrado.';
    }

    if (!isset($_SESSION['login_attempts'])) {
        $_SESSION['login_attempts'] = 0;
    }
    $_SESSION['login_attempts']++;
    $_SESSION['last_attempt'] = time();
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../styles/login.css?v=<?php echo time(); ?>">
    <title>Inicio de Sesión</title>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body>
    <div class="login-container">
        <h2>Iniciar Sesión</h2>
        <?php if ($error_message): ?>
            <script>
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: '<?= $error_message ?>',
                    confirmButtonText: 'Aceptar'
                });
            </script>
        <?php endif; ?>
        <form action="login.php" method="POST">
            <label for="correo">Correo electrónico:</label>
            <input type="email" name="correo" id="correo" required>
            <label for="password">Contraseña:</label>
            <input type="password" name="password" id="password" required>
            <button type="submit">Ingresar</button>
        </form>
        <p>¿No tienes cuenta? <a href="register.php">Regístrate aquí</a></p>
    </div>
</body>
</html>
