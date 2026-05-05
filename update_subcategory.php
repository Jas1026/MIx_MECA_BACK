<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json");

include('dbconnect.php');

$id_subcategory = $_POST['id_subcategory'] ?? null;
$name = $_POST['name'] ?? null;
$id_category = $_POST['id_category'] ?? null;
$system = $_POST['system'] ?? null;

if (!$id_subcategory || !$name || !$id_category) {
    echo json_encode(["error" => 1, "message" => "Datos incompletos"]);
    exit;
}

try {
    $stmt = $pdo->prepare("
        UPDATE subcategories 
        SET name = ?, id_category = ?
        WHERE id_subcategory = ?
    ");
    $stmt->execute([$name, $id_category, $id_subcategory]);

    echo json_encode([
        "error" => 0,
        "message" => "Subcategoría actualizada"
    ]);
} catch (Exception $e) {
    echo json_encode(["error" => 1, "message" => $e->getMessage()]);
}
?>