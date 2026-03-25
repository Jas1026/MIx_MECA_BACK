<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");

// 🔥 manejar preflight (clave)
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

header("Content-Type: application/json");

require_once 'dbconnect.php';

$input = json_decode(file_get_contents("php://input"), true);

$id_pago = $input['id_pago'] ?? 0;
$system  = $input['system'] ?? 'mixtura';

try {
    $pdo->exec("USE `$system`");
    $pdo->beginTransaction();

    // 🔥 1. Obtener pago
    $stmt = $pdo->prepare("SELECT * FROM pagos_realizados WHERE id_pago = ?");
    $stmt->execute([$id_pago]);
    $pago = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$pago) {
        throw new Exception("Pago no encontrado");
    }

    $items = json_decode($pago['items_ids'], true);

    // 🔥 2. Revertir items
    if (!empty($items)) {
        foreach ($items as $detail_id => $cant_pagada) {

            // 🔎 buscar item original pendiente
            $stmtItem = $pdo->prepare("
                SELECT * FROM order_details 
                WHERE detail_id = ?
            ");
            $stmtItem->execute([$detail_id]);
            $item = $stmtItem->fetch(PDO::FETCH_ASSOC);

            if ($item) {
                // 🔥 CASO 1: estaba marcado como pagado completo
                if ($item['estado_pago'] === 'pagado') {

                    $pdo->prepare("
                        UPDATE order_details 
                        SET estado_pago = 'pendiente'
                        WHERE detail_id = ?
                    ")->execute([$detail_id]);

                } else {
                    // 🔥 CASO 2: fue SPLIT (hay que buscar el pagado separado)

                    // buscar item pagado creado
                    $stmtSplit = $pdo->prepare("
                        SELECT * FROM order_details
                        WHERE order_id = ? 
                        AND product_id = ? 
                        AND estado_pago = 'pagado'
                        ORDER BY detail_id DESC
                        LIMIT 1
                    ");
                    $stmtSplit->execute([
                        $item['order_id'],
                        $item['product_id']
                    ]);
                    $split = $stmtSplit->fetch(PDO::FETCH_ASSOC);

                    if ($split) {
                        // devolver cantidad al original
                        $nuevaCantidad = $item['quantity'] + $cant_pagada;

                        $pdo->prepare("
                            UPDATE order_details 
                            SET quantity = ?, total_price = unit_price * ?
                            WHERE detail_id = ?
                        ")->execute([
                            $nuevaCantidad,
                            $nuevaCantidad,
                            $detail_id
                        ]);

                        // eliminar el split pagado
                        $pdo->prepare("
                            DELETE FROM order_details WHERE detail_id = ?
                        ")->execute([$split['detail_id']]);
                    }
                }
            }
        }
    }

    // 🔥 3. eliminar pago
    $pdo->prepare("DELETE FROM pagos_realizados WHERE id_pago = ?")
        ->execute([$id_pago]);

    // 🔥 4. reabrir mesa si estaba cerrada
    $stmtCheck = $pdo->prepare("
        SELECT COUNT(*) FROM order_details 
        WHERE order_id = ? AND estado_pago = 'pendiente'
    ");
    $stmtCheck->execute([$pago['order_id']]);

    if ((int)$stmtCheck->fetchColumn() > 0) {
        // reabrir orden
        $pdo->prepare("UPDATE orders SET status = 'open' WHERE order_id = ?")
            ->execute([$pago['order_id']]);

        // poner mesa ocupada
        $pdo->prepare("
            UPDATE cafe_tables 
            SET estado = 'Ocupada'
            WHERE id_table = (
                SELECT table_id FROM orders WHERE order_id = ? LIMIT 1
            )
        ")->execute([$pago['order_id']]);
    }

    $pdo->commit();

    echo json_encode([
        "error" => 0,
        "message" => "Pago eliminado y items restaurados 🔥"
    ]);

} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();

    echo json_encode([
        "error" => 1,
        "message" => $e->getMessage()
    ]);
}