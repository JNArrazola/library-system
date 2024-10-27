<?php
session_start();
include('../../config/config.php');

if (!isset($_SESSION['user_id']) || $_SESSION['rol'] !== 'usuario') {
    header('Location: ../../index.php');
    exit();
}

if (!isset($_GET['id'])) {
    header('Location: ../catalog.php');
    exit();
}

$book_id = $_GET['id'];

$query = "SELECT * FROM Reservas WHERE usuario_id = :usuario_id AND libro_id = :libro_id AND fecha_recepcion IS NULL";
$stmt = $pdo->prepare($query);
$stmt->execute([
    'usuario_id' => $_SESSION['user_id'],
    'libro_id' => $book_id
]);
$existing_reservation = $stmt->fetch();

if ($existing_reservation) {
    header('Location: ../book_details.php?id=' . $book_id . '&error=Ya has reservado este libro');
    exit();
}

$query = "SELECT cantidad FROM Libros WHERE id = :book_id";
$stmt = $pdo->prepare($query);
$stmt->execute(['book_id' => $book_id]);
$book = $stmt->fetch();

if (!$book || $book['cantidad'] <= 0) {
    header('Location: ../book_details.php?id=' . $book_id . '&error=El libro no está disponible para reserva');
    exit();
}

$query = "INSERT INTO Reservas (usuario_id, libro_id, fecha_reserva, fecha_recepcion, fecha_devolucion, B_Entregado)
          VALUES (:usuario_id, :libro_id, CURDATE(), NULL, NULL, 0)";
$stmt = $pdo->prepare($query);
$stmt->execute([
    'usuario_id' => $_SESSION['user_id'],
    'libro_id' => $book_id
]);

$query = "UPDATE Libros SET cantidad = cantidad - 1 WHERE id = :book_id";
$stmt = $pdo->prepare($query);
$stmt->execute(['book_id' => $book_id]);

header('Location: ../book_details.php?id=' . $book_id . '&success=Reserva realizada con éxito');
exit();
