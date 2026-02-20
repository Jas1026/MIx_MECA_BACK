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
$user_cash = '';
if (isset($_POST['user_cash'])) {$user_cash = $_POST['user_cash'];}


try {
	$statement = $pdo->prepare("SELECT SUM(total) as sum_total, SUM(cash) as sum_cash, SUM(card) as sum_card, SUM(qr) as sum_qr, SUM(debt) as sum_debt fROM invoice WHERE id_user_cash = ?");
	$statement->execute([$user_cash]);
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