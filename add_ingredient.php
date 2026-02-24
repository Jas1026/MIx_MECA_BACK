<?php
// 1. Configuración de errores para debug (puedes quitarlos después)
ini_set('display_errors', 1);
error_reporting(E_ALL);

// 2. Headers obligatorios
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header("Access-Control-Allow-Credentials: true");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

header("Content-Type: application/json");

// 3. Incluir conexión (Aquí se crea la variable $pdo)
include "dbconnect.php";

// 4. Capturar datos del JSON
$data = json_decode(file_get_contents("php://input"), true);

if (!$data) {
    echo json_encode(["success" => false, "error" => "No se recibieron datos"]);
    exit;
}

// 5. Mapeo de variables (usando los nombres de tu JSON de Postman)
$nombre          = $data["nombre"] ?? null;
$stock           = $data["stock_act"] ?? 0;
$unidad          = $data["unidad_med"] ?? "";
$tipo            = $data["tipo"] ?? "normal";
$peso_envase     = $data["peso_envase"] ?? null;
$peso_actual     = $data["peso_actual"] ?? null;
$capacidad_total = $data["capacidad_total"] ?? null;

if (!$nombre) {
    echo json_encode(["success" => false, "error" => "El nombre es obligatorio"]);
    exit;
}

try {
    // 6. Usamos $pdo (que es como se llama en tu dbconnect.php)
    $sql = "INSERT INTO ingredients 
            (nombre, stock_act, unidad_med, tipo, peso_envase, peso_actual, capacidad_total)
            VALUES 
            (:nombre, :stock, :unidad, :tipo, :peso_envase, :peso_actual, :capacidad_total)";

    $stmt = $pdo->prepare($sql);
    
    // Ejecutamos pasando el array directamente
    $result = $stmt->execute([
        ":nombre"          => $nombre,
        ":stock"           => $stock,
        ":unidad"          => $unidad,
        ":tipo"            => $tipo,
        ":peso_envase"     => $peso_envase,
        ":peso_actual"     => $peso_actual,
        ":capacidad_total" => $capacidad_total
    ]);

    echo json_encode(["success" => true]);

} catch (PDOException $e) {
    // Si algo falla en la base de datos, lo veremos aquí
    http_response_code(500);
    echo json_encode([
        "success" => false, 
        "error" => "Error de BD: " . $e->getMessage()
    ]);
}
?>