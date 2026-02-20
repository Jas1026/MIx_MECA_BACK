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
$state = '';
if (isset($_POST['pid'])) {$pid = $_POST['pid'];}
if (isset($_POST['state'])) {$state = $_POST['state'];}


try {
	if($state == 'listo') {
    	$productos = $pdo->prepare("UPDATE product_ticket SET state = ?, cooked_at = NOW() where id = ?");
		$productos->execute([$state, $pid]);
	} elseif ($state == 'entregado') {
    	$productos = $pdo->prepare("UPDATE product_ticket SET state = ?, delivered_at = NOW() where id = ?");
		$productos->execute([$state, $pid]);
	}
	else {
    	$productos = $pdo->prepare("UPDATE product_ticket SET state = ? where id = ?");
		$productos->execute([$state, $pid]);		
	}


	//Verificar si ya entreg todos
	if ($state == "entregado") {

    	$entregado = $pdo->prepare("SELECT t.id, t.id_table FROM ticket t  where t.id = (SELECT pt.id_ticket FROM product_ticket pt WHERE pt.id = ?)");
		$entregado->execute([$pid]);
		$results_entregado = $entregado->fetchAll(PDO::FETCH_ASSOC);

    	$entregados = $pdo->prepare("SELECT id FROM product_ticket where state IN ('sin enviar', 'espera', 'listo') and id_ticket = ?");
		$entregados->execute([$results_entregado[0]["id"]]);
		$results_entregados = $entregados->fetchAll(PDO::FETCH_ASSOC);
		if (count($results_entregados) == 0) {
	    	$mesa = $pdo->prepare("UPDATE tables SET state = 'entregado' where id = ?");
			$mesa->execute([$results_entregado[0]["id_table"]]);			
		}
	}
	

	if (!$productos){
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