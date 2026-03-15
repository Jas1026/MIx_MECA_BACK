<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Content-Type: application/json");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { exit(); }

require_once 'dbconnect.php';
$input = json_decode(file_get_contents("php://input"), true);

$order_id = $input['order_id'];
$pagos = $input['pagos']; 
$system = $input['system'];

try {
    $pdo->exec("USE `$system` ");
    $pdo->beginTransaction();

    foreach ($pagos as $pago) {
        $stmt = $pdo->prepare("INSERT INTO pagos_realizados (order_id, nit_cliente, razon_social, monto_total, metodo_pago, items_ids) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            $order_id, $pago['nit'], $pago['razonSocial'], $pago['monto'], $pago['metodo_pago'], json_encode($pago['detalle_ids'])
        ]);

        if (!empty($pago['detalle_ids'])) {
            foreach ($pago['detalle_ids'] as $detail_id => $cant_a_pagar) {
                // Si el ID es "undefined" o inválido, saltar para evitar errores
                if ($detail_id == "undefined" || !$detail_id) continue;

                $stmtI = $pdo->prepare("SELECT * FROM order_details WHERE detail_id = ?");
                $stmtI->execute([$detail_id]);
                $item = $stmtI->fetch(PDO::FETCH_ASSOC);

                if (!$item) continue;

                $cant_actual = (int)$item['quantity'];

                if ($cant_actual <= $cant_a_pagar) {
                    $pdo->prepare("UPDATE order_details SET estado_pago = 'pagado' WHERE detail_id = ?")->execute([$detail_id]);
                } else {
                    $restante = $cant_actual - $cant_a_pagar;
                    $pdo->prepare("UPDATE order_details SET quantity = ?, total_price = unit_price * ? WHERE detail_id = ?")
                        ->execute([$restante, $restante, $detail_id]);
                    
                    // Insertar la parte pagada (Asegúrate de NO incluir columnas que no existan)
                    $pdo->prepare("INSERT INTO order_details (order_id, product_id, quantity, unit_price, total_price, estado_pago, status) 
                                   VALUES (?, ?, ?, ?, ?, 'pagado', 'ready')")
                        ->execute([$order_id, $item['product_id'], $cant_a_pagar, $item['unit_price'], ($cant_a_pagar * $item['unit_price'])]);
                }
            }
        }
    }

    // --- LÓGICA DE CIERRE MEJORADA ---
    // Contamos solo lo que NO está pagado y NO está cancelado
    $stmtCheck = $pdo->prepare("SELECT COUNT(*) FROM order_details WHERE order_id = ? AND estado_pago = 'pendiente' AND status != 'canceled'");
    $stmtCheck->execute([$order_id]);
    $pendientes = (int)$stmtCheck->fetchColumn();

    if ($pendientes === 0) {
        // 1. Cerrar la orden
        $pdo->prepare("UPDATE orders SET status = 'closed' WHERE order_id = ?")->execute([$order_id]);
        
        // 2. Liberar la mesa (Buscamos el table_id de la orden)
        $pdo->prepare("UPDATE cafe_tables SET estado = 'Libre' WHERE id_table = (SELECT table_id FROM orders WHERE order_id = ? LIMIT 1)")->execute([$order_id]);
    }

    $pdo->commit();
    echo json_encode(["error" => 0, "mesa_cerrada" => ($pendientes === 0)]);
} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    echo json_encode(["error" => 1, "message" => $e->getMessage()]);
}