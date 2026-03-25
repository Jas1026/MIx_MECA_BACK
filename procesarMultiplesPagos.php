<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Content-Type: application/json");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit();
}

require_once 'dbconnect.php';

$input = json_decode(file_get_contents("php://input"), true);

if (!isset($input['order_id']) || !isset($input['pagos'])) {
    echo json_encode(["error" => 1, "message" => "Datos incompletos"]);
    exit();
}

$order_id = $input['order_id'];
$pagos    = $input['pagos'];
$system   = $input['system'] ?? 'mixtura';

// 🔥 DETECTAR SI ES PARCIAL
$esParcial = isset($input['parcial']) && $input['parcial'] == 1;

try {
    $pdo->exec("USE `$system`");
    $pdo->beginTransaction();

    // 🔥 SI ES FINAL → BORRAR TODOS LOS PARCIALES
    if (!$esParcial) {
        $stmtDelete = $pdo->prepare("
            DELETE FROM pagos_realizados 
            WHERE order_id = ? AND tipo_pago = 'parcial'
        ");
        $stmtDelete->execute([$order_id]);
    }

    foreach ($pagos as $pago) {

        // 🔥 VALIDACIÓN CLAVE (EVITA BASURA)
        if (empty($pago['monto']) || $pago['monto'] <= 0) continue;

        $voucher = !empty($pago['voucher']) ? $pago['voucher'] : null;
        $detalle_ids = $pago['detalle_ids'] ?? [];

        // 🔥 LIMPIAR IDs inválidos
        $detalle_ids = array_filter($detalle_ids, function($v, $k) {
            return !empty($k) && $k !== "undefined" && $v > 0;
        }, ARRAY_FILTER_USE_BOTH);

        $items_ids_json = json_encode($detalle_ids);

        $tipo_pago = $esParcial ? 'parcial' : 'final';

        // 🔥 INSERTAR PAGO
        $stmt = $pdo->prepare("
            INSERT INTO pagos_realizados 
            (order_id, nit_cliente, razon_social, monto_total, metodo_pago, voucher, items_ids, tipo_pago) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ");

        $stmt->execute([
            $order_id,
            $pago['nit'],
            $pago['razonSocial'],
            $pago['monto'],
            $pago['metodo_pago'],
            $voucher,
            $items_ids_json,
            $tipo_pago
        ]);

        // 🔥 PROCESAR ITEMS SOLO SI EXISTEN
        if (!empty($detalle_ids)) {
            foreach ($detalle_ids as $detail_id => $cant) {

                if (!$detail_id || $cant <= 0) continue;

                $stmtI = $pdo->prepare("SELECT * FROM order_details WHERE detail_id = ?");
                $stmtI->execute([$detail_id]);
                $item = $stmtI->fetch(PDO::FETCH_ASSOC);

                if (!$item) continue;

                $cant_original = (int)$item['quantity'];
                $cant_a_pagar  = (int)$cant;

                if ($cant_original <= $cant_a_pagar) {
                    // ✅ PAGADO COMPLETO
                    $pdo->prepare("
                        UPDATE order_details 
                        SET estado_pago = 'pagado' 
                        WHERE detail_id = ?
                    ")->execute([$detail_id]);

                } else {
                    // 🔥 SPLIT CORRECTO
                    $restante = $cant_original - $cant_a_pagar;

                    // actualizar original
                    $pdo->prepare("
                        UPDATE order_details 
                        SET quantity = ?, total_price = unit_price * ? 
                        WHERE detail_id = ?
                    ")->execute([$restante, $restante, $detail_id]);

                    // insertar pagado
                    $pdo->prepare("
                        INSERT INTO order_details 
                        (order_id, product_id, quantity, unit_price, total_price, estado_pago, status) 
                        VALUES (?, ?, ?, ?, ?, 'pagado', 'ready')
                    ")->execute([
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

    // 🔥 SOLO CERRAR SI ES FINAL
    if (!$esParcial) {
        $stmtCheck = $pdo->prepare("
            SELECT COUNT(*) FROM order_details 
            WHERE order_id = ? AND estado_pago = 'pendiente' AND status != 'canceled'
        ");
        $stmtCheck->execute([$order_id]);

        if ((int)$stmtCheck->fetchColumn() === 0) {

            $pdo->prepare("UPDATE orders SET status = 'closed' WHERE order_id = ?")
                ->execute([$order_id]);

            $pdo->prepare("
                UPDATE cafe_tables SET estado = 'Libre' 
                WHERE id_table = (
                    SELECT table_id FROM orders WHERE order_id = ? LIMIT 1
                )
            ")->execute([$order_id]);
        }
    }

    $pdo->commit();

    echo json_encode([
        "error" => 0,
        "message" => $esParcial ? "Pago parcial guardado" : "Pagos completados correctamente"
    ]);

} catch (Exception $e) {

    if ($pdo->inTransaction()) $pdo->rollBack();

    echo json_encode([
        "error" => 1,
        "message" => "Error: " . $e->getMessage()
    ]);
}