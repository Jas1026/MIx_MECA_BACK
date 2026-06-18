<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json");

require_once 'dbconnect.php';

try {
    // Agregamos una subconsulta que cuenta si existen detalles que NO sean 'new' o 'preparing'
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
            u.code as mesero,
            -- Si el conteo es 0, significa que todos los platos están en 'new' o 'preparing' (Es editable)
            CASE 
                WHEN (
                    SELECT COUNT(*) 
                    FROM order_details od 
                    WHERE od.order_id = o.order_id 
                    AND od.process_status NOT IN ('new', 'preparing')
                ) = 0 THEN 1
                ELSE 0 
            END as es_editable
        FROM orders o
        INNER JOIN user u ON o.user_id = u.id
        ORDER BY o.order_id DESC
    ");

    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        "error" => 0,
        "data" => $data
    ]);

} catch (PDOException $e) {
    echo json_encode([
        "error" => 1,
        "message" => $e->getMessage()
    ]);
}