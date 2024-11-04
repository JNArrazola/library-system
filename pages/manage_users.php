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
    // Procesamiento de eliminación de usuarios
    if (isset($_POST['delete_user_ids'])) {
        $deleted_count = 0;
        foreach ($_POST['delete_user_ids'] as $delete_user_id) {
            // Evita eliminar al usuario actual y otros administradores
            if ($delete_user_id != $_SESSION['user_id']) {
                // Verifica que el usuario a eliminar no sea un administrador
                $query_check_role = "SELECT rol FROM Usuarios WHERE id = :usuario_id";
                $stmt_check_role = $pdo->prepare($query_check_role);
                $stmt_check_role->execute(['usuario_id' => $delete_user_id]);
                $user_role = $stmt_check_role->fetchColumn();

                if ($user_role !== 'administrador') {
                    // Solo los administradores pueden eliminar usuarios directamente
                    if ($_SESSION['rol'] === 'administrador') {
                        $query = "DELETE FROM Usuarios WHERE id = :usuario_id";
                        $stmt = $pdo->prepare($query);
                        $stmt->execute(['usuario_id' => $delete_user_id]);

                        if ($stmt->rowCount() > 0) {
                            $deleted_count++;
                        }
                    }
                }
            }
        }
        if ($deleted_count > 0) {
            $success_message = "Usuarios eliminados correctamente.";
        } else {
            $error_message = "No se pudieron eliminar los usuarios seleccionados.";
        }
    }

    // Procesamiento de actualización de usuarios
    if (isset($_POST['updates'])) {
        foreach ($_POST['updates'] as $user_id => $fields) {
            if ($_SESSION['rol'] === 'administrador') {
                $query = "UPDATE Usuarios SET nombre = :nombre, apellido = :apellido, direccion = :direccion, rol = :rol WHERE id = :id";
                $stmt = $pdo->prepare($query);
                $stmt->execute([
                    'nombre' => $fields['nombre'],
                    'apellido' => $fields['apellido'],
                    'direccion' => $fields['direccion'],
                    'rol' => $fields['rol'],
                    'id' => $user_id
                ]);
                $success_message = "Cambios aplicados correctamente.";
            }
        }
    }
}

// Carga de usuarios
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
</head>
<body>
    <header>
        <h1>Gestionar Usuarios</h1>
        <div class="user-menu">
            <span><?= htmlspecialchars($_SESSION['nombre']) ?> (<?= htmlspecialchars($_SESSION['rol']) ?>)</span>
            <a href="catalog.php" class="catalog-link">Volver al Catálogo</a>
            <a href="../config/logout.php" class="logout-button">Cerrar sesión</a>
        </div>
    </header>

    <?php if ($success_message): ?>
        <p class="success"><?= htmlspecialchars($success_message) ?></p>
    <?php elseif ($error_message): ?>
        <p class="error"><?= htmlspecialchars($error_message) ?></p>
    <?php endif; ?>

    <form action="manage_users.php" method="POST">
        <table class="styled-table">
            <thead>
                <tr>
                    <th>Eliminar <input type="checkbox" onclick="toggleAll(this)" class="delete-checkbox"></th>
                    <th>ID Usuario</th>
                    <th>Nombre</th>
                    <th>Apellido</th>
                    <th>Correo</th>
                    <th>Dirección</th>
                    <th>Rol</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($users as $user): ?>
                    <tr id="row_<?= $user['id'] ?>">
                        <td>
                            <input type="checkbox" name="delete_user_ids[]" value="<?= $user['id'] ?>" 
                                class="delete-checkbox"
                                <?= ($user['id'] == $_SESSION['user_id'] || $user['rol'] === 'administrador') ? 'disabled' : '' ?>>
                        </td>
                        <td><?= htmlspecialchars($user['id']) ?></td>
                        <td><input type="text" name="updates[<?= $user['id'] ?>][nombre]" value="<?= htmlspecialchars($user['nombre']) ?>" onchange="markRowChanged(<?= $user['id'] ?>)"></td>
                        <td><input type="text" name="updates[<?= $user['id'] ?>][apellido]" value="<?= htmlspecialchars($user['apellido']) ?>" onchange="markRowChanged(<?= $user['id'] ?>)"></td>
                        <td><input type="email" value="<?= htmlspecialchars($user['correo']) ?>" disabled></td>
                        <td><input type="text" name="updates[<?= $user['id'] ?>][direccion]" value="<?= htmlspecialchars($user['direccion']) ?>" onchange="markRowChanged(<?= $user['id'] ?>)"></td>
                        <td>
                            <select name="updates[<?= $user['id'] ?>][rol]" onchange="markRowChanged(<?= $user['id'] ?>)">
                                <option value="usuario" <?= $user['rol'] === 'usuario' ? 'selected' : '' ?>>Usuario</option>
                                <option value="bibliotecario" <?= $user['rol'] === 'bibliotecario' ? 'selected' : '' ?>>Bibliotecario</option>
                                <?php if ($_SESSION['rol'] === 'administrador'): ?>
                                    <option value="administrador" <?= $user['rol'] === 'administrador' ? 'selected' : '' ?>>Administrador</option>
                                <?php endif; ?>
                            </select>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <div class="button-group">
            <button type="submit" id="submit_button" onclick="return confirmUpdate();">Aplicar cambios</button>
            <button type="submit" id="delete_button" onclick="return confirmDeletion();" name="delete">Eliminar seleccionados</button>
        </div>
    </form>

    <div class="return-menu">
        <a href="catalog.php" class="return-button">Volver al Menú Principal</a>
    </div>
</body>
</html>
