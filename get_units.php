<?php

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json");

include('dbconnect.php');

try {

    $stmt = $pdo->prepare("
        SELECT
            id_unidad,
            nombre,
            abreviatura
        FROM unidades_medida
        WHERE activo = 1
        ORDER BY nombre
    ");

    $stmt->execute();

    echo json_encode([
        "error"=>0,
        "data"=>$stmt->fetchAll(PDO::FETCH_ASSOC)
    ]);

}
catch(Exception $e){

    echo json_encode([
        "error"=>1,
        "message"=>$e->getMessage()
    ]);

}
?>