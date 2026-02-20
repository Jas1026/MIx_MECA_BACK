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

$id_table = '';
$id_user = '';
$id_cash = '';
$note = '';
$cost = '';
if (isset($_POST['id_table'])) {$id_table = $_POST['id_table'];}
if (isset($_POST['id_user'])) {$id_user = $_POST['id_user'];}
if (isset($_POST['id_cash'])) {$id_cash = $_POST['id_cash'];}
if (isset($_POST['note'])) {$note = $_POST['note'];}
if (isset($_POST['cost'])) {$cost = $_POST['cost'];}

try {

    $statement = $pdo->prepare("INSERT INTO extra (id_table, id_user, id_cash, note, cost, created_at) VALUES (?, ?, ?, ?, ?, NOW())");
	$statement->execute([$id_table, $id_user, $id_cash, $note, $cost]);
	if (!$statement){
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