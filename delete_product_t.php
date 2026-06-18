<?php

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json");

include "dbconnect.php";

$id = $_POST['id_product'];
$system = $_POST['system'];

try{

    $pdo->exec("USE `$system`");

    $stmt=$pdo->prepare("
        UPDATE products
        SET state='deleted'
        WHERE id_product=?
    ");

    $stmt->execute([$id]);

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