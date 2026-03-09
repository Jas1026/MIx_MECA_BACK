<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json");

 

include "dbconnect.php";




$system = $_GET['system'] ?? 'mixtura';
$pdo->exec("USE `$system` "); 

 
$filtro = $data['filtro'] ?? null;
$fecha = $data['fecha'] ?? null;
$fechaInicio = $data['fecha_inicio'] ?? null;
$fechaFin = $data['fecha_fin'] ?? null;




 
$where = "";

 
/* =============================
   FILTRO DE FECHAS
    ==============================*/


date_default_timezone_set('America/La_Paz');

$where = "";
$params = [];

switch ($filtro) {

    case "dia":
        $where = " AND DATE(o.order_date) = CURDATE()";
    break;

    case "mes":
        $where = " AND MONTH(o.order_date) = MONTH(CURDATE())
                   AND YEAR(o.order_date) = YEAR(CURDATE())";
    break;

    case "anio":
        $where = " AND YEAR(o.order_date) = YEAR(CURDATE())";
    break;

    case "fecha":
        if(!empty($fechaFiltro)){
            $where = " AND DATE(o.order_date) = :fechaFiltro";
            $params[':fechaFiltro'] = $fechaFiltro;
        }
    break;

    case "rango":
        if(!empty($fechaInicio) && !empty($fechaFin)){
            $where = " AND DATE(o.order_date) 
                      BETWEEN :inicio AND :fin";

            $params[':inicio'] = $fechaInicio;
            $params[':fin'] = $fechaFin;
        }
    break;
}



