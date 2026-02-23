<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json");

include "dbconnect.php";

$id_product = $_GET["id_product"];

$sql = "SELECT i.nombre, i.unidad_med, pi.cant_us
        FROM product_ingredient pi
        JOIN ingredients i ON pi.id_ingredient = i.id_ingredients
        WHERE pi.id_product = :id";

$stmt = $conn->prepare($sql);
$stmt->bindParam(":id", $id_product);
$stmt->execute();

$data = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode([
    "success" => true,
    "data" => $data
]);