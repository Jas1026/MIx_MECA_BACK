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
    $force = isset($input['force']) ? $input['force'] : ($_POST['force'] ?? 0);

    if (!$detail_id || !$status) {
        throw new Exception("Faltan parámetros");
    }

    $pdo->beginTransaction();

    // 1. Obtener info del producto, cantidad y la fecha de creación del pedido original
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
            SELECT pi.id_ingredient, pi.cant_us, i.nombre, i.stock_act 
            FROM product_ingredient pi
            JOIN ingredients i ON pi.id_ingredient = i.id_ingredients
            WHERE pi.id_product = ?
        ");
        $stmtIng->execute([$itemInfo['product_id']]);
        $ingredients = $stmtIng->fetchAll(PDO::FETCH_ASSOC);

        $insuficientes = [];
        foreach ($ingredients as $ing) {
            $totalNecesario = $ing['cant_us'] * $itemInfo['quantity'];
            if ($ing['stock_act'] < $totalNecesario) {
                $insuficientes[] = $ing['nombre'];
            }
        }

        if (!empty($insuficientes) && $force == 0) {
            $pdo->rollBack();
            echo json_encode(["error" => 2, "message" => "Stock insuficiente"]);
            exit;
        }

        foreach ($ingredients as $ing) {
            $totalDescontar = $ing['cant_us'] * $itemInfo['quantity'];
            $pdo->prepare("UPDATE ingredients SET stock_act = stock_act - ? WHERE id_ingredients = ?")
                ->execute([$totalDescontar, $ing['id_ingredient']]);
        }
    }

    // 3. ACTUALIZAR ESTADO Y CALCULAR TIEMPO REAL (preparation_time)
    // Usamos TIMESTAMPDIFF para obtener los minutos transcurridos
// 3. ACTUALIZAR ESTADO Y CALCULAR TIEMPO REAL CON DECIMALES
    // Calculamos la diferencia en SEGUNDOS y dividimos entre 60.0 para obtener decimales
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
    echo json_encode(["error" => 0, "message" => "¡Listo! Tiempo guardado e inventario actualizado"]);

} catch (Throwable $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    echo json_encode(["error" => 1, "message" => $e->getMessage()]);
}