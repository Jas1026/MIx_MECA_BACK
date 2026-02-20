<?php

header("Access-Control-Allow-Origin: http://localhost:8101");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Allow-Credentials: true");
header("Content-Type: application/json");

// Responder preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

include('dbconnect.php');
$token = $_POST['token'] ?? '';

if (!$token) {
    echo json_encode(["valid" => false]);
    exit;
}

$stmt = $pdo->prepare("SELECT id, name FROM user WHERE api_token = ? AND state = 1");
$stmt->execute([$token]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if ($user) {
    echo json_encode([
        "valid" => true,
        "id" => $user["id"],
        "name" => $user["name"]
    ]);
} else {
    echo json_encode(["valid" => false]);
}