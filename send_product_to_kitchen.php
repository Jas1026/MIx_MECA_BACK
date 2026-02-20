<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
include('dbconnect.php');
if (isset($_SERVER['HTTP_ORIGIN'])) {
    header("Access-Control-Allow-Origin: {$_SERVER['HTTP_ORIGIN']}");
    header('Access-Control-Allow-Credentials: true');
    header('Access-Control-Max-Age: 86400');    // cache for 1 day
}
$pid = '';
$table = '';
$people = '';
if (isset($_POST['pid'])) {$pid = $_POST['pid'];}
if (isset($_POST['table'])) {$table = $_POST['table'];}
if (isset($_POST['people'])) {$people = $_POST['people'];}


try {

    $productos = $pdo->prepare("UPDATE product_ticket SET state = 'espera', sent_at = NOW() where id_ticket = ? AND state = 'sin enviar'");
	$productos->execute([$pid]);

    $tableq = $pdo->prepare("UPDATE tables SET state = 'con pedido', persons = ? where id = ?");
	$tableq->execute([$people, $table]);

	if (!$productos){
    	$returnArray = array('error' => '1');
	    $jsonEncodedReturnArray = json_encode($returnArray, JSON_PRETTY_PRINT);
	    echo $jsonEncodedReturnArray;
	    die();
	}else{
    	$returnArray = array('error' => '0');
	    $jsonEncodedReturnArray = json_encode($returnArray, JSON_PRETTY_PRINT);
	    echo $jsonEncodedReturnArray;
	    die();	
	}

} catch (PDOException $e) {
    print "¡Error!: " . $e->getMessage() . "<br/>";
    die();
}
?>