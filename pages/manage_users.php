<?php
session_start();
include('../config/config.php');

if (!isset($_SESSION['user_id']) || ($_SESSION['rol'] !== 'administrador' && $_SESSION['rol'] !== 'bibliotecario' && $_SESSION['rol'] !== 'admin_general')) {
    header('Location: ../index.php');
    exit();
}

$success_message = '';
$error_message = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['delete_user_ids'])) {
        $deleted_count = 0;
        foreach ($_POST['delete_user_ids'] as $delete_user_id) {
            if ($delete_user_id != $_SESSION['user_id']) {
                $query_check_role = "SELECT rol FROM Usuarios WHERE id = :usuario_id";
                $stmt_check_role = $pdo->prepare($query_check_role);
                $stmt_check_role->execute(['usuario_id' => $delete_user_id]);
                $user_role = $stmt_check_role->fetchColumn();

                if ($user_role !== 'administrador') {
                    if ($_SESSION['rol'] === 'administrador' || $_SESSION['rol'] === 'admin_general') {
                        $query = "DELETE FROM Usuarios WHERE id = :usuario_id";
                        $stmt = $pdo->prepare($query);
                        $stmt->execute(['usuario_id' => $delete_user_id]);

                        if ($stmt->rowCount() > 0) {
                            $deleted_count++;
                        }
                    } else {
                        $check_query = "SELECT COUNT(*) FROM Solicitudes WHERE tipo = 'eliminacion' AND usuario_id = :usuario_id AND solicitante_id = :solicitante_id AND estado = 'pendiente'";
                        $check_stmt = $pdo->prepare($check_query);
                        $check_stmt->execute(['usuario_id' => $delete_user_id, 'solicitante_id' => $_SESSION['user_id']]);

                        if ($check_stmt->fetchColumn() == 0) {
                            $query = "INSERT INTO Solicitudes (tipo, usuario_id, solicitante_id, estado) VALUES ('eliminacion', :usuario_id, :solicitante_id, 'pendiente')";
                            $stmt = $pdo->prepare($query);
                            $stmt->execute(['usuario_id' => $delete_user_id, 'solicitante_id' => $_SESSION['user_id']]);
                            $deleted_count++;
                        }
                    }
                }
            }
        }

        $success_message = ($deleted_count > 0) ? "Usuarios eliminados correctamente." : "No se pudieron procesar las eliminaciones.";
    }

    if (isset($_POST['updates'])) {
        foreach ($_POST['updates'] as $user_id => $fields) {
            if ($_SESSION['rol'] === 'administrador' || $_SESSION['rol'] === 'admin_general') {
                $update_fields = [];
                $params = ['id' => $user_id];

                if (isset($fields['nombre'])) {
                    $update_fields[] = 'nombre = :nombre';
                    $params['nombre'] = $fields['nombre'];
                }
                if (isset($fields['apellido'])) {
                    $update_fields[] = 'apellido = :apellido';
                    $params['apellido'] = $fields['apellido'];
                }
                if (isset($fields['correo'])) {
                    if (filter_var($fields['correo'], FILTER_VALIDATE_EMAIL)) {
                        $update_fields[] = 'correo = :correo';
                        $params['correo'] = $fields['correo'];
                    } else {
                        $error_message = "Correo inválido para el usuario con ID $user_id.";
                        break;
                    }
                }
                if (isset($fields['direccion'])) {
                    $update_fields[] = 'direccion = :direccion';
                    $params['direccion'] = $fields['direccion'];
                }
                if (isset($fields['rol'])) {
                    $update_fields[] = 'rol = :rol';
                    $params['rol'] = $fields['rol'];
                }

                if (!empty($update_fields) && !$error_message) {
                    $query = "UPDATE Usuarios SET " . implode(', ', $update_fields) . " WHERE id = :id";
                    $stmt = $pdo->prepare($query);
                    $stmt->execute($params);
                    $success_message = "Cambios aplicados correctamente.";
                }
            } else {
                $original_query = "SELECT * FROM Usuarios WHERE id = :id";
                $original_stmt = $pdo->prepare($original_query);
                $original_stmt->execute(['id' => $user_id]);
                $original_user = $original_stmt->fetch();

                $changes = [];
                if (isset($fields['nombre']) && $fields['nombre'] !== $original_user['nombre']) {
                    $changes['nombre'] = $fields['nombre'];
                }
                if (isset($fields['apellido']) && $fields['apellido'] !== $original_user['apellido']) {
                    $changes['apellido'] = $fields['apellido'];
                }
                if (isset($fields['correo']) && $fields['correo'] !== $original_user['correo']) {
                    if (filter_var($fields['correo'], FILTER_VALIDATE_EMAIL)) {
                        $changes['correo'] = $fields['correo'];
                    } else {
                        $error_message = "Correo inválido para el usuario con ID $user_id.";
                        break;
                    }
                }
                if (isset($fields['direccion']) && $fields['direccion'] !== $original_user['direccion']) {
                    $changes['direccion'] = $fields['direccion'];
                }
                if (isset($fields['rol']) && $fields['rol'] !== $original_user['rol']) {
                    $changes['rol'] = $fields['rol'];
                }

                if (!empty($changes) && !$error_message) {
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

$condition = "WHERE id != :user_id";
if ($_SESSION['rol'] === 'bibliotecario') {
    $condition .= " AND rol IN ('usuario', 'bibliotecario')";
} elseif ($_SESSION['rol'] === 'admin_general') {
    $condition .= " AND rol IN ('usuario', 'bibliotecario')"; 
} elseif ($_SESSION['rol'] === 'administrador') {
}

$query = "SELECT * FROM Usuarios $condition";
$stmt = $pdo->prepare($query);
$stmt->execute(['user_id' => $_SESSION['user_id']]);
$users = $stmt->fetchAll();

?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestionar Usuarios</title>
    <link rel="stylesheet" href="../styles/manage_users.css?v=<?= time(); ?>">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
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

    <div style="text-align: center; margin-top: 20px;">
        <a href="add_user.php" class="add-user-button">Añadir usuario</a>
    </div>

    <?php if ($success_message): ?>
        <script>
            Swal.fire({
                icon: 'success',
                title: 'Éxito',
                text: '<?= $success_message ?>',
                confirmButtonText: 'Aceptar'
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

    <form action="manage_users.php" method="POST" id="manageForm">
        <table class="styled-table">
            <thead>
                <tr>
                    <th>Seleccionar</th>
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
                <tr id="row_<?= $user['id'] ?>">
                    <td>
                        <input type="checkbox" name="select_user[]" value="<?= $user['id'] ?>" onclick="toggleRow(<?= $user['id'] ?>)">
                    </td>
                    <td><?= htmlspecialchars($user['id']) ?></td>
                    <td><input type="text" name="updates[<?= $user['id'] ?>][nombre]" class="custom-input" value="<?= htmlspecialchars($user['nombre']) ?>" disabled></td>
                    <td><input type="text" name="updates[<?= $user['id'] ?>][apellido]" class= "custom-input" value="<?= htmlspecialchars($user['apellido']) ?>" disabled></td>
                    <td><input type="email" name="updates[<?= $user['id'] ?>][correo]" class= "custom-input" value="<?= htmlspecialchars($user['correo']) ?>" disabled></td>
                    <td><input type="text" name="updates[<?= $user['id'] ?>][direccion]" class= "custom-input" value="<?= htmlspecialchars($user['direccion']) ?>" disabled></td>
                    <td>
    <select name="updates[<?= $user['id'] ?>][rol]" disabled>
        <option value="usuario" <?= $user['rol'] === 'usuario' ? 'selected' : '' ?>>Usuario</option>
        <option value="bibliotecario" <?= $user['rol'] === 'bibliotecario' ? 'selected' : '' ?>>Bibliotecario</option>
        <?php if ($_SESSION['rol'] === 'administrador'): ?>
            <option value="admin_general" <?= $user['rol'] === 'admin_general' ? 'selected' : '' ?>>Admin General</option>
            <option value="administrador" <?= $user['rol'] === 'administrador' ? 'selected' : '' ?>>Administrador</option>
        <?php endif; ?>
    </select>
</td>



                    <td>
                        <button type="button" class="delete-button" onclick="confirmDeleteUser(<?= $user['id'] ?>)" id="delete_<?= $user['id'] ?>" disabled>Eliminar</button>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>

        <div class="button-group">
            <button type="button" id="submit_button" onclick="confirmUpdate()">Aplicar cambios</button>
        </div>
    </form>

    <div class="return-menu">
        <a href="catalog.php" class="return-button">Volver al Menú Principal</a>
    </div>

    <script>
        function confirmUpdate() {
            Swal.fire({
                icon: 'question',
                title: '¿Confirmar cambios?',
                text: '¿Estás seguro de que deseas aplicar los cambios?',
                showCancelButton: true,
                confirmButtonText: 'Sí, aplicar',
                cancelButtonText: 'Cancelar'
            }).then((result) => {
                if (result.isConfirmed) {
                    document.getElementById('manageForm').submit();
                }
            });
        }

        function toggleRow(userId) {
            const row = document.getElementById(`row_${userId}`);
            const checkbox = row.querySelector(`input[name="select_user[]"][value="${userId}"]`);
            const inputs = row.querySelectorAll('input, select, button');
            inputs.forEach(input => {
                if (input.type !== 'checkbox') {
                    input.disabled = !checkbox.checked;
                }
            });
        }

        function confirmDeleteUser(userId) {
            Swal.fire({
                icon: 'warning',
                title: '¿Confirmar eliminación?',
                text: '¿Estás seguro de que deseas eliminar este usuario?',
                showCancelButton: true,
                confirmButtonText: 'Sí, eliminar',
                cancelButtonText: 'Cancelar'
            }).then((result) => {
                if (result.isConfirmed) {
                    const deleteField = document.createElement('input');
                    deleteField.type = 'hidden';
                    deleteField.name = 'delete_user_ids[]';
                    deleteField.value = userId;
                    document.getElementById('manageForm').appendChild(deleteField);
                    document.getElementById('manageForm').submit();
                }
            });
        }
    </script>
</body>
</html>
