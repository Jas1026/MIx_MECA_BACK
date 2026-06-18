<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Content-Type: application/json");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once 'dbconnect.php';

// LEER EL PARÁMETRO QUE VIENE POR LA URL (GET)
$system = $_GET['system'] ?? 'mecapos';

try {
    // IMPORTANTE: Cambiar a la base de datos que recibimos
    $pdo->exec("USE `$system` ");

    $stmt = $pdo->prepare("
SELECT *

FROM assets

WHERE LOWER(estado) != 'eliminado'

ORDER BY nombre ASC
    ");

    $stmt->execute();
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        "error" => 0,
        "data" => $data,
        "debug_system" => $system // Esto te servirá para verificar en consola
    ]);

} catch (PDOException $e) {
    echo json_encode([
        "error" => 1,
        "message" => "Error de DB: " . $e->getMessage()
    ]);
}