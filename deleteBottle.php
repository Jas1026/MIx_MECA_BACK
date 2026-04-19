<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') exit;

include("dbconnect.php");

$id_bottle = $_POST['id_bottle'] ?? null;
$system    = $_POST['system'] ?? 'mecapos';

if(!$id_bottle){
    echo json_encode(["error"=>1, "message"=>"ID requerido"]);
    exit;
}

try {

    $pdo->exec("USE `$system` ");

    $stmt = $pdo->prepare("DELETE FROM ingredient_bottles WHERE id_bottle = ?");
    $stmt->execute([$id_bottle]);

    echo json_encode([
        "error" => 0,
        "message" => "Botella eliminada"
    ]);

} catch(Exception $e){
    echo json_encode(["error"=>1, "message"=>$e->getMessage()]);
}