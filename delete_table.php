<?php

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json");

include "dbconnect.php";

$id_table = $_POST['id_table'] ?? '';
$system = $_POST['system'] ?? '';

if($id_table==''){

    echo json_encode([

        "error"=>1,

        "message"=>"ID requerido"

    ]);

    exit;

}

try{

    $pdo->exec("USE `$system`");

    $stmt = $pdo->prepare("

        UPDATE cafe_tables

        SET estado='Eliminada'

        WHERE id_table=?

    ");

    $stmt->execute([$id_table]);

    echo json_encode([

        "error"=>0,

        "message"=>"Mesa eliminada"

    ]);

}

catch(PDOException $e){

    echo json_encode([

        "error"=>1,

        "message"=>$e->getMessage()

    ]);

}