<?php

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json");

include "dbconnect.php";

$id = $_POST['id_ingredient'];
$system = $_POST['system'];

try{

    $pdo->exec("USE `$system`");

    $stmt = $pdo->prepare("
        UPDATE ingredients
        SET estado='eliminado'
        WHERE id_ingredients=?
    ");

    $stmt->execute([$id]);

    echo json_encode([
        "error"=>0,
        "message"=>"Ingrediente eliminado"
    ]);

}
catch(PDOException $e){

    echo json_encode([
        "error"=>1,
        "message"=>$e->getMessage()
    ]);

}