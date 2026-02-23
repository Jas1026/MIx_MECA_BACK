<?php
ini_set('display_errors', 0);
error_reporting(0);

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Content-Type: application/json");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once 'dbconnect.php';

$id_flat = $_POST['id_flat'] ?? $_GET['id_flat'] ?? '';

if (empty($id_flat)) {
    echo json_encode(["error" => 1, "message" => "Flat requerido"]);
    exit;
}

try {

    $stmt = $pdo->prepare("
        SELECT 
            t.id_table,
            t.nombre,
            t.capacidad,
            t.estado,
            o.order_id,
            o.status
        FROM cafe_tables t
        LEFT JOIN orders o 
            ON t.id_table = o.table_id
            AND o.status IN ('open','ready')
        WHERE t.id_flats = ?
    ");

    $stmt->execute([$id_flat]);

    $tables = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        "error" => 0,
        "data" => $tables
    ]);

} catch (PDOException $e) {

    echo json_encode([
        "error" => 1,
        "message" => $e->getMessage()
    ]);
}