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
if (isset($_POST['table'])) {$table = $_POST['table'];}


try {

    $statement = $pdo->prepare("SELECT * from tables where id = ?");
	$statement->execute([$table]);
	if (!$statement){
    	$returnArray = array('error' => '1');
	    $jsonEncodedReturnArray = json_encode($returnArray, JSON_PRETTY_PRINT);
	    echo $jsonEncodedReturnArray;
	    die();
	}else{
	    $results = $statement->fetchAll(PDO::FETCH_ASSOC);
	    $ticket = $pdo->prepare("SELECT * from ticket where id_table = ? AND active = 1");
		$ticket->execute([$table]);
	    $results_ticket = $ticket->fetchAll(PDO::FETCH_ASSOC);
	    if (count($results_ticket) > 0) {
	    	$results[0]["ticket"] = $results_ticket;
	    	$productos = $pdo->prepare("SELECT pt.*, p.name, p.price, k.name as kitchen from product_ticket pt, product p, kitchen k where id_ticket = ? AND pt.id_product = p.id AND p.id_kitchen = k.id ORDER BY pt.state ASC, pt.id ASC");
			$productos->execute([$results_ticket[0]["id"]]);
		    $results_productos = $productos->fetchAll(PDO::FETCH_ASSOC);
	    	$results[0]["products"] = $results_productos;
	    } else {
	    	$results[0]["ticket"] = 0;
	    	$results[0]["products"] = 0;
	    }
	    echo json_encode($results, JSON_UNESCAPED_UNICODE);
	}
} catch (PDOException $e) {
    print "¡Error!: " . $e->getMessage() . "<br/>";
    die();
}
?>