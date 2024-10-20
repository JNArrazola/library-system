<?php
session_start();
include('../config/config.php');

if (!isset($_SESSION['user_id']) || ($_SESSION['rol'] !== 'administrador' && $_SESSION['rol'] !== 'bibliotecario')) {
    header('Location: ../index.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['user_id'])) {
    $user_id = $_POST['user_id'];
    $nombre = $_POST['nombre'];
    $apellido = $_POST['apellido'];
    $correo = $_POST['correo'];
    $rol = isset($_POST['rol']) ? $_POST['rol'] : null; 

    if ($_SESSION['rol'] === 'administrador') {
        $query = "UPDATE Usuarios SET nombre = :nombre, apellido = :apellido, correo = :correo, rol = :rol WHERE id = :id";
        $stmt = $pdo->prepare($query);
        $stmt->execute([
            'id' => $user_id,
            'nombre' => $nombre,
            'apellido' => $apellido,
            'correo' => $correo,
            'rol' => $rol
        ]);
    } else {
        $query = "UPDATE Usuarios SET nombre = :nombre, apellido = :apellido, correo = :correo WHERE id = :id";
        $stmt = $pdo->prepare($query);
        $stmt->execute([
            'id' => $user_id,
            'nombre' => $nombre,
            'apellido' => $apellido,
            'correo' => $correo
        ]);
    }
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
                        </td>
                    </form>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</body>
</html>
