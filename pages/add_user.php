 <?php
session_start();
include('../config/config.php'); 
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;

require '../Packages/PHPMailer/src/Exception.php';
require '../Packages/PHPMailer/src/PHPMailer.php';
require '../Packages/PHPMailer/src/SMTP.php';
require '../config/emailConfig.php';

$error_message = '';
$success_message = '';

if (!isset($_SESSION['user_id']) || ($_SESSION['rol'] !== 'administrador' && $_SESSION['rol'] !== 'bibliotecario')) {
    header('Location: ../index.php');
    exit();
}

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
        $activation_code = bin2hex(random_bytes(16));

        $query_check_email = "SELECT id FROM Usuarios WHERE correo = :correo";
        $stmt_check_email = $pdo->prepare($query_check_email);
        $stmt_check_email->execute(['correo' => $correo]);

        if ($stmt_check_email->fetch()) {
            $error_message = 'El correo electrónico ya está registrado.';
        } else {
            if ($_SESSION['rol'] === '') {
                $query_insert_solicitud = "INSERT INTO Solicitudes (tipo, usuario_id, solicitante_id, estado, detalles_cambio) 
                                           VALUES ('creacion', NULL, :solicitante_id, 'pendiente', :detalles_cambio)";
                $detalles_cambio = json_encode([
                    'nombre' => $nombre,
                    'apellido' => $apellido,
                    'correo' => $correo,
                    'direccion' => $direccion,
                    'rol' => $rol,
                    'password' => $hashed_password,
                    'activation_code' => $activation_code,
                    'is_active' => 0
                ]);
                $stmt_insert_solicitud = $pdo->prepare($query_insert_solicitud);
                $stmt_insert_solicitud->execute([
                    'solicitante_id' => $_SESSION['user_id'],
                    'detalles_cambio' => $detalles_cambio
                ]);
                $success_message = "Solicitud de creación de usuario enviada correctamente.";
            } else {
                $query_insert_user = "INSERT INTO Usuarios (nombre, apellido, correo, direccion, rol, password, activation_code, is_active) 
                                      VALUES (:nombre, :apellido, :correo, :direccion, :rol, :password, :activation_code, 1)";
                $stmt_insert_user = $pdo->prepare($query_insert_user);
                $stmt_insert_user->execute([
                    'nombre' => $nombre,
                    'apellido' => $apellido,
                    'correo' => $correo,
                    'direccion' => $direccion,
                    'rol' => $rol,
                    'password' => $hashed_password,
                    'activation_code' => $activation_code
                ]);
                $success_message = "Usuario creado exitosamente.";
            }

            // $activation_link = "http://localhost/library-system/pages/activate.php?correo=" . urlencode($correo);
            // sendMail($correo, "Activación de cuenta", "Por favor, activa tu cuenta usando este enlace: <a href='$activation_link'>Activar cuenta</a>");
        }
    }
}

function sendMail($email, $subject, $message) {
    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->SMTPAuth = true;
        $mail->Host = MAILHOST;
        $mail->Username = USERNAME;
        $mail->Password = PASSWORD;
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;

        $mail->setFrom(SEND_FROM, SEND_FROM_NAME);
        $mail->addAddress($email);
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body = $message;
        $mail->AltBody = strip_tags($message);

        $mail->send();
    } catch (Exception $e) {
        global $error_message;
        $error_message = 'No se pudo enviar el correo de activación.';
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Añadir Usuario</title>
    <link rel="stylesheet" href="../styles/add_user.css?v=<?= time(); ?>">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body>
    <header>
        <h1>Añadir Usuario</h1>
        <div class="user-menu">
            <span><?= htmlspecialchars($_SESSION['nombre']) ?> (<?= htmlspecialchars($_SESSION['rol']) ?>)</span>
            <a href="manage_users.php" class="catalog-link">Volver a Gestión de Usuarios</a>
            <a href="../config/logout.php" class="logout-button">Cerrar sesión</a>
        </div>
    </header>

    <section class="add-user-form">
        <?php if ($success_message): ?>
            <script>
                Swal.fire({
                    icon: 'success',
                    title: 'Éxito',
                    text: '<?= $success_message ?>',
                    confirmButtonText: 'Aceptar'
                }).then(() => {
                    document.getElementById('addUserForm').reset();
                });
            </script>
        <?php elseif ($error_message): ?>
            <script>
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: '<?= $error_message ?>',
                    confirmButtonText: 'Aceptar'
                });
            </script>
        <?php endif; ?>

        <form action="add_user.php" method="POST" id="addUserForm">
            <label for="nombre">Nombre:</label>
            <input type="text" name="nombre" id="nombre" required>

            <label for="apellido">Apellido:</label>
            <input type="text" name="apellido" id="apellido" required>

            <label for="correo">Correo electrónico:</label>
            <input type="email" name="correo" id="correo" required>

            <label for="direccion">Dirección:</label>
            <input type="text" name="direccion" id="direccion" required>

            <label for="rol">Rol:</label>
            <select name="rol" id="rol" required>
                <option value="usuario">Usuario</option>
                <option value="bibliotecario">Bibliotecario</option>
                <?php if ($_SESSION['rol'] === 'administrador'): ?>
                    <option value="administrador">Administrador</option>
                <?php endif; ?>
            </select>

            <label for="password">Contraseña:</label>
            <input type="password" name="password" id="password" required>

            <label for="confirm_password">Confirmar Contraseña:</label>
            <input type="password" name="confirm_password" id="confirm_password" required>

            <button type="submit">Añadir Usuario</button>
        </form>
    </section>
</body>
</html>