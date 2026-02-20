<?php
include('dbconnect.php');
if (isset($_SERVER['HTTP_ORIGIN'])) {
    header("Access-Control-Allow-Origin: {$_SERVER['HTTP_ORIGIN']}");
    header('Access-Control-Allow-Credentials: true');
    header('Access-Control-Max-Age: 86400');    // cache for 1 day
}

$max_invoice = 0;

try {
    $statement = $pdo->prepare("SELECT id_product_ticket FROM consolidado ORDER BY id_product_ticket DESC LIMIT 1");
	$statement->execute();		

	if (!$statement){
    	echo 'Error al ejecutar la consulta';
	}else{
	    $results = $statement->fetchAll(PDO::FETCH_ASSOC);
	    if(count($results) > 0) {
	    	$max_invoice = $results[0]["id_product_ticket"];
	    }
	    	$statement2 = $pdo->prepare("SELECT 
	    									u.name as mesero, 
	    									t.name as mesa, 
	    									cat.name as categoria, 
	    									p.name as producto, 
	    									pt.quantity as cantidad, 
	    									(p.price + pt.modify_price) as precio, 
	    									pt.notes as notas, 
	    									pt.accompaniment as acomp, 
	    									pt.id as id_product_ticket, 
	    									ti.id as id_ticket, 
	    									pt.state as estado, 
	    									pt.created_at as creado,
	    									pt.sent_at as enviado,
                                            pt.cooked_at as cocinando,
                                            pt.delivered_at as entregado,
	    									(SELECT us.name FROM user us, user_cash uc, invoice inv WHERE us.id = uc.id_user AND uc.id = inv.id_user_cash AND inv.id_ticket = ti.id LIMIT 1) as cobrador
										FROM product_ticket pt, tables t, user u, product p, category cat, ticket ti 
										WHERE ti.id_table = t.id 
										AND pt.id_ticket = ti.id 
										AND ti.id_user = u.id 
										AND pt.id_product = p.id
										AND p.id_category = cat.id 
										AND pt.id > ?
										ORDER BY cat.id ASC;");
			$statement2->execute([$max_invoice]);
			if ($statement2->rowCount() > 0) {
		    	$results2 = $statement2->fetchAll(PDO::FETCH_ASSOC);

			    foreach($results2 as $row){
					$statement0 = $pdo->prepare("INSERT INTO consolidado(mesero, cobrador, mesa, categoria, producto, cantidad, precio, notas, acomp, estado, id_product_ticket, id_ticket, creado, enviado, cocinado, entregado) 
											VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
					$statement0->execute([$row["mesero"], $row["cobrador"], $row["mesa"], $row["categoria"], $row["producto"], $row["cantidad"], $row["precio"], $row["notas"], $row["acomp"], $row["estado"], $row["id_product_ticket"], $row["id_ticket"], $row["creado"], $row["enviado"], $row["cocinado"], $row["entregado"]]);
				}

			}

	        header("Location: http://192.168.88.204/mecapos/mixtura/posAdmin/index.php/mainpanel/sells");
	}
} catch (PDOException $e) {
    print "¡Error!: " . $e->getMessage() . "<br/>";
    die();
}
?>