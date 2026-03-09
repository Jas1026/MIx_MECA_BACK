<?php

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type, Authorization, system");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Content-Type: application/json");

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit();
}

include "dbconnect.php";

$data = json_decode(file_get_contents("php://input"), true);

$filtro = $data['filtro'] ?? '';
$fecha = $data['fecha'] ?? '';
$fecha_inicio = $data['fecha_inicio'] ?? '';
$fecha_fin = $data['fecha_fin'] ?? '';

$where = "";

if ($filtro == "fecha" && $fecha != "") {
    $where = "AND DATE(o.Created) = '$fecha'";
}

if ($filtro == "rango" && $fecha_inicio != "" && $fecha_fin != "") {
    $where = "AND DATE(o.Created) BETWEEN '$fecha_inicio' AND '$fecha_fin'";
}

$query = "
SELECT 
    f.Name as area,
    COUNT(o.Id) as total
FROM orders o
JOIN cafe_tables t ON o.TableId = t.Id
JOIN floors f ON t.FloorId = f.Id
WHERE 1=1 $where
GROUP BY f.Name
";

$stmt = $pdo->prepare($query);
$stmt->execute();

$result = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode($result);