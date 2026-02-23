<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json");

require_once 'dbconnect.php';

try {

    $stmt = $pdo->query("
        SELECT id, code 
        FROM user
        ORDER BY code ASC
    ");

    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        "error"=>0,
        "data"=>$data
    ]);

} catch (PDOException $e) {

    echo json_encode([
        "error"=>1,
        "message"=>$e->getMessage()
    ]);

}