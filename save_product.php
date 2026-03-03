<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Content-Type: application/json");

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') { exit(0); }

include "dbconnect.php"; 

$data = json_decode(file_get_contents("php://input"), true);

if (!$data || !isset($data['system'])) {
    echo json_encode(["success" => false, "error" => "Datos o Sistema no recibidos"]);
    exit;
}

$target_db = $data['system'];

try {
    // Cambiamos a la base de datos seleccionada
    $pdo->exec("USE `$target_db` "); 
} catch (PDOException $e) {
    echo json_encode(["success" => false, "error" => "Base de datos no encontrada"]);
    exit;
}

try {
    $pdo->beginTransaction();

    $p = $data['product'];
    $id_product = null;

    // --- 1. INSERTAR O ACTUALIZAR PRODUCTO ---
    if (isset($p['id_product']) && $p['id_product'] > 0) {
        // MODO EDICIÓN
        $sql = "UPDATE products SET nombre_producto=?, alias=?, price=?, id_category=?, time_prep=? WHERE id_product=?";
        $pdo->prepare($sql)->execute([
            $p['nombre_producto'], 
            $p['alias'] ?? '', 
            $p['price'], 
            $p['id_category'], 
            $p['time_prep'] ?? 0, 
            $p['id_product']
        ]);
        $id_product = $p['id_product'];
        
        // Limpiamos relaciones antiguas para re-insertar
        $pdo->prepare("DELETE FROM product_ingredient WHERE id_product = ?")->execute([$id_product]);
        $pdo->prepare("DELETE FROM product_kitchen WHERE product_id = ?")->execute([$id_product]);
    } else {
        // MODO CREACIÓN
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

    // --- 2. INSERTAR RECETA (Multi-Insumos) ---
    if (!empty($data['recipe'])) {
        $sqlR = "INSERT INTO product_ingredient (id_product, id_ingredient, cant_us) VALUES (?, ?, ?)";
        $stmtR = $pdo->prepare($sqlR);
        foreach ($data['recipe'] as $item) {
            if (!empty($item['id_ingredient'])) {
                $stmtR->execute([$id_product, $item['id_ingredient'], $item['cant_us']]);
            }
        }
    }

    // --- 3. INSERTAR COCINAS ---
    if (!empty($data['kitchens'])) {
        $sqlK = "INSERT INTO product_kitchen (product_id, kitchen_id) VALUES (?, ?)";
        $stmtK = $pdo->prepare($sqlK);
        foreach ($data['kitchens'] as $k_id) {
            $stmtK->execute([$id_product, $k_id]);
        }
    }

    $pdo->commit();
    echo json_encode(["success" => true, "id" => $id_product]);

} catch (Exception $e) {
    if ($pdo->inTransaction()) { $pdo->rollBack(); }
    echo json_encode(["success" => false, "error" => $e->getMessage()]);
}
?>