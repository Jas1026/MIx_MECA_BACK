<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");

// Si es una petición OPTIONS (preflight), terminar aquí
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    exit;
}

include("dbconnect.php");

// Si usas FormData en Angular, usa $_POST en PHP:
$ingredient_id = $_POST['ingredient_id'] ?? null;
$system = $_POST['system'] ?? 'mecapos';
// ... resto de tu lógica

$pdo->exec("USE `$system` "); // Selección dinámica de DB

if(!$ingredient_id) {
    echo json_encode(["error"=>1, "message"=>"ID requerido"]);
    exit;
}

$stmt = $pdo->prepare("SELECT * FROM ingredient_bottles WHERE ingredient_id = ? AND estado != 'finalizada' ORDER BY created_at ASC");
$stmt->execute([$ingredient_id]);
$bottles = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode(["error"=>0, "data"=>$bottles]);