<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS, PUT");
header("Access-Control-Allow-Headers: Content-Type");
header("Access-Control-Allow-Credentials: true");

// 1. LEER EL JSON ANTES QUE CUALQUIER OTRA COSA
$input = file_get_contents("php://input");
$data = json_decode($input, true);

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// 2. PARCHE: Inyectar el sistema en $_POST antes del include
// Esto es vital para que dbconnect.php elija la BD correcta
if (isset($data['system'])) {
    $_POST['system'] = $data['system'];
}

header("Content-Type: application/json");

// 3. AHORA SÍ incluimos la conexión
include "dbconnect.php"; 

if (!$data) {
    echo json_encode(["success" => false, "error" => "No se recibieron datos"]);
    exit;
}

if (!isset($data["id_ingredients"])) {
    echo json_encode(["success" => false, "error" => "ID del ingrediente faltante"]);
    exit;
}

try {
    $sql = "UPDATE ingredients 
            SET nombre = :nombre,
                stock_act = :stock,
                unidad_med = :unidad,
                tipo = :tipo,
                peso_envase = :peso_envase,
                peso_actual = :peso_actual,
                capacidad_total = :capacidad_total,
location_id = :location_id
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
       ":capacidad_total" => $data["capacidad_total"] ?? null,
":location_id"     => $data["location_id"] ?? null
    ]);

    echo json_encode(["success" => true, "message" => "Actualizado correctamente en " . $_POST['system']]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(["success" => false, "error" => "Error de BD: " . $e->getMessage()]);
}
?>