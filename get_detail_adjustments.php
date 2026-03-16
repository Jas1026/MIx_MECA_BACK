<?php

require_once 'dbconnect.php';

$detail_id = $_GET['detail_id'] ?? null;

$stmt = $pdo->prepare("
SELECT 
ingredient_id,
SUM(adjustment_qty) as qty
FROM order_detail_adjustments
WHERE detail_id = ?
GROUP BY ingredient_id
");

$stmt->execute([$detail_id]);

echo json_encode([
"error"=>0,
"data"=>$stmt->fetchAll(PDO::FETCH_ASSOC)
]);