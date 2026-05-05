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

// Nota: Asegúrate de que tu dbconnect.php use la variable $system 
// si manejas múltiples bases de datos, de lo contrario usará la por defecto.
$system = $_POST['system'] ?? '';

try {
    $stmt = $pdo->prepare("
    SELECT 
        p.id_product,
        p.nombre_producto,
        p.alias,
        p.price,
        p.time_prep,
        p.state,
        p.id_category,
        p.id_subcategory,
        sc.name AS subcategory_name,
        p.stock_congelado,
        p.stock_disponible,
        p.stock_minimo
    FROM products p
    LEFT JOIN subcategories sc 
        ON p.id_subcategory = sc.id_subcategory
    ORDER BY p.nombre_producto ASC
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