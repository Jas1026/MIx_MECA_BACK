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
if (isset($_POST['pid'])) {$pid = $_POST['pid'];}


try {    
    $productos = $pdo->prepare("SELECT pt.*, p.name, p.price, k.name as kitchen from product_ticket pt, product p, kitchen k where pt.id = ? AND pt.id_product = p.id AND p.id_kitchen = k.id");
	$productos->execute([$pid]);

	if (!$productos){
    	$returnArray = array('error' => '1');
	    $jsonEncodedReturnArray = json_encode($returnArray, JSON_PRETTY_PRINT);
	    echo $jsonEncodedReturnArray;
	    die();
	}else{
	    $results_productos = $productos->fetchAll(PDO::FETCH_ASSOC);
	    echo json_encode($results_productos, JSON_UNESCAPED_UNICODE);	
	}

} catch (PDOException $e) {
    print "¡Error!: " . $e->getMessage() . "<br/>";
    die();
}
?>