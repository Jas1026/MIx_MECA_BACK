<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json");
include "dbconnect.php";

$id_product = $_GET["id_product"];
$stmt = $pdo->prepare("SELECT kitchen_id FROM product_kitchen WHERE product_id = ?");
$stmt->execute([$id_product]);
$data = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode(["success" => true, "data" => $data]);