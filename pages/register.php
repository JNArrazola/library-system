<?php

use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\PHPMailer;  
use PHPMailer\PHPMailer\SMTP;

require '../Packages/PHPMailer/src/Exception.php';
require '../Packages/PHPMailer/src/PHPMailer.php';
require '../Packages/PHPMailer/src/SMTP.php';
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
$direccion = ''; 

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nombre = $_POST['nombre'];
    $apellido = $_POST['apellido'];
    $correo = $_POST['correo'];
    $direccion = $_POST['direccion']; 
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
            $query = "INSERT INTO Usuarios (nombre, apellido, correo, direccion, password, rol, is_active) 
                      VALUES (:nombre, :apellido, :correo, :direccion, :password, 'usuario', 0)";
            $stmt = $pdo->prepare($query);
            $stmt->execute([
                'nombre' => $nombre,
                'apellido' => $apellido,
                'correo' => $correo,
                'direccion' => $direccion, 
                'password' => $hashed_password
            ]);
            
            $activation_link = "http://localhost/library-system/pages/activate.php?correo=" . urlencode($correo);
            $success_message = 'Se ha enviado un correo de activación a su dirección de correo electrónico.';
            
            $email_body = "
                <html>
                <head>
                    <style>
                        .email-container {
                            font-family: Arial, sans-serif;
                            max-width: 600px;
                            margin: auto;
                            padding: 20px;
                            border: 1px solid #e0e0e0;
                            border-radius: 8px;
                            background-color: #f5f3f2;
                            color: #4b3832;
                        }
                        .email-header {
                            text-align: center;
                            margin-bottom: 20px;
                        }
                        .email-header h2 {
                            color: #4b3832;
                            font-size: 24px;
                            margin: 0;
                        }
                        .email-content {
                            font-size: 16px;
                            line-height: 1.6;
                            color: #4b3832;
                        }
                        .email-button {
                            display: inline-block;
                            margin-top: 20px;
                            padding: 12px 24px;
                            background-color: #756c63;
                            color: #ffffff;
                            text-decoration: none;
                            font-weight: bold;
                            border-radius: 5px;
                            text-align: center;
                            font-size: 16px;
                        }
                        .email-button:hover {
                            background-color: #5e4b41;
                        }
                        .email-footer {
                            margin-top: 30px;
                            font-size: 14px;
                            color: #6e645f;
                            text-align: center;
                        }
                        .email-link {
                            color: #756c63;
                            text-decoration: underline;
                        }
                    </style>
                </head>
                <body>
                    <div class='email-container'>
                        <div class='email-header'>
                            <h2>Bienvenido a la Biblioteca</h2>
                        </div>
                        <div class='email-content'>
                            <p>Hola <strong>$nombre $apellido</strong>,</p>
                            <p>Gracias por registrarte en nuestra biblioteca. Para activar tu cuenta y empezar a utilizar nuestros servicios, por favor confirma tu correo electrónico haciendo clic en el siguiente botón:</p>
                            <p style='text-align: center;'>
                                <a href='$activation_link' class='email-button'>Activar cuenta</a>
                            </p>
                            <p>Si tienes algún problema, copia y pega el siguiente enlace en tu navegador:</p>
                            <p><a href='$activation_link' class='email-link'>$activation_link</a></p>
                        </div>
                        <div class='email-footer'>
                            <p>© 2023 Biblioteca Online. Todos los derechos reservados.</p>
                        </div>
                    </div>
                </body>
                </html>
            ";
            
            sendMail($correo, "Activación de cuenta", $email_body);
        }
    }
}

