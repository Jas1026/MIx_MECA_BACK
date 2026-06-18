<?php

header("Access-Control-Allow-Origin: *");

header("Access-Control-Allow-Headers: Content-Type");

header("Access-Control-Allow-Methods: POST, GET, OPTIONS");


if ($_SERVER['REQUEST_METHOD']=='OPTIONS'){

    http_response_code(200);

    exit();

}


include("dbconnect.php");

$id_subcategory=

$_POST['id_subcategory'] ?? 0;


$stmt=

$pdo->prepare("

SELECT

COUNT(*) as total

FROM products

WHERE

id_subcategory=?

AND state='active'

");


$stmt->execute([

$id_subcategory

]);


echo json_encode([

"error"=>0,

"data"=>

$stmt->fetch(PDO::FETCH_ASSOC)

]);

?>