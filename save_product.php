<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json");

// 1. Incluimos tu conexión actual para no perder la configuración de host/user/pass
include "dbconnect.php"; 

$data = json_decode(file_get_contents("php://input"), true);

if (!$data) {
    echo json_encode(["success" => false, "error" => "No data received"]);
    exit;
}

// 2. FORZAR CAMBIO DE BASE DE DATOS SEGÚN EL SYSTEM
$target_db = $data['system']; // "mixtura" o "mecapos"

try {
    // Ejecutamos un comando SQL para cambiar de base de datos en caliente
    // Esto hace que todas las consultas siguientes se hagan en 'mixtura'
    $pdo->exec("USE `$target_db` "); 
} catch (PDOException $e) {
    echo json_encode(["success" => false, "error" => "No existe la BD: " . $target_db]);
    exit;
}

try {
    $pdo->beginTransaction();

    $p = $data['product'];
    $id_product = null;

    // --- 1. INSERTAR O ACTUALIZAR PRODUCTO ---
    if (isset($p['id_product']) && $p['id_product'] > 0) {
        $sql = "UPDATE products SET nombre_producto=?, price=?, id_category=?, time_prep=? WHERE id_product=?";
        $pdo->prepare($sql)->execute([
            $p['nombre_producto'], 
            $p['price'], 
            $p['id_category'], 
            $p['time_prep'] ?? 0, 
            $p['id_product']
        ]);
        $id_product = $p['id_product'];
        
        // Limpiar para evitar duplicados
        $pdo->prepare("DELETE FROM product_ingredient WHERE id_product = ?")->execute([$id_product]);
        $pdo->prepare("DELETE FROM product_kitchen WHERE product_id = ?")->execute([$id_product]);
    } else {
        $sql = "INSERT INTO products (nombre_producto, price, id_category, time_prep, state) VALUES (?, ?, ?, ?, 'active')";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            $p['nombre_producto'], 
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
            if ($item['id_ingredient']) {
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
    echo json_encode(["success" => true, "id" => $id_product, "db_used" => $target_db]);

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo json_encode(["success" => false, "error" => $e->getMessage()]);
}