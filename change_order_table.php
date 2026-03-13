<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json");

include('dbconnect.php');

$order_id = $_POST['order_id'] ?? null;
$new_table_id = $_POST['new_table_id'] ?? null;

if (!$order_id || !$new_table_id) {
    echo json_encode(["error" => 1, "message" => "Datos incompletos"]);
    exit;
}

try {
    $pdo->beginTransaction();

    // 1. Obtener el ID de la mesa actual antes de cambiarlo
    $stmtMesaActual = $pdo->prepare("SELECT table_id FROM orders WHERE order_id = ?");
    $stmtMesaActual->execute([$order_id]);
    $old_table_id = $stmtMesaActual->fetchColumn();

    // 2. Liberar la mesa anterior
    if ($old_table_id) {
        $stmtLiberar = $pdo->prepare("UPDATE cafe_tables SET estado = 'Libre' WHERE id_table = ?");
        $stmtLiberar->execute([$old_table_id]);
    }

    // 3. Ocupar la mesa nueva
    // Usamos 'Pendiente' u 'Ocupado' según manejes tu lógica
    $stmtOcupar = $pdo->prepare("UPDATE cafe_tables SET estado = 'Pendiente' WHERE id_table = ?");
    $stmtOcupar->execute([$new_table_id]);

    // 4. Actualizar la orden con la nueva mesa
    $stmtUpdateOrder = $pdo->prepare("UPDATE orders SET table_id = ? WHERE order_id = ?");
    $stmtUpdateOrder->execute([$new_table_id, $order_id]);

    $pdo->commit();
    echo json_encode(["error" => 0, "message" => "Cambio de mesa procesado correctamente"]);

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo json_encode(["error" => 1, "message" => "Error: " . $e->getMessage()]);
}
?>