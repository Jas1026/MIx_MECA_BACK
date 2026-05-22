<?php

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Content-Type: application/json");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

include("dbconnect.php");

$body = json_decode(file_get_contents("php://input"), true);

$data = json_decode($body['data'], true);
$system = $body['system'] ?? 'mecapos';

try {

    $pdo->exec("USE `$system`");

    foreach($data as $item){

        $stmt = $pdo->prepare("
            UPDATE product_location
            SET orden = ?
            WHERE id = ?
        ");

        $stmt->execute([
            $item['orden'],
            $item['id']
        ]);
    }

    echo json_encode([
        "error" => 0
    ]);

}catch(Exception $e){

    echo json_encode([
        "error" => 1,
        "message" => $e->getMessage()
    ]);
}
?>