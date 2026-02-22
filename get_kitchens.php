<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type");
header("Access-Control-Allow-Methods: GET, OPTIONS");
header("Content-Type: application/json");

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    exit(0);
}

include('dbconnect.php');

try {

    // 👇 TABLA CORRECTA (plural)
    $stmt = $pdo->prepare("SELECT id, name FROM kitchen");
    $stmt->execute();

    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        "error" => 0,
        "data" => $data
    ]);

} catch (Exception $e) {

    echo json_encode([
        "error" => 1,
        "message" => $e->getMessage()
    ]);
}