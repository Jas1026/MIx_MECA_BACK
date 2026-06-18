<?php

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json");

include('dbconnect.php');

$id = $_POST['id_unidad'] ?? null;

if(!$id){

    echo json_encode([
        "error"=>1,
        "message"=>"ID requerido"
    ]);

    exit;
}

try{

    $stmt = $pdo->prepare("
        UPDATE unidades_medida
        SET activo=0
        WHERE id_unidad=?
    ");

    $stmt->execute([$id]);

    echo json_encode([

        "error"=>0,

        "message"=>"Unidad eliminada"

    ]);

}
catch(Exception $e){

    echo json_encode([

        "error"=>1,

        "message"=>$e->getMessage()

    ]);

}
?>