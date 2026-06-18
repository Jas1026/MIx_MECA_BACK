<?php

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json");

include "dbconnect.php";

$id = $_POST['id'] ?? '';
$state = $_POST['state'] ?? '';
$system = $_POST['system'] ?? '';

try {

    $pdo->exec("USE `$system`");

    $stmt = $pdo->prepare("
        UPDATE kitchen
        SET active = ?
        WHERE id = ?
    ");

    $stmt->execute([
        intval($state),
        intval($id)
    ]);

    echo json_encode([

        "error" => 0,

        "rows" => $stmt->rowCount(),

        "id" => $id,

        "state" => $state

    ]);

}
catch(PDOException $e){

    echo json_encode([

        "error" => 1,

        "message" => $e->getMessage()

    ]);

}