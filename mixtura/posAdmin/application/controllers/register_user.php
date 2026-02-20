<?php
include('dbconnect.php');
if (isset($_SERVER['HTTP_ORIGIN'])) {
	header("Access-Control-Allow-Origin: {$_SERVER['HTTP_ORIGIN']}");
	header('Access-Control-Allow-Credentials: true');
    header('Access-Control-Max-Age: 86400');    // cache for 1 day
}
$content_id = '';
$msg = '';
$user_id = '';
$nick = '';

if (isset($_POST['content_id'])) {$content_id = $_POST['content_id'];}
if (isset($_POST['msg'])) {$msg = $_POST['msg'];}
if (isset($_POST['user_id'])) {$user_id = $_POST['user_id'];}
if (isset($_POST['nick'])) {$nick = $_POST['nick'];}

try {
		$statement = $pdo->prepare("INSERT INTO foro (content_id, user_id, nick, msg, active, created_at) values (?, ?, ?, ?, 1, NOW())");
		$statement->execute([$content_id, $user_id, $nick, $msg]);
	

	if (!$statement){
		echo 'Error al ejecutar la consulta';
	}else{
		$returnArray = array('res' => $pdo->lastInsertId());
		$jsonEncodedReturnArray = json_encode($returnArray, JSON_PRETTY_PRINT);
		echo $jsonEncodedReturnArray;
	}
} catch (PDOException $e) {
	print "¡Error!: " . $e->getMessage() . "<br/>";
	die();
}
?>