try {

$stmtVentas = $pdo->query("
    SELECT 
        COALESCE(SUM(od.quantity * od.unit_price),0) as total_ventas
    FROM orders o
    JOIN order_details od ON o.order_id = od.order_id
    WHERE o.status IN ('closed','paid','completed')
    $where
");

$dataVentas = $stmtVentas->fetch(PDO::FETCH_ASSOC);
$totalVentas = $dataVentas['total_ventas'];
$gananciaTotal = $totalVentas;






    /* =============================
       2️⃣ TOP PRODUCTOS
    ==============================*/
$stmtTop = $pdo->query("
    SELECT p.nombre_producto, SUM(od.quantity) as cantidad 
    FROM orders o
    JOIN order_details od ON o.order_id = od.order_id
    JOIN products p ON od.product_id = p.id_product
    WHERE o.status = 'closed'
    $where
    GROUP BY od.product_id 
    ORDER BY cantidad DESC 
    LIMIT 5
");






    $topProductos = $stmtTop->fetchAll(PDO::FETCH_ASSOC);


    /* =============================
       3️⃣ ALERTAS INVENTARIO
    ==============================*/
    $stmtAlertas = $pdo->query("
        SELECT nombre, stock_act, unidad_med 
        FROM ingredients 
        WHERE stock_act < 10 
        LIMIT 5
    ");
    $alertasStock = $stmtAlertas->fetchAll(PDO::FETCH_ASSOC);


    /* =============================
       4️⃣ ACTIVOS
    ==============================*/
    $stmtAssets = $pdo->query("
        SELECT COUNT(*) as total 
        FROM assets 
        WHERE estado = 'Activo'
    ");
    $activosVivos = $stmtAssets->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;


    /* =============================
       5️⃣ MESA CON MÁS PEDIDOS
    ==============================*/
  $stmtMesaTop = $pdo->query("
    SELECT t.nombre,
           COUNT(o.order_id) as total_pedidos
    FROM orders o
    JOIN cafe_tables t ON o.table_id = t.id_table
    GROUP BY o.table_id
    ORDER BY total_pedidos DESC
    LIMIT 1
");
$mesaTop = $stmtMesaTop->fetch(PDO::FETCH_ASSOC);

/*===================
6️⃣ MESA CON MENOS PEDIDOS
=======================*/

$sqlMesaLow = "
SELECT 
    t.id_table,
    t.nombre,
    COUNT(o.order_id) AS total_pedidos
FROM cafe_tables t
LEFT JOIN orders o 
    ON o.table_id = t.id_table
    $where
GROUP BY t.id_table, t.nombre
ORDER BY total_pedidos ASC
LIMIT 1
";

$stmtMesaLow = $pdo->prepare($sqlMesaLow);
$stmtMesaLow->execute($params);

$mesaLow = $stmtMesaLow->fetch(PDO::FETCH_ASSOC);

if(!$mesaLow){
    $mesaLow = [
        "nombre" => "Sin datos",
        "total_pedidos" => 0
    ];
}


    /* =============================
       6️⃣ MESERO CON MÁS VENTAS
    ==============================*/
  $stmtMeseroTop = $pdo->query("
    SELECT u.name,
           SUM(od.quantity * od.unit_price) as total_ventas
    FROM orders o
    JOIN user u ON o.user_id = u.id
    JOIN order_details od ON od.order_id = o.order_id
    GROUP BY o.user_id
    ORDER BY total_ventas DESC
    LIMIT 1
");
$meseroTop = $stmtMeseroTop->fetch(PDO::FETCH_ASSOC);

/*===================
6 mesero con menos ventas
=======================*/
$stmtMeseros = $pdo->query("
    SELECT u.name,
           SUM(od.quantity * od.unit_price) as total_ventas
    FROM orders o
    JOIN user u ON o.user_id = u.id
    JOIN order_details od ON od.order_id = o.order_id
    WHERE o.status = 'closed'
    GROUP BY o.user_id
    ORDER BY total_ventas DESC
");
$meseros = $stmtMeseros->fetchAll(PDO::FETCH_ASSOC);






    /* =============================
       7️⃣ ÁREAS (FLATS) QUE MÁS GENERAN
    ==============================*/
    $stmtAreas = $pdo->query("
        SELECT f.Name as area,
               SUM(od.quantity * od.unit_price) as total_area
        FROM orders o
        JOIN cafe_tables t ON o.table_id = t.id_table
        JOIN flats f ON t.id_flats = f.Id_flats
        JOIN order_details od ON od.order_id = o.order_id
        GROUP BY f.Id_flats
        ORDER BY total_area DESC
    ");
    $areasTop = $stmtAreas->fetchAll(PDO::FETCH_ASSOC);








/* =============================
   8️⃣ HORAS PICO MEJORADO
=============================*/

$stmtHoras = $pdo->query("
    SELECT 
        HOUR(o.order_date) as hora,
        COUNT(DISTINCT o.order_id) as total_pedidos,
        SUM(od.quantity * od.unit_price) as total_ventas,
        AVG(od.quantity * od.unit_price) as ticket_promedio
    FROM orders o
    JOIN order_details od ON o.order_id = od.order_id
    WHERE o.status = 'closed'
    GROUP BY HOUR(o.order_date)
    ORDER BY total_pedidos DESC
    LIMIT 5
");

$horasPico = $stmtHoras->fetchAll(PDO::FETCH_ASSOC);







/*
está consulta obtiene el ingrediente con stock mínimo, el producto más lento, el producto menos vendido, el producto más vendido, el ingrediente más usado y el ingrediente menos usado. 
*/
$stmtStockMin = $pdo->query("
    SELECT nombre, stock_act, unidad_med
    FROM ingredients
    WHERE stock_act > 0
    ORDER BY stock_act ASC
    LIMIT 1
");
$stockMin = $stmtStockMin->fetch(PDO::FETCH_ASSOC);




/* =============================

PRODUCTO MAS LENTO

============================= */



$sql="SELECT

p.nombre_producto,

ROUND(AVG(od.preparation_time),2) as tiempo_promedio

FROM order_details od

JOIN products p ON od.product_id=p.id_product

WHERE od.preparation_time>0

GROUP BY od.product_id

ORDER BY tiempo_promedio DESC

LIMIT 1";



$productoLento=$pdo->query($sql)->fetch(PDO::FETCH_ASSOC);







/*===================
PRODUCTO MÁS RÁPIDO 
===================*/

$stmtProductoRapido = $pdo->query("
SELECT 
    p.nombre_producto,
    AVG(od.preparation_time) AS tiempo_promedio
FROM order_details od
JOIN products p ON od.product_id = p.id_product
WHERE od.preparation_time IS NOT NULL
GROUP BY od.product_id
ORDER BY tiempo_promedio ASC
LIMIT 1
");

$productoRapido = $stmtProductoRapido->fetch(PDO::FETCH_ASSOC);








/* =============================
   PEDIDO CON MAYOR RETRASO
==============================*/

$stmtPedidoRetraso = $pdo->query("
SELECT 
    p.nombre_producto,
    od.preparation_time,
    od.order_id
FROM order_details od
JOIN products p ON od.product_id = p.id_product
WHERE od.preparation_time IS NOT NULL
ORDER BY od.preparation_time DESC
LIMIT 1
");

$pedidoRetraso = $stmtPedidoRetraso->fetch(PDO::FETCH_ASSOC);









/* =============================
   TIEMPO PROMEDIO DE COCINA
==============================*/

$stmtTiempoCocina = $pdo->query("
SELECT 
    AVG(preparation_time) AS tiempo_promedio_cocina
FROM order_details
WHERE preparation_time IS NOT NULL
");

$tiempoCocina = $stmtTiempoCocina->fetch(PDO::FETCH_ASSOC);

















 /* =============================
   ÓRDENES CERRADAS
=============================*/

$stmtOrdenes = $pdo->query("
    SELECT 
        o.order_id,
        p.nombre_producto,
        od.quantity,
        od.unit_price,
        (od.quantity * od.unit_price) as subtotal
    FROM orders o
    JOIN order_details od ON o.order_id = od.order_id
    JOIN products p ON od.product_id = p.id_product
    WHERE o.status = 'closed'
    $where
");

$ordenes = $stmtOrdenes->fetchAll(PDO::FETCH_ASSOC);



$stmtProductoLow = $pdo->query("
    SELECT p.nombre_producto,
           SUM(od.quantity) as total_vendido
    FROM order_details od
    JOIN products p ON od.product_id = p.id_product
    GROUP BY od.product_id
    ORDER BY total_vendido ASC
    LIMIT 5
");
$productoLow = $stmtProductoLow->fetch(PDO::FETCH_ASSOC);


$stmtIngTop = $pdo->query("
    SELECT i.nombre,
           SUM(od.quantity) as total_usado
    FROM order_details od
    JOIN products p ON od.product_id = p.id_product
    JOIN ingredients i ON i.id_ingredients = p.id_product
    GROUP BY i.id_ingredients
    ORDER BY total_usado DESC
");
$ingredientes = $stmtIngTop->fetchAll(PDO::FETCH_ASSOC);

$ingredienteMasUsado = $ingredientes[0] ?? null;
$ingredienteMenosUsado = end($ingredientes) ?: null;


$stmtProductoTop = $pdo->query("
    SELECT p.nombre_producto,
           SUM(od.quantity) as total_vendido
    FROM order_details od
    JOIN products p ON od.product_id = p.id_product
    GROUP BY od.product_id
    ORDER BY total_vendido DESC
    LIMIT 5
");
$productoTop = $stmtProductoTop->fetch(PDO::FETCH_ASSOC);







$stmtAlcohol = $pdo->query("
    SELECT 
        SUM(CASE 
            WHEN c.name LIKE '%ALCOHOL%' 
            THEN od.quantity * od.unit_price 
            ELSE 0 
        END) as con_alcohol,

        SUM(CASE 
            WHEN c.name NOT LIKE '%ALCOHOL%' 
            THEN od.quantity * od.unit_price 
            ELSE 0 
        END) as sin_alcohol

    FROM orders o
    JOIN order_details od ON o.order_id = od.order_id
    JOIN products p ON od.product_id = p.id_product
    JOIN category c ON p.id_category = c.id
    WHERE o.status = 'closed'
    $where
");
$ventasAlcohol = $stmtAlcohol->fetch(PDO::FETCH_ASSOC);





/* =============================
   PEDIDO CON MAYOR RETRASO
==============================*/

$stmtPedidoMayorRetraso = $pdo->query("
    SELECT 
        o.order_id,
        u.name as mesero,
        o.client_name,
        o.estimated_time,
        o.actual_time,
        (o.actual_time - o.estimated_time) as retraso
    FROM orders o
    JOIN user u ON o.user_id = u.id
    WHERE o.status = 'closed'
    AND o.actual_time IS NOT NULL
    AND o.estimated_time IS NOT NULL
    ORDER BY retraso DESC
    LIMIT 1
");

$pedidoMayorRetraso = $stmtPedidoMayorRetraso->fetch(PDO::FETCH_ASSOC);




/* =============================
   MESERO MÁS LENTO (PROMEDIO)
==============================*/

$stmtMeseroRetraso = $pdo->query("
    SELECT 
        u.name,
        AVG(o.actual_time - o.estimated_time) as promedio_retraso
    FROM orders o
    JOIN user u ON o.user_id = u.id
    WHERE o.status = 'closed'
    AND o.actual_time IS NOT NULL
    AND o.estimated_time IS NOT NULL
    GROUP BY o.user_id
    ORDER BY promedio_retraso DESC
    LIMIT 5
");

$meserosRetraso = $stmtMeseroRetraso->fetchAll(PDO::FETCH_ASSOC);







$mesas = [];

if($areaSeleccionada){

    $stmtMesas = $pdo->prepare("
        SELECT 
            t.nombre as mesa,
            COUNT(o.order_id) as total_pedidos,
            SUM(od.quantity * od.unit_price) as total_ventas
        FROM cafe_tables t
        LEFT JOIN orders o ON o.table_id = t.id_table AND o.status = 'closed'
        LEFT JOIN order_details od ON od.order_id = o.order_id
        JOIN flats f ON t.id_flats = f.Id_flats
        WHERE f.Name = :area
        GROUP BY t.id_table
        ORDER BY total_ventas DESC
    ");

    $stmtMesas->execute(['area' => $areaSeleccionada]);
    $mesas = $stmtMesas->fetchAll(PDO::FETCH_ASSOC);
}





/* =============================
   CATEGORÍA MÁS RENTABLE
==============================*/

$stmtCategoriaTop = $pdo->query("
    SELECT c.name,
           SUM(od.quantity * od.unit_price) as total_ganancia
    FROM orders o
    JOIN order_details od ON o.order_id = od.order_id
    JOIN products p ON od.product_id = p.id_product
    JOIN category c ON p.id_category = c.id
    WHERE o.status = 'closed'
    $where
    GROUP BY c.id
    ORDER BY total_ganancia DESC
    LIMIT 1
");

$categoriaTop = $stmtCategoriaTop->fetch(PDO::FETCH_ASSOC);


/* =============================
   RESPUESTA FINAL
=============================*/

echo json_encode([
    "error" => 0,
    "resumen" => [

        /* ===============================
        RESUMEN GENERAL
        =============================== */

        "total_dinero" => round($totalVentas ?? 0, 2),
        "ganancia_total" => round($totalVentas ?? 0, 2),
        "activos_conteo" => $activosVivos ?? 0,


        /* ===============================
        PRODUCTOS
        =============================== */

        "top_productos" => $topProductos ?? [],
        "producto_mas_usado" => $productoTop ?? [
            "nombre_producto" => "Sin datos",
            "total_vendido" => 0
        ],

        "producto_menos_usado" => $productoLow ?? [
            "nombre_producto" => "Sin datos",
            "total_vendido" => 0
        ],

        "producto_mas_lento" => $productoLento ?? [
            "nombre_producto" => "Sin datos",
            "tiempo_promedio" => 0
        ],


        /* ===============================
        INVENTARIO
        =============================== */

        "alertas_inventario" => $alertasStock ?? [],
        "stock_minimo" => $stockMin ?? [],

        "ingredientes_top" => $ingredientes ?? [],

        "ingrediente_mas_usado" => $ingredienteMasUsado ?? [
            "nombre" => "Sin datos",
            "cantidad_usada" => 0
        ],

        "ingrediente_menos_usado" => $ingredienteMenosUsado ?? [
            "nombre" => "Sin datos",
            "cantidad_usada" => 0
        ],


        /* ===============================
        MESAS
        =============================== */

        "mesa_top" => $mesaTop ?? [
            "nombre" => "Sin datos",
            "total_pedidos" => 0
        ],

        "mesa_low" => $mesaLow ?? [
            "nombre" => "Sin datos",
            "total_pedidos" => 0
        ],


        /* ===============================
        MESEROS
        =============================== */

        "mesero_top" => $meseroTop ?? [
            "nombre" => "Sin datos",
            "total_ventas" => 0
        ],

        "meseros" => $meseros ?? [],

        "meseros_retraso" => $meserosRetraso ?? [],


        /* ===============================
        ÁREAS
        =============================== */

        "areas_top" => $areasTop ?? [],


        /* ===============================
        HORAS PICO
        =============================== */

        "horas_pico" => $horasPico ?? [],


        /* ===============================
        PEDIDOS CON RETRASO
        =============================== */

        "pedido_mayor_retraso" => $pedidoMayorRetraso ?? [
            "nombre_producto" => "Sin datos",
            "preparation_time" => 0,
            "order_id" => null
        ],


        /* ===============================
        CATEGORÍA MÁS RENTABLE
        =============================== */

        "categoria_top" => $categoriaTop ?? [
            "name" => "Sin datos",
            "total_ganancia" => 0
        ],


        /* ===============================
        VENTAS ALCOHOL / NO ALCOHOL
        =============================== */

        "ventas_alcohol" => [
            "con" => $ventasAlcohol['con_alcohol'] ?? 0,
            "sin" => $ventasAlcohol['sin_alcohol'] ?? 0
        ],


        /* ===============================
        LISTA DE ÓRDENES
        =============================== */

        "ordenes" => $ordenes ?? []

    ]
]);



 
} catch (PDOException $e) {
    echo json_encode([
        "error" => 1,
        "message" => $e->getMessage()
    ]);
}