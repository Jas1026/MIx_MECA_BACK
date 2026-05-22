<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Content-Type: application/json");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

include "dbconnect.php";

$data = json_decode(file_get_contents("php://input"), true);

if (!$data || !isset($data['product'])) {
    echo json_encode([
        "success" => false,
        "error" => "No se recibieron datos"
    ]);
    exit;
}

$target_db = $data['system'] ?? 'mixtura';

try {
    // Seleccionar BD correcta
    $pdo->exec("USE `$target_db`");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->beginTransaction();

    $p = $data['product'];

    $id_product = isset($p['id_product']) ? intval($p['id_product']) : null;
    $tipo_producto = $p['tipo_producto'] ?? 'elaborado';
    $esFinal = ($tipo_producto === 'final');

    $nombre_producto = trim($p['nombre_producto'] ?? '');
    if ($nombre_producto === '') {
        throw new Exception("El nombre del producto es obligatorio");
    }

    // alias no puede ser null si tu BD lo tiene NOT NULL
    // para final puede quedar vacío
    $alias = trim($p['alias'] ?? '');
    if ($alias === null) {
        $alias = '';
    }

    $price = floatval($p['price'] ?? 0);
    $id_category = !$esFinal && isset($p['id_category']) && $p['id_category'] !== ''
        ? intval($p['id_category'])
        : null;

    $id_subcategory = !$esFinal && isset($p['id_subcategory']) && $p['id_subcategory'] !== ''
        ? intval($p['id_subcategory'])
        : null;

    $time_prep = !$esFinal ? intval($p['time_prep'] ?? 0) : 0;

    $stock_congelado = floatval($p['stock_congelado'] ?? 0);
    $stock_disponible = floatval($p['stock_disponible'] ?? 0);
    $stock_minimo = floatval($p['stock_minimo'] ?? 1);

    $proveedor_id = isset($p['proveedor_id']) && $p['proveedor_id'] !== ''
        ? intval($p['proveedor_id'])
        : null;

    // Validación stock distribuido
    $totalDisponible = 0;
    $totalCongelado = 0;

    if (!empty($data['stocks'])) {
        foreach ($data['stocks'] as $loc) {
            $totalDisponible += floatval($loc['stock_disponible'] ?? 0);
            $totalCongelado += floatval($loc['stock_congelado'] ?? 0);
        }

        if ($totalDisponible > $stock_disponible) {
            throw new Exception("El stock disponible distribuido excede el total");
        }

        if ($totalCongelado > $stock_congelado) {
            throw new Exception("El stock congelado distribuido excede el total");
        }
    }

    // =========================================
    // 1. PRODUCTO
    // =========================================
    if ($id_product && $id_product > 0) {

        $sql = "UPDATE products SET 
                    nombre_producto = :nombre_producto,
                    alias = :alias,
                    price = :price,
                    id_category = :id_category,
                    id_subcategory = :id_subcategory,
                    time_prep = :time_prep,
                    stock_congelado = :stock_congelado,
                    stock_disponible = :stock_disponible,
                    stock_minimo = :stock_minimo,
                    proveedor_id = :proveedor_id,
                    tipo_producto = :tipo_producto
                WHERE id_product = :id_product";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':nombre_producto'   => $nombre_producto,
            ':alias'             => $alias,
            ':price'             => $price,
            ':id_category'       => $id_category,
            ':id_subcategory'    => $id_subcategory,
            ':time_prep'         => $time_prep,
            ':stock_congelado'   => $stock_congelado,
            ':stock_disponible'  => $stock_disponible,
            ':stock_minimo'      => $stock_minimo,
            ':proveedor_id'      => $proveedor_id,
            ':tipo_producto'     => $tipo_producto,
            ':id_product'        => $id_product
        ]);

        // Limpia relaciones siempre al editar
        $pdo->prepare("DELETE FROM product_ingredient WHERE id_product = ?")
            ->execute([$id_product]);

        $pdo->prepare("DELETE FROM product_kitchen WHERE product_id = ?")
            ->execute([$id_product]);

        $pdo->prepare("DELETE FROM product_location WHERE product_id = ?")
            ->execute([$id_product]);

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
                    proveedor_id,
                    tipo_producto,
                    state
                ) VALUES (
                    :nombre_producto,
                    :alias,
                    :price,
                    :id_category,
                    :id_subcategory,
                    :time_prep,
                    :stock_congelado,
                    :stock_disponible,
                    :stock_minimo,
                    :proveedor_id,
                    :tipo_producto,
                    :state
                )";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':nombre_producto'   => $nombre_producto,
            ':alias'             => $alias,
            ':price'             => $price,
            ':id_category'       => $id_category,
            ':id_subcategory'    => $id_subcategory,
            ':time_prep'         => $time_prep,
            ':stock_congelado'   => $stock_congelado,
            ':stock_disponible'  => $stock_disponible,
            ':stock_minimo'      => $stock_minimo,
            ':proveedor_id'      => $proveedor_id,
            ':tipo_producto'     => $tipo_producto,
            ':state'             => 'active'
        ]);

        $id_product = $pdo->lastInsertId();
    }

    // =========================================
    // 2. RECETA
    // Solo si es elaborado
    // =========================================
    if ($tipo_producto === 'elaborado' && !empty($data['recipe'])) {
        $sqlR = "INSERT INTO product_ingredient (id_product, id_ingredient, cant_us)
                 VALUES (:id_product, :id_ingredient, :cant_us)";
        $stmtR = $pdo->prepare($sqlR);

        $usados = [];

        foreach ($data['recipe'] as $item) {
            $ing_id = intval($item['id_ingredient'] ?? 0);
            $cant_us = floatval($item['cant_us'] ?? 0);

            if ($ing_id > 0 && $cant_us > 0 && !in_array($ing_id, $usados)) {
                $stmtR->execute([
                    ':id_product'   => $id_product,
                    ':id_ingredient'=> $ing_id,
                    ':cant_us'      => $cant_us
                ]);
                $usados[] = $ing_id;
            }
        }
    }

    // =========================================
    // 3. COCINAS
    // Solo si es elaborado
    // =========================================
    if ($tipo_producto === 'elaborado' && !empty($data['kitchens'])) {
        $sqlK = "INSERT INTO product_kitchen (product_id, kitchen_id)
                 VALUES (:product_id, :kitchen_id)";
        $stmtK = $pdo->prepare($sqlK);

        foreach ($data['kitchens'] as $k_id) {
            $k_id = intval($k_id);
            if ($k_id > 0) {
                $stmtK->execute([
                    ':product_id' => $id_product,
                    ':kitchen_id' => $k_id
                ]);
            }
        }
    }

    // =========================================
    // 4. UBICACIONES MULTIPLES
    // =========================================
    if (!empty($data['stocks'])) {
        $sqlL = "INSERT INTO product_location
                    (product_id, location_id, stock_congelado, stock_disponible, stock_minimo)
                 VALUES
                    (:product_id, :location_id, :stock_congelado, :stock_disponible, :stock_minimo)";
        $stmtL = $pdo->prepare($sqlL);

        foreach ($data['stocks'] as $loc) {
            $location_id = intval($loc['location_id'] ?? 0);

            if ($location_id > 0) {
                $stmtL->execute([
                    ':product_id'       => $id_product,
                    ':location_id'      => $location_id,
                    ':stock_congelado'  => floatval($loc['stock_congelado'] ?? 0),
                    ':stock_disponible' => floatval($loc['stock_disponible'] ?? 0),
                    ':stock_minimo'     => floatval($loc['stock_minimo'] ?? 1)
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