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

    // 1️⃣ Obtener mesa y fecha de creación para calcular el tiempo
    $stmt = $pdo->prepare("SELECT table_id, order_date FROM orders WHERE order_id = ?");
    $stmt->execute([$order_id]);
    $order = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$order) {
        throw new Exception("Orden no encontrada");
    }

    $table_id = $order['table_id'];
    $fecha_inicio = new DateTime($order['order_date']);
    $fecha_fin = new DateTime(); // Ahora mismo
    
    // Calcular diferencia en minutos
    $intervalo = $fecha_inicio->diff($fecha_fin);
    $minutos_transcurridos = ($intervalo->days * 24 * 60) + ($intervalo->h * 60) + $intervalo->i;

    // 2️⃣ Cerrar orden y guardar el tiempo real (actual_time)
    $stmtUpdate = $pdo->prepare("
        UPDATE orders 
        SET status = 'closed', 
            actual_time = ? 
        WHERE order_id = ?
    ");
    $stmtUpdate->execute([$minutos_transcurridos, $order_id]);

    // 3️⃣ Liberar mesa
    $pdo->prepare("UPDATE cafe_tables SET estado = 'Libre' WHERE id_table = ?")
        ->execute([$table_id]);

    $pdo->commit();

    echo json_encode([
        "error" => 0, 
        "message" => "Pedido entregado", 
        "tiempo_total" => $minutos_transcurridos
    ]);

} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    echo json_encode(["error" => 1, "message" => $e->getMessage()]);
}