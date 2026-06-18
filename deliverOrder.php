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

$order_id=$request['order_id'];

$system=$request['system'];

try{

$stmt=$pdo->prepare("

UPDATE

".$system.".order_details

SET

process_status='delivered'

WHERE

order_id=?

");

$stmt->execute([
$order_id
]);

echo json_encode([

'error'=>0

]);

}
catch(Exception $e){

echo json_encode([

'error'=>1,

'message'=>$e->getMessage()

]);

}