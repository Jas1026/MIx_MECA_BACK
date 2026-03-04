<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type");
header("Access-Control-Allow-Methods: POST, OPTIONS");

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') { exit(0); }

include('dbconnect.php');

$order_id = $_POST['order_id'] ?? null;
$products = isset($_POST['products']) ? json_decode($_POST['products'], true) : [];

if (!$order_id || empty($products)) {
    echo json_encode(["error"=>1,"message"=>"Datos incompletos"]);
    exit;
}

try {
    $pdo->beginTransaction();

    // 1️⃣ Borrar detalles actuales para reemplazarlos
    $pdo->prepare("DELETE FROM order_details WHERE order_id = ?")
        ->execute([$order_id]);

    // 2️⃣ Preparar la inserción con los 10 campos (incluyendo notes y sides)
    $stmtDetail = $pdo->prepare("
        INSERT INTO order_details
        (order_id, product_id, kitchen_id, quantity, unit_price, total_price, preparation_time, status, notes, sides)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");

    foreach ($products as $p) {
        // Buscamos la cocina del producto
        $stmtKitchen = $pdo->prepare("SELECT kitchen_id FROM product_kitchen WHERE product_id = ?");
        $stmtKitchen->execute([$p['id_product']]);
        $kitchen = $stmtKitchen->fetch(PDO::FETCH_ASSOC);
        $kitchen_id = $kitchen ? $kitchen['kitchen_id'] : null;

        $total_price = (float)$p['price'] * (int)$p['quantity'];
        $notes = !empty($p['notes']) ? $p['notes'] : null;
        $sides = !empty($p['sides']) ? $p['sides'] : null;

        // Ejecutamos con los 10 valores
        $stmtDetail->execute([
            $order_id,        // 1
            $p['id_product'], // 2
            $kitchen_id,      // 3
            $p['quantity'],   // 4
            $p['price'],      // 5
            $total_price,     // 6
            0,                // 7
            'pending',        // 8
            $notes,           // 9
            $sides            // 10
        ]);
    }

    $pdo->commit();
    echo json_encode(["error" => 0, "message" => "Pedido actualizado correctamente"]);

} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    echo json_encode(["error" => 1, "message" => $e->getMessage()]);
}