<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json");

include('dbconnect.php');

$name = $_POST['name'] ?? null;
$id_category = $_POST['id_category'] ?? null;
$system = $_POST['system'] ?? null;

if (!$name || !$id_category) {
    echo json_encode(["error" => 1, "message" => "Datos incompletos"]);
    exit;
}

try {
    $stmt = $pdo->prepare("
        INSERT INTO subcategories (name, id_category, active, created_at) 
        VALUES (?, ?, 1, NOW())
    ");
    $stmt->execute([$name, $id_category]);

    echo json_encode([
        "error" => 0,
        "message" => "Subcategoría creada con éxito",
        "id_subcategory" => $pdo->lastInsertId()
    ]);
} catch (Exception $e) {
    echo json_encode(["error" => 1, "message" => $e->getMessage()]);
}
?>