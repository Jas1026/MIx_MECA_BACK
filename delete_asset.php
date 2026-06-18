<?php

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json");

include "dbconnect.php";

$id_asset = $_POST['id_asset'];
$system = $_POST['system'];

try{

    $pdo->exec("USE `$system`");

    $stmt=$pdo->prepare("

        UPDATE assets

        SET estado='Eliminado'

        WHERE id_asset=?

    ");

    $stmt->execute([$id_asset]);

    echo json_encode([
        "error"=>0
    ]);

}
catch(PDOException $e){

    echo json_encode([
        "error"=>1,
        "message"=>$e->getMessage()
    ]);

}