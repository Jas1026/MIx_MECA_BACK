<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header("Access-Control-Allow-Credentials: true");

// 1. Leer el JSON del cuerpo de la petición
$input = file_get_contents("php://input");
$data = json_decode($input, true);

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// 2. PARCHE PARA EL SISTEMA: Inyectamos el sistema en $_POST para que dbconnect.php lo vea
if (isset($data['system'])) {
    $_POST['system'] = $data['system'];
}

header("Content-Type: application/json");

// 3. Incluimos la conexión (ahora $dbname será el correcto)
include "dbconnect.php";

if (!$data) {
    echo json_encode(["success" => false, "error" => "No data received"]);
    exit;
}

try {
    $sql = "INSERT INTO ingredients 
            (nombre, stock_act, unidad_med, tipo, peso_envase, peso_actual, capacidad_total)
            VALUES 
            (:nombre, :stock, :unidad, :tipo, :peso_envase, :peso_actual, :capacidad_total)";

    $stmt = $pdo->prepare($sql);
    
    $result = $stmt->execute([
        ":nombre"          => $data["nombre"] ?? null,
        ":stock"           => $data["stock_act"] ?? 0,
        ":unidad"          => $data["unidad_med"] ?? null,
        ":tipo"            => $data["tipo"] ?? "normal",
        ":peso_envase"     => $data["peso_envase"] ?? null,
        ":peso_actual"     => $data["peso_actual"] ?? null,
        ":capacidad_total" => $data["capacidad_total"] ?? null
    ]);

    echo json_encode(["success" => true]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(["success" => false, "error" => $e->getMessage()]);
}
?>