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
$kitchen = isset($_POST['kitchen']) ? (int)$_POST['kitchen'] : 0;

$kitchens = [$kitchen];

if ($kitchen === 3) {
    $kitchens = [3, 4];
}

$placeholders = implode(',', array_fill(0, count($kitchens), '?'));

try {

    $statement = $pdo->prepare("SELECT t.*, m.name as tableName, z.restaurant_id as zone, (SELECT pt.created_at FROM product_ticket pt, product p WHERE p.id = pt.id_product AND pt.id_ticket = t.id AND p.id_kitchen IN ($placeholders) AND (pt.state = 'espera' OR pt.state = 'preparando' OR pt.state = 'listo') ORDER BY pt.created_at DESC limit 1) as last_update from ticket t, tables m, zone z where t.active = 1 AND t.id_table = m.id AND z.id = m.id_zone ORDER BY last_update DESC;");
	$statement->execute($kitchens);
	$results = $statement->fetchAll(PDO::FETCH_ASSOC);
	$i = 0;
	foreach ($results as $key => $value) {
		$productos = $pdo->prepare("SELECT pt.*, p.name, p.alias, 0 AS extra from product_ticket pt, product p where pt.id_ticket = ? AND pt.id_product = p.id AND p.id_kitchen IN ($placeholders) AND (pt.state = 'espera' OR pt.state = 'preparando' OR pt.state = 'listo') ORDER BY pt.created_at DESC");
		$productos->execute([$value["id"], $kitchen]);
		$results_productos = $productos->fetchAll(PDO::FETCH_ASSOC);
		if ($value["zone"] == 2) {
			$results[$i]["tableName"] = "Meca ".$results[$i]["tableName"];
		}
		if (count($results_productos) > 0) {
			$results[$i]["products"] = $results_productos;
			$results[$i]["pstate"] = "si";
		} else {
			$results[$i]["products"] = 0;
			$results[$i]["pstate"] = "no";			
		}
		$extra_productos = $pdo->prepare("SELECT pt.*, p.name, p.alias, 1 AS extra from product_ticket pt, product p where pt.id_ticket = ? AND pt.id_product = p.id AND p.id_kitchen <> ? AND (pt.state = 'espera' OR pt.state = 'preparando' OR pt.state = 'listo')");
		$extra_productos->execute([$value["id"], $kitchen]);
		$results_eproductos = $extra_productos->fetchAll(PDO::FETCH_ASSOC);
		if (count($results_eproductos) > 0 && $kitchen == 1) {
			$results[$i]["extra_products"] = $results_eproductos;
			$results[$i]["epstate"] = "si";
		} else {
			$results[$i]["extra_products"] = 0;
			$results[$i]["epstate"] = "no";			
		}
		$i++;
	}


	if (!$statement){
    	$returnArray = array('error' => '1');
	    $jsonEncodedReturnArray = json_encode($returnArray, JSON_PRETTY_PRINT);
	    echo $jsonEncodedReturnArray;
	    die();
	}else{
	    echo json_encode($results, JSON_UNESCAPED_UNICODE);
	}
} catch (PDOException $e) {
    print "¡Error!: " . $e->getMessage() . "<br/>";
    die();
}
?>