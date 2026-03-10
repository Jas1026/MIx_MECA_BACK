<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json");

include('dbconnect.php');

$id = $_POST['id'] ?? null;

if (!$id) {
    echo json_encode(["error" => 1, "message" => "ID requerido"]);
    exit;
}

try {
    // Verificamos si hay productos
    $check = $pdo->prepare("SELECT COUNT(*) FROM products WHERE id_category = ?");
    $check->execute([$id]);
    
    if ($check->fetchColumn() > 0) {
        echo json_encode(["error" => 1, "message" => "No se puede eliminar: Tiene productos asociados."]);
    } else {
        $stmt = $pdo->prepare("DELETE FROM category WHERE id = ?");
        $stmt->execute([$id]);
        echo json_encode(["error" => 0, "message" => "Categoría eliminada"]);
    }
} catch (Exception $e) {
    echo json_encode(["error" => 1, "message" => $e->getMessage()]);
}
?>