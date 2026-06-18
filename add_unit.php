<?php

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json");

include('dbconnect.php');

$name = $_POST['name'] ?? null;

if(!$name){

    echo json_encode([
        "error"=>1,
        "message"=>"Nombre requerido"
    ]);

    exit;
}

try{

    $stmt = $pdo->prepare("
        INSERT INTO unidades_medida
        (
            nombre,
            abreviatura,
            activo,
            created_at
        )
        VALUES
        (
            ?,
            ?,
            1,
            NOW()
        )
    ");

    $stmt->execute([
        $name,
        strtolower($name)
    ]);

    echo json_encode([

        "error"=>0,

        "message"=>"Unidad creada",

        "id"=>$pdo->lastInsertId()

    ]);

}
catch(Exception $e){

    echo json_encode([

        "error"=>1,

        "message"=>$e->getMessage()

    ]);

}
?>