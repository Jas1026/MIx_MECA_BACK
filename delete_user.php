<?php

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type");
header("Access-Control-Allow-Methods: POST");
header("Content-Type: application/json");

include "dbconnect.php";

$id = $_POST["id"] ?? "";
$system = $_POST["system"] ?? "";

if(!$id || !$system){

    echo json_encode([
        "error"=>1,
        "message"=>"Datos incompletos"
    ]);

    exit;
}

try{

    $pdo->exec("USE `$system`");

    $stmt = $pdo->prepare("
        UPDATE user
        SET state = 2
        WHERE id = ?
    ");

    $stmt->execute([$id]);

    echo json_encode([

        "error"=>0,

        "message"=>"Usuario eliminado"

    ]);

}
catch(PDOException $e){

    echo json_encode([

        "error"=>1,

        "message"=>$e->getMessage()

    ]);

}

?>