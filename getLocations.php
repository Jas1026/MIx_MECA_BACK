<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json");

include("dbconnect.php");

$stmt = $pdo->prepare("
SELECT 
  l.*, 
  p.nombre as parent_nombre
FROM locations l
LEFT JOIN locations p ON l.parent_id = p.id_location
ORDER BY l.tipo, l.nombre
");

$stmt->execute();
$data = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode([
  "error" => 0,
  "data" => $data
]);