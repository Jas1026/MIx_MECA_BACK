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
$product_id = '';
$quantity = '';
$notes = '';
$accompaniment = '';
$price = '';
$id_user = '';
if (isset($_POST['table'])) {$table = $_POST['table'];}
if (isset($_POST['product_id'])) {$product_id = $_POST['product_id'];}
if (isset($_POST['quantity'])) {$quantity = $_POST['quantity'];}
if (isset($_POST['notes'])) {$notes = $_POST['notes'];}
if (isset($_POST['accompaniment'])) {$accompaniment = $_POST['accompaniment'];}
if (isset($_POST['price'])) {$price = $_POST['price'];}
if (isset($_POST['id_user'])) {$id_user = $_POST['id_user'];}


try {

    $select_ticket = $pdo->prepare("SELECT id from ticket where id_table = ? AND active = 1");
	$select_ticket->execute([$table]);
	$results = $select_ticket->fetchAll(PDO::FETCH_ASSOC);
    //SI YA EXISTE TICKET, AGREGAMOS LOS PRODUCTOS AL EXISTENTE. SINO CREAMOS UNO NUEVO
    if (count($results) > 0) { 
    	$ticket_id = $results[0]["id"];
    } else {
		$insert_ticket = $pdo->prepare("INSERT INTO ticket (id_table, id_user, created_at) VALUES (?, ?, NOW())");
		$insert_ticket->execute([$table, $id_user]);
		$ticket_id = $pdo->lastInsertId();
    }

    //ACTUALIZAMOS MESA
	$update_table = $pdo->prepare("UPDATE tables SET state = 'ocupada', id_user = ?, timer = NOW() WHERE id = ?");
	$update_table->execute([ $id_user, $table]);

	//REGISTRAMOS LOS PRODUCTOS
	$insert_product = $pdo->prepare("INSERT INTO product_ticket (id_product, id_ticket, quantity, notes, accompaniment, modify_price) VALUES (?, ?, ?, ?, ?, ?)");
	$insert_product->execute([$product_id, $ticket_id, $quantity, $notes, $accompaniment, $price]);

	if (!$insert_product){
    	$returnArray = array('error' => '1');
	    $jsonEncodedReturnArray = json_encode($returnArray, JSON_PRETTY_PRINT);
	    echo $jsonEncodedReturnArray;
	    die();
	}else{
    	$returnArray = array('error' => '0');
	    $jsonEncodedReturnArray = json_encode($returnArray, JSON_PRETTY_PRINT);
	    echo $jsonEncodedReturnArray;
	    die();
	}
} catch (PDOException $e) {
    print "¡Error!: " . $e->getMessage() . "<br/>";
    die();
}
?>