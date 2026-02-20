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
$category = '';
if (isset($_POST['category'])) {$category = $_POST['category'];}


try {

    $statement = $pdo->prepare("SELECT * from product where id_category = ? AND active = 1 order by name asc");
	$statement->execute([$category]);
	if (!$statement){
    	$returnArray = array('error' => '1');
	    $jsonEncodedReturnArray = json_encode($returnArray, JSON_PRETTY_PRINT);
	    echo $jsonEncodedReturnArray;
	    die();
	}else{
	    $results = $statement->fetchAll(PDO::FETCH_ASSOC);
	    echo json_encode($results, JSON_UNESCAPED_UNICODE);
	}
} catch (PDOException $e) {
    print "¡Error!: " . $e->getMessage() . "<br/>";
    die();
}
?>