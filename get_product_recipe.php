<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json");
include "dbconnect.php";

// Capturamos el sistema de la URL
$system = $_GET['system'] ?? 'mecapos';
$pdo->exec("USE `$system` "); 

$id_product = $_GET["id_product"];

// El JOIN es la clave para traer el nombre desde la tabla ingredients
$sql = "SELECT 
            pi.id_ingredient, 
            pi.cant_us, 
            i.nombre, 
            i.unidad_med 
        FROM product_ingredient pi
        INNER JOIN ingredients i ON pi.id_ingredient = i.id_ingredients
        WHERE pi.id_product = :id";

$stmt = $pdo->prepare($sql);
$stmt->execute(['id' => $id_product]);
$data = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode(["error" => 0, "data" => $data]);