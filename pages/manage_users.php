<?php
session_start();
include('../config/config.php');

if (!isset($_SESSION['user_id']) || ($_SESSION['rol'] !== 'administrador' && $_SESSION['rol'] !== 'bibliotecario')) {
    header('Location: ../index.php');
    exit();
}

$success_message = '';
$error_message = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['delete_user_id']) && $_POST['delete_user_id'] != $_SESSION['user_id']) {
        $delete_user_id = $_POST['delete_user_id'];

        $check_query = "SELECT COUNT(*) FROM Solicitudes WHERE tipo = 'eliminacion' AND usuario_id = :usuario_id AND solicitante_id = :solicitante_id AND estado = 'pendiente'";
        $check_stmt = $pdo->prepare($check_query);
        $check_stmt->execute(['usuario_id' => $delete_user_id, 'solicitante_id' => $_SESSION['user_id']]);
        
        if ($check_stmt->fetchColumn() == 0) {
            $query = "INSERT INTO Solicitudes (tipo, usuario_id, solicitante_id, estado) VALUES ('eliminacion', :usuario_id, :solicitante_id, 'pendiente')";
            $stmt = $pdo->prepare($query);
            $stmt->execute(['usuario_id' => $delete_user_id, 'solicitante_id' => $_SESSION['user_id']]);
            $success_message = "Solicitud de eliminación enviada correctamente.";
        } else {
            $error_message = "Ya existe una solicitud pendiente de eliminación para este usuario.";
        }
    }
    
    if (isset($_POST['user_id']) && ($_SESSION['rol'] === 'administrador' || $_SESSION['rol'] === 'bibliotecario')) {
        $user_id = $_POST['user_id'];
        $nombre = $_POST['nombre'];
        $apellido = $_POST['apellido'];
        $direccion = $_POST['direccion'];
        $rol = $_POST['rol'] ?? 'usuario';

        $check_query = "SELECT COUNT(*) FROM Solicitudes WHERE tipo = 'actualizacion' AND usuario_id = :usuario_id AND solicitante_id = :solicitante_id AND estado = 'pendiente'";
        $check_stmt = $pdo->prepare($check_query);
        $check_stmt->execute(['usuario_id' => $user_id, 'solicitante_id' => $_SESSION['user_id']]);

        if ($check_stmt->fetchColumn() == 0) {
            $detalles_cambio = json_encode([
                'nombre' => $nombre,
                'apellido' => $apellido,
                'direccion' => $direccion,
                'rol' => $rol
            ]);

            $query = "INSERT INTO Solicitudes (tipo, usuario_id, solicitante_id, estado, detalles_cambio) VALUES ('actualizacion', :usuario_id, :solicitante_id, 'pendiente', :detalles_cambio)";
            $stmt = $pdo->prepare($query);
            $stmt->execute([
                'usuario_id' => $user_id,
                'solicitante_id' => $_SESSION['user_id'],
                'detalles_cambio' => $detalles_cambio
            ]);
            $success_message = "Solicitud de actualización enviada correctamente.";
        } else {
            $error_message = "Ya existe una solicitud pendiente de actualización para este usuario.";
        }
    }
}

$condition = ($_SESSION['rol'] === 'bibliotecario') ? "WHERE rol = 'usuario'" : "";
$query = "SELECT * FROM Usuarios $condition";
$stmt = $pdo->query($query);
$users = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestionar Usuarios</title>
    <link rel="stylesheet" href="../styles/manage_users.css?v=<?= time(); ?>">
    <script>
        function confirmDeletion(userId) {
            if (confirm("¿Estás seguro de que quieres enviar la solicitud para eliminar este usuario?")) {
                document.getElementById('delete_user_form_' + userId).submit();
            }
        }
    </script>
</head>
<body>
    <h1>Gestionar Usuarios</h1>

    <?php if ($success_message): ?>
        <p class="success"><?= htmlspecialchars($success_message) ?></p>
    <?php elseif ($error_message): ?>
        <p class="error"><?= htmlspecialchars($error_message) ?></p>
    <?php endif; ?>

    <div class="add-user">
        <a href="add_user.php" class="add-user-button">Agregar Usuario</a> 
    </div>

    <table>
        <thead>
            <tr>
                <th>ID Usuario</th>
                <th>Nombre</th>
                <th>Apellido</th>
                <th>Correo</th>
                <th>Dirección</th>
                <th>Rol</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($users as $user): ?>
                <tr>
                    <form action="manage_users.php" method="POST">
                        <td><?= htmlspecialchars($user['id']) ?></td>
                        <td><input type="text" name="nombre" value="<?= htmlspecialchars($user['nombre']) ?>"></td>
                        <td><input type="text" name="apellido" value="<?= htmlspecialchars($user['apellido']) ?>"></td>
                        <td><input type="email" value="<?= htmlspecialchars($user['correo']) ?>" disabled></td> 
                        <td><input type="text" name="direccion" value="<?= htmlspecialchars($user['direccion']) ?>"></td>
                        <td>
                            <?php if ($_SESSION['rol'] === 'administrador' || ($_SESSION['rol'] === 'bibliotecario' && $user['rol'] !== 'administrador')): ?>
                                <select name="rol">
                                    <option value="usuario" <?= $user['rol'] === 'usuario' ? 'selected' : '' ?>>Usuario</option>
                                    <option value="bibliotecario" <?= $user['rol'] === 'bibliotecario' ? 'selected' : '' ?>>Bibliotecario</option>
                                    <?php if ($_SESSION['rol'] === 'administrador'): ?>
                                        <option value="administrador" <?= $user['rol'] === 'administrador' ? 'selected' : '' ?>>Administrador</option>
                                    <?php endif; ?>
                                </select>
                            <?php else: ?>
                                <?= htmlspecialchars($user['rol']) ?>
                            <?php endif; ?>
                        </td>
                        <td>
                            <input type="hidden" name="user_id" value="<?= htmlspecialchars($user['id']) ?>">
                            <button type="submit">Solicitar Cambio</button>
                        </td>
                    </form>
                    
                    <?php if (($_SESSION['rol'] === 'bibliotecario' && $user['rol'] === 'usuario') || $_SESSION['rol'] === 'administrador'): ?>
                        <?php if ($user['id'] != $_SESSION['user_id']): ?>
                            <td>
                                <form id="delete_user_form_<?= $user['id'] ?>" action="manage_users.php" method="POST" style="display:inline;">
                                    <input type="hidden" name="delete_user_id" value="<?= htmlspecialchars($user['id']) ?>">
                                    <button type="button" class="delete-button" onclick="confirmDeletion(<?= $user['id'] ?>)">Solicitar Eliminación</button>
                                </form>
                            </td>
                        <?php endif; ?>
                    <?php endif; ?>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <div class="return-menu">
        <?php if ($_SESSION['rol'] === 'administrador'): ?>
            <a href="administrators/admin_dashboard.php" class="return-button">Volver al Menú Principal</a>
        <?php elseif ($_SESSION['rol'] === 'bibliotecario'): ?>
            <a href="librarians/librarian_dashboard.php" class="return-button">Volver al Menú Principal</a>
        <?php endif; ?>
    </div>
</body>
</html>
