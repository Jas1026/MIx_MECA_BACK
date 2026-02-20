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
$total = '';
$cash = '';
$card = '';
$qr = '';
$debt = '';
$tickets = '';
$quantities = '';
$subtotals = '';
$user_cash = '';
if (isset($_POST['table'])) {$table = $_POST['table'];}
if (isset($_POST['total'])) {$total = $_POST['total'];}
if (isset($_POST['cash'])) {$cash = $_POST['cash'];}
if (isset($_POST['card'])) {$card = $_POST['card'];}
if (isset($_POST['qr'])) {$qr = $_POST['qr'];}
if (isset($_POST['debt'])) {$debt = $_POST['debt'];}
if (isset($_POST['tickets'])) {$tickets = explode(',', $_POST['tickets']);}
if (isset($_POST['quantities'])) {$quantities = explode(',', $_POST['quantities']);}
if (isset($_POST['subtotals'])) {$subtotals = explode(',', $_POST['subtotals']);}
if (isset($_POST['user_cash'])) {$user_cash = $_POST['user_cash'];}


try {
	//CREAMOS LA FACTURA
    $create_invoice = $pdo->prepare("INSERT INTO invoice (id_ticket, total, cash, card, qr, debt, id_user_cash, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())");
	$create_invoice->execute([$table, $total, $cash, $card, $qr, $debt, $user_cash]);
	$invoice_id = $pdo->lastInsertId();
    //REPASAMOS EL ESTADO DE CADA ELEMENTO DEL TICKET PARA CAMBIAR SU ESTADO O DIVIDIRLO
    $i = 0;
    foreach ($tickets as $key => $value) {    	
		$invoice_detail = $pdo->prepare("SELECT * from product_ticket WHERE id = ?");
		$invoice_detail->execute([$value]);
		$results_invoice = $invoice_detail->fetchAll(PDO::FETCH_ASSOC);
		//SI SE PAGO LA CANTIDAD COMPLETA DEL ITEM DEL TICKET
		if($quantities[$i] == $results_invoice[0]["quantity"]) {

			//ACTUALIZAMOS ESTADO		 	
			$update_invoice_detail = $pdo->prepare("UPDATE product_ticket SET state = 'pagado' WHERE id = ?");
			$update_invoice_detail->execute([$value]);

			//INSERTAMOS DETALLE DE FACTURA		 	
			$insert_invoice_detail = $pdo->prepare("INSERT INTO invoice_detail (id_invoice, id_product_ticket, quantity, subtotal, created_at) VALUES (?, ?, ?, ?, NOW())");
			$insert_invoice_detail->execute([$invoice_id, $value, $quantities[$i], $subtotals[$i]]);

		} else {

			//ACTUALIZAMOS ESTADO		 	
			$update_invoice_detail = $pdo->prepare("UPDATE product_ticket SET state = 'pagado', quantity = ? WHERE id = ?");
			$update_invoice_detail->execute([$quantities[$i], $value]);

			//INSERTAMOS DETALLE DE FACTURA		 	
			$insert_invoice_detail = $pdo->prepare("INSERT INTO invoice_detail (id_invoice, id_product_ticket, quantity, subtotal, created_at) VALUES (?, ?, ?, ?, NOW())");
			$insert_invoice_detail->execute([$invoice_id, $value, $quantities[$i], $subtotals[$i]]);

			//INSERTAMOS DETALLE DE TICKET DIVIDIDO		 	
			$insert_ticket_detail = $pdo->prepare("INSERT INTO product_ticket (id_product, id_ticket, quantity, notes, accompaniment, modify_price, state, delivered_at, sent_at, cooked_at, created_at) 
				VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
			$insert_ticket_detail->execute([$results_invoice[0]["id_product"], $results_invoice[0]["id_ticket"], ($results_invoice[0]["quantity"] - $quantities[$i]), $results_invoice[0]["notes"], $results_invoice[0]["accompaniment"], $results_invoice[0]["modify_price"], $results_invoice[0]["state"], $results_invoice[0]["delivered_at"], $results_invoice[0]["sent_at"], $results_invoice[0]["cooked_at"], $results_invoice[0]["created_at"]]);
		}
		$i++;
    }

	if (!$create_invoice){
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