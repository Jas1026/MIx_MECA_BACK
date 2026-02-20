<?php
include('dbconnect.php');
if (isset($_SERVER['HTTP_ORIGIN'])) {
    header("Access-Control-Allow-Origin: {$_SERVER['HTTP_ORIGIN']}");
    header('Access-Control-Allow-Credentials: true');
    header('Access-Control-Max-Age: 86400');    // cache for 1 day
}


try {
	$statement2 = $pdo->prepare("SELECT id, name FROM category;");
	$statement2->execute();
	if ($statement2->rowCount() > 0) {
    	$results2 = $statement2->fetchAll(PDO::FETCH_ASSOC);

	    foreach($results2 as $row){
	    	if($row["name"] !="BRIEF") {	    		
	    		$code = substr($row["name"], 0, 2)+10;
				$statement3 = $pdo->prepare("SELECT id, orden FROM product WHERE id_category = ?;");
				$statement3->execute([$row["id"]]);
    			$results3 = $statement3->fetchAll(PDO::FETCH_ASSOC);
	    		$i = 1;
	    		foreach($results3 as $row2){ 
	    			$newcode = $code . sprintf("%02d", $i);
	    			$statement0 = $pdo->prepare("UPDATE product SET orden = ? WHERE id = ?");
					$statement0->execute([$newcode, $row2["id"]]);
	    			$i++;
	    		}
	    	}
		}

	}
} catch (PDOException $e) {
    print "¡Error!: " . $e->getMessage() . "<br/>";
    die();
}
?>