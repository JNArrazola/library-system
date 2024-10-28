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
    if (isset($_POST['delete_user_ids'])) {
        foreach ($_POST['delete_user_ids'] as $delete_user_id) {
            if ($delete_user_id != $_SESSION['user_id']) {
                if ($_SESSION['rol'] === 'administrador') {
                    $query = "DELETE FROM Usuarios WHERE id = :usuario_id";
                    $stmt = $pdo->prepare($query);
                    $stmt->execute(['usuario_id' => $delete_user_id]);
                } else {
                    $check_query = "SELECT COUNT(*) FROM Solicitudes WHERE tipo = 'eliminacion' AND usuario_id = :usuario_id AND solicitante_id = :solicitante_id AND estado = 'pendiente'";
                    $check_stmt = $pdo->prepare($check_query);
                    $check_stmt->execute(['usuario_id' => $delete_user_id, 'solicitante_id' => $_SESSION['user_id']]);
                    
                    if ($check_stmt->fetchColumn() == 0) {
                        $query = "INSERT INTO Solicitudes (tipo, usuario_id, solicitante_id, estado) VALUES ('eliminacion', :usuario_id, :solicitante_id, 'pendiente')";
                        $stmt = $pdo->prepare($query);
                        $stmt->execute(['usuario_id' => $delete_user_id, 'solicitante_id' => $_SESSION['user_id']]);
                    }
                }
            }
        }
        $success_message = ($_SESSION['rol'] === 'administrador') ? "Usuarios eliminados correctamente." : "Solicitudes de eliminación enviadas correctamente.";
    }
    
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
            } else {
                $original_query = "SELECT * FROM Usuarios WHERE id = :id";
                $original_stmt = $pdo->prepare($original_query);
                $original_stmt->execute(['id' => $user_id]);
                $original_user = $original_stmt->fetch();

                $changes = [];
                if ($fields['nombre'] !== $original_user['nombre']) {
                    $changes['nombre'] = $fields['nombre'];
                }
                if ($fields['apellido'] !== $original_user['apellido']) {
                    $changes['apellido'] = $fields['apellido'];
                }
                if ($fields['direccion'] !== $original_user['direccion']) {
                    $changes['direccion'] = $fields['direccion'];
                }
                if (isset($fields['rol']) && $fields['rol'] !== $original_user['rol']) {
                    $changes['rol'] = $fields['rol'];
                }

                if (!empty($changes)) {
                    $detalles_cambio = json_encode($changes);
                    $query = "INSERT INTO Solicitudes (tipo, usuario_id, solicitante_id, estado, detalles_cambio) VALUES ('actualizacion', :usuario_id, :solicitante_id, 'pendiente', :detalles_cambio)";
                    $stmt = $pdo->prepare($query);
                    $stmt->execute([
                        'usuario_id' => $user_id,
                        'solicitante_id' => $_SESSION['user_id'],
                        'detalles_cambio' => $detalles_cambio
                    ]);
                    $success_message = "Solicitudes de actualización enviadas correctamente.";
                }
            }
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
    const confirmDeleteMessage = <?php echo ($_SESSION['rol'] === 'administrador') ? "'¿Estás seguro de que quieres realizar esta eliminación?'" : "'¿Estás seguro de que quieres solicitar la eliminación?'"; ?>;
    const confirmUpdateMessage = <?php echo ($_SESSION['rol'] === 'administrador') ? "'¿Estás seguro de que quieres aplicar los cambios?'" : "'¿Estás seguro de que quieres solicitar esta actualización?'"; ?>;

    function toggleDeletionMode() {
        const checkboxes = document.querySelectorAll('.delete-checkbox');
        const deleteButton = document.getElementById('delete_button');
        const submitButton = document.getElementById('submit_button');
        const isDeletionMode = deleteButton.style.display === 'inline';

        checkboxes.forEach(checkbox => checkbox.style.display = isDeletionMode ? 'none' : 'inline');
        deleteButton.style.display = isDeletionMode ? 'none' : 'inline';
        submitButton.style.display = isDeletionMode ? 'inline' : 'none';
    }

    function markRowChanged(userId) {
        const row = document.getElementById('row_' + userId);
        row.classList.add('changed-row');
    }

    function confirmDeletion() {
        return confirm(confirmDeleteMessage);
    }

    function confirmUpdate() {
        return confirm(confirmUpdateMessage);
    }

    function toggleAll(source) {
        const checkboxes = document.querySelectorAll('input[name="delete_user_ids[]"]');
        checkboxes.forEach(checkbox => checkbox.checked = source.checked);
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

    <form action="manage_users.php" method="POST">
    <table>
        <thead>
            <tr>
                <th><input type="checkbox" onclick="toggleAll(this)" class="delete-checkbox"></th>
                <th>ID Usuario</th>
                <th>Nombre</th>
                <th>Apellido</th>
                <th>Correo</th>
                <th>Dirección</th>
                <th>Rol</th>
                <th>Acción</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($users as $user): ?>
                <tr id="row_<?= $user['id'] ?>">
                    <td><input type="checkbox" name="delete_user_ids[]" value="<?= $user['id'] ?>" class="delete-checkbox"></td>
                    <td><?= htmlspecialchars($user['id']) ?></td>
                    <td><input type="text" name="updates[<?= $user['id'] ?>][nombre]" value="<?= htmlspecialchars($user['nombre']) ?>" onchange="markRowChanged(<?= $user['id'] ?>)"></td>
                    <td><input type="text" name="updates[<?= $user['id'] ?>][apellido]" value="<?= htmlspecialchars($user['apellido']) ?>" onchange="markRowChanged(<?= $user['id'] ?>)"></td>
                    <td><input type="email" value="<?= htmlspecialchars($user['correo']) ?>" disabled></td>
                    <td><input type="text" name="updates[<?= $user['id'] ?>][direccion]" value="<?= htmlspecialchars($user['direccion']) ?>" onchange="markRowChanged(<?= $user['id'] ?>)"></td>
                    <td>
                        <?php if ($_SESSION['rol'] === 'bibliotecario' && $user['rol'] !== 'administrador'): ?>
                            <select name="updates[<?= $user['id'] ?>][rol]" onchange="markRowChanged(<?= $user['id'] ?>)">
                                <option value="usuario" <?= $user['rol'] === 'usuario' ? 'selected' : '' ?>>Usuario</option>
                                <option value="bibliotecario" <?= $user['rol'] === 'bibliotecario' ? 'selected' : '' ?>>Bibliotecario</option>
                            </select>
                        <?php elseif ($_SESSION['rol'] === 'administrador'): ?>
                            <select name="updates[<?= $user['id'] ?>][rol]" onchange="markRowChanged(<?= $user['id'] ?>)">
                                <option value="usuario" <?= $user['rol'] === 'usuario' ? 'selected' : '' ?>>Usuario</option>
                                <option value="bibliotecario" <?= $user['rol'] === 'bibliotecario' ? 'selected' : '' ?>>Bibliotecario</option>
                                <option value="administrador" <?= $user['rol'] === 'administrador' ? 'selected' : '' ?>>Administrador</option>
                            </select>
                        <?php else: ?>
                            <?= htmlspecialchars($user['rol']) ?>
                        <?php endif; ?>
                    </td>
                    <td><button type="submit" name="update_single" value="<?= $user['id'] ?>" onclick="return confirmUpdate();">Actualizar</button></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <?php if ($_SESSION['rol'] === 'administrador'): ?>
        <div class="button-group" style="display: flex; gap: 10px; margin-top: 20px;">
            <button type="button" onclick="toggleDeletionMode()">Marcar para eliminar</button>
            <button type="submit" id="submit_button" onclick="return confirmUpdate();">Aplicar cambios a todos</button>
            <button type="submit" id="delete_button" style="display:none;" onclick="return confirmDeletion();">Eliminar seleccionados</button>
        </div>
    <?php elseif ($_SESSION['rol'] === 'bibliotecario'): ?>
        <div class="button-group" style="display: flex; gap: 10px; margin-top: 20px;">
            <button type="button" onclick="toggleDeletionMode()">Marcar para eliminar</button>
            <button type="submit" id="submit_button" onclick="return confirmUpdate();">Enviar todos los cambios</button>
            <button type="submit" id="delete_button" style="display:none;" onclick="return confirmDeletion();">Enviar solicitudes de eliminación</button>
        </div>
    <?php endif; ?>
</form>


    <div class="return-menu">
        <?php if ($_SESSION['rol'] === 'administrador'): ?>
            <a href="administrators/admin_dashboard.php" class="return-button">Volver al Menú Principal</a>
        <?php elseif ($_SESSION['rol'] === 'bibliotecario'): ?>
            <a href="librarians/librarian_dashboard.php" class="return-button">Volver al Menú Principal</a>
        <?php endif; ?>
    </div>
</body>
</html>

