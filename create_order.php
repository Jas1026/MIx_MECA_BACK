<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') { exit(0); }

include('dbconnect.php');

$id_table = $_POST['id_table'] ?? null;
$id_user = $_POST['id_user'] ?? null;
$products = isset($_POST['products']) ? json_decode($_POST['products'], true) : [];

if (!$id_table || !$id_user || empty($products)) {
    echo json_encode(["error"=>1,"message"=>"Datos incompletos"]);
    exit;
}

try {
    $pdo->beginTransaction();

    // --- ALGORITMO DE TIEMPO ESTIMADO ---
    $estaciones = []; // Para guardar la suma por kitchen_id

    foreach ($products as $p) {
        // Obtenemos el tiempo de preparación y la cocina del producto
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

        // Sumamos el tiempo a su estación correspondiente
        if (!isset($estaciones[$k_id])) {
            $estaciones[$k_id] = 0;
        }
        // Multiplicamos tiempo por cantidad (opcional, según tu política de cocina)
        $estaciones[$k_id] += $t_prep; 
    }

    // El tiempo estimado de la orden es el MAX de las sumas de las estaciones
    $estimated_time = !empty($estaciones) ? max($estaciones) : 0;

    // 1️⃣ Crear pedido con el tiempo estimado calculado
    $stmt = $pdo->prepare("
        INSERT INTO orders 
        (table_id, user_id, order_date, status, estimated_time, actual_time)
        VALUES (?, ?, NOW(), 'open', ?, 0)
    ");
    $stmt->execute([$id_table, $id_user, $estimated_time]);
    $id_order = $pdo->lastInsertId();

    // 2️⃣ Insertar detalles (esta parte se mantiene para el registro individual)
    foreach ($products as $p) {
        $stmtKitchen = $pdo->prepare("SELECT kitchen_id FROM product_kitchen WHERE product_id = ?");
        $stmtKitchen->execute([$p['id_product']]);
        $kitchen = $stmtKitchen->fetch(PDO::FETCH_ASSOC);
        $kitchen_id = $kitchen ? $kitchen['kitchen_id'] : null;

        $total_price = $p['price'] * $p['quantity'];

        $stmtDetail = $pdo->prepare("
            INSERT INTO order_details
            (order_id, product_id, kitchen_id, quantity, unit_price, total_price, preparation_time, status)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ");

        $stmtDetail->execute([
            $id_order,
            $p['id_product'],
            $kitchen_id,
            $p['quantity'],
            $p['price'],
            $total_price,
            0, // Aquí podrías poner el time_prep individual si lo necesitas
            'pending'
        ]);
    }

    // 3️⃣ Actualizar Mesa
    $pdo->prepare("UPDATE cafe_tables SET estado = 'Pendiente' WHERE id_table = ?")
        ->execute([$id_table]);

    $pdo->commit();

    echo json_encode([
        "error" => 0, 
        "id_order" => $id_order, 
        "estimated_minutes" => $estimated_time
    ]);

} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    echo json_encode(["error"=>1,"message"=>$e->getMessage()]);
}