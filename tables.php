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
$zone = isset($_POST['zone']) ? (int) $_POST['zone'] : 0;

$zones = [$zone];
$excludeG = false;

if ($zone === 11) {
	$zones = [7, 8, 9, 10];
	$excludeG = true;
}

$placeholders = implode(',', array_fill(0, count($zones), '?'));


try {
	$sql = "
			SELECT 
				t.*, 
				u.name AS user_name,
				(
					SELECT COUNT(pt.id)
					FROM product_ticket pt
					JOIN ticket ti ON pt.id_ticket = ti.id
					WHERE ti.id_table = t.id
					AND ti.active = 1
				) AS espera,
				(
					SELECT COUNT(pt.id)
					FROM product_ticket pt
					JOIN ticket ti ON pt.id_ticket = ti.id
					WHERE ti.id_table = t.id
					AND ti.active = 1
					AND pt.state IN ('entregado','listo')
				) AS listo
			FROM tables t
			LEFT JOIN user u ON t.id_user = u.id
			WHERE t.id_zone IN ($placeholders)
			AND t.active = 1
			AND t.closed = 0
			";
	if ($excludeG) {
		$sql .= " AND t.name NOT LIKE 'G%'";
	}

	$sql .= " ORDER BY t.id ASC";
	
	$statement = $pdo->prepare($sql);
	$statement->execute($zones);

	if (!$statement) {
		$returnArray = array('error' => '1');
		$jsonEncodedReturnArray = json_encode($returnArray, JSON_PRETTY_PRINT);
		echo $jsonEncodedReturnArray;
		die();
	} else {
		$results = $statement->fetchAll(PDO::FETCH_ASSOC);
		echo json_encode($results, JSON_UNESCAPED_UNICODE);
	}
} catch (PDOException $e) {
	print "¡Error!: " . $e->getMessage() . "<br/>";
	die();
}
?>