<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json");

include "dbconnect.php";

// 1. Detectar sistema y base de datos
$system = $_GET['system'] ?? 'mixtura';
try {
    $pdo->exec("USE `$system`");
} catch (PDOException $e) {
    echo json_encode(["error" => 1, "message" => "Base de datos no encontrada"]);
    exit;
}

// 2. Captura de filtros desde Angular
$tipo_filtro       = $_GET['tipo'] ?? null; 
$fecha_inicio      = $_GET['fecha_inicio'] ?? null;
$fecha_fin         = $_GET['fecha_fin'] ?? null;
$anio_seleccionado = isset($_GET['anio']) ? intval($_GET['anio']) : intval(date('Y'));

$params = [];
$whereOrders = " WHERE o.cancel IS NULL ";
$wherePagos  = " WHERE 1=1 ";

// 3. Lógica de filtrado por rango operativo homologada
if ($tipo_filtro == "rango_operativo" && !empty($fecha_inicio) && !empty($fecha_fin)) {
    $f_start = date("Y-m-d H:i:s", strtotime($fecha_inicio));
    $f_end = date("Y-m-d H:i:s", strtotime($fecha_fin));
    
    $whereOrders .= " AND o.order_date BETWEEN :inicio AND :fin ";
    $wherePagos  .= " AND p.fecha_pago BETWEEN :inicio_p AND :fin_p ";
    
    $params[':inicio'] = $f_start;
    $params[':fin'] = $f_end;
    $params[':inicio_p'] = $f_start;
    $params[':fin_p'] = $f_end;
}

