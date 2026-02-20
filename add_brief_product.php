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

$name = '';
$price = '';
$kitchen = '';
if (isset($_POST['name'])) {$name = $_POST['name'];}
if (isset($_POST['price'])) {$price = $_POST['price'];}
if (isset($_POST['kitchen'])) {$kitchen = $_POST['kitchen'];}

try {

    $statement = $pdo->prepare("INSERT INTO product (id_category, id_kitchen, name, alias, price, created_at) VALUES (99, ?, ?, ?, ?, NOW())");
	$statement->execute([$kitchen, $name, $name, $price]);
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