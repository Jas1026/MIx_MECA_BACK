<?php
include('dbconnect.php');

$id_order = $_POST['id_order'];
$status = $_POST['status'];

$pdo->prepare("UPDATE orders SET status = ? WHERE order_id = ?")
    ->execute([$status, $id_order]);

if ($status === 'closed') {

    $pdo->prepare("
        UPDATE cafe_tables 
        SET estado = 'Libre'
        WHERE id_table = (
            SELECT id_table FROM orders WHERE order_id = ?
        )
    ")->execute([$id_order]);
}

echo json_encode(["error" => 0]);