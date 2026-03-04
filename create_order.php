<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') { exit(0); }

include('dbconnect.php');

// Recibimos los datos
$id_table = $_POST['id_table'] ?? null;
$id_user = $_POST['id_user'] ?? null;
$products = isset($_POST['products']) ? json_decode($_POST['products'], true) : [];

if (!$id_table || !$id_user || empty($products)) {
    echo json_encode(["error" => 1, "message" => "Datos incompletos"]);
    exit;
}

try {
    $pdo->beginTransaction();

    // --- ALGORITMO DE TIEMPO ESTIMADO ---
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
        $k_id = $info ? $info['kitchen_id'] : 0;

        if (!isset($estaciones[$k_id])) {
            $estaciones[$k_id] = 0;
        }
        $estaciones[$k_id] += $t_prep; 
    }

    $estimated_time = !empty($estaciones) ? max($estaciones) : 0;

    // 1️⃣ Crear la cabecera del pedido (ORDERS)
    $stmt = $pdo->prepare("
        INSERT INTO orders 
        (table_id, user_id, order_date, status, estimated_time, actual_time)
        VALUES (?, ?, NOW(), 'open', ?, 0)
    ");
    $stmt->execute([$id_table, $id_user, $estimated_time]);
    $id_order = $pdo->lastInsertId();

    // 2️⃣ Insertar los detalles (ORDER_DETAILS)
    // Preparamos la consulta una sola vez fuera del bucle por eficiencia
    $stmtDetail = $pdo->prepare("
        INSERT INTO order_details
        (order_id, product_id, kitchen_id, quantity, unit_price, total_price, preparation_time, status, notes, sides)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");

    foreach ($products as $p) {
        // Obtenemos el kitchen_id para este producto específico
        $stmtKitchen = $pdo->prepare("SELECT kitchen_id FROM product_kitchen WHERE product_id = ?");
        $stmtKitchen->execute([$p['id_product']]);
        $kitchen = $stmtKitchen->fetch(PDO::FETCH_ASSOC);
        $kitchen_id = $kitchen ? $kitchen['kitchen_id'] : null;

        $total_price = (float)$p['price'] * (int)$p['quantity'];
        $notes = !empty($p['notes']) ? $p['notes'] : null;
        $sides = !empty($p['sides']) ? $p['sides'] : null;

        // EJECUCIÓN CON 10 PARÁMETROS EXACTOS
        $stmtDetail->execute([
            $id_order,           // 1. order_id
            $p['id_product'],    // 2. product_id
            $kitchen_id,         // 3. kitchen_id
            $p['quantity'],      // 4. quantity
            $p['price'],         // 5. unit_price
            $total_price,        // 6. total_price
            0,                   // 7. preparation_time (inicial)
            'pending',           // 8. status
            $notes,              // 9. notes
            $sides               // 10. sides
        ]);
    }

    // 3️⃣ Actualizar el estado de la mesa
    $stmtUpdateTable = $pdo->prepare("UPDATE cafe_tables SET estado = 'Pendiente' WHERE id_table = ?");
    $stmtUpdateTable->execute([$id_table]);

    $pdo->commit();

    echo json_encode([
        "error" => 0, 
        "id_order" => $id_order, 
        "estimated_minutes" => $estimated_time,
        "message" => "Pedido creado con éxito"
    ]);

} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    echo json_encode([
        "error" => 1, 
        "message" => "Error en servidor: " . $e->getMessage()
    ]);
}
?>