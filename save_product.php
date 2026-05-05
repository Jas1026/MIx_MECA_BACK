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

    // 🔥 DEFAULTS SEGUROS
    $p['stock_minimo'] = isset($p['stock_minimo']) ? floatval($p['stock_minimo']) : 1;
    $p['stock_disponible'] = floatval($p['stock_disponible'] ?? 0);
    $p['stock_congelado'] = floatval($p['stock_congelado'] ?? 0);

    // =========================================
    // 🔥 VALIDAR STOCK TOTAL VS DISTRIBUCIÓN
    // =========================================
    $totalDisponible = 0;
    $totalCongelado = 0;

    if (!empty($data['stocks'])) {
        foreach ($data['stocks'] as $loc) {
            $totalDisponible += floatval($loc['stock_disponible'] ?? 0);
            $totalCongelado += floatval($loc['stock_congelado'] ?? 0);
        }

        if ($totalDisponible > $p['stock_disponible']) {
            throw new Exception("El stock disponible distribuido excede el total");
        }

        if ($totalCongelado > $p['stock_congelado']) {
            throw new Exception("El stock congelado distribuido excede el total");
        }
    }

    // =========================================
    // 1. PRODUCTO
    // =========================================
    if ($id_product && $id_product > 0) {

        $sql = "UPDATE products SET 
                    nombre_producto = ?, 
                    alias = ?, 
                    price = ?, 
                    id_category = ?, 
                    id_subcategory = ?, 
                    time_prep = ?, 
                    stock_congelado = ?, 
                    stock_disponible = ?,
                    stock_minimo = ?
                WHERE id_product = ?";
        
        $pdo->prepare($sql)->execute([
            $p['nombre_producto'], 
            $p['alias'] ?? '', 
            $p['price'], 
            $p['id_category'], 
            $p['id_subcategory'],
            $p['time_prep'] ?? 0,
            $p['stock_congelado'],
            $p['stock_disponible'],
            $p['stock_minimo'],
            $id_product
        ]);

        // limpiar relaciones
        $pdo->prepare("DELETE FROM product_ingredient WHERE id_product = ?")->execute([$id_product]);
        $pdo->prepare("DELETE FROM product_kitchen WHERE product_id = ?")->execute([$id_product]);
        $pdo->prepare("DELETE FROM product_location WHERE product_id = ?")->execute([$id_product]);

    } else {

        $sql = "INSERT INTO products (
                    nombre_producto, 
                    alias, 
                    price, 
                    id_category,
                    id_subcategory, 
                    time_prep, 
                    stock_congelado, 
                    stock_disponible, 
                    stock_minimo,
                    state
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'active')";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            $p['nombre_producto'], 
            $p['alias'] ?? '', 
            $p['price'], 
            $p['id_category'], 
            $p['id_subcategory'],
            $p['time_prep'] ?? 0,
            $p['stock_congelado'],
            $p['stock_disponible'],
            $p['stock_minimo']
        ]);

        $id_product = $pdo->lastInsertId();
    }

    // =========================================
    // 2. RECETA
    // =========================================
    if (!empty($data['recipe'])) {
        $sqlR = "INSERT INTO product_ingredient (id_product, id_ingredient, cant_us) VALUES (?, ?, ?)";
        $stmtR = $pdo->prepare($sqlR);

        $usados = [];

        foreach ($data['recipe'] as $item) {
            $ing_id = intval($item['id_ingredient']);

            if ($ing_id > 0 && !in_array($ing_id, $usados)) {
                $stmtR->execute([$id_product, $ing_id, $item['cant_us']]);
                $usados[] = $ing_id;
            }
        }
    }

    // =========================================
    // 3. COCINAS
    // =========================================
    if (!empty($data['kitchens'])) {
        $sqlK = "INSERT INTO product_kitchen (product_id, kitchen_id) VALUES (?, ?)";
        $stmtK = $pdo->prepare($sqlK);

        foreach ($data['kitchens'] as $k_id) {
            $stmtK->execute([$id_product, intval($k_id)]);
        }
    }

    // =========================================
    // 4. UBICACIONES MULTIPLES 🔥
    // =========================================
    if (!empty($data['stocks'])) {

        $sqlL = "INSERT INTO product_location 
                 (product_id, location_id, stock_congelado, stock_disponible, stock_minimo)
                 VALUES (?, ?, ?, ?, ?)";

        $stmtL = $pdo->prepare($sqlL);

        foreach ($data['stocks'] as $loc) {

            $location_id = intval($loc['location_id']);

            if ($location_id > 0) {
                $stmtL->execute([
                    $id_product,
                    $location_id,
                    floatval($loc['stock_congelado'] ?? 0),
                    floatval($loc['stock_disponible'] ?? 0),
                    floatval($loc['stock_minimo'] ?? 1)
                ]);
            }
        }
    }

    $pdo->commit();

    echo json_encode([
        "success" => true,
        "id" => $id_product
    ]);

} catch (Exception $e) {

    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    echo json_encode([
        "success" => false,
        "error" => $e->getMessage()
    ]);
}
?>