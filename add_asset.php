<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Content-Type: application/json");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once 'dbconnect.php';

// LEER JSON DESDE ANGULAR
$json = file_get_contents("php://input");
$data = json_decode($json, true);

$nombre = $data['nombre'] ?? '';
$categoria = $data['categoria'] ?? '';
$stock = $data['stock'] ?? 0;
$system = $data['system'] ?? 'mecapos';

// CAMBIAR A LA DB CORRECTA ANTES DE INSERTAR
$pdo->exec("USE `$system` ");

if ($nombre == '' || $categoria == '') {
    echo json_encode(["error" => 1, "message" => "Faltan datos", "recibido" => $data]);
    exit();
}

try {
    // Agregamos la columna "created_at" (o como se llame en tu tabla assets) y NOW()
    $stmt = $pdo->prepare("INSERT INTO assets (nombre, categoria, stock, created_at) VALUES (?, ?, ?, NOW())");
    $stmt->execute([$nombre, $categoria, $stock]);
    echo json_encode(["error" => 0, "message" => "Asset creado"]);
} catch (PDOException $e) {
    echo json_encode(["error" => 1, "message" => $e->getMessage()]);
}
?>