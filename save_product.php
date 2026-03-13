<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Content-Type: application/json");

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') { exit(0); }

include "dbconnect.php"; 

$data = json_decode(file_get_contents("php://input"), true);
$target_db = $data['system'] ?? 'mixtura'; 

try {
    $pdo->exec("USE `$target_db` "); 
    $pdo->beginTransaction();

    $p = $data['product'];
    $id_product = isset($p['id_product']) ? intval($p['id_product']) : null;

    // --- 1. INSERTAR O ACTUALIZAR PRODUCTO (Incluyendo Stock Mínimo) ---
    if ($id_product && $id_product > 0) {
        $sql = "UPDATE products SET 
                    nombre_producto = ?, 
                    alias = ?, 
                    price = ?, 
                    id_category = ?, 
                    time_prep = ?, 
                    stock_congelado = ?, 
                    stock_disponible = ?,
                    stock_minimo = ?    -- 🔥 Agregado
                WHERE id_product = ?";
        
        $pdo->prepare($sql)->execute([
            $p['nombre_producto'], 
            $p['alias'] ?? '', 
            $p['price'], 
            $p['id_category'], 
            $p['time_prep'] ?? 0,
            $p['stock_congelado'] ?? 0,
            $p['stock_disponible'] ?? 0,
            $p['stock_minimo'] ?? 0,     // 🔥 Agregado
            $id_product
        ]);
        
        $pdo->prepare("DELETE FROM product_ingredient WHERE id_product = ?")->execute([$id_product]);
        $pdo->prepare("DELETE FROM product_kitchen WHERE product_id = ?")->execute([$id_product]);

    } else {
        $sql = "INSERT INTO products (
                    nombre_producto, 
                    alias, 
                    price, 
                    id_category, 
                    time_prep, 
                    stock_congelado, 
                    stock_disponible, 
                    stock_minimo,       -- 🔥 Agregado
                    state
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'active')";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            $p['nombre_producto'], 
            $p['alias'] ?? '', 
            $p['price'], 
            $p['id_category'], 
            $p['time_prep'] ?? 0,
            $p['stock_congelado'] ?? 0,
            $p['stock_disponible'] ?? 0,
            $p['stock_minimo'] ?? 0      // 🔥 Agregado
        ]);
        $id_product = $pdo->lastInsertId();
    }

    // --- 2. INSERTAR RECETA ---
    if (!empty($data['recipe'])) {
        $sqlR = "INSERT INTO product_ingredient (id_product, id_ingredient, cant_us) VALUES (?, ?, ?)";
        $stmtR = $pdo->prepare($sqlR);
        $insumos_procesados = []; 
        foreach ($data['recipe'] as $item) {
            $ing_id = intval($item['id_ingredient']);
            if ($ing_id > 0 && !in_array($ing_id, $insumos_procesados)) {
                $stmtR->execute([$id_product, $ing_id, $item['cant_us']]);
                $insumos_procesados[] = $ing_id;
            }
        }
    }

    // --- 3. INSERTAR COCINAS ---
    if (!empty($data['kitchens'])) {
        $sqlK = "INSERT INTO product_kitchen (product_id, kitchen_id) VALUES (?, ?)";
        $stmtK = $pdo->prepare($sqlK);
        foreach ($data['kitchens'] as $k_id) {
            $stmtK->execute([$id_product, intval($k_id)]);
        }
    }

    $pdo->commit();
    echo json_encode(["success" => true, "id" => $id_product]);

} catch (Exception $e) {
    if ($pdo->inTransaction()) { $pdo->rollBack(); }
    echo json_encode(["success" => false, "error" => $e->getMessage()]);
}
?>