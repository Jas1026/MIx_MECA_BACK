<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS, PUT");
header("Access-Control-Allow-Headers: Content-Type");
header("Access-Control-Allow-Credentials: true");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

header("Content-Type: application/json");

// Importante: Aquí se crea la variable $pdo
include "dbconnect.php";

$data = json_decode(file_get_contents("php://input"), true);

if (!$data) {
    echo json_encode(["success" => false, "error" => "No se recibieron datos"]);
    exit;
}

// Verificamos que al menos el ID exista
if (!isset($data["id_ingredients"])) {
    echo json_encode(["success" => false, "error" => "ID del ingrediente faltante"]);
    exit;
}

try {
    // Usamos $pdo de dbconnect.php
    // Incluimos todos los campos para que la actualización sea completa
    $sql = "UPDATE ingredients 
            SET nombre = :nombre,
                stock_act = :stock,
                unidad_med = :unidad,
                tipo = :tipo,
                peso_envase = :peso_envase,
                peso_actual = :peso_actual,
                capacidad_total = :capacidad_total
            WHERE id_ingredients = :id";

    $stmt = $pdo->prepare($sql);
    
    $result = $stmt->execute([
        ":id"              => $data["id_ingredients"],
        ":nombre"          => $data["nombre"] ?? "",
        ":stock"           => $data["stock_act"] ?? 0,
        ":unidad"          => $data["unidad_med"] ?? "",
        ":tipo"            => $data["tipo"] ?? "normal",
        ":peso_envase"     => $data["peso_envase"] ?? null,
        ":peso_actual"     => $data["peso_actual"] ?? null,
        ":capacidad_total" => $data["capacidad_total"] ?? null
    ]);

    if ($result) {
        // rowCount() sirve para saber si realmente se cambió algo o si el ID no existía
        if ($stmt->rowCount() > 0) {
            echo json_encode(["success" => true, "message" => "Actualizado correctamente"]);
        } else {
            echo json_encode(["success" => true, "message" => "No hubo cambios o el ID no existe"]);
        }
    }

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(["success" => false, "error" => "Error de BD: " . $e->getMessage()]);
}
?>