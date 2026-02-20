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

$result = $pdo->prepare("SELECT z.restaurant_id from zone z, tables t WHERE t.id_zone = z.id AND t.id = ?");
$result->execute([$table]);
$restaurant_id = $result->fetchColumn();

try {

    $statement = $pdo->prepare("SELECT * from category where active = 1 AND restaurant_id = ? ORDER BY name ASC");
	$statement->execute([$restaurant_id]);
	if (!$statement){
    	$returnArray = array('error' => '1');
	    $jsonEncodedReturnArray = json_encode($returnArray, JSON_PRETTY_PRINT);
	    echo $jsonEncodedReturnArray;
	    die();
	}else{
	    $results = $statement->fetchAll(PDO::FETCH_ASSOC);
	$i = 0;
		foreach ($results as $key => $value) { 
			$products = $pdo->prepare("SELECT * from product where id_category = ? AND active = 1 order by name asc");
			$products->execute([$value["id"]]);
	    	$products_results = $products->fetchAll(PDO::FETCH_ASSOC);
			$results[$i]["products"] = $products_results;
			$i++;
		}
	    echo json_encode($results, JSON_UNESCAPED_UNICODE);
	}
} catch (PDOException $e) {
    print "¡Error!: " . $e->getMessage() . "<br/>";
    die();
}
?>