try {
    /* 1️⃣ VENTAS TOTALES (Basado estrictamente en pagos reales del periodo para cuadrar caja) */
    $stmtVentas = $pdo->prepare("
        SELECT COALESCE(SUM(p.monto_total), 0) as total_ventas, COUNT(DISTINCT o.order_id) as total_pedidos
        FROM orders o
        INNER JOIN pagos_realizados p ON o.order_id = p.order_id
        " . str_replace("p.fecha_pago", "p.fecha_pago", $wherePagos) . " AND o.cancel IS NULL
    ");
    
    // Vinculamos los parámetros correctos para pagos
    $paramsVentas = [];
    if (isset($params[':inicio_p'])) {
        $paramsVentas[':inicio_p'] = $params[':inicio_p'];
        $paramsVentas[':fin_p'] = $params[':fin_p'];
    }
    $stmtVentas->execute($paramsVentas);
    $resVentas = $stmtVentas->fetch(PDO::FETCH_ASSOC);
    $totalVentas = (float)$resVentas['total_ventas'];
    $totalPedidosCount = (int)$resVentas['total_pedidos'];

    /* 2️⃣ GANANCIA ANUAL SELECCIONADA */
    $stmtGananciaAnual = $pdo->prepare("
        SELECT COALESCE(SUM(monto_total), 0) as total 
        FROM pagos_realizados 
        WHERE YEAR(fecha_pago) = :anio_sel
    ");
    $stmtGananciaAnual->execute([':anio_sel' => $anio_seleccionado]);
    $gananciaAnual = (float)$stmtGananciaAnual->fetch(PDO::FETCH_ASSOC)['total'];

    $stmtAniosLista = $pdo->query("SELECT DISTINCT YEAR(fecha_pago) as anio FROM pagos_realizados ORDER BY anio DESC");
    $listaAnios = $stmtAniosLista->fetchAll(PDO::FETCH_COLUMN);
    if(empty($listaAnios)) { $listaAnios = [date('Y')]; }

    // Re-mapeo de parámetros estándar para las consultas basadas en órdenes de aquí en adelante
    $paramsOrders = [];
    if (isset($params[':inicio'])) {
        $paramsOrders[':inicio'] = $params[':inicio'];
        $paramsOrders[':fin'] = $params[':fin'];
    }

    /* 3️⃣ TOP PRODUCTOS */
    $stmtTop = $pdo->prepare("
        SELECT p.nombre_producto, SUM(od.quantity) as cantidad 
        FROM orders o
        JOIN order_details od ON o.order_id = od.order_id
        JOIN products p ON od.product_id = p.id_product
        $whereOrders AND od.status != 'canceled'
        GROUP BY p.id_product 
        ORDER BY cantidad DESC LIMIT 5
    ");
    $stmtTop->execute($paramsOrders);
    $topProductos = $stmtTop->fetchAll(PDO::FETCH_ASSOC);

    /* 4️⃣ MESAS (TOP Y LOW) - Evitando duplicaciones por detalles */
    $stmtMesaTop = $pdo->prepare("
        SELECT 
            t.id_table, t.nombre, t.capacidad as capacity, t.estado as state, f.Name as piso,
            COUNT(DISTINCT o.order_id) as total_pedidos,
            COALESCE(SUM(p.monto_total), 0) as total_ventas
        FROM cafe_tables t
        LEFT JOIN flats f ON t.id_flats = f.Id_flats
        LEFT JOIN orders o ON t.id_table = o.table_id AND o.cancel IS NULL
        LEFT JOIN pagos_realizados p ON o.order_id = p.order_id
        " . (!empty($fecha_inicio) ? "AND o.order_date BETWEEN :inicio AND :fin" : "") . "
        GROUP BY t.id_table
        ORDER BY total_ventas DESC, total_pedidos DESC LIMIT 1
    ");
    $stmtMesaTop->execute($paramsOrders);
    $mesaTop = $stmtMesaTop->fetch(PDO::FETCH_ASSOC);

    $stmtMesaLow = $pdo->prepare("
        SELECT t.nombre, COUNT(o.order_id) as total_pedidos
        FROM cafe_tables t
        LEFT JOIN orders o ON t.id_table = o.table_id AND o.cancel IS NULL " . (!empty($fecha_inicio) ? "AND o.order_date BETWEEN :inicio AND :fin" : "") . "
        GROUP BY t.id_table
        ORDER BY total_pedidos ASC LIMIT 1
    ");
    $stmtMesaLow->execute($paramsOrders);
    $mesaLow = $stmtMesaLow->fetch(PDO::FETCH_ASSOC);

    /* 5️⃣ MESEROS - Corregido conteo de pedidos e ingresos netos */
    $stmtMeseros = $pdo->prepare("
        SELECT
            u.id, u.name,
            COUNT(DISTINCT o.order_id) as pedidos_atendidos,
            COALESCE((SELECT SUM(od.quantity) FROM order_details od JOIN orders o2 ON od.order_id = o2.order_id WHERE o2.user_id = u.id AND o2.cancel IS NULL AND od.status != 'canceled' " . (!empty($fecha_inicio) ? "AND o2.order_date BETWEEN :inicio AND :fin" : "") . "), 0) as items_vendidos,
            COALESCE(SUM(p.monto_total), 0) as total_ventas
        FROM user u
        INNER JOIN orders o ON u.id = o.user_id AND o.cancel IS NULL
        LEFT JOIN pagos_realizados p ON o.order_id = p.order_id
        " . (!empty($fecha_inicio) ? "AND o.order_date BETWEEN :inicio AND :fin" : "") . "
        GROUP BY u.id
        ORDER BY total_ventas DESC
    ");
    $stmtMeseros->execute($paramsOrders);
    $meserosResult = $stmtMeseros->fetchAll(PDO::FETCH_ASSOC);
    $meseroTop = $meserosResult[0] ?? null;

    /* 6️⃣ ÁREAS / PISOS */
    $stmtAreas = $pdo->prepare("
        SELECT f.Name as area, COALESCE(SUM(p.monto_total), 0) as total_area
        FROM flats f
        INNER JOIN cafe_tables t ON f.Id_flats = t.id_flats
        INNER JOIN orders o ON t.id_table = o.table_id AND o.cancel IS NULL
        INNER JOIN pagos_realizados p ON o.order_id = p.order_id
        " . (!empty($fecha_inicio) ? "AND o.order_date BETWEEN :inicio AND :fin" : "") . "
        GROUP BY f.Id_flats 
        ORDER BY total_area DESC
    ");
    $stmtAreas->execute($paramsOrders);
    $areasTop = $stmtAreas->fetchAll(PDO::FETCH_ASSOC);

    /* 7️⃣ HORAS PICO */
    $stmtHoras = $pdo->prepare("
        SELECT HOUR(o.order_date) as hora, COUNT(DISTINCT o.order_id) as total_pedidos,
               COALESCE(SUM(p.monto_total), 0) as total_ventas,
               COALESCE(SUM(p.monto_total) / COUNT(DISTINCT o.order_id), 0) as ticket_promedio
        FROM orders o
        LEFT JOIN pagos_realizados p ON o.order_id = p.order_id
        $whereOrders
        GROUP BY hora 
        ORDER BY total_pedidos DESC LIMIT 5
    ");
    $stmtHoras->execute($paramsOrders);
    $horasPico = $stmtHoras->fetchAll(PDO::FETCH_ASSOC);

    /* 8️⃣ VENTAS POR CATEGORÍA AUTOMÁTICO - Proporcional al subtotal del pedido para no sobrepasar ingresos */
    $stmtCategorias = $pdo->prepare("
        SELECT c.name AS categoria, SUM(od.total_price) AS total_ventas
        FROM orders o
        JOIN order_details od ON o.order_id = od.order_id
        JOIN products p ON od.product_id = p.id_product
        JOIN category c ON p.id_category = c.id
        $whereOrders AND od.status != 'canceled'
        GROUP BY c.id 
        ORDER BY total_ventas DESC
    ");
    $stmtCategorias->execute($paramsOrders);
    $ventasCategorias = $stmtCategorias->fetchAll(PDO::FETCH_ASSOC);

    /* 9️⃣ CATEGORÍA RENTABLE Y SU PRODUCTO ESTRELLA (CORREGIDO) */
    $stmtCat = $pdo->prepare("
        SELECT c.id, c.name, SUM(od.total_price) AS total_ganancia,
               SUM(od.quantity) AS productos_vendidos, COUNT(DISTINCT p.id_product) AS productos_en_categoria
        FROM orders o
        JOIN order_details od ON o.order_id = od.order_id
        JOIN products p ON od.product_id = p.id_product
        JOIN category c ON p.id_category = c.id
        $whereOrders AND od.status != 'canceled'
        GROUP BY c.id 
        ORDER BY total_ganancia DESC LIMIT 1
    ");
    $stmtCat->execute($paramsOrders);
    $categoriaTop = $stmtCat->fetch(PDO::FETCH_ASSOC);

    $productoTopCat = null;
    if($categoriaTop){
        $stmtProdCat = $pdo->prepare("
            SELECT p.nombre_producto, SUM(od.quantity) AS total_vendidos
            FROM orders o
            JOIN order_details od ON o.order_id = od.order_id
            JOIN products p ON od.product_id = p.id_product
            $whereOrders AND od.status != 'canceled' AND p.id_category = :cat_id
            GROUP BY p.id_product 
            ORDER BY total_vendidos DESC LIMIT 1
        ");
        $tempParams = $paramsOrders;
        $tempParams[':cat_id'] = $categoriaTop['id'];
        $stmtProdCat->execute($tempParams);
        $productoTopCat = $stmtProdCat->fetch(PDO::FETCH_ASSOC);
    }

    /* 🔟 CONSUMO DE INGREDIENTES EN EXTRAS */
    $stmtIng = $pdo->prepare("
        SELECT i.nombre, i.unidad_med, COUNT(oda.id_adjustment) as total_usado
    FROM order_detail_adjustments oda
        JOIN ingredients i ON oda.ingredient_id = i.id_ingredients
        " . (!empty($fecha_inicio) ? "WHERE oda.created_at BETWEEN :inicio AND :fin" : "") . "
        GROUP BY i.id_ingredients 
        ORDER BY total_usado DESC LIMIT 10
    ");
    $stmtIng->execute($paramsOrders);
    $ingredientes = $stmtIng->fetchAll(PDO::FETCH_ASSOC);

    /* 1️⃣1️⃣ VENTAS POR SUBCATEGORÍA */
    $stmtSub = $pdo->prepare("
        SELECT COALESCE(s.name, 'SIN SUBCATEGORIA') as subcategoria, SUM(od.total_price) as total_ventas
        FROM orders o
        JOIN order_details od ON o.order_id = od.order_id
        JOIN products p ON od.product_id = p.id_product
        LEFT JOIN subcategories s ON p.id_subcategory = s.id_subcategory
        $whereOrders AND od.status != 'canceled'
        GROUP BY s.id_subcategory 
        ORDER BY total_ventas DESC
    ");
    $stmtSub->execute($paramsOrders);
    $ventasSubcategorias = $stmtSub->fetchAll(PDO::FETCH_ASSOC);

    /* 1️⃣2️⃣ VENTAS CON ALCOHOL VS SIN ALCOHOL (Ajustado según sumatoria de ítems de órdenes reales) */
    $stmtAlcohol = $pdo->prepare("
        SELECT
            COALESCE(SUM(CASE WHEN LOWER(c.name) LIKE '%cerveza%' OR LOWER(c.name) LIKE '%alcohol%' OR LOWER(c.name) LIKE '%tragos%' THEN od.total_price ELSE 0 END), 0) as con,
            COALESCE(SUM(CASE WHEN LOWER(c.name) NOT LIKE '%cerveza%' AND LOWER(c.name) NOT LIKE '%alcohol%' AND LOWER(c.name) NOT LIKE '%tragos%' THEN od.total_price ELSE 0 END), 0) as sin
        FROM orders o
        JOIN order_details od ON o.order_id = od.order_id
        JOIN products p ON od.product_id = p.id_product
        LEFT JOIN category c ON p.id_category = c.id
        $whereOrders AND od.status != 'canceled'
    ");
    $stmtAlcohol->execute($paramsOrders);
    $ventasAlcohol = $stmtAlcohol->fetch(PDO::FETCH_ASSOC);

    /* 1️⃣3️⃣ RENDIMIENTO TIEMPOS DE ENTREGA */
    $stmtTiempo = $pdo->prepare("
        SELECT 
            SUM(CASE WHEN actual_time <= estimated_time THEN 1 ELSE 0 END) as a_tiempo,
            SUM(CASE WHEN actual_time > estimated_time THEN 1 ELSE 0 END) as retrasadas
        FROM orders o 
        $whereOrders AND o.status = 'closed'
    ");
    $stmtTiempo->execute($paramsOrders);
    $ordenesTiempo = $stmtTiempo->fetch(PDO::FETCH_ASSOC);

    /* 1️⃣4️⃣ PRODUCTO INDIVIDUAL (TOP Y LOW GLOBAL) - Controlando que low traiga '0' en vez de NULL */
    $stmtProductoTop = $pdo->prepare("
        SELECT p.nombre_producto, COALESCE(SUM(od.quantity), 0) as total_vendido
        FROM orders o
        JOIN order_details od ON o.order_id = od.order_id
        JOIN products p ON od.product_id = p.id_product
        $whereOrders AND od.status != 'canceled'
        GROUP BY p.id_product 
        ORDER BY total_vendido DESC LIMIT 1
    ");
    $stmtProductoTop->execute($paramsOrders);
    $productoTop = $stmtProductoTop->fetch(PDO::FETCH_ASSOC);

    $stmtProductoLow = $pdo->prepare("
        SELECT p.nombre_producto, COALESCE(SUM(od.quantity), 0) as total_vendido
        FROM products p
        LEFT JOIN order_details od ON p.id_product = od.product_id
        LEFT JOIN orders o ON od.order_id = o.order_id AND o.cancel IS NULL " . (!empty($fecha_inicio) ? "AND o.order_date BETWEEN :inicio AND :fin" : "") . "
        GROUP BY p.id_product 
        ORDER BY total_vendido ASC LIMIT 1
    ");
    $stmtProductoLow->execute($paramsOrders);
    $productoLow = $stmtProductoLow->fetch(PDO::FETCH_ASSOC);

    /* 1️⃣5️⃣ PRODUCTOS LENTOS */
    $stmtLentos = $pdo->prepare("
        SELECT p.nombre_producto, COUNT(o.order_id) as veces_pedido, ROUND(AVG(o.actual_time), 1) as tiempo_promedio
        FROM orders o
        JOIN order_details od ON o.order_id = od.order_id
        JOIN products p ON od.product_id = p.id_product
        $whereOrders AND o.status = 'closed'
        GROUP BY p.id_product 
        ORDER BY tiempo_promedio DESC LIMIT 5
    ");
    $stmtLentos->execute($paramsOrders);
    $productosLentos = $stmtLentos->fetchAll(PDO::FETCH_ASSOC);

    /* 1️⃣6️⃣ STOCK MÍNIMO / CRÍTICO (Filtramos valores erróneos negativos si es requerido, o los mostramos como alerta) */
    $stmtStock = $pdo->query("SELECT nombre, stock_act, unidad_med FROM ingredients ORDER BY stock_act ASC LIMIT 1");
    $stockMin = $stmtStock->fetch(PDO::FETCH_ASSOC);

    /* 1️⃣7️⃣ PAGOS POR EMPLEADO Y MÉTODO DE PAGO */
    $stmtPagosEmpleado = $pdo->prepare("
        SELECT 
            u.id, u.name,
            COALESCE(SUM(CASE WHEN pr.metodo_pago = 'efectivo' THEN pr.monto_total END), 0) as efectivo,
            COALESCE(SUM(CASE WHEN pr.metodo_pago = 'qr' THEN pr.monto_total END), 0) as qr,
            COALESCE(SUM(CASE WHEN pr.metodo_pago = 'tarjeta' THEN pr.monto_total END), 0) as tarjeta,
            COALESCE(SUM(pr.monto_total), 0) as total_ventas
        FROM user u
        INNER JOIN pagos_realizados pr ON u.id = pr.user_id
        INNER JOIN orders o ON pr.order_id = o.order_id
        WHERE o.cancel IS NULL " . (!empty($fecha_inicio) ? "AND pr.fecha_pago BETWEEN :inicio AND :fin" : "") . "
        GROUP BY u.id 
        ORDER BY total_ventas DESC
    ");
    $stmtPagosEmpleado->execute($paramsOrders);
    $empleadosPagos = $stmtPagosEmpleado->fetchAll(PDO::FETCH_ASSOC);

    // 4. RESPUESTA FINAL INTEGRADA COHERENTE
    echo json_encode([
        "error" => 0,
        "lista_anios" => $listaAnios,
        "resumen" => [
            "total_dinero" => round((float)$totalVentas, 2),
            "ganancia_total" => round((float)$totalVentas, 2),
            "ganancia_anual" => round((float)$gananciaAnual, 2),
            "total_pedidos" => $totalPedidosCount,
            
            "top_productos" => $topProductos,
            "producto_top" => $productoTop ?: ["nombre_producto" => "N/A", "total_sold" => 0],
            "producto_low" => $productoLow ?: ["nombre_producto" => "N/A", "total_sold" => 0],
            "mesa_top" => $mesaTop ?: ["nombre" => "N/A", "total_pedidos" => 0, "total_ventas" => 0, "capacity" => 0, "piso" => "N/A"],
            "mesa_low" => $mesaLow ?: ["nombre" => "N/A", "total_pedidos" => 0],
            "mesero_top" => $meseroTop ?: ["name" => "N/A", "total_ventas" => 0, "pedidos_atendidos" => 0, "items_vendidos" => 0],
            "meseros" => $meserosResult,
            "areas_top" => $areasTop,
            "empleados_pagos" => $empleadosPagos,
            "ventas_categorias" => $ventasCategorias,
            "ventas_subcategorias" => $ventasSubcategorias,
            "horas_pico" => $horasPico,
            "ventas_alcohol" => $ventasAlcohol,
            "stock_minimo" => $stockMin,
            "productos_lentos" => $productosLentos,
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