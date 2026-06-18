<?php

header("Access-Control-Allow-Origin: *");

header("Access-Control-Allow-Headers: Content-Type");

header("Access-Control-Allow-Methods: GET, POST, OPTIONS");

header("Content-Type: application/json");

if($_SERVER['REQUEST_METHOD']=='OPTIONS'){

    exit();

}

require_once 'dbconnect.php';

$postjson=file_get_contents("php://input");

$request=json_decode($postjson,true);


if(!$request){

    echo json_encode([

        'error'=>1,

        'message'=>'No llegaron datos'

    ]);

    exit;

}


$system=$request['system'] ?? '';

$detail_id=$request['detail_id'] ?? 0;

$process_status=$request['process_status'] ?? '';



try{


$stmt=$pdo->prepare("

UPDATE

".$system.".order_details

SET

process_status=?

WHERE detail_id=?

");


$stmt->execute([

$process_status,

$detail_id

]);


echo json_encode([

    'error'=>0,

    'message'=>'Actualizado',

    'detail_id'=>$detail_id,

    'process_status'=>$process_status

]);


}

catch(Exception $e){

echo json_encode([

    'error'=>1,

    'message'=>$e->getMessage()

]);

}