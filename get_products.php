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

    $stmt = $pdo->prepare("
        SELECT 
            id_product,
            nombre_producto,
            alias,
            price,
            time_prep,
            state,
            id_category
        FROM products
        ORDER BY nombre_producto ASC
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