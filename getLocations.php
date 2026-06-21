<?php

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Content-Type: application/json");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

include('dbconnect.php');

$stmt = $pdo->prepare("
    SELECT *
    FROM locations where estado = 1
");

$stmt->execute();

echo json_encode([
    "error" => 0,
    "data" => $stmt->fetchAll(PDO::FETCH_ASSOC)
]);