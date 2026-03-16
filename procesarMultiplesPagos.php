<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Content-Type: application/json");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { exit(); }

require_once 'dbconnect.php';

$input = json_decode(file_get_contents("php://input"), true);

// Validar entrada básica
if (!isset($input['order_id']) || !isset($input['pagos'])) {
    echo json_encode(["error" => 1, "message" => "Datos incompletos"]);
    exit();
}

$order_id = $input['order_id'];
$pagos    = $input['pagos']; 
$system   = $input['system'];

try {
    // Seleccionar la base de datos del sistema correspondiente
    $pdo->exec("USE `$system` ");
    $pdo->beginTransaction();

    foreach ($pagos as $pago) {
        // Capturar voucher (solo si existe, si no, null)
        $voucher = !empty($pago['voucher']) ? $pago['voucher'] : null;

        // 1. Insertar en pagos_realizados incluyendo la nueva columna 'voucher'
        $stmt = $pdo->prepare("INSERT INTO pagos_realizados 
            (order_id, nit_cliente, razon_social, monto_total, metodo_pago, voucher, items_ids) 
            VALUES (?, ?, ?, ?, ?, ?, ?)");
        
        $stmt->execute([
            $order_id, 
            $pago['nit'], 
            $pago['razonSocial'], 
            $pago['monto'], 
            $pago['metodo_pago'], 
            $voucher, 
            json_encode($pago['detalle_ids'])
        ]);

        // 2. Procesar los items para marcarlos como pagados o dividirlos (Split logic)
        if (!empty($pago['detalle_ids'])) {
            foreach ($pago['detalle_ids'] as $detail_id => $cant) {
                
                // Obtener datos actuales del detalle
                $stmtI = $pdo->prepare("SELECT * FROM order_details WHERE detail_id = ?");
                $stmtI->execute([$detail_id]);
                $item = $stmtI->fetch(PDO::FETCH_ASSOC);

                if (!$item) continue;

                $cant_original = (int)$item['quantity'];
                $cant_a_pagar = (int)$cant;

                if ($cant_original <= $cant_a_pagar) {
                    // Caso A: Se pagó la totalidad de las unidades de este item
                    $pdo->prepare("UPDATE order_details SET estado_pago = 'pagado' WHERE detail_id = ?")
                        ->execute([$detail_id]);
                } else {
                    // Caso B: SPLIT - Se pagó solo una parte de las unidades (ej: 1 de 3)
                    $restante = $cant_original - $cant_a_pagar;
                    
                    // Actualizamos el registro original restándole lo pagado
                    $pdo->prepare("UPDATE order_details SET quantity = ?, total_price = unit_price * ? WHERE detail_id = ?")
                        ->execute([$restante, $restante, $detail_id]);
                    
                    // Insertamos un nuevo registro que representa la parte YA PAGADA
                    // (Sin la columna nombre_producto por tu restricción de DB)
                    $pdo->prepare("INSERT INTO order_details 
                        (order_id, product_id, quantity, unit_price, total_price, estado_pago, status) 
                        VALUES (?, ?, ?, ?, ?, 'pagado', 'ready')")
                        ->execute([
                            $order_id, 
                            $item['product_id'], 
                            $cant_a_pagar, 
                            $item['unit_price'], 
                            ($cant_a_pagar * $item['unit_price'])
                        ]);
                }
            }
        }
    }

    // 3. Verificar si la mesa debe cerrarse (si ya no quedan items pendientes)
    $stmtCheck = $pdo->prepare("SELECT COUNT(*) FROM order_details 
                                WHERE order_id = ? AND estado_pago = 'pendiente' AND status != 'canceled'");
    $stmtCheck->execute([$order_id]);
    
    if ((int)$stmtCheck->fetchColumn() === 0) {
        // Cerrar la orden
        $pdo->prepare("UPDATE orders SET status = 'closed' WHERE order_id = ?")
            ->execute([$order_id]);
            
        // Liberar la mesa físicamente
        $pdo->prepare("UPDATE cafe_tables SET estado = 'Libre' 
                       WHERE id_table = (SELECT table_id FROM orders WHERE order_id = ? LIMIT 1)")
            ->execute([$order_id]);
    }

    $pdo->commit();
    echo json_encode(["error" => 0, "message" => "Pagos procesados correctamente"]);

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo json_encode(["error" => 1, "message" => "Error en servidor: " . $e->getMessage()]);
}
?>