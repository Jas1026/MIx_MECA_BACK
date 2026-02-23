<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json");

include "dbconnect.php";

$data = json_decode(file_get_contents("php://input"), true);

$nombre = $data["nombre"];
$stock = $data["stock_act"];
$unidad = $data["unidad_med"];

$sql = "INSERT INTO ingredients (nombre, stock_act, unidad_med)
        VALUES (:nombre, :stock, :unidad)";

$stmt = $conn->prepare($sql);
$stmt->bindParam(":nombre", $nombre);
$stmt->bindParam(":stock", $stock);
$stmt->bindParam(":unidad", $unidad);

if($stmt->execute()){
    echo json_encode(["success" => true]);
}else{
    echo json_encode(["success" => false]);
}