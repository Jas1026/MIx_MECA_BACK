<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json");

require_once 'dbconnect.php';

// Capturamos el ID del piso
$id_flat = $_POST['id_flat'] ?? $_GET['id_flat'] ?? '';

if (empty($id_flat)) {
    echo json_encode(["error" => 1, "message" => "ID de piso faltante"]);
    exit;
}

try {
    // Consulta limpia: Trae todo de cafe_tables donde id_flats sea el que pedimos
    $stmt = $pdo->prepare("SELECT * FROM cafe_tables WHERE id_flats = ?");
    $stmt->execute([$id_flat]);
    $tables = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Esto te dirá exactamente qué está encontrando
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
?>