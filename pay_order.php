<?php
include('dbconnect.php');

$order_id = $_POST['order_id'];
$payment_method = $_POST['payment_method'];

try {

    $pdo->beginTransaction();

    // 1️⃣ Calcular total
    $stmt = $pdo->prepare("
        SELECT SUM(total_price) as total
        FROM order_details
        WHERE order_id = ?
    ");
    $stmt->execute([$order_id]);
    $total = $stmt->fetch()['total'];

    // 2️⃣ Insertar invoice
    $pdo->prepare("
        INSERT INTO invoices (order_id, total, payment_method)
        VALUES (?, ?, ?)
    ")->execute([$order_id, $total, $payment_method]);

    // 3️⃣ Pedido pagado
    $pdo->prepare("
        UPDATE orders SET status='paid'
        WHERE order_id=?
    ")->execute([$order_id]);

    // 4️⃣ Mesa Libre
    $pdo->prepare("
        UPDATE cafe_tables
        SET estado='Libre'
        WHERE id_table = (
            SELECT table_id FROM orders WHERE order_id=?
        )
    ")->execute([$order_id]);

    $pdo->commit();

    echo json_encode(["error"=>0,"total"=>$total]);

} catch (Exception $e) {
    $pdo->rollBack();
    echo json_encode(["error"=>1]);
}