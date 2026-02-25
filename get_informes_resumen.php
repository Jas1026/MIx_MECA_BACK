<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json");
include "dbconnect.php";

$system = $_GET['system'] ?? 'mecapos';
$pdo->exec("USE `$system` "); 

try {
    // 1. Total Ventas Histórico (Sumando detalles de pedidos)
    $stmtVentas = $pdo->query("SELECT SUM(quantity * unit_price) as total FROM order_details");
    $totalVentas = $stmtVentas->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;

    // 2. Top 5 Productos más vendidos
    $stmtTop = $pdo->query("
        SELECT p.nombre_producto, SUM(od.quantity) as cantidad 
        FROM order_details od
        JOIN products p ON od.product_id = p.id_product
        GROUP BY od.product_id 
        ORDER BY cantidad DESC 
        LIMIT 5
    ");
    $topProductos = $stmtTop->fetchAll(PDO::FETCH_ASSOC);

    // 3. Alertas: Ingredientes con stock menor a 10 (ajustable)
    $stmtAlertas = $pdo->query("SELECT nombre, stock_act, unidad_med FROM ingredients WHERE stock_act < 10 LIMIT 5");
    $alertasStock = $stmtAlertas->fetchAll(PDO::FETCH_ASSOC);

    // 4. Conteo de Activos
    $stmtAssets = $pdo->query("SELECT COUNT(*) as total FROM assets WHERE estado = 'Activo'");
    $activosVivos = $stmtAssets->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;

    echo json_encode([
        "error" => 0,
        "resumen" => [
            "total_dinero" => round($totalVentas, 2),
            "activos_conteo" => $activosVivos,
            "top_productos" => $topProductos,
            "alertas_inventario" => $alertasStock
        ]
    ]);

} catch (PDOException $e) {
    echo json_encode(["error" => 1, "message" => $e->getMessage()]);
}