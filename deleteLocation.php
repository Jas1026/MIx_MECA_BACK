<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json");

include("dbconnect.php");

$id = $_GET['id'];

$stmt = $pdo->prepare("DELETE FROM locations WHERE id_location=?");
$stmt->execute([$id]);

echo json_encode(["error"=>0]);