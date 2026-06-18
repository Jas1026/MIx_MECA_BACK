<?php

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Content-Type: application/json");

if($_SERVER['REQUEST_METHOD']=='OPTIONS'){
    exit();
}

require_once 'dbconnect.php';

$postjson = file_get_contents("php://input");

$request = json_decode($postjson,true);

$user_id = $request['user_id'];
$system  = $request['system'];

try{
$sql = "

SELECT

o.order_id,
o.table_id,
o.order_date,

d.detail_id,
d.quantity,
d.process_status,
d.alert_status,

f.Name as piso,
t.nombre as mesa,

p.nombre_producto as product_name,
p.alias

FROM ".$system.".orders o

INNER JOIN ".$system.".order_details d
ON o.order_id=d.order_id

INNER JOIN ".$system.".products p
ON d.product_id=p.id_product

INNER JOIN ".$system.".cafe_tables t
ON o.table_id=t.id_table

INNER JOIN ".$system.".flats f
ON t.id_flats=f.Id_flats

WHERE

o.user_id=?

AND o.status='open'

AND d.process_status IN(

'new',

'preparing',

'ready_pickup'

)

ORDER BY

CASE

WHEN d.process_status='ready_pickup'

THEN 0

ELSE 1

END,

o.order_date ASC

";

$stmt = $pdo->prepare($sql);

$stmt->execute([
    $user_id
]);

$data = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode([
    'error'=>0,
    'data'=>$data
]);

}
catch(Exception $e){

echo json_encode([
    'error'=>1,
    'message'=>$e->getMessage()
]);

}