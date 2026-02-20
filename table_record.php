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

    $statement = $pdo->prepare("SELECT u.name as user_name, p.name as product, pt.state, pt.quantity, pt.created_at FROM product_ticket pt, ticket ti, tables t, user u, product p WHERE ti.id_table = t.id AND pt.id_ticket = ti.id AND ti.id_user = u.id AND pt.id_product = p.id AND t.id = ?  ORDER BY pt.created_at DESC limit 30");
	$statement->execute([$table]);
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