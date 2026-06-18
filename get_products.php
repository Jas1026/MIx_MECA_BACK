<?php
ini_set('display_errors', 0);
error_reporting(0);

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Content-Type: application/json");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once 'dbconnect.php';

$system = $_POST['system'] ?? '';

try {

    if (!empty($system)) {
        $pdo->exec("USE `$system`");
    }

    $stmt = $pdo->prepare("
        SELECT 
            pr.id_product,
            pr.nombre_producto,
            pr.alias,
            pr.price,
            pr.time_prep,
            pr.state,
            pr.id_category,
            pr.id_subcategory,
            pr.stock_congelado,
            pr.stock_disponible,
            pr.stock_minimo,
            pr.proveedor_id,
            pr.tipo_producto,
            sc.name AS subcategory_name,
            pv.nombre_empresa AS nombre_proveedor,
            
            -- Nuevos campos agregados para la cocina
            pk.kitchen_id,
            COALESCE(k.name, 'Sin asignar') AS nombre_cocina

        FROM products pr
 
        LEFT JOIN subcategories sc
            ON pr.id_subcategory = sc.id_subcategory

        LEFT JOIN proveedor pv
            ON pv.id_proveedor = pr.proveedor_id

        -- Relación con la tabla intermedia
        LEFT JOIN product_kitchen pk
            ON pr.id_product = pk.product_id

        -- Relación con la tabla maestra de cocinas (Ajusta 'id_kitchen' o 'nombre_cocina' si sus columnas se llaman diferente)
        LEFT JOIN kitchen k
            ON pk.kitchen_id = k.id

        WHERE pr.state IN ('active','inactive')
        ORDER BY pr.nombre_producto ASC
    ");

    $stmt->execute();
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        "error" => 0,
        "data" => $data
    ]);

} catch (PDOException $e) {
    echo json_encode([
        "error" => 1,
        "message" => $e->getMessage()
    ]);
}
?>