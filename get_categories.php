<?php

header("Access-Control-Allow-Origin: http://localhost:8100");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Credentials: true");
header("Content-Type: application/json");

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