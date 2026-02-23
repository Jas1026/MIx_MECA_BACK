<?php
ini_set('display_errors', 0);
error_reporting(0);

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Content-Type: application/json");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once 'dbconnect.php';

$nombre = $_POST['nombre'] ?? '';
$categoria = $_POST['categoria'] ?? '';
$stock = $_POST['stock'] ?? 0;
$system = $_POST['system'] ?? '';

if ($nombre == '' || $categoria == '') {
    echo json_encode(["error" => 1, "message" => "Campos incompletos"]);
    exit();
}

try {

    $stmt = $pdo->prepare("
        INSERT INTO assets (nombre, categoria, stock)
        VALUES (?, ?, ?)
    ");

    $stmt->execute([$nombre, $categoria, $stock]);

    echo json_encode(["error" => 0, "message" => "Asset creado"]);

} catch (PDOException $e) {

    echo json_encode([
        "error" => 1,
        "message" => $e->getMessage()
    ]);
}|