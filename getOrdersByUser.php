<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json");

require_once 'dbconnect.php';

$user_id = $_POST['user_id'] ?? 0;
$system  = $_POST['system'] ?? 'mecapos';

try {
  $stmt = $pdo->prepare("
        SELECT 
            o.order_id,
            o.user_id,
            o.status,
            o.order_date,
            o.estimated_time AS estimated_total_time,
            o.actual_time,
            u.code AS mesero,
            t.nombre AS nombre_mesa,     -- Nombre de la mesa (a1, m2, etc)
            f.Name AS nombre_piso        -- Nombre del piso (Piso 1, etc)
        FROM orders o
        INNER JOIN user u ON o.user_id = u.id
        INNER JOIN cafe_tables t ON o.table_id = t.id_table  -- Relación con mesas
        INNER JOIN flats f ON t.id_flats = f.Id_flats       -- Relación con pisos
        WHERE o.user_id = :user_id
        ORDER BY o.order_id DESC
    ");

    $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
    $stmt->execute();

    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        "error" => 0,
        "data"  => $data
    ]);

} catch (PDOException $e) {

    echo json_encode([
        "error" => 1,
        "message" => $e->getMessage()
    ]);
}