<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json");

require_once 'dbconnect.php';

try {

$stmt = $pdo->query("
    SELECT 
        o.order_id,
        o.user_id,
        o.status,
        o.cancel,
        o.client_name,
        o.client_nit,
        o.order_date,    
        o.estimated_time,
        o.actual_time,
        u.code as mesero
    FROM orders o
    INNER JOIN user u ON o.user_id = u.id
    ORDER BY o.order_id DESC
");

$data = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode([
    "error"=>0,
    "data"=>$data
]);

} catch (PDOException $e) {

echo json_encode([
    "error"=>1,
    "message"=>$e->getMessage()
]);

}