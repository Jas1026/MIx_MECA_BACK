<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json");

include('dbconnect.php');

$id = $_POST['id'] ?? null;
$name = $_POST['name'] ?? null;
$system = $_POST['system'] ?? null;

if (!$id || !$name) {
    echo json_encode(["error" => 1, "message" => "Datos incompletos"]);
    exit;
}

try {
    $stmt = $pdo->prepare("UPDATE category SET name = ? WHERE id = ?");
    $stmt->execute([$name, $id]);
    echo json_encode(["error" => 0, "message" => "Categoría actualizada"]);
} catch (Exception $e) {
    echo json_encode(["error" => 1, "message" => $e->getMessage()]);
}
?>