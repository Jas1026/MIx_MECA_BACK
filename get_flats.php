<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type, Authorization, System");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Allow-Credentials: true");
header("Content-Type: application/json");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

include('dbconnect.php');

try {

    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $stmt = $pdo->prepare("SELECT Id_flats, name FROM flats WHERE state = 1;");
    $stmt->execute();

    $flats = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        "error" => 0,
        "data" => $flats
    ]);

} catch (PDOException $e) {

    echo json_encode([
        "error" => 1,
        "message" => $e->getMessage()
    ]);
}