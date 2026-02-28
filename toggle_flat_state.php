<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json");
include "dbconnect.php";

$id = $_POST['id_flat'] ?? '';
$state = $_POST['state'] ?? '';
$system = $_POST['system'] ?? '';

try {
    $pdo->exec("USE `$system` "); 
    $stmt = $pdo->prepare("UPDATE flats SET state = ? WHERE Id_flats = ?");
    $stmt->execute([$state, $id]);

    echo json_encode(["error" => 0, "message" => "Estado actualizado"]);
} catch (PDOException $e) {
    echo json_encode(["error" => 1, "message" => $e->getMessage()]);
}