<?php
ini_set('display_errors', 0);
error_reporting(0);

header("Access-Control-Allow-Origin: http://localhost:8101");
header("Access-Control-Allow-Headers: Content-Type, Authorization, System");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Allow-Credentials: true");
header("Content-Type: application/json");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

include('dbconnect.php');

$id_flat = $_POST['id_flat'] ?? $_GET['id_flat'] ?? '';

if (empty($id_flat)) {
    echo json_encode(["error" => 1, "message" => "Flat requerido"]);
    exit;
}

try {

    $stmt = $pdo->prepare("SELECT id_table, nombre, capacidad, estado 
                           FROM cafe_tables 
                           WHERE id_flats = ?");
    $stmt->execute([$id_flat]);

    $tables = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        "error" => 0,
        "data" => $tables
    ]);

} catch (PDOException $e) {

    echo json_encode([
        "error" => 1,
        "message" => "Error cargando mesas"
    ]);

}