<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    exit(0);
}

include('dbconnect.php');

$id_table = $_POST['id_table'] ?? null;
$id_user = $_POST['id_user'] ?? null;
$products = isset($_POST['products']) ? json_decode($_POST['products'], true) : [];

if (!$id_table || !$id_user || empty($products)) {
    echo json_encode(["error"=>1,"message"=>"Datos incompletos"]);
    exit;
}

try {

    $pdo->beginTransaction();

    // 1️⃣ Crear pedido
    $stmt = $pdo->prepare("
        INSERT INTO orders 
        (table_id, user_id, order_date, status, estimated_time, actual_time)
        VALUES (?, ?, NOW(), 'open', 0, 0)
    ");
    $stmt->execute([$id_table, $id_user]);
    $id_order = $pdo->lastInsertId();

    // 2️⃣ Insertar detalles con kitchen_id
    foreach ($products as $p) {

        // Obtener kitchen
        $stmtKitchen = $pdo->prepare("
    SELECT kitchen_id 
    FROM `product_kitchen`
    WHERE product_id = ?
");
        $stmtKitchen->execute([$p['id_product']]);
        $kitchen = $stmtKitchen->fetch(PDO::FETCH_ASSOC);

        $kitchen_id = $kitchen ? $kitchen['kitchen_id'] : null;

        $total_price = $p['price'] * $p['quantity'];

        $stmtDetail = $pdo->prepare("
            INSERT INTO order_details
            (order_id, product_id, kitchen_id, quantity, unit_price, total_price, preparation_time, status)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ");

        $stmtDetail->execute([
            $id_order,
            $p['id_product'],
            $kitchen_id,
            $p['quantity'],
            $p['price'],
            $total_price,
            0,
            'pending'
        ]);
    }

    // 3️⃣ Mesa → Pendiente
    $pdo->prepare("UPDATE cafe_tables SET estado = 'Pendiente' WHERE id_table = ?")
        ->execute([$id_table]);

    $pdo->commit();

    echo json_encode(["error"=>0,"id_order"=>$id_order]);

} catch (Exception $e) {

    $pdo->rollBack();
    echo json_encode(["error"=>1,"message"=>$e->getMessage()]);
}