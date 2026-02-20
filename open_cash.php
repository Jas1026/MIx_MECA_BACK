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
$user_name = '';
$user_type = '';
if (isset($_POST['user_name'])) {$user_name = $_POST['user_name'];}
if (isset($_POST['user_type'])) {$user_type = $_POST['user_type'];}


try {
	$user_cash = $pdo->prepare("SELECT * fROM user_cash WHERE id_user = ? AND state = 1");
	$user_cash->execute([$user_name]);
	$user_cash_result = $user_cash->fetchAll(PDO::FETCH_ASSOC);
	if (count($user_cash_result) > 0){
		$returnArray = array('error' => '0', 'cash_id' => $user_cash_result[0]["id"]);
	    $jsonEncodedReturnArray = json_encode($returnArray, JSON_PRETTY_PRINT);
	    echo $jsonEncodedReturnArray;
	    die();
	} else {
		$statement = $pdo->prepare("INSERT INTO user_cash (id_user, floor, state, open_date, created_at) values (?, ?, 1, NOW(), NOW())");
		$statement->execute([$user_name, $user_type]);
		if (!$statement){
	    	$returnArray = array('error' => '1');
		    $jsonEncodedReturnArray = json_encode($returnArray, JSON_PRETTY_PRINT);
		    echo $jsonEncodedReturnArray;
		    die();
		}else{
	    	$returnArray = array('error' => '0', 'cash_id' => $pdo->lastInsertId());
		    $jsonEncodedReturnArray = json_encode($returnArray, JSON_PRETTY_PRINT);
		    echo $jsonEncodedReturnArray;
		    die();
		}
	}
    
} catch (PDOException $e) {
    print "¡Error!: " . $e->getMessage() . "<br/>";
    die();
}
?>