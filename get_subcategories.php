<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type, Authorization, system");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

include('dbconnect.php');

$id_category = $_GET['id_category'] ?? null;

try {

    if ($id_category) {
        $stmt = $pdo->prepare("
            SELECT * 
            FROM subcategories 
            WHERE id_category = ? AND active = 1
        ");
        $stmt->execute([$id_category]);
    } else {
        $stmt = $pdo->prepare("
            SELECT * 
            FROM subcategories 
            WHERE active = 1
        ");
        $stmt->execute();
    }

    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        "error" => 0,
        "data" => $data
    ]);

} catch (Exception $e) {
    echo json_encode([
        "error" => 1,
        "message" => $e->getMessage()
    ]);
}