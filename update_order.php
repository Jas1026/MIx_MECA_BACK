<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type");
header("Access-Control-Allow-Methods: POST, OPTIONS");

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    exit(0);
}

include('dbconnect.php');

$order_id = $_POST['order_id'] ?? null;
$products = isset($_POST['products']) ? json_decode($_POST['products'], true) : [];

if (!$order_id || empty($products)) {
    echo json_encode(["error"=>1,"message"=>"Datos incompletos"]);
    exit;
}

try {

    $pdo->beginTransaction();

    // 1️⃣ Borrar detalles actuales
    $pdo->prepare("DELETE FROM order_details WHERE order_id = ?")
        ->execute([$order_id]);

    // 2️⃣ Insertar nuevos detalles
    foreach ($products as $p) {

        $stmtKitchen = $pdo->prepare("
            SELECT kitchen_id 
            FROM product_kitchen
            WHERE product_id = ?
        ");
        $stmtKitchen->execute([$p['id_product']]);
        $kitchen = $stmtKitchen->fetch(PDO::FETCH_ASSOC);
        $kitchen_id = $kitchen ? $kitchen['kitchen_id'] : null;

        $total_price = $p['price'] * $p['quantity'];

        $stmt = $pdo->prepare("
            INSERT INTO order_details
            (order_id, product_id, kitchen_id, quantity, unit_price, total_price, preparation_time, status)
            VALUES (?, ?, ?, ?, ?, ?, 0, 'pending')
        ");

        $stmt->execute([
            $order_id,
            $p['id_product'],
            $kitchen_id,
            $p['quantity'],
            $p['price'],
            $total_price
        ]);
    }

    $pdo->commit();

    echo json_encode(["error"=>0]);

} catch (Exception $e) {

    $pdo->rollBack();
    echo json_encode(["error"=>1,"message"=>$e->getMessage()]);
}