<?php

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type");
header("Access-Control-Allow-Methods: POST");
header("Content-Type: application/json");

include "dbconnect.php";

$id_flat = $_POST["id_flat"] ?? "";
$system = $_POST["system"] ?? "";

if(!$id_flat || !$system){

    echo json_encode([
        "error"=>1,
        "message"=>"Datos incompletos"
    ]);

    exit;
}

try{

    $pdo->exec("USE `$system`");

    $stmt = $pdo->prepare("
        UPDATE flats
        SET state = 2
        WHERE Id_flats = ?
    ");

    $stmt->execute([$id_flat]);

    echo json_encode([

        "error"=>0,

        "message"=>"Piso eliminado"

    ]);

}
catch(PDOException $e){

    echo json_encode([

        "error"=>1,

        "message"=>$e->getMessage()

    ]);

}

?>