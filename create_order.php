<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Content-Type: application/json");

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') { exit(0); }

include('dbconnect.php');

// Recibimos los datos
$id_table = $_POST['id_table'] ?? null;
$id_user = $_POST['id_user'] ?? null;
$system = $_POST['system'] ?? 'mixtura';
$products = isset($_POST['products']) ? json_decode($_POST['products'], true) : [];
$force_order = $_POST['force_order'] ?? 'false'; // 'true' si el usuario aceptó vender sin stock

if (!$id_table || !$id_user || empty($products)) {
    echo json_encode(["error" => 1, "message" => "Datos incompletos"]);
    exit;
}

try {
    $pdo->exec("USE `$system` ");
    $pdo->beginTransaction();

    // --- 1. VALIDACIÓN DE STOCK DISPONIBLE ---
    // Solo validamos si force_order es 'false'
    if ($force_order !== 'true') {
        foreach ($products as $p) {
            $stmtStock = $pdo->prepare("SELECT stock_disponible, nombre_producto FROM products WHERE id_product = ?");
            $stmtStock->execute([$p['id_product']]);
            $prod = $stmtStock->fetch(PDO::FETCH_ASSOC);

            if ($prod && (float)$prod['stock_disponible'] < (int)$p['quantity']) {
                $pdo->rollBack();
                echo json_encode([
                    "error" => 2, 
                    "message" => "Stock insuficiente para: " . $prod['nombre_producto'] . ". Disponible: " . $prod['stock_disponible'],
                    "product_id" => $p['id_product']
                ]);
                exit;
            }
        }
    }

    // --- 2. ALGORITMO DE TIEMPO ESTIMADO POR ESTACIÓN ---
    $estaciones = []; 
    foreach ($products as $p) {
        $stmtP = $pdo->prepare("
            SELECT p.time_prep, pk.kitchen_id 
            FROM products p
            LEFT JOIN product_kitchen pk ON p.id_product = pk.product_id
            WHERE p.id_product = ?
        ");
        $stmtP->execute([$p['id_product']]);
        $info = $stmtP->fetch(PDO::FETCH_ASSOC);

        $t_prep = $info ? (int)$info['time_prep'] : 0;
        $k_id = $info ? (int)$info['kitchen_id'] : 0;

        if (!isset($estaciones[$k_id])) { $estaciones[$k_id] = 0; }
        $estaciones[$k_id] += ($t_prep * (int)$p['quantity']); 
    }
    $estimated_time = !empty($estaciones) ? max($estaciones) : 0;

    // --- 3. CREAR CABECERA DEL PEDIDO (ORDERS) ---
    $stmt = $pdo->prepare("
        INSERT INTO orders 
        (table_id, user_id, order_date, status, estimated_time, actual_time)
        VALUES (?, ?, NOW(), 'open', ?, 0)
    ");
    $stmt->execute([$id_table, $id_user, $estimated_time]);
    $id_order = $pdo->lastInsertId();

    // --- 4. INSERTAR DETALLES Y DESCONTAR STOCK ---
    $stmtDetail = $pdo->prepare("
        INSERT INTO order_details
        (order_id, product_id, kitchen_id, quantity, unit_price, total_price, preparation_time, status, notes, sides)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");

    $stmtUpdateStock = $pdo->prepare("
        UPDATE products 
        SET stock_disponible = stock_disponible - ? 
        WHERE id_product = ?
    ");

    foreach ($products as $p) {
        // Obtener la cocina del producto
        $stmtK = $pdo->prepare("SELECT kitchen_id FROM product_kitchen WHERE product_id = ? LIMIT 1");
        $stmtK->execute([$p['id_product']]);
        $k_data = $stmtK->fetch(PDO::FETCH_ASSOC);
        $kitchen_id = $k_data ? $k_data['kitchen_id'] : null;

        $total_price = (float)$p['price'] * (int)$p['quantity'];

        // Insertar detalle
        $stmtDetail->execute([
            $id_order,
            $p['id_product'],
            $kitchen_id,
            $p['quantity'],
            $p['price'],
            $total_price,
            0,
            'pending',
            $p['notes'] ?? null,
            $p['sides'] ?? null
        ]);

        // 🔥 DESCUENTO DINÁMICO DE STOCK (Aquí es donde puede quedar en negativo)
        $stmtUpdateStock->execute([$p['quantity'], $p['id_product']]);
    }

    // --- 5. ACTUALIZAR ESTADO DE MESA ---
    $stmtUpdateTable = $pdo->prepare("UPDATE cafe_tables SET estado = 'Pendiente' WHERE id_table = ?");
    $stmtUpdateTable->execute([$id_table]);

    $pdo->commit();

    echo json_encode([
        "error" => 0, 
        "id_order" => $id_order, 
        "message" => "Pedido creado y stock descontado correctamente"
    ]);

} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    echo json_encode([
        "error" => 1, 
        "message" => "Error crítico: " . $e->getMessage()
    ]);
}
?>