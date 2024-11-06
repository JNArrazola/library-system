<?php
// return_functions.php

function getUsuariosActivos($pdo) {
    $query = "SELECT id, nombre, apellido, correo FROM Usuarios WHERE is_active = 1 AND rol = 'usuario'";
    $stmt = $pdo->query($query);
    return $stmt->fetchAll();
}

function registrarDevolucion($pdo, $reserva_id) {
    $fecha_actual = date('Y-m-d');

    $query = "UPDATE Reservas SET fecha_devolucion = :fecha_devolucion, B_Entregado = 1 WHERE id = :reserva_id";
    $stmt = $pdo->prepare($query);
    $stmt->execute(['fecha_devolucion' => $fecha_actual, 'reserva_id' => $reserva_id]);

    $query = "SELECT libro_id FROM Reservas WHERE id = :reserva_id";
    $stmt = $pdo->prepare($query);
    $stmt->execute(['reserva_id' => $reserva_id]);
    $libro = $stmt->fetch();

    $query = "UPDATE Libros SET cantidad = cantidad + 1 WHERE id = :libro_id";
    $stmt = $pdo->prepare($query);
    $stmt->execute(['libro_id' => $libro['libro_id']]);

    return 'Devolución registrada exitosamente.';
}

function obtenerPrestamosPendientes($pdo, $usuario_id = null) {
    $condition = "r.B_Entregado = 0 AND r.fecha_recepcion IS NOT NULL";
    if ($usuario_id) {
        $condition .= " AND r.usuario_id = :usuario_id";
    }

    $query = "SELECT r.id, l.nombre AS libro_nombre, r.fecha_recepcion, u.nombre AS usuario_nombre, u.apellido AS usuario_apellido 
              FROM Reservas r
              JOIN Libros l ON r.libro_id = l.id
              JOIN Usuarios u ON r.usuario_id = u.id
              WHERE $condition";
    $stmt = $pdo->prepare($query);
    if ($usuario_id) {
        $stmt->execute(['usuario_id' => $usuario_id]);
    } else {
        $stmt->execute();
    }

    return $stmt->fetchAll();
}
