<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Content-Type: application/json");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit();
}

require_once 'dbconnect.php';

$input = json_decode(file_get_contents("php://input"), true);
$order_id = $input['order_id'] ?? null;

if (!$order_id) {
    echo json_encode(["error"=>1,"message"=>"Falta order_id"]);
    exit;
}

try {

    $pdo->beginTransaction();

    // 1️⃣ Obtener mesa antes de cerrar
    $stmt = $pdo->prepare("SELECT table_id FROM orders WHERE order_id = ?");
    $stmt->execute([$order_id]);
    $order = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$order) {
        throw new Exception("Orden no encontrada");
    }

    $table_id = $order['table_id'];

    // 2️⃣ Cerrar orden
    $pdo->prepare("UPDATE orders SET status='closed' WHERE order_id=?")
        ->execute([$order_id]);

    // 3️⃣ Liberar mesa
    $pdo->prepare("UPDATE cafe_tables SET estado='Libre' WHERE id_table=?")
        ->execute([$table_id]);

    $pdo->commit();

    echo json_encode(["error"=>0]);

} catch (Exception $e) {

    $pdo->rollBack();

    echo json_encode([
        "error"=>1,
        "message"=>$e->getMessage()
    ]);
}