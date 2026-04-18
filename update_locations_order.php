<?php
header('Content-Type: application/json; charset=utf-8');
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type");
header("Access-Control-Allow-Methods: POST, OPTIONS");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit;
}

include "dbconnect.php";

$data = json_decode($_POST['data'] ?? '', true);

if (!$data) {
    echo json_encode([
        "success" => false,
        "msg" => "No data"
    ]);
    exit;
}

try {

    $stmt = $pdo->prepare("
        UPDATE locations 
        SET parent_id = :parent_id, orden = :orden
        WHERE id_location = :id
    ");

    foreach ($data as $item) {

        $id = (int)$item['id_location'];
        $orden = (int)$item['orden'];

        $parent_id = $item['parent_id'];

        if ($parent_id === null || $parent_id === "null" || $parent_id === "") {
            $parent_id = null;
        } else {
            $parent_id = (int)$parent_id;
        }

        $stmt->execute([
            ':parent_id' => $parent_id,
            ':orden' => $orden,
            ':id' => $id
        ]);
    }

    echo json_encode([
        "success" => true
    ]);

} catch (Exception $e) {

    echo json_encode([
        "success" => false,
        "error" => $e->getMessage()
    ]);
}
exit;