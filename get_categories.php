<?php
// Obtener el origen de la petición (por ejemplo http://localhost:8101)
$origin = isset($_SERVER['HTTP_ORIGIN']) ? $_SERVER['HTTP_ORIGIN'] : '*';

// Configurar los headers de CORS dinámicamente
header("Access-Control-Allow-Origin: $origin"); 
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Credentials: true");
header("Content-Type: application/json");

// Manejo del preflight (petición previa que hace el navegador)
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

include('dbconnect.php');

$stmt = $pdo->prepare("SELECT id, name FROM category WHERE active = 1");
$stmt->execute();

echo json_encode([
    "error" => 0,
    "data" => $stmt->fetchAll(PDO::FETCH_ASSOC)
]);