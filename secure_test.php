<?php
header("Content-Type: application/json");
include('dbconnect.php');

$headers = getallheaders();
$token = $headers['Authorization'] ?? '';

if (!$token) {
    echo json_encode(["error" => 1, "message" => "Token requerido"]);
    exit;
}

// Quitar "Bearer "
$token = str_replace("Bearer ", "", $token);

$stmt = $pdo->prepare("SELECT * FROM user WHERE api_token = ?");
$stmt->execute([$token]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    echo json_encode(["error" => 1, "message" => "Token inválido"]);
    exit;
}

// Si llegó aquí está autenticado
echo json_encode([
    "error" => 0,
    "message" => "Acceso permitido",
    "user" => $user['name']
]);