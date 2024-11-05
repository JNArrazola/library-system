<?php
session_start();
include('../config/config.php');

if (isset($_GET['correo'])) {
    $correo = $_GET['correo'];

    // Verifica que el usuario y el token coincidan
    $query = "SELECT * FROM Usuarios WHERE correo = :correo AND is_active = 0";
    $stmt = $pdo->prepare($query);
    $stmt->execute(['correo' => $correo]);
    $user = $stmt->fetch();

    if ($user) {
        // Activa la cuenta y elimina el token de activación
        $query = "UPDATE Usuarios SET is_active = 1 WHERE correo = :correo";
        $stmt = $pdo->prepare($query);
        $stmt->execute(['correo' => $correo]);
    } else {
        echo "Enlace de activación no válido o cuenta ya activada.";
    }
} else {
    echo "Parámetros de activación faltantes.";
}
?>
<script>
    alert("Cuenta activada con éxito. Ahora puedes iniciar sesión.");
    window.location.href = 'login.php';
</script>
