<?php
include('dbconnect.php');
if (isset($_SERVER['HTTP_ORIGIN'])) {
    header("Access-Control-Allow-Origin: {$_SERVER['HTTP_ORIGIN']}");
    header('Access-Control-Allow-Credentials: true');
    header('Access-Control-Max-Age: 86400');    // cache for 1 day
}

try {
    //borrar todo en la base de datos
    $ventas_producto = $pdo->prepare("DELETE FROM ventas_producto");
    $ventas_producto->execute();

    $user_cash = $pdo->prepare("SELECT DISTINCT uc.id_user FROM user_cash uc WHERE uc.open_date >= DATE_SUB(NOW(), INTERVAL 1 DAY)");
	$user_cash->execute();		

	if (!$user_cash){
    	echo 'Error al ejecutar la consulta';
	}else{
	    $user_cash_results = $user_cash->fetchAll(PDO::FETCH_ASSOC);
        if (count($user_cash_results) > 0) {
            $all_products = $pdo->prepare("SELECT p.* FROM product p ORDER BY p.orden ASC");
            $all_products->execute();
            $all_products_results = $all_products->fetchAll(PDO::FETCH_ASSOC);
            //ITERAMOS A LOS MESEROS QUE TRABAJARON EN EL DIA
            foreach ($user_cash_results as $key => $value) {
                $user_id = $value['id_user'];
                //RECUPERAMOS LA SUMATORIA DE LOS PRODUCTOS VENDIDOS POR EL MESERO
                $sells_by_product = $pdo->prepare("SELECT p.id, p.name, IFNULL(SUM(pt.quantity), 0) as cantidad, CONCAT(pt.accompaniment) as acomp FROM ticket t, product p 
                                                    INNER JOIN product_ticket pt ON p.id = pt.id_product AND pt.created_at >= DATE_SUB(NOW(), INTERVAL 1 DAY)
                                                    WHERE pt.id_ticket = t.id AND t.id_user = ? GROUP BY p.id;");
                $sells_by_product->execute([$user_id]);
                $sells_by_product_results = $sells_by_product->fetchAll(PDO::FETCH_ASSOC);

                //ITERAMOS LOS PRODUCTOS
                foreach ($all_products_results as $key2 => $value2) {
                    $product_id = $value2['id'];
                    $product_orden = $value2['orden'];
                    $product_category = $value2['id_category'];
                    $product_seller = $user_id;
                    $product_quantity = 0;
                    $product_acomp = '';
                    //buscamos si el producto ya fue vendido por el mesero
                    foreach ($sells_by_product_results as $key3 => $value3) {
                        if ($product_id == $value3['id']) {
                            $product_quantity = $value3['cantidad'];
                            $product_acomp = $value3['acomp'];
                            break;
                        }
                    }                    
                    $insert_sells_by_product = $pdo->prepare("INSERT INTO ventas_producto (id_producto, orden, id_categoria, id_mesero, acomp, cantidad) VALUES (?, ?, ?, ?, ?, ?)");
                    $insert_sells_by_product->execute([$product_id, $product_orden, $product_category, $product_seller, $product_acomp, $product_quantity]);
                }
            }

        } else {
            header("Location: http://192.168.88.204/mecapos/mixtura/posAdmin/index.php/mainpanel/sells_by_productt");            
        }
        
	    

	    header("Location: http://192.168.88.204/mecapos/mixtura/posAdmin/index.php/mainpanel/sells_by_productt");
	}
} catch (PDOException $e) {
    print "¡Error!: " . $e->getMessage() . "<br/>";
    die();
}
?>