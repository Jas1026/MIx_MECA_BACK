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
$old_table = '';
if (isset($_POST['table'])) {$table = $_POST['table'];}
if (isset($_POST['old_table'])) {$old_table = $_POST['old_table'];}


try {
	//old_table data
    $tables = $pdo->prepare("SELECT * FROM tables where id = ?");
	$tables->execute([$old_table]);
	$oldTable = $tables->fetchAll(PDO::FETCH_ASSOC);

	//update old_table
    $tables = $pdo->prepare("UPDATE tables SET state = 'vacia', id_user = 0, timer = NULL where id = ?");
	$tables->execute([$old_table]);

	//update new_table
    $ntables = $pdo->prepare("UPDATE tables SET state = ?, timer = ?, id_user = ? where id = ?");
	$ntables->execute([$oldTable[0]["state"], $oldTable[0]["timer"], $oldTable[0]["id_user"], $table]); 

	//update ticket
    $ntables = $pdo->prepare("UPDATE ticket SET id_table = ? where id_table = ? AND active = 1");
	$ntables->execute([$table, $old_table]); 
	

	if (!$tables){
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