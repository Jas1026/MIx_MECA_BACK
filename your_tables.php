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
$id_user = '';
if (isset($_POST['id_user'])) {$id_user = $_POST['id_user'];}


try {
		$statement = $pdo->prepare("SELECT t.*
								, (SELECT count(pt.id) FROM product_ticket pt, ticket ti WHERE pt.id_ticket = ti.id AND ti.id_table = t.id AND ti.active = 1) AS espera
								, (SELECT count(pt.id) FROM product_ticket pt, ticket ti WHERE pt.id_ticket = ti.id AND ti.id_table = t.id AND ti.active = 1 AND pt.state = 'listo') AS listo 
								, (SELECT count(pt.id) FROM product_ticket pt, ticket ti WHERE pt.id_ticket = ti.id AND ti.id_table = t.id AND ti.active = 1 AND pt.state IN ('entregado', 'pagado')) AS entregado 
								from tables t 
								where t.id_user = ? AND active = 1 order by id asc;");
		$statement->execute([$id_user]);
    
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