<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Content-Type: application/json");

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') { exit(0); }

include "dbconnect.php"; 

$data = json_decode(file_get_contents("php://input"), true);

// DETECCIÓN DINÁMICA DEL SISTEMA
$target_db = $data['system'] ?? 'mixtura'; // Por defecto mixtura si no viene

try {
    $pdo->exec("USE `$target_db` "); 
    $pdo->beginTransaction();

    $p = $data['product'];
    $id_product = isset($p['id_product']) ? intval($p['id_product']) : null;

    // --- 1. INSERTAR O ACTUALIZAR PRODUCTO ---
    if ($id_product && $id_product > 0) {
        $sql = "UPDATE products SET nombre_producto=?, alias=?, price=?, id_category=?, time_prep=? WHERE id_product=?";
        $pdo->prepare($sql)->execute([
            $p['nombre_producto'], 
            $p['alias'] ?? '', 
            $p['price'], 
            $p['id_category'], 
            $p['time_prep'] ?? 0, 
            $id_product
        ]);
        
        // LIMPIEZA TOTAL DE RELACIONES PREVIAS (Para evitar Duplicate Entry)
        $pdo->prepare("DELETE FROM product_ingredient WHERE id_product = ?")->execute([$id_product]);
        $pdo->prepare("DELETE FROM product_kitchen WHERE product_id = ?")->execute([$id_product]);
    } else {
        $sql = "INSERT INTO products (nombre_producto, alias, price, id_category, time_prep, state) VALUES (?, ?, ?, ?, ?, 'active')";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            $p['nombre_producto'], 
            $p['alias'] ?? '', 
            $p['price'], 
            $p['id_category'], 
            $p['time_prep'] ?? 0
        ]);
        $id_product = $pdo->lastInsertId();
    }

    // --- 2. INSERTAR RECETA (Validando duplicados en el Array) ---
    if (!empty($data['recipe'])) {
        $sqlR = "INSERT INTO product_ingredient (id_product, id_ingredient, cant_us) VALUES (?, ?, ?)";
        $stmtR = $pdo->prepare($sqlR);
        
        $insumos_procesados = []; // Para evitar mandar el mismo ID dos veces en el mismo request
        
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
    echo json_encode(["success" => true, "id" => $id_product, "system_used" => $target_db]);

} catch (Exception $e) {
    if ($pdo->inTransaction()) { $pdo->rollBack(); }
    echo json_encode(["success" => false, "error" => "Error SQL: " . $e->getMessage()]);
}
?>