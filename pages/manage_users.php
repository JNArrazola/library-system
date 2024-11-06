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
        $deleted_count = 0;
        foreach ($_POST['delete_user_ids'] as $delete_user_id) {
            if ($delete_user_id != $_SESSION['user_id']) {
                $query_check_role = "SELECT rol FROM Usuarios WHERE id = :usuario_id";
                $stmt_check_role = $pdo->prepare($query_check_role);
                $stmt_check_role->execute(['usuario_id' => $delete_user_id]);
                $user_role = $stmt_check_role->fetchColumn();
    
                if ($user_role !== 'administrador') {
                    if ($_SESSION['rol'] === 'administrador') {
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
    
        if ($deleted_count > 0) {
            $success_message = ($_SESSION['rol'] === 'administrador') 
                ? "Usuarios eliminados correctamente."
                : "Solicitudes de eliminación enviadas correctamente.";
        } else {
            $error_message = "No se pudieron procesar las eliminaciones.";
        }
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
