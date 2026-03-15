<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
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
        // 1. Insertar en pagos_realizados (Asegúrate que esta tabla NO tenga 'nombre_producto')
        $stmt = $pdo->prepare("INSERT INTO pagos_realizados (order_id, nit_cliente, razon_social, monto_total, metodo_pago, items_ids) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            $order_id, 
            $pago['nit'], 
            $pago['razonSocial'], 
            $pago['monto'], 
            $pago['metodo_pago'], 
            json_encode($pago['detalle_ids'])
        ]);

        // 2. Procesar los items para marcarlos como pagados o dividirlos
        if (!empty($pago['detalle_ids'])) {
            foreach ($pago['detalle_ids'] as $detail_id => $cant) {
                $stmtI = $pdo->prepare("SELECT * FROM order_details WHERE detail_id = ?");
                $stmtI->execute([$detail_id]);
                $item = $stmtI->fetch(PDO::FETCH_ASSOC);

                if (!$item) continue;

                $cant_original = (int)$item['quantity'];

                if ($cant_original <= $cant) {
                    // Se pagó todo el item
                    $pdo->prepare("UPDATE order_details SET estado_pago = 'pagado' WHERE detail_id = ?")->execute([$detail_id]);
                } else {
                    // SPLIT: Se pagó una parte (ej: 1 de 3)
                    $restante = $cant_original - $cant;
                    // El original se queda con lo que falta pagar
                    $pdo->prepare("UPDATE order_details SET quantity = ?, total_price = unit_price * ? WHERE detail_id = ?")
                        ->execute([$restante, $restante, $detail_id]);
                    
                    // Insertamos el nuevo registro YA PAGADO (Sin la columna nombre_producto)
                    $pdo->prepare("INSERT INTO order_details (order_id, product_id, quantity, unit_price, total_price, estado_pago, status) 
                                   VALUES (?, ?, ?, ?, ?, 'pagado', 'ready')")
                        ->execute([$order_id, $item['product_id'], $cant, $item['unit_price'], ($cant * $item['unit_price'])]);
                }
            }
        }
    }

    // 3. Verificar si la mesa se libera
    $stmtCheck = $pdo->prepare("SELECT COUNT(*) FROM order_details WHERE order_id = ? AND estado_pago = 'pendiente' AND status != 'canceled'");
    $stmtCheck->execute([$order_id]);
    if ((int)$stmtCheck->fetchColumn() === 0) {
        $pdo->prepare("UPDATE orders SET status = 'closed' WHERE order_id = ?")->execute([$order_id]);
        $pdo->prepare("UPDATE cafe_tables SET estado = 'Libre' WHERE id_table = (SELECT table_id FROM orders WHERE order_id = ?)")->execute([$order_id]);
    }

    $pdo->commit();
    echo json_encode(["error" => 0]);
} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    echo json_encode(["error" => 1, "message" => $e->getMessage()]);
}