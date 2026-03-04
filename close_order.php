<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Content-Type: application/json");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { exit(); }

require_once 'dbconnect.php';

$order_id = $_POST['order_id'] ?? null;

if (!$order_id) {
    echo json_encode(["error" => 1, "message" => "Falta order_id"]);
    exit;
}

try {
    $pdo->beginTransaction();

    // 1️⃣ Obtenemos los segundos totales transcurridos desde la DB
    $stmtTime = $pdo->prepare("
        SELECT table_id, TIMESTAMPDIFF(SECOND, order_date, NOW()) as segundos_totales
        FROM orders 
        WHERE order_id = ?
    ");
    $stmtTime->execute([$order_id]);
    $orderData = $stmtTime->fetch(PDO::FETCH_ASSOC);

    if (!$orderData) {
        throw new Exception("Orden no encontrada");
    }

    $table_id = $orderData['table_id'];
    $segundos_totales = (int)$orderData['segundos_totales'];

    // 2️⃣ Convertimos a formato visual (Ejemplo: 100 segundos -> 1.40)
    $minutos = floor($segundos_totales / 60);
    $segundos_restantes = $segundos_totales % 60;
    
    // Creamos el número: Minutos + (Segundos / 100)
    // Así 1 min y 40 seg se convierte en 1.40
    $tiempo_visual = $minutos + ($segundos_restantes / 100);

    // 3️⃣ Actualizar la orden con el tiempo formateado
    $stmtUpdate = $pdo->prepare("
        UPDATE orders 
        SET status = 'closed', 
            actual_time = ? 
        WHERE order_id = ?
    ");
    $stmtUpdate->execute([$tiempo_visual, $order_id]);

    // 4️⃣ Liberar la mesa
    $pdo->prepare("UPDATE cafe_tables SET estado = 'Libre' WHERE id_table = ?")
        ->execute([$table_id]);

    $pdo->commit();

    echo json_encode([
        "error" => 0, 
        "message" => "Pedido entregado", 
        "tiempo_registrado" => number_format($tiempo_visual, 2)
    ]);

} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    echo json_encode(["error" => 1, "message" => $e->getMessage()]);
}