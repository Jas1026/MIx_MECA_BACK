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


try {

    $statement = $pdo->prepare("SELECT b.id, b.message, 'si' as brief from brief b
								UNION ALL
								SELECT p.id, CONCAT(p.name, ' Bs. ', p.price) as message, 'no' as brief from product p where p.id_category = 99 AND p.active = 1;");
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
} catch (PDOException $e) {
    print "¡Error!: " . $e->getMessage() . "<br/>";
    die();
}
?>