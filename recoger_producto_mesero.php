<?php

header("Access-Control-Allow-Origin:*");
header("Access-Control-Allow-Headers:Content-Type");
header("Access-Control-Allow-Methods:POST,OPTIONS");
header("Content-Type:application/json");

if($_SERVER['REQUEST_METHOD']=='OPTIONS'){
    exit();
}

require_once 'dbconnect.php';

$request=json_decode(
file_get_contents("php://input"),
true
);

$detail_id=$request['detail_id'];
$system=$request['system'];

try{

$pdo->beginTransaction();


// 1. MARCAR PRODUCTO COMO RECOGIDO

$stmt=$pdo->prepare("

UPDATE

".$system.".order_details

SET

process_status='picked_up',

status='ready'

WHERE

detail_id=?

");

$stmt->execute([
$detail_id
]);



// 2. OBTENER ORDER_ID

$stmt=$pdo->prepare("

SELECT

order_id

FROM

".$system.".order_details

WHERE

detail_id=?

");

$stmt->execute([
$detail_id
]);

$order=$stmt->fetch(PDO::FETCH_ASSOC);

$order_id=$order['order_id'];



// 3. CONTAR PRODUCTOS QUE FALTAN

$stmt=$pdo->prepare("

SELECT

COUNT(*) total

FROM

".$system.".order_details

WHERE

order_id=?

AND

process_status<>'picked_up'

AND

status<>'canceled'

");

$stmt->execute([
$order_id
]);

$row=$stmt->fetch(PDO::FETCH_ASSOC);



if($row['total']==0){

    // TODOS RECOGIDOS

    $stmt=$pdo->prepare("

    UPDATE

    ".$system.".orders

    SET

    status='ready'

    WHERE

    order_id=?

    ");

    $stmt->execute([
    $order_id
    ]);


    // OBTENER MESA

    $stmt=$pdo->prepare("

    SELECT

    table_id

    FROM

    ".$system.".orders

    WHERE

    order_id=?

    ");

    $stmt->execute([
    $order_id
    ]);

    $order=$stmt->fetch(PDO::FETCH_ASSOC);

    $table_id=$order['table_id'];



    // MESA -> READY

    $stmt=$pdo->prepare("

    UPDATE

    ".$system.".cafe_tables

    SET

    estado='Ready'

    WHERE

    id_table=?

    ");

    $stmt->execute([
    $table_id
    ]);

}


$pdo->commit();


echo json_encode([

'error'=>0

]);

}
catch(Exception $e){

$pdo->rollBack();

echo json_encode([

'error'=>1,

'message'=>$e->getMessage()

]);

}

?>