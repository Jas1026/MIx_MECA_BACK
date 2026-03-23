<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Content-Type: application/json; charset=UTF-8");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { exit(0); }

require_once 'dbconnect.php';

// Leemos de $_POST porque el Service envía FormData
$detail_id = $_POST['detail_id'] ?? null;
$status    = $_POST['status'] ?? null;
$system    = $_POST['system'] ?? 'mixtura';

if (!$detail_id || !$status) {
    echo json_encode(["error" => 1, "message" => "Faltan parámetros (ID o Estado)"]);
    exit;
}

try {
    $pdo->exec("USE `$system` ");
    $pdo->beginTransaction();

    // 1. Obtener datos básicos para el flujo
    $stmtInfo = $pdo->prepare("SELECT order_id, status FROM order_details WHERE detail_id = ?");
    $stmtInfo->execute([$detail_id]);
    $current = $stmtInfo->fetch(PDO::FETCH_ASSOC);

    if (!$current) {
        throw new Exception("Detalle de pedido no encontrado.");
    }

    // Si ya está listo, no hacemos nada más
    if ($current['status'] === 'ready' && $status === 'ready') {
        $pdo->rollBack();
        echo json_encode(["error" => 0, "message" => "Ya estaba marcado como listo."]);
        exit;
    }

    // 2. Actualizar estado y calcular tiempo de preparación desde que se creó la orden
    $stmtUpdate = $pdo->prepare("
        UPDATE order_details od
        JOIN orders o ON od.order_id = o.order_id
        SET 
            od.status = ?,
            od.preparation_time = TIMESTAMPDIFF(SECOND, o.order_date, NOW()) / 60.0
        WHERE od.detail_id = ?
    ");
    $stmtUpdate->execute([$status, $detail_id]);

    // 3. Lógica de cierre de Orden y Mesa (Cascada de estados)
    $order_id = $current['order_id'];
    
    // Contamos cuántos productos de esta orden faltan por estar 'ready'
    $stmtCheck = $pdo->prepare("SELECT COUNT(*) FROM order_details WHERE order_id = ? AND status != 'ready'");
    $stmtCheck->execute([$order_id]);
    $faltantes = $stmtCheck->fetchColumn();
    
    if ($faltantes == 0) {
        // Toda la comida de la mesa está lista
        $pdo->prepare("UPDATE orders SET status = 'ready' WHERE order_id = ?")->execute([$order_id]);
        
        // Actualizar mesa para que el mesero vea que ya puede recoger todo
        $pdo->prepare("
            UPDATE cafe_tables 
            SET estado = 'Ready' 
            WHERE id_table = (SELECT table_id FROM orders WHERE order_id = ?)
        ")->execute([$order_id]);
    }

    $pdo->commit();
    echo json_encode([
        "error" => 0, 
        "message" => "Producto marcado como listo. Estado de mesa sincronizado."
    ]);

} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    echo json_encode(["error" => 1, "message" => "Error: " . $e->getMessage()]);
}