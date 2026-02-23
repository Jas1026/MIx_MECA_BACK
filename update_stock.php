<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json");

include "dbconnect.php";

$data = json_decode(file_get_contents("php://input"), true);

$id = $data["id_ingredient"];
$newStock = $data["stock_act"];

$sql = "UPDATE ingredients 
        SET stock_act = :stock
        WHERE id_ingredients = :id";

$stmt = $conn->prepare($sql);
$stmt->bindParam(":stock", $newStock);
$stmt->bindParam(":id", $id);

if($stmt->execute()){
    echo json_encode(["success" => true]);
}else{
    echo json_encode(["success" => false]);
}