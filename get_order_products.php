<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json");

require_once 'dbconnect.php';

$order_id = $_POST['order_id'] ?? $_GET['order_id'] ?? null;

if (!$order_id) {

    echo json_encode([
        "error" => 1,
        "message" => "Falta order_id"
    ]);

    exit;
}

try {

    //=================================================
    // ORDEN + MESA + PISO + MESERO CREADOR
    //=================================================

    $stmtOrden = $pdo->prepare("

        SELECT

        o.*,

        u.name as mesero_creador,

        t.nombre as mesa,

        f.Name as piso

        FROM orders o

        LEFT JOIN user u

        ON o.user_id=u.id

        LEFT JOIN cafe_tables t

        ON o.table_id=t.id_table

        LEFT JOIN flats f

        ON t.id_flats=f.Id_flats

        WHERE o.order_id=?

    ");

    $stmtOrden->execute([$order_id]);

    $orden = $stmtOrden->fetch(PDO::FETCH_ASSOC);



    //=================================================
    // HISTORIAL
    //=================================================

    $stmtHistorial=$pdo->prepare("

        SELECT

        h.*,

        u.name

        FROM historial_mesa h

        LEFT JOIN user u

        ON h.user_id=u.id

        WHERE h.order_id=?

        ORDER BY h.created_at ASC

    ");

    $stmtHistorial->execute([$order_id]);

    $historial=$stmtHistorial->fetchAll(PDO::FETCH_ASSOC);



    $meseroCobro=null;

    $meseroCerro=null;



    foreach($historial as $h){

        if(

            $h['accion']=='pago_parcial'

            ||

            $h['accion']=='cobro'

        ){

            $meseroCobro=$h['name'];

        }


        if(

            $h['accion']=='cerrar'

        ){

            $meseroCerro=$h['name'];

        }

    }



    //=================================================
    // PRODUCTOS
    //=================================================

    $stmt = $pdo->prepare("

        SELECT

            od.detail_id,

            od.quantity,

            od.unit_price,

            od.total_price,

            od.preparation_time,

            od.status,

            od.alert_status,

            od.notes,

            od.sides,

            od.estado_pago,

            p.nombre_producto AS producto,

            o.order_date,

            (

                SELECT GROUP_CONCAT(k.name SEPARATOR ' - ')

                FROM product_kitchen pk

                JOIN kitchen k

                ON pk.kitchen_id = k.id

                WHERE pk.product_id = p.id_product

            ) as nombres_cocinas

        FROM order_details od

        INNER JOIN products p

        ON od.product_id = p.id_product

        INNER JOIN orders o

        ON od.order_id = o.order_id

        WHERE od.order_id = ?

    ");

    $stmt->execute([$order_id]);

    $productos = $stmt->fetchAll(PDO::FETCH_ASSOC);



    //=================================================
    // PAGOS
    //=================================================

    $stmtPagos = $pdo->prepare("

        SELECT

        p.*,

        u.name as cajero

        FROM pagos_realizados p

        LEFT JOIN user u

        ON p.user_id=u.id

        WHERE p.order_id=?

        ORDER BY p.fecha_pago ASC

    ");

    $stmtPagos->execute([$order_id]);

    $pagos = $stmtPagos->fetchAll(PDO::FETCH_ASSOC);



    //=================================================
    // MAPEO PAGOS
    //=================================================

    $pagosPorItem = [];

    $pagosGenerales = [];



    foreach ($pagos as $p) {

        $items = json_decode($p['items_ids'], true);

        $pagoFormateado = [

            "id_pago"=>$p["id_pago"],

            "nit"=>$p["nit_cliente"],

            "razonSocial"=>$p["razon_social"],

            "metodo_pago"=>$p["metodo_pago"],

            "voucher"=>$p["voucher"],

            "fecha_pago"=>$p["fecha_pago"],

            "cuf"=>$p["cuf"],

            "tipo_pago"=>$p["tipo_pago"],

            "cajero"=>$p["cajero"],

            "monto"=>(float)$p["monto_total"]

        ];


        if (!$items || count($items) === 0) {

            $pagosGenerales[] = $pagoFormateado;

            continue;
        }


        foreach ($items as $detail_id => $cantidad) {

            if (!$detail_id) continue;


            if (!isset($pagosPorItem[$detail_id])) {

                $pagosPorItem[$detail_id] = [];

            }


            $pagosPorItem[$detail_id][] = array_merge(

                $pagoFormateado,

                [

                    "cantidad"=>$cantidad

                ]

            );

        }

    }



    //=================================================
    // PRODUCTOS + EXTRAS + PAGOS
    //=================================================

    $resultadoFinal = [];



    foreach ($productos as $prod) {


        $detail_id = $prod['detail_id'];



        // EXTRAS

        $stmtExtra=$pdo->prepare("

            SELECT

            tda.*,

            i.nombre,

            i.unidad_med

            FROM order_detail_adjustments tda

            INNER JOIN ingredients i

            ON tda.ingredient_id=i.id_ingredients

            WHERE tda.detail_id=?

        ");


        $stmtExtra->execute([

            $detail_id

        ]);


        $extras=

        $stmtExtra->fetchAll(PDO::FETCH_ASSOC);


        $prod['extras']=$extras;



        // PAGOS

        $pagosItem = isset($pagosPorItem[$detail_id])

            ? $pagosPorItem[$detail_id]

            : [];


        if (

            empty($pagosItem)

            &&

            !empty($pagosGenerales)

        ) {

            $pagosItem = $pagosGenerales;

        }


        $prod['pagos'] = $pagosItem;


        $resultadoFinal[] = $prod;

    }



    //=================================================
    // RESPUESTA FINAL
    //=================================================

    echo json_encode([

        "error"=>0,

        "orden"=>$orden,

        "mesero_creador"=>$orden['mesero_creador'],

        "mesero_cobro"=>$meseroCobro,

        "mesero_cerro"=>$meseroCerro,

        "productos"=>$resultadoFinal,

        "pagos"=>$pagos,

        "historial"=>$historial

    ]);



} catch (Exception $e) {


    echo json_encode([

        "error" => 1,

        "message" => $e->getMessage()

    ]);

}