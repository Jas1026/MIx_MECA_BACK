<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Content-Type: application/json; charset=UTF-8");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once 'dbconnect.php';

try {
    $input = json_decode(file_get_contents("php://input"), true);
    $detail_id = $input['detail_id'] ?? ($_POST['detail_id'] ?? null);
    $status = $input['status'] ?? ($_POST['status'] ?? null);
    $force = isset($input['force']) ? (bool)$input['force'] : (bool)($_POST['force'] ?? false);

    if (!$detail_id || !$status) {
        throw new Exception("Faltan parámetros");
    }

    $pdo->beginTransaction();

    // 1. Obtener info del producto y cantidad
    $stmtProd = $pdo->prepare("
        SELECT od.product_id, od.quantity, od.order_id, o.order_date
        FROM order_details od 
        JOIN orders o ON od.order_id = o.order_id 
        WHERE od.detail_id = ?
    ");
    $stmtProd->execute([$detail_id]);
    $itemInfo = $stmtProd->fetch(PDO::FETCH_ASSOC);

    if (!$itemInfo) throw new Exception("Detalle no encontrado");

    // 2. Lógica de Inventario (Solo al pasar a 'ready')
    if ($status === 'ready') {
        $stmtIng = $pdo->prepare("
            SELECT pi.id_ingredient, pi.cant_us, i.nombre, i.stock_act, i.tipo 
            FROM product_ingredient pi
            JOIN ingredients i ON pi.id_ingredient = i.id_ingredients
            WHERE pi.id_product = ?
        ");
        $stmtIng->execute([$itemInfo['product_id']]);
        $ingredients = $stmtIng->fetchAll(PDO::FETCH_ASSOC);

        // --- VALIDACIÓN PREVIA (FORCE CHECK) ---
        $insuficientes = [];
        foreach ($ingredients as $ing) {
            $totalNecesario = (float)$ing['cant_us'] * (int)$itemInfo['quantity'];
            if ($ing['stock_act'] < $totalNecesario) {
                $insuficientes[] = $ing['nombre'];
            }
        }

        if (!empty($insuficientes) && !$force) {
            $pdo->rollBack();
            echo json_encode(["error" => 2, "message" => "Stock insuficiente en: " . implode(", ", $insuficientes)]);
            exit;
        }

        // --- PROCESO DE DESCUENTO ---
        foreach ($ingredients as $ing) {
            $id_ing = $ing['id_ingredient'];
            $cantidadTotalADescontar = (float)$ing['cant_us'] * (int)$itemInfo['quantity'];

            // A. Descontar del Stock General (siempre)
            $stmtUpdateStock = $pdo->prepare("UPDATE ingredients SET stock_act = stock_act - ? WHERE id_ingredients = ?");
            $stmtUpdateStock->execute([$cantidadTotalADescontar, $id_ing]);

            // B. Si es BOTELLA, aplicamos lógica de cascada
            if ($ing['tipo'] === 'botella') {
                $restantePorDescontar = $cantidadTotalADescontar;

                // Buscamos botellas ABIERTAS ordenadas por la que tiene MENOS contenido neto
                $stmtBottles = $pdo->prepare("
                    SELECT id_bottle, peso_actual, peso_envase, (peso_actual - peso_envase) as contenido_neto 
                    FROM ingredient_bottles 
                    WHERE ingredient_id = ? AND estado = 'abierta'
                    ORDER BY (peso_actual - peso_envase) ASC
                ");
                $stmtBottles->execute([$id_ing]);
                $openBottles = $stmtBottles->fetchAll(PDO::FETCH_ASSOC);

                foreach ($openBottles as $bot) {
                    if ($restantePorDescontar <= 0) break;

                    $id_bot = $bot['id_bottle'];
                    $contenidoNetoActual = (float)$bot['contenido_neto'];

                    if ($contenidoNetoActual > $restantePorDescontar) {
                        // Caso 1: La botella tiene más de lo que necesito.
                        // Descontamos solo lo necesario y terminamos el ciclo para este ingrediente.
                        $stmtUpdateBot = $pdo->prepare("UPDATE ingredient_bottles SET peso_actual = peso_actual - ? WHERE id_bottle = ?");
                        $stmtUpdateBot->execute([$restantePorDescontar, $id_bot]);
                        
                        $restantePorDescontar = 0;
                    } else {
                        // Caso 2: La botella tiene justo o MENOS de lo que necesito (ej: tiene 1g y necesito 5g)
                        // Vaciamos la botella (peso_actual = peso_envase), la finalizamos y restamos lo que dio.
                        $stmtFinalizar = $pdo->prepare("UPDATE ingredient_bottles SET peso_actual = peso_envase, estado = 'finalizada' WHERE id_bottle = ?");
                        $stmtFinalizar->execute([$id_bot]);

                        $restantePorDescontar -= $contenidoNetoActual;
                    }
                }
                
                // Nota: Si después del loop restantePorDescontar > 0, significa que se agotaron las botellas abiertas.
                // El stock general ya se restó arriba, por lo que la integridad numérica se mantiene.
            }
        }
    }

    // 3. ACTUALIZAR ESTADO Y TIEMPO REAL
    $stmtUpdate = $pdo->prepare("
        UPDATE order_details od
        JOIN orders o ON od.order_id = o.order_id
        SET 
            od.status = ?,
            od.alert_status = 0,
            od.preparation_time = TIMESTAMPDIFF(SECOND, o.order_date, NOW()) / 60.0
        WHERE od.detail_id = ?
    ");
    $stmtUpdate->execute([$status, $detail_id]);

    // 4. Verificar si todo el pedido terminó
    $order_id = $itemInfo['order_id'];
    $stmtCheck = $pdo->prepare("SELECT COUNT(*) AS pendientes FROM order_details WHERE order_id = ? AND status != 'ready'");
    $stmtCheck->execute([$order_id]);
    $result = $stmtCheck->fetch(PDO::FETCH_ASSOC);

    if ($result['pendientes'] == 0) {
        $pdo->prepare("UPDATE orders SET status = 'ready' WHERE order_id = ?")->execute([$order_id]);
        $pdo->prepare("
            UPDATE cafe_tables SET estado = 'Ready' 
            WHERE id_table = (SELECT table_id FROM orders WHERE order_id = ?)
        ")->execute([$order_id]);
    }

    $pdo->commit();
    echo json_encode(["error" => 0, "message" => "¡Listo! Inventario procesado en cascada"]);

} catch (Throwable $e) {
    if ($pdo && $pdo->inTransaction()) $pdo->rollBack();
    echo json_encode(["error" => 1, "message" => $e->getMessage()]);
}