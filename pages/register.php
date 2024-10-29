<?php

use Packages\Exception;
use Packages\PHPMailer;  
use Packages\SMTP;

require '../Packages/Exception.php';
require '../Packages/PHPMailer.php';
require '../Packages/SMTP.php';

require '../config/emailConfig.php';

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
include('../config/config.php');
$error_message = '';
$success_message = '';

$nombre = '';
$apellido = '';
$correo = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nombre = $_POST['nombre'];
    $apellido = $_POST['apellido'];
    $correo = $_POST['correo'];
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    if ($password !== $confirm_password) {
        $error_message = 'Las contraseñas no coinciden.';
    } elseif (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
        $error_message = 'Correo inválido.';
    } elseif (strlen($password) < 8 || !preg_match('/[A-Z]/', $password) || !preg_match('/[0-9]/', $password)) {
        $error_message = 'La contraseña debe tener al menos 8 caracteres, una letra mayúscula y un número.';
    } else {
        $query = "SELECT * FROM Usuarios WHERE correo = :correo";
        $stmt = $pdo->prepare($query);
        $stmt->execute(['correo' => $correo]);
        $user = $stmt->fetch();

        if ($user) {
            $error_message = 'El correo ya está registrado.';
        } else {
            
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);

            $query = "INSERT INTO Usuarios (nombre, apellido, correo, password, rol, is_active) 
                      VALUES (:nombre, :apellido, :correo, :password, 'usuario', 1)";
            $stmt = $pdo->prepare($query);
            $stmt->execute([
                'nombre' => $nombre,
                'apellido' => $apellido,
                'correo' => $correo,
                'password' => $hashed_password
            ]);
            
            $success_message = 'Registro exitoso. Tu cuenta ha sido activada.';

            sendMail($correo,$nombre,$success_message);

            header("Location: succesfull_register.php");
            exit;

        }
    }
}

function sendMail($email,$subject,$message){
    
    $mail = new PHPMailer(true);
    
    $mail->isSMTP();

    $mail->SMTPAuth = true;
    
    $mail->Host = MAILHOST;

    $mail->Username = USERNAME;

    $mail->Password = PASSWORD;

    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;

    $mail->Port = 587;

    $mail->setFrom(SEND_FROM, SEND_FROM_NAME);

    $mail->addAddress($email);
    
    $mail->addReplyTo(REPLY_TO,REPLY_TO_NAME);

    $mail->IsHTML(true);

    $mail->Subject = $subject;

    $mail->Body = $message;

    $mail->AltBody = $message;

    if(!$mail->send()){
        return "Correo no enviado. Porfavor initente otravez";
    }
    else{
        return "El correo se ha enviado con exito";
    }

}

?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registro</title>
    <link rel="stylesheet" href="../styles/register.css">
</head>
<body>
    <div class="login-container">
        <h2>Registro de Usuario</h2>
        <?php if ($error_message): ?>
            <p class="error"><?= $error_message ?></p>
        <?php elseif ($success_message): ?>
            <p class="success"><?= $success_message ?></p>
        <?php endif; ?>
        <form action="register.php" method="POST">
            <label for="nombre">Nombre:</label>
            <input type="text" name="nombre" id="nombre" value="<?= htmlspecialchars($nombre) ?>" required>
            <label for="apellido">Apellido:</label>
            <input type="text" name="apellido" id="apellido" value="<?= htmlspecialchars($apellido) ?>" required>
            <label for="correo">Correo electrónico:</label>
            <input type="email" name="correo" id="correo" value="<?= htmlspecialchars($correo) ?>" required>
            <label for="password">Contraseña:</label>
            <input type="password" name="password" id="password" required>
            <label for="confirm_password">Confirmar Contraseña:</label>
            <input type="password" name="confirm_password" id="confirm_password" required>
            <button type="submit">Registrarse</button>
        </form>
        <p>¿Ya tienes una cuenta? <a href="login.php">Inicia sesión aquí</a></p>
    </div>
</body>
</html>
