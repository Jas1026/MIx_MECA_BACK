<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json");

include "dbconnect.php";

$system = $_GET['system'] ?? 'mixtura';
$area   = $_GET['area'] ?? null;

if(!$area){
    echo json_encode(["error" => 1, "message" => "Área requerida"]);
    exit;
}

$pdo->exec("USE `$system`");

try {

    /* ==========================================
       1️⃣ OBTENER MESAS DEL ÁREA
    ==========================================*/

    $stmtMesas = $pdo->prepare("
        SELECT 
            t.id_table,
            t.nombre as mesa,
            COUNT(DISTINCT o.order_id) as total_pedidos,
            IFNULL(SUM(od.quantity * od.unit_price),0) as total_ventas
        FROM cafe_tables t
        JOIN flats f ON t.id_flats = f.Id_flats
        LEFT JOIN orders o 
            ON o.table_id = t.id_table 
            AND o.status = 'closed'
        LEFT JOIN order_details od 
            ON od.order_id = o.order_id
        WHERE f.Name = :area
        GROUP BY t.id_table
        ORDER BY total_ventas DESC
    ");

    $stmtMesas->execute(["area" => $area]);
    $mesasRaw = $stmtMesas->fetchAll(PDO::FETCH_ASSOC);

    $mesas = [];

    foreach($mesasRaw as $mesa){

        /* ==========================================
           2️⃣ OBTENER PEDIDOS DE CADA MESA
        ==========================================*/

        $stmtPedidos = $pdo->prepare("
            SELECT 
                o.order_id,
                o.order_date,
                IFNULL(SUM(od.quantity * od.unit_price),0) as total_pedido
            FROM orders o
            JOIN order_details od 
                ON od.order_id = o.order_id
            WHERE o.table_id = :table
            AND o.status = 'closed'
            GROUP BY o.order_id
            ORDER BY o.order_date DESC
        ");

        $stmtPedidos->execute(["table" => $mesa['id_table']]);
        $pedidos = $stmtPedidos->fetchAll(PDO::FETCH_ASSOC);

        /* ==========================================
           3️⃣ OBTENER PRODUCTOS POR MESA
        ==========================================*/

        $stmtProductos = $pdo->prepare("
            SELECT 
                p.nombre_producto as producto,
                SUM(od.quantity) as cantidad
            FROM orders o
            JOIN order_details od 
                ON od.order_id = o.order_id
            JOIN products p 
                ON od.product_id = p.id_product
            WHERE o.table_id = :table
            AND o.status = 'closed'
            GROUP BY od.product_id
            ORDER BY cantidad DESC
        ");

        $stmtProductos->execute(["table" => $mesa['id_table']]);
        $productos = $stmtProductos->fetchAll(PDO::FETCH_ASSOC);

        /* ==========================================
           4️⃣ ARMAR ESTRUCTURA FINAL
        ==========================================*/

        $mesas[] = [
            "id_mesa" => $mesa["id_table"],
            "mesa" => $mesa["mesa"],
            "total_ventas" => round($mesa["total_ventas"],2),
            "total_pedidos" => $mesa["total_pedidos"],
            "pedidos" => $pedidos,
            "productos" => $productos
        ];
    }

    $mejorMesa = $mesas[0] ?? null;

    echo json_encode([
        "error" => 0,
        "area" => $area,
        "mejor_mesa" => $mejorMesa,
        "mesas" => $mesas
    ]);

} catch(PDOException $e){

    echo json_encode([
        "error" => 1,
        "message" => $e->getMessage()
    ]);

}