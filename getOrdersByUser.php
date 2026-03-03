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
            o.client_name,
            o.client_nit,
            o.order_date,
            u.code as mesero,

            COALESCE(SUM(od.preparation_time * od.quantity),0) as estimated_total_time

        FROM orders o
        INNER JOIN user u ON o.user_id = u.id
        LEFT JOIN order_details od ON o.order_id = od.order_id

        WHERE o.user_id = :user_id

        GROUP BY o.order_id
        ORDER BY o.order_id DESC
    ");

    $stmt->bindParam(':user_id', $user_id);
    $stmt->execute();

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