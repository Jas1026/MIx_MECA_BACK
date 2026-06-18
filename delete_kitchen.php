<?php

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json");

include "dbconnect.php";

$id = $_POST['id'] ?? '';
$system = $_POST['system'] ?? '';

if($id==''){

    echo json_encode([
        "error"=>1,
        "message"=>"ID requerido"
    ]);

    exit;
}

try{

    $pdo->exec("USE `$system`");

    $stmt=$pdo->prepare("
        UPDATE kitchen
        SET active=2
        WHERE id=?
    ");

    $stmt->execute([$id]);

    echo json_encode([
        "error"=>0,
        "message"=>"Cocina eliminada"
    ]);

}

catch(PDOException $e){

    echo json_encode([
        "error"=>1,
        "message"=>$e->getMessage()
    ]);

}