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

$id_brief = '';
if (isset($_POST['id'])) {$id_brief = $_POST['id'];}

try {

    $statement = $pdo->prepare("UPDATE product SET active = 0 WHERE id = ?");
	$statement->execute([$id_brief]);
	if (!$statement){
    	$returnArray = array('error' => '1');
	    $jsonEncodedReturnArray = json_encode($returnArray, JSON_PRETTY_PRINT);
	    echo $jsonEncodedReturnArray;
	    die();
	}else{
    	$statement = $pdo->prepare("SELECT * from brief where created_at WHERE >= DATE_SUB(NOW(), INTERVAL 1 DAY)");
		$statement->execute();
		if (!$statement){
	    	$returnArray = array('error' => '1');
		    $jsonEncodedReturnArray = json_encode($returnArray, JSON_PRETTY_PRINT);
		    echo $jsonEncodedReturnArray;
		    die();
		}else{
		    $results = $statement->fetchAll(PDO::FETCH_ASSOC);
		    echo json_encode($results, JSON_UNESCAPED_UNICODE);
		}
	}
} catch (PDOException $e) {
    print "¡Error!: " . $e->getMessage() . "<br/>";
    die();
}
?>