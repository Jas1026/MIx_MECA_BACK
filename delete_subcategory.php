<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json");

include('dbconnect.php');

$id_subcategory = $_POST['id_subcategory'] ?? null;

if (!$id_subcategory) {
    echo json_encode(["error" => 1, "message" => "ID requerido"]);
    exit;
}

try {
    // Validar relación con productos
    $check = $pdo->prepare("
        SELECT COUNT(*) 
        FROM products 
        WHERE id_subcategory = ?
    ");
    $check->execute([$id_subcategory]);

    if ($check->fetchColumn() > 0) {
        echo json_encode([
            "error" => 1,
            "message" => "No se puede eliminar: Tiene productos asociados."
        ]);
    } else {
        $stmt = $pdo->prepare("
            DELETE FROM subcategories 
            WHERE id_subcategory = ?
        ");
        $stmt->execute([$id_subcategory]);

        echo json_encode([
            "error" => 0,
            "message" => "Subcategoría eliminada"
        ]);
    }
} catch (Exception $e) {
    echo json_encode(["error" => 1, "message" => $e->getMessage()]);
}
?>