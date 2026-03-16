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
    $status    = $input['status'] ?? ($_POST['status'] ?? null);
    $force     = isset($input['force']) ? (bool)$input['force'] : (bool)($_POST['force'] ?? false);

    if (!$detail_id || !$status) {
        throw new Exception("Faltan parámetros");
    }

    $pdo->beginTransaction();

    // Obtener detalle
    $stmtProd = $pdo->prepare("
        SELECT od.product_id, od.quantity, od.order_id, od.status AS current_status, o.order_date
        FROM order_details od
        JOIN orders o ON od.order_id = o.order_id
        WHERE od.detail_id = ?
    ");
    $stmtProd->execute([$detail_id]);
    $itemInfo = $stmtProd->fetch(PDO::FETCH_ASSOC);

    if (!$itemInfo) {
        throw new Exception("Detalle no encontrado");
    }

    // Evitar doble descuento
    if ($itemInfo['current_status'] === 'ready') {
        $pdo->rollBack();
        echo json_encode([
            "error" => 0,
            "message" => "Este detalle ya estaba procesado"
        ]);
        exit;
    }

    if ($status === 'ready') {

        // INGREDIENTES DE RECETA
        $stmtIng = $pdo->prepare("
            SELECT pi.id_ingredient, pi.cant_us, i.nombre, i.stock_act, i.tipo
            FROM product_ingredient pi
            JOIN ingredients i ON pi.id_ingredient = i.id_ingredients
            WHERE pi.id_product = ?
        ");
        $stmtIng->execute([$itemInfo['product_id']]);
        $ingredients = $stmtIng->fetchAll(PDO::FETCH_ASSOC);

        // AJUSTES
        $stmtAdj = $pdo->prepare("
            SELECT ingredient_id, SUM(adjustment_qty) AS adj
            FROM order_detail_adjustments
            WHERE detail_id = ?
            GROUP BY ingredient_id
        ");
        $stmtAdj->execute([$detail_id]);

        $adjustments = [];

        foreach ($stmtAdj->fetchAll(PDO::FETCH_ASSOC) as $a) {
            $adjustments[$a['ingredient_id']] = $a['adj'];
        }

        // VALIDACIÓN DE STOCK
        $insuficientes = [];

        foreach ($ingredients as $ing) {

            $base  = (float)$ing['cant_us'] * (int)$itemInfo['quantity'];
            $extra = $adjustments[$ing['id_ingredient']] ?? 0;

            $totalNecesario = $base + $extra;

            if ($totalNecesario < 0) $totalNecesario = 0;

            if ($ing['stock_act'] < $totalNecesario) {
                $insuficientes[] = $ing['nombre'];
            }
        }

        if (!empty($insuficientes) && !$force) {

            $pdo->rollBack();

            echo json_encode([
                "error" => 2,
                "message" => "Stock insuficiente en: " . implode(", ", $insuficientes)
            ]);

            exit;
        }

        // DESCONTAR RECETA + AJUSTES
        foreach ($ingredients as $ing) {

            $id_ing = $ing['id_ingredient'];

            $base  = (float)$ing['cant_us'] * (int)$itemInfo['quantity'];
            $extra = $adjustments[$id_ing] ?? 0;

            $cantidadTotalADescontar = $base + $extra;

            if ($cantidadTotalADescontar < 0) $cantidadTotalADescontar = 0;

            // STOCK GENERAL
            $stmtUpdateStock = $pdo->prepare("
                UPDATE ingredients
                SET stock_act = stock_act - ?
                WHERE id_ingredients = ?
            ");

            $stmtUpdateStock->execute([
                $cantidadTotalADescontar,
                $id_ing
            ]);

            // BOTELLAS
            if ($ing['tipo'] === 'botella') {

                $restante = $cantidadTotalADescontar;

                $stmtBottles = $pdo->prepare("
                    SELECT id_bottle, peso_actual, peso_envase,
                    (peso_actual - peso_envase) AS contenido_neto
                    FROM ingredient_bottles
                    WHERE ingredient_id = ?
                    AND estado = 'abierta'
                    ORDER BY (peso_actual - peso_envase) ASC
                ");

                $stmtBottles->execute([$id_ing]);
                $bottles = $stmtBottles->fetchAll(PDO::FETCH_ASSOC);

                foreach ($bottles as $bot) {

                    if ($restante <= 0) break;

                    $contenido = (float)$bot['contenido_neto'];

                    if ($contenido > $restante) {

                        $stmtUpdateBot = $pdo->prepare("
                            UPDATE ingredient_bottles
                            SET peso_actual = peso_actual - ?
                            WHERE id_bottle = ?
                        ");

                        $stmtUpdateBot->execute([
                            $restante,
                            $bot['id_bottle']
                        ]);

                        $restante = 0;

                    } else {

                        $stmtFinal = $pdo->prepare("
                            UPDATE ingredient_bottles
                            SET peso_actual = peso_envase,
                                estado = 'finalizada'
                            WHERE id_bottle = ?
                        ");

                        $stmtFinal->execute([$bot['id_bottle']]);

                        $restante -= $contenido;
                    }
                }
            }
        }

        // PROCESAR EXTRAS QUE NO ESTÁN EN LA RECETA
        foreach ($adjustments as $id_ing => $extra) {

            $found = false;

            foreach ($ingredients as $ing) {
                if ($ing['id_ingredient'] == $id_ing) {
                    $found = true;
                    break;
                }
            }

            if (!$found && $extra > 0) {

                $stmtUpdateStock = $pdo->prepare("
                    UPDATE ingredients
                    SET stock_act = stock_act - ?
                    WHERE id_ingredients = ?
                ");

                $stmtUpdateStock->execute([
                    $extra,
                    $id_ing
                ]);
            }
        }
    }

    // ACTUALIZAR STATUS
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

    // VERIFICAR PEDIDO COMPLETO
    $order_id = $itemInfo['order_id'];

    $stmtCheck = $pdo->prepare("
        SELECT COUNT(*) AS pendientes
        FROM order_details
        WHERE order_id = ?
        AND status != 'ready'
    ");

    $stmtCheck->execute([$order_id]);
    $result = $stmtCheck->fetch(PDO::FETCH_ASSOC);

    if ($result['pendientes'] == 0) {

        $pdo->prepare("
            UPDATE orders
            SET status = 'ready'
            WHERE order_id = ?
        ")->execute([$order_id]);

        $pdo->prepare("
            UPDATE cafe_tables
            SET estado = 'Ready'
            WHERE id_table = (
                SELECT table_id FROM orders WHERE order_id = ?
            )
        ")->execute([$order_id]);
    }

    $pdo->commit();

    echo json_encode([
        "error" => 0,
        "message" => "Inventario actualizado correctamente"
    ]);

} catch (Throwable $e) {

    if ($pdo && $pdo->inTransaction()) {
        $pdo->rollBack();
    }

    echo json_encode([
        "error" => 1,
        "message" => $e->getMessage()
    ]);
}