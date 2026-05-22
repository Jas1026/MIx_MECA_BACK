<?php

ini_set('display_errors', 1);
error_reporting(E_ALL);

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type, Authorization, System");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Content-Type: application/json");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

$input = file_get_contents("php://input");
$data = json_decode($input, true);

// 👇 importante
if (isset($data['system'])) {
    $_POST['system'] = $data['system'];
}

include "dbconnect.php";

try {

    if (!isset($data['id_fraction'])) {

        echo json_encode([
            "error" => 1,
            "message" => "ID faltante"
        ]);

        exit;
    }

    $sql = "
        DELETE FROM ingredient_fractions
        WHERE id_fraction = :id_fraction
    ";

    $stmt = $pdo->prepare($sql);

    $stmt->execute([
        ":id_fraction" => intval($data['id_fraction'])
    ]);

    echo json_encode([
        "error" => 0,
        "message" => "🗑️ Eliminado"
    ]);

} catch(PDOException $e) {

    echo json_encode([
        "error" => 1,
        "message" => $e->getMessage()
    ]);

}
?>