<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json");
include "dbconnect.php";

$system = $_GET['system'] ?? 'mecapos';
try {
    $pdo->exec("USE `$system` "); 
    $stmt = $pdo->query("SELECT * FROM flats ORDER BY Id_flats DESC");
    $flats = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode($flats);
} catch (PDOException $e) {
    echo json_encode(["error" => $e->getMessage()]);
}