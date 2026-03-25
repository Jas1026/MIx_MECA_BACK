<?php
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

    // 🔥 PRODUCTOS
    $stmt = $pdo->prepare("
        SELECT 
            od.detail_id,
            p.nombre_producto AS producto,
            od.quantity,
            od.status,
            od.alert_status,
            o.order_date,
            (
                SELECT GROUP_CONCAT(k.name SEPARATOR ' - ') 
                FROM product_kitchen pk 
                JOIN kitchen k ON pk.kitchen_id = k.id 
                WHERE pk.product_id = p.id_product
            ) as nombres_cocinas
        FROM order_details od
        INNER JOIN products p ON od.product_id = p.id_product
        INNER JOIN orders o ON od.order_id = o.order_id
        WHERE od.order_id = ?
    ");
    $stmt->execute([$order_id]);
    $productos = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // 🔥 PAGOS
    $stmtPagos = $pdo->prepare("
        SELECT 
            id_pago,
            nit_cliente,
            razon_social,
            metodo_pago,
            voucher,
            monto_total,
            items_ids
        FROM pagos_realizados
        WHERE order_id = ?
        ORDER BY id_pago ASC
    ");
    $stmtPagos->execute([$order_id]);
    $pagos = $stmtPagos->fetchAll(PDO::FETCH_ASSOC);

    // 🔥 MAPEO
    $pagosPorItem = [];
    $pagosGenerales = [];

    foreach ($pagos as $p) {

        $items = json_decode($p['items_ids'], true);

        $pagoFormateado = [
            "id_pago" => $p["id_pago"],
            "nit" => $p["nit_cliente"],
            "razonSocial" => $p["razon_social"],
            "metodo_pago" => $p["metodo_pago"],
            "voucher" => $p["voucher"],
            "monto" => (float)$p["monto_total"]
        ];

        // 🔥 SIN ITEMS → GENERAL
        if (!$items || count($items) === 0) {
            $pagosGenerales[] = $pagoFormateado;
            continue;
        }

        // 🔥 CON ITEMS
        foreach ($items as $detail_id => $cantidad) {

            if (!$detail_id) continue;

            if (!isset($pagosPorItem[$detail_id])) {
                $pagosPorItem[$detail_id] = [];
            }

            $pagosPorItem[$detail_id][] = array_merge(
                $pagoFormateado,
                ["cantidad" => $cantidad]
            );
        }
    }

    // 🔥 ASIGNAR A PRODUCTOS
    $resultadoFinal = [];

    foreach ($productos as $prod) {

        $detail_id = $prod['detail_id'];

        $pagosItem = isset($pagosPorItem[$detail_id]) 
            ? $pagosPorItem[$detail_id] 
            : [];

        if (empty($pagosItem) && !empty($pagosGenerales)) {
            $pagosItem = $pagosGenerales;
        }

        $prod['pagos'] = $pagosItem;

        $resultadoFinal[] = $prod;
    }

    echo json_encode([
        "error" => 0,
        "data" => $resultadoFinal
    ]);

} catch (Exception $e) {

    echo json_encode([
        "error" => 1,
        "message" => $e->getMessage()
    ]);
}