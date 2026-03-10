<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json");

include "dbconnect.php";

// 1. Detectar sistema
$system = $_GET['system'] ?? 'mixtura';
try {
    $pdo->exec("USE `$system` ");
} catch (PDOException $e) {
    echo json_encode(["error" => 1, "message" => "Base de datos no encontrada"]);
    exit;
}

// 2. CAPTURA SINCRONIZADA CON ANGULAR
// Cambiamos $filtro por la variable 'tipo' que envías desde Angular
$tipo_filtro  = $_GET['tipo'] ?? null; 
$fecha_inicio = $_GET['fecha_inicio'] ?? null;
$fecha_fin    = $_GET['fecha_fin'] ?? null;

$params = [];
$where = " WHERE 1=1 ";

// 3. Lógica de filtrado (Actualizada para reconocer 'rango_operativo')
if ($tipo_filtro == "rango_operativo" && !empty($fecha_inicio) && !empty($fecha_fin)) {
    // Convertimos a formato MySQL
    $f_start = date("Y-m-d H:i:s", strtotime($fecha_inicio));
    $f_end = date("Y-m-d H:i:s", strtotime($fecha_fin));
    
    $where .= " AND o.order_date BETWEEN :inicio AND :fin ";
    $params[':inicio'] = $f_start;
    $params[':fin'] = $f_end;
}