function sendMail($email, $subject, $message) {
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
    $mail->addReplyTo(REPLY_TO, REPLY_TO_NAME);
    $mail->IsHTML(true);
    $mail->Subject = $subject;
    $mail->Body = $message;
    $mail->AltBody = strip_tags($message);

    $mail->send();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registro</title>
    <link rel="stylesheet" href="../styles/register.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/zxcvbn/4.4.2/zxcvbn.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        .progress-container {
            width: 100%;
            background-color: #e0e0e0;
            border-radius: 8px;
            margin-top: 5px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 5px;
        }
        .progress-bar {
            height: 8px;
            border-radius: 8px;
            transition: width 0.3s;
            flex-grow: 1;
        }
        .strength-text {
            margin-left: 10px;
            font-size: 0.9em;
        }
        .checkbox-container {
            display: flex;
            align-items: center;
            margin-top: 10px;
            font-size: 0.9em;
            color: #555;
        }
        .checkbox-container input[type="checkbox"] {
            margin-right: 10px;
        }
        .checkbox-container a {
            color: #007bff;
            text-decoration: underline;
            cursor: pointer;
        }
        .checkbox-container a:hover {
            color: #0056b3;
        }
        .info-icon {
            color: #007bff;
            cursor: pointer;
            margin-left: 5px;
            font-size: 1.1em;
        }
        .info-icon:hover {
            color: #0056b3;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <h2>Registro de Usuario</h2>

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
                    title: 'Registro Exitoso',
                    text: '<?= $success_message ?>',
                    confirmButtonText: 'Aceptar'
                }).then(() => {
                    window.location.href = 'login.php';
                });
            </script>
        <?php endif; ?>

        <form action="register.php" method="POST">
            <label for="nombre">Nombre:</label>
            <input type="text" name="nombre" id="nombre" value="<?= htmlspecialchars($nombre) ?>" required>
            
            <label for="apellido">Apellido:</label>
            <input type="text" name="apellido" id="apellido" value="<?= htmlspecialchars($apellido) ?>" required>
            
            <label for="correo">Correo electrónico:</label>
            <input type="email" name="correo" id="correo" value="<?= htmlspecialchars($correo) ?>" required>

            <label for="direccion">Dirección:</label>
            <input type="text" name="direccion" id="direccion" value="<?= htmlspecialchars($direccion) ?>" required>
            
            <label for="password">Contraseña:
                <i class="fas fa-info-circle info-icon" onclick="showPasswordInfo()"></i>
            </label>
            <input type="password" name="password" id="password" required>
            <div class="progress-container">
                <div id="password-strength-bar" class="progress-bar"></div>
                <span id="strength-text" class="strength-text">Débil</span>
            </div>
            <label for="confirm_password">Confirmar Contraseña:</label>
            <input type="password" name="confirm_password" id="confirm_password" required>
            
            <div class="checkbox-container">
                <input type="checkbox" id="agree_terms" name="agree_terms" required>
                <label for="agree_terms">Acepto los <a href="../doc/TOS.html" target="_blank">términos y condiciones</a>, incluida la protección de datos según las leyes locales.</label>
            </div>

            <button type="submit">Registrarse</button>
        </form>

        <p>¿Ya tienes una cuenta? <a href="login.php">Inicia sesión aquí</a></p>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const passwordInput = document.getElementById('password');
            const strengthBar = document.getElementById('password-strength-bar');
            const strengthText = document.getElementById('strength-text');

            passwordInput.addEventListener('input', function() {
                const strength = zxcvbn(passwordInput.value);
                const score = strength.score;
                
                const meetsRequirements = passwordInput.value.length >= 8 && /[A-Z]/.test(passwordInput.value) && /[0-9]/.test(passwordInput.value);

                const colors = ['#ff4b4b', '#ffae42', '#ffd700', '#4caf50', '#008000'];
                const labels = ['Muy débil', 'Débil', 'Aceptable', 'Buena', 'Fuerte'];

                if (meetsRequirements && score >= 2) {
                    strengthBar.style.width = '100%';
                    strengthBar.style.backgroundColor = colors[4];
                    strengthText.textContent = 'Aceptable';
                } else {
                    strengthBar.style.width = (score + 1) * 20 + '%';
                    strengthBar.style.backgroundColor = colors[score];
                    strengthText.textContent = labels[score];
                }
            });
        });

        function showPasswordInfo() {
            Swal.fire({
                icon: 'info',
                title: 'Requisitos de Contraseña',
                text: 'La contraseña debe tener al menos 8 caracteres, una letra mayúscula y un número.',
                confirmButtonText: 'Entendido'
            });
        }
    </script>
</body>
</html>

