<?php
session_start();
include('../config/config.php');

if (!isset($_SESSION['user_id']) || ($_SESSION['rol'] !== 'administrador' && $_SESSION['rol'] !== 'bibliotecario')) {
    header('Location: ../index.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['delete_user_id']) && $_POST['delete_user_id'] != $_SESSION['user_id']) {
    $delete_user_id = $_POST['delete_user_id'];
    $query = "DELETE FROM Usuarios WHERE id = :id";
    $stmt = $pdo->prepare($query);
    $stmt->execute(['id' => $delete_user_id]);
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['user_id']) && $_SESSION['rol'] === 'administrador') {
    $user_id = $_POST['user_id'];
    $nombre = $_POST['nombre'];
    $apellido = $_POST['apellido'];
    $correo = $_POST['correo'];
    $rol = $_POST['rol'];

    $query = "UPDATE Usuarios SET nombre = :nombre, apellido = :apellido, correo = :correo, rol = :rol WHERE id = :id";
    $stmt = $pdo->prepare($query);
    $stmt->execute([
        'id' => $user_id,
        'nombre' => $nombre,
        'apellido' => $apellido,
        'correo' => $correo,
        'rol' => $rol
    ]);
} elseif ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['user_id'])) {
    $user_id = $_POST['user_id'];
    $nombre = $_POST['nombre'];
    $apellido = $_POST['apellido'];
    $correo = $_POST['correo'];

    $query = "UPDATE Usuarios SET nombre = :nombre, apellido = :apellido, correo = :correo WHERE id = :id";
    $stmt = $pdo->prepare($query);
    $stmt->execute([
        'id' => $user_id,
        'nombre' => $nombre,
        'apellido' => $apellido,
        'correo' => $correo
    ]);
}

$query = "SELECT * FROM Usuarios";
$stmt = $pdo->query($query);
$users = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestionar Usuarios</title>
    <link rel="stylesheet" href="../styles/manage_users.css">
    <script>
        function confirmDeletion(userId) {
            if (confirm("¿Estás seguro de que quieres eliminar este usuario?")) {
                document.getElementById('delete_user_id_' + userId).submit();
            }
        }
    </script>
</head>
<body>
    <h1>Gestionar Usuarios</h1>

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
                        <td><input type="email" name="correo" value="<?= htmlspecialchars($user['correo']) ?>"></td>
                        <td>
                            <?php if ($_SESSION['rol'] === 'administrador'): ?>
                                <select name="rol">
                                    <option value="usuario" <?= $user['rol'] === 'usuario' ? 'selected' : '' ?>>Usuario</option>
                                    <option value="bibliotecario" <?= $user['rol'] === 'bibliotecario' ? 'selected' : '' ?>>Bibliotecario</option>
                                    <option value="administrador" <?= $user['rol'] === 'administrador' ? 'selected' : '' ?>>Administrador</option>
                                </select>
                            <?php else: ?>
                                <?= htmlspecialchars($user['rol']) ?>
                            <?php endif; ?>
                        </td>
                        <td>
                            <input type="hidden" name="user_id" value="<?= htmlspecialchars($user['id']) ?>">
                            <button type="submit">Guardar</button>
                            <?php if (($_SESSION['rol'] === 'bibliotecario' && $user['rol'] === 'usuario') || $_SESSION['rol'] === 'administrador'): ?>
                                <?php if ($user['id'] != $_SESSION['user_id']): ?>
                                    <form id="delete_user_id_<?= $user['id'] ?>" action="manage_users.php" method="POST" style="display:inline;">
                                        <input type="hidden" name="delete_user_id" value="<?= htmlspecialchars($user['id']) ?>">
                                        <button type="button" class="delete-button" onclick="confirmDeletion(<?= $user['id'] ?>)">Eliminar</button>
                                    </form>
                                <?php endif; ?>
                            <?php endif; ?>
                        </td>
                    </form>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <div class="return-menu">
        <?php if ($_SESSION['rol'] === 'administrador'): ?>
            <a href="administrators/admin_dashboard.php" class="return-button">Volver al Menú Principal</a>
        <?php elseif ($_SESSION['rol'] === 'bibliotecario'): ?>
            <a href="librarians/lib_dashboard.php" class="return-button">Volver al Menú Principal</a>
        <?php endif; ?>
    </div>
</body>
</html>