try {
    /* 1️⃣ VENTAS TOTALES */
    $stmtVentas = $pdo->prepare("
        SELECT COALESCE(SUM(od.quantity * od.unit_price),0) as total_ventas
        FROM orders o
        JOIN order_details od ON o.order_id = od.order_id
        $where AND o.status IN ('closed','paid','completed')
    ");
    $stmtVentas->execute($params);
    $totalVentas = $stmtVentas->fetch(PDO::FETCH_ASSOC)['total_ventas'];

    /* 2️⃣ TOP PRODUCTOS */
    $stmtTop = $pdo->prepare("
        SELECT p.nombre_producto, SUM(od.quantity) as cantidad 
        FROM orders o
        JOIN order_details od ON o.order_id = od.order_id
        JOIN products p ON od.product_id = p.id_product
        $where AND o.status IN ('closed','paid','completed')
        GROUP BY p.id_product ORDER BY cantidad DESC LIMIT 5
    ");
    $stmtTop->execute($params);
    $topProductos = $stmtTop->fetchAll(PDO::FETCH_ASSOC);

    /* 3️⃣ MESAS (TOP Y LOW) */
    $stmtMesaTop = $pdo->prepare("
        SELECT t.nombre, COUNT(o.order_id) as total_pedidos
        FROM orders o
        JOIN cafe_tables t ON o.table_id = t.id_table
        $where
        GROUP BY o.table_id ORDER BY total_pedidos DESC LIMIT 1
    ");
    $stmtMesaTop->execute($params);
    $mesaTop = $stmtMesaTop->fetch(PDO::FETCH_ASSOC);

    // Mesa Low con subconsulta para evitar errores de sintaxis
    $stmtMesaLow = $pdo->prepare("
        SELECT t.nombre, 
               (SELECT COUNT(*) FROM orders o $where AND o.table_id = t.id_table) as total_pedidos
        FROM cafe_tables t
        ORDER BY total_pedidos ASC LIMIT 1
    ");
    $stmtMesaLow->execute($params);
    $mesaLow = $stmtMesaLow->fetch(PDO::FETCH_ASSOC);

    /* 4️⃣ MESEROS */
    $stmtMeseros = $pdo->prepare("
        SELECT u.name, SUM(od.quantity * od.unit_price) as total_ventas
        FROM orders o
        JOIN user u ON o.user_id = u.id
        JOIN order_details od ON od.order_id = o.order_id
        $where AND o.status IN ('closed','paid','completed')
        GROUP BY o.user_id ORDER BY total_ventas DESC
    ");
    $stmtMeseros->execute($params);
    $meserosResult = $stmtMeseros->fetchAll(PDO::FETCH_ASSOC);
    $meseroTop = $meserosResult[0] ?? null;

    /* 5️⃣ ÁREAS */
    $stmtAreas = $pdo->prepare("
        SELECT f.Name as area, SUM(od.quantity * od.unit_price) as total_area
        FROM orders o
        JOIN cafe_tables t ON o.table_id = t.id_table
        JOIN flats f ON t.id_flats = f.Id_flats
        JOIN order_details od ON od.order_id = o.order_id
        $where AND o.status IN ('closed','paid','completed')
        GROUP BY f.Id_flats ORDER BY total_area DESC
    ");
    $stmtAreas->execute($params);
    $areasTop = $stmtAreas->fetchAll(PDO::FETCH_ASSOC);

    /* 6️⃣ HORAS PICO */
    $stmtHoras = $pdo->prepare("
        SELECT HOUR(o.order_date) as hora, COUNT(DISTINCT o.order_id) as total_pedidos,
               SUM(od.quantity * od.unit_price) as total_ventas,
               AVG(od.quantity * od.unit_price) as ticket_promedio
        FROM orders o
        JOIN order_details od ON o.order_id = od.order_id
        $where AND o.status IN ('closed','paid','completed')
        GROUP BY hora ORDER BY total_pedidos DESC LIMIT 5
    ");
    $stmtHoras->execute($params);
    $horasPico = $stmtHoras->fetchAll(PDO::FETCH_ASSOC);

    /* 7️⃣ ANALÍTICA ALCOHOL */
    $stmtAlc = $pdo->prepare("
        SELECT CASE WHEN c.name LIKE '%ALCOHOL%' THEN 'con_alcohol' ELSE 'sin_alcohol' END as tipo_alc,
        SUM(od.quantity * od.unit_price) as total_ventas
        FROM orders o
        JOIN order_details od ON o.order_id = od.order_id
        JOIN products p ON od.product_id = p.id_product
        JOIN category c ON p.id_category = c.id
        $where AND o.status IN ('closed','paid','completed')
        GROUP BY tipo_alc
    ");
    $stmtAlc->execute($params);
    $resAlc = $stmtAlc->fetchAll(PDO::FETCH_ASSOC);
    $ventasAlcohol = ['con' => 0, 'sin' => 0];
    foreach($resAlc as $r) { 
        $ventasAlcohol[$r['tipo_alc'] == 'con_alcohol' ? 'con' : 'sin'] = $r['total_ventas']; 
    }

    /* 8️⃣ CATEGORÍA RENTABLE */
    $stmtCat = $pdo->prepare("
        SELECT c.id, c.name, SUM(od.quantity * od.unit_price) AS total_ganancia,
               SUM(od.quantity) AS productos_vendidos, COUNT(DISTINCT p.id_product) AS productos_en_categoria
        FROM orders o
        JOIN order_details od ON o.order_id = od.order_id
        JOIN products p ON od.product_id = p.id_product
        JOIN category c ON p.id_category = c.id
        $where AND o.status IN ('closed','paid','completed')
        GROUP BY c.id ORDER BY total_ganancia DESC LIMIT 1
    ");
    $stmtCat->execute($params);
    $categoriaTop = $stmtCat->fetch(PDO::FETCH_ASSOC);

    $productoTopCat = null;
    if($categoriaTop){
        $stmtProdCat = $pdo->prepare("
            SELECT p.nombre_producto, SUM(od.quantity) AS total_vendidos
            FROM orders o
            JOIN order_details od ON o.order_id = od.order_id
            JOIN products p ON od.product_id = p.id_product
            $where AND o.status IN ('closed','paid','completed') AND p.id_category = :cat_id
            GROUP BY p.id_product ORDER BY total_vendidos DESC LIMIT 1
        ");
        $tempParams = $params;
        $tempParams[':cat_id'] = $categoriaTop['id'];
        $stmtProdCat->execute($tempParams);
        $productoTopCat = $stmtProdCat->fetch(PDO::FETCH_ASSOC);
    }

    /* 9️⃣ INGREDIENTES */
    $stmtIng = $pdo->prepare("
        SELECT i.nombre, SUM(od.quantity) as total_usado
        FROM order_details od
        JOIN orders o ON od.order_id = o.order_id
        JOIN products p ON od.product_id = p.id_product
        JOIN ingredients i ON i.id_ingredients = p.id_product 
        $where
        GROUP BY i.id_ingredients ORDER BY total_usado DESC
    ");
    $stmtIng->execute($params);
    $ingredientes = $stmtIng->fetchAll(PDO::FETCH_ASSOC);

    /* 🔟 RENDIMIENTO TIEMPO */
    $stmtTiempo = $pdo->prepare("
        SELECT SUM(CASE WHEN actual_time <= estimated_time THEN 1 ELSE 0 END) as a_tiempo,
               SUM(CASE WHEN actual_time > estimated_time THEN 1 ELSE 0 END) as retrasadas
        FROM orders o 
        $where AND status IN ('closed','paid','completed')
    ");
    $stmtTiempo->execute($params);
    $ordenesTiempo = $stmtTiempo->fetch(PDO::FETCH_ASSOC);

    // 4. RESPUESTA FINAL
    echo json_encode([
        "error" => 0,
        "debug_recibido" => [
            "tipo_detectado" => $tipo_filtro,
            "inicio_detectado" => $fecha_inicio,
            "fin_detectado" => $fecha_fin
        ],
        "rango_consultado" => [
            "inicio" => $params[':inicio'] ?? 'No definido',
            "fin" => $params[':fin'] ?? 'No definido'
        ],
        "resumen" => [
            "total_dinero" => round($totalVentas, 2),
            "ganancia_total" => round($totalVentas, 2),
            "top_productos" => $topProductos,
            "mesa_top" => $mesaTop ?: ["nombre" => "N/A", "total_pedidos" => 0],
            "mesa_low" => $mesaLow ?: ["nombre" => "N/A", "total_pedidos" => 0],
            "mesero_top" => $meseroTop ?: ["name" => "N/A", "total_ventas" => 0],
            "meseros" => $meserosResult,
            "areas_top" => $areasTop,
            "horas_pico" => $horasPico,
            "ventas_alcohol" => $ventasAlcohol,
            "categoria_top" => [
                "nombre" => $categoriaTop['name'] ?? "N/A",
                "ganancia_total" => $categoriaTop['total_ganancia'] ?? 0,
                "productos_vendidos" => $categoriaTop['productos_vendidos'] ?? 0,
                "productos_en_categoria" => $categoriaTop['productos_en_categoria'] ?? 0,
                "producto_mas_vendido" => $productoTopCat['nombre_producto'] ?? "N/A",
                "cantidad_vendida" => $productoTopCat['total_vendidos'] ?? 0
            ],
            "ingredientes_top" => $ingredientes,
            "ordenes_tiempo" => $ordenesTiempo
        ]
    ]);

} catch (PDOException $e) {
    echo json_encode(["error" => 1, "message" => $e->getMessage()]);
}
?>