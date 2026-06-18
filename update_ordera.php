<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Content-Type: application/json; charset=UTF-8");

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    exit(0);
}

require_once 'dbconnect.php';

$order_id    = $_POST['order_id'] ?? null;
$id_user     = $_POST['id_user'] ?? null;
$system      = $_POST['system'] ?? 'mixtura';
$products    = isset($_POST['products']) ? json_decode($_POST['products'], true) : [];
$force_order = $_POST['force_order'] ?? 'false';

if (!$order_id || !$id_user || empty($products)) {
    echo json_encode(["error" => 1, "message" => "Datos incompletos para actualizar"]);
    exit;
}

try {
    $pdo->exec("USE `$system`");
    $pdo->beginTransaction();

    // 1. Obtener el estado actual de los detalles en la base de datos para comparar
    $stmtOld = $pdo->prepare("SELECT product_id, quantity FROM order_details WHERE order_id = ?");
    $stmtOld->execute([$order_id]);
    $oldDetails = $stmtOld->fetchAll(PDO::FETCH_KEY_PAIR); // Retorna un array [product_id => quantity]

    // 2. Procesar inventario basándose en las diferencias
    foreach ($products as $p) {
        $prod_id = $p['id_product'];
        $new_qty = (int)$p['quantity'];
        $old_qty = isset($oldDetails[$prod_id]) ? (int)$oldDetails[$prod_id] : 0;
        
        // Diferencia: positiva significa que pide MÁS (descontar), negativa significa que pide MENOS (devolver)
        $diff = $new_qty - $old_qty;

        if ($diff == 0) continue; // No cambió la cantidad de este producto

        // Validar stock disponible si se requiere más de lo que ya se tenía
        $stmtP = $pdo->prepare("SELECT stock_disponible, nombre_producto FROM products WHERE id_product = ?");
        $stmtP->execute([$prod_id]);
        $prod = $stmtP->fetch(PDO::FETCH_ASSOC);

        if ($diff > 0 && $force_order !== 'true' && (float)$prod['stock_disponible'] < $diff) {
            throw new Exception("Stock insuficiente para aumentar: " . $prod['nombre_producto']);
        }

        // Actualizar stock del producto usando la diferencia
        $stmtStock = $pdo->prepare("UPDATE products SET stock_disponible = stock_disponible - ? WHERE id_product = ?");
        $stmtStock->execute([$diff, $prod_id]);

        // Procesar Ingredientes de la Receta con la diferencia ($diff)
        $stmtReceta = $pdo->prepare("
            SELECT pi.id_ingredient, pi.cant_us, i.tipo 
            FROM product_ingredient pi
            JOIN ingredients i ON pi.id_ingredient = i.id_ingredients
            WHERE pi.id_product = ?
        ");
        $stmtReceta->execute([$prod_id]);
        $receta = $stmtReceta->fetchAll(PDO::FETCH_ASSOC);

        foreach ($receta as $ing) {
            $cantidadGastoDiff = (float)$ing['cant_us'] * $diff;

            // Actualizar maestro de ingredientes
            $stmtIng = $pdo->prepare("UPDATE ingredients SET stock_act = stock_act - ? WHERE id_ingredients = ?");
            $stmtIng->execute([$cantidadGastoDiff, $ing['id_ingredient']]);

            // Manejo de botellas/fraccionados si la diferencia es positiva (gasto extra)
            if ($diff > 0) {
                if ($ing['tipo'] === 'botella') {
                    // [Tu misma lógica de reducción de botellas usando $cantidadGastoDiff]
                }
                if ($ing['tipo'] === 'fraccionado') {
                    // [Tu misma lógica de reducción de fracciones usando $cantidadGastoDiff]
                }
            } 
            // Si el diff es negativo, idealmente el inventario general ($stmtIng) aumenta de nuevo.
        }
    }

    // 3. Devolver stock de los productos que fueron ELIMINADOS completamente del carrito
    foreach ($oldDetails as $old_prod_id => $old_qty) {
        $encontrado = false;
        foreach ($products as $p) {
            if ($p['id_product'] == $old_prod_id) { $encontrado = true; break; }
        }
        if (!$encontrado) {
            // Se eliminó el ítem por completo: devolver stock e ingredientes
            $stmtRestaurar = $pdo->prepare("UPDATE products SET stock_disponible = stock_disponible + ? WHERE id_product = ?");
            $stmtRestaurar->execute([$old_qty, $old_prod_id]);
            
            // Revertir ingredientes de manera general
            $stmtRecetaDel = $pdo->prepare("SELECT id_ingredient, cant_us FROM product_ingredient WHERE id_product = ?");
            $stmtRecetaDel->execute([$old_prod_id]);
            foreach ($stmtRecetaDel->fetchAll(PDO::FETCH_ASSOC) as $ingDel) {
                $cantDevolver = (float)$ingDel['cant_us'] * $old_qty;
                $pdo->prepare("UPDATE ingredients SET stock_act = stock_act + ? WHERE id_ingredients = ?")
                    ->execute([$cantDevolver, $ingDel['id_ingredient']]);
            }
        }
    }

    // 4. Recalcular tiempos de cocina de los nuevos productos
    $kitchen_times = [];
    foreach ($products as $p) {
        $stmtK = $pdo->prepare("SELECT p.time_prep, pk.kitchen_id FROM products p LEFT JOIN product_kitchen pk ON p.id_product = pk.product_id WHERE p.id_product = ?");
        $stmtK->execute([$p['id_product']]);
        $prodK = $stmtK->fetch(PDO::FETCH_ASSOC);
        
        $k_id = $prodK['kitchen_id'] ?? 0;
        $kitchen_times[$k_id] = ($kitchen_times[$k_id] ?? 0) + ((int)$prodK['time_prep'] * (int)$p['quantity']);
    }
    $max_estimated_time = !empty($kitchen_times) ? max($kitchen_times) : 0;

    // 5. Actualizar cabecera de la orden
    $stmtUpdateOrder = $pdo->prepare("UPDATE orders SET estimated_time = ? WHERE order_id = ?");
    $stmtUpdateOrder->execute([$max_estimated_time, $order_id]);

    // Historial
    $stmtHist = $pdo->prepare("INSERT INTO historial_mesa (order_id, user_id, accion, observacion) VALUES (?, ?, 'editar', 'Pedido modificado en caja/panel')");
    $stmtHist->execute([$order_id, $id_user]);

    // 6. Reconstruir por completo los detalles de la orden
    // Borramos los anteriores (ya manejamos sus inventarios) para insertar la lista limpia actualizada
    $stmtDelDetails = $pdo->prepare("DELETE FROM order_details WHERE order_id = ?");
    $stmtDelDetails->execute([$order_id]);

    $stmtDetail = $pdo->prepare("
        INSERT INTO order_details (order_id, product_id, kitchen_id, quantity, unit_price, status, process_status, notes, sides)
        VALUES (?, ?, ?, ?, ?, 'pending', 'new', ?, ?)
    ");

    foreach ($products as $p) {
        $stmtK = $pdo->prepare("SELECT kitchen_id FROM product_kitchen WHERE product_id = ? LIMIT 1");
        $stmtK->execute([$p['id_product']]);
        $k_id = $stmtK->fetchColumn() ?: null;

        $stmtDetail->execute([
            $order_id,
            $p['id_product'],
            $k_id,
            $p['quantity'],
            $p['price'],
            $p['notes'] ?? '',
            $p['sides'] ?? ''
        ]);
    }

    $pdo->commit();
    echo json_encode(["error" => 0, "message" => "Pedido actualizado correctamente.", "estimated" => $max_estimated_time]);

} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    echo json_encode(["error" => 1, "message" => $e->getMessage()]);
}