<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Content-Type: application/json; charset=UTF-8");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { exit(); }

require_once 'dbconnect.php';

$order_id = $_POST['order_id'] ?? null;
$system   = $_POST['system'] ?? 'mixtura'; 

if (!$order_id) {
    echo json_encode(["error" => 1, "message" => "Falta order_id"]);
    exit;
}

try {
    $pdo->exec("USE `$system` ");
    $pdo->beginTransaction();

    // 1️⃣ OBTENER TIEMPO TRANSCURRIDO (Detener el reloj ahora)
    $stmtTime = $pdo->prepare("
        SELECT table_id, TIMESTAMPDIFF(SECOND, order_date, NOW()) as segundos_totales
        FROM orders 
        WHERE order_id = ?
    ");
    $stmtTime->execute([$order_id]);
    $orderData = $stmtTime->fetch(PDO::FETCH_ASSOC);

    if (!$orderData) {
        throw new Exception("Orden no encontrada");
    }

    $table_id = $orderData['table_id'];
    $segundos_totales = (int)$orderData['segundos_totales'];
    
    // Formato visual (Minutos.Segundos)
    $minutos = floor($segundos_totales / 60);
    $segundos_restantes = $segundos_totales % 60;
    $tiempo_visual = $minutos + ($segundos_restantes / 100);

    // 2️⃣ OBTENER PRODUCTOS PARA REPOSICIÓN
    $stmtItems = $pdo->prepare("
        SELECT product_id, quantity 
        FROM order_details 
        WHERE order_id = ? AND status != 'canceled'
    ");
    $stmtItems->execute([$order_id]);
    $items = $stmtItems->fetchAll(PDO::FETCH_ASSOC);

    foreach ($items as $item) {
        $qty = (int)$item['quantity'];
        $prod_id = $item['product_id'];

        // --- 🟢 NIVEL 1: Devolver Stock Disponible ---
        $pdo->prepare("UPDATE products SET stock_disponible = stock_disponible + ? WHERE id_product = ?")
            ->execute([$qty, $prod_id]);

        // --- 🟢 NIVEL 2: Devolver Ingredientes ---
        $stmtReceta = $pdo->prepare("
            SELECT pi.id_ingredient, pi.cant_us, i.tipo 
            FROM product_ingredient pi
            JOIN ingredients i ON pi.id_ingredient = i.id_ingredients
            WHERE pi.id_product = ?
        ");
        $stmtReceta->execute([$prod_id]);
        $receta = $stmtReceta->fetchAll(PDO::FETCH_ASSOC);

        foreach ($receta as $ing) {
            $cantidadADevolver = (float)$ing['cant_us'] * $qty;
            if ($cantidadADevolver <= 0) continue;

            // Devolver a tabla general
            $pdo->prepare("UPDATE ingredients SET stock_act = stock_act + ? WHERE id_ingredients = ?")
                ->execute([$cantidadADevolver, $ing['id_ingredient']]);

            // --- 🟢 NIVEL 3: REPOSICIÓN EN BOTELLAS (CASCADA) ---
            if ($ing['tipo'] === 'botella') {
                $restante = $cantidadADevolver;

                while ($restante > 0) {
                    // Buscamos botellas que tengan espacio (peso_actual < capacidad_total)
                    // Priorizamos la que esté 'abierta' y tenga más contenido para terminar de llenarla
                    $stmtB = $pdo->prepare("
                        SELECT id_bottle, peso_actual, capacidad_total 
                        FROM ingredient_bottles 
                        WHERE ingredient_id = ? AND estado != 'vacia' AND peso_actual < capacidad_total
                        ORDER BY (CASE WHEN estado = 'abierta' THEN 1 ELSE 2 END) ASC, peso_actual DESC 
                        LIMIT 1
                    ");
                    $stmtB->execute([$ing['id_ingredient']]);
                    $bot = $stmtB->fetch(PDO::FETCH_ASSOC);

                    if (!$bot) {
                        // Si no hay botellas con espacio, lo devolvemos a la última abierta de todas formas
                        $pdo->prepare("UPDATE ingredient_bottles SET peso_actual = peso_actual + ?, estado = 'abierta' WHERE ingredient_id = ? AND estado = 'abierta' LIMIT 1")
                            ->execute([$restante, $ing['id_ingredient']]);
                        break; 
                    }

                    $espacioDisponible = (float)$bot['capacidad_total'] - (float)$bot['peso_actual'];

                    if ($espacioDisponible >= $restante) {
                        $pdo->prepare("UPDATE ingredient_bottles SET peso_actual = peso_actual + ?, estado = 'abierta' WHERE id_bottle = ?")
                            ->execute([$restante, $bot['id_bottle']]);
                        $restante = 0;
                    } else {
                        $pdo->prepare("UPDATE ingredient_bottles SET peso_actual = capacidad_total, estado = 'abierta' WHERE id_bottle = ?")
                            ->execute([$bot['id_bottle']]);
                        $restante -= $espacioDisponible;
                    }
                }
            }
        }
    }

    // 3️⃣ ACTUALIZAR ESTADOS FINALES
    $pdo->prepare("
        UPDATE orders 
        SET status = 'closed',
            actual_time = ?,
            cancel = 1
        WHERE order_id = ?
    ")->execute([$tiempo_visual, $order_id]);

    $pdo->prepare("UPDATE order_details SET status = 'canceled' WHERE order_id = ?")->execute([$order_id]);
    $pdo->prepare("UPDATE cafe_tables SET estado = 'Libre' WHERE id_table = ?")->execute([$table_id]);

    $pdo->commit();

    echo json_encode([
        "error" => 0,
        "message" => "Orden cancelada. Tiempo parado en $tiempo_visual y stock repuesto en cascada.",
        "tiempo_registrado" => number_format($tiempo_visual, 2)
    ]);

} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    echo json_encode(["error" => 1, "message" => "Error: " . $e->getMessage()]);
}