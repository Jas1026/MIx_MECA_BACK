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
$code = '';
$user_type = '';
if (isset($_POST['code'])) {$code = $_POST['code'];}
if (isset($_POST['user_type'])) {$user_type = $_POST['user_type'];}


try {
	$login = $pdo->prepare("SELECT * fROM user WHERE code = ? AND state = 1");
	$login->execute([$code]);
	$login_result = $login->fetchAll(PDO::FETCH_ASSOC);
	//VERIFICAR SI EXISTE USUARIO
	if (count($login_result) > 0){
		//SI ESTA LOGGEADO
		//if($login_result[0]["logged"] == 0) {
			//ACTUALIZAMOS SU ESTADO
			$update_user = $pdo->prepare("UPDATE user SET logged = 1, last_loggin = NOW() WHERE id = ?");
			$update_user->execute([$login_result[0]["id"]]);
			$returnArray = array('error' => '0', 'id' => $login_result[0]["id"], 'role' => $login_result[0]["role"], 'name' => $login_result[0]["name"], 'roles' => $login_result[0]["change_roles"]);
		    $jsonEncodedReturnArray = json_encode($returnArray, JSON_PRETTY_PRINT);
		    echo $jsonEncodedReturnArray;
		    die();
			//SI NO ABRIO CAJA LA ABRIMOS O DEVOLVEMOS LA ABIERTA
			$user_cash = $pdo->prepare("SELECT * fROM user_cash WHERE id_user = ? AND state = 1");
			$user_cash->execute([$login_result[0]["id"]]);
			$user_cash_result = $user_cash->fetchAll(PDO::FETCH_ASSOC);
			if (count($user_cash_result) > 0){
				$returnArray = array('error' => '0', 'id' => $login_result[0]["id"], 'cash_id' => $user_cash_result[0]["id"], 'role' => $login_result[0]["role"], 'name' => $login_result[0]["name"], 'roles' => $login_result[0]["change_roles"]);
			    $jsonEncodedReturnArray = json_encode($returnArray, JSON_PRETTY_PRINT);
			    echo $jsonEncodedReturnArray;
			    die();
			} else {
				$statement = $pdo->prepare("INSERT INTO user_cash (id_user, floor, state, open_date, created_at) values (?, ?, 1, NOW(), NOW())");
				$statement->execute([$login_result[0]["id"], $user_type]);
				if (!$statement){
			    	$returnArray = array('error' => '1');
				    $jsonEncodedReturnArray = json_encode($returnArray, JSON_PRETTY_PRINT);
				    echo $jsonEncodedReturnArray;
				    die();
				}else{
					$returnArray = array('error' => '0', 'id' => $login_result[0]["id"], 'cash_id' => $pdo->lastInsertId(), 'role' => $login_result[0]["role"], 'name' => $login_result[0]["name"], 'roles' => $login_result[0]["change_roles"]);
				    $jsonEncodedReturnArray = json_encode($returnArray, JSON_PRETTY_PRINT);
				    echo $jsonEncodedReturnArray;
				    die();
				}
			}


		// } else {
		// 	//NO EXISTE USUARIO
		// 	$returnArray = array('error' => '1', 'message' => 'El usuario ya inició sesión en otro dispositivo.');
		//     $jsonEncodedReturnArray = json_encode($returnArray, JSON_PRETTY_PRINT);
		//     echo $jsonEncodedReturnArray;
		//     die();
		// }

	} else {
		//NO EXISTE USUARIO
		$returnArray = array('error' => '1', 'message' => 'El código no existe.');
	    $jsonEncodedReturnArray = json_encode($returnArray, JSON_PRETTY_PRINT);
	    echo $jsonEncodedReturnArray;
	    die();		
	}

	
    
} catch (PDOException $e) {
    print "¡Error!: " . $e->getMessage() . "<br/>";
    die();
}
?>