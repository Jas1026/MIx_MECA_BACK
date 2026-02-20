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
$table = '';
$id_user = '';
if (isset($_POST['table'])) {$table = $_POST['table'];}
if (isset($_POST['id_user'])) {$id_user = $_POST['id_user'];}


try {

    $tableq = $pdo->prepare("UPDATE tables SET id_user = ? where id = ?");
	$tableq->execute([$id_user, $table]);

    $ticketq = $pdo->prepare("UPDATE ticket SET id_user = ? where id_user = ? AND id_table = ? AND active = 1");
	$ticketq->execute([$id_user, $id_user, $table]);

	if (!$tableq){
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