<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json");

include('dbconnect.php');

// Recibimos los datos por $_POST (FormData de Angular)
$name = $_POST['name'] ?? null;
$system = $_POST['system'] ?? null; // Lo recibimos, aunque no lo guardemos aún

if (!$name) {
    echo json_encode(["error" => 1, "message" => "Nombre requerido"]);
    exit;
}

try {
    // Solo usamos las columnas que confirmaste que existen: id, name, active, created_at
    // El 'id' es autoincremental, así que no se pone.
    $stmt = $pdo->prepare("INSERT INTO category (name, active, created_at) VALUES (?, 1, NOW())");
    $stmt->execute([$name]);
    
    echo json_encode([
        "error" => 0, 
        "message" => "Categoría creada con éxito",
        "id" => $pdo->lastInsertId()
    ]);
} catch (Exception $e) {
    echo json_encode(["error" => 1, "message" => "Error de BD: " . $e->getMessage()]);
}
?>