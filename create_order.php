<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Content-Type: application/json; charset=UTF-8");

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    exit(0);
}

require_once 'dbconnect.php';

$id_table    = $_POST['id_table'] ?? null;
$id_user     = $_POST['id_user'] ?? null;
$system      = $_POST['system'] ?? 'mixtura';
$products    = isset($_POST['products']) ? json_decode($_POST['products'], true) : [];
$force_order = $_POST['force_order'] ?? 'false';

if (!$id_table || !$id_user || empty($products)) {
    echo json_encode([
        "error" => 1,
        "message" => "Datos incompletos"
    ]);
    exit;
}

try {

    $pdo->exec("USE `$system`");

    $pdo->beginTransaction();

    // =========================
    // TIEMPOS POR COCINA
    // =========================
    $kitchen_times = [];

    // =========================
    // VALIDACIÓN + STOCK
    // =========================
    foreach ($products as $p) {

        $stmtP = $pdo->prepare("
            SELECT 
                p.stock_disponible,
                p.nombre_producto,
                p.time_prep,
                pk.kitchen_id
            FROM products p
            LEFT JOIN product_kitchen pk
                ON p.id_product = pk.product_id
            WHERE p.id_product = ?
        ");

        $stmtP->execute([$p['id_product']]);

        $prod = $stmtP->fetch(PDO::FETCH_ASSOC);

        if (!$prod) {
            throw new Exception("Producto no encontrado");
        }

        // =========================
        // VALIDAR STOCK
        // =========================
        if (
            $force_order !== 'true' &&
            (float)$prod['stock_disponible'] < (int)$p['quantity']
        ) {
            throw new Exception(
                "Stock insuficiente para: " .
                $prod['nombre_producto']
            );
        }

        // =========================
        // TIEMPOS
        // =========================
        $k_id = $prod['kitchen_id'] ?? 0;

        $tiempo_total_item =
            (int)$prod['time_prep'] *
            (int)$p['quantity'];

        if (!isset($kitchen_times[$k_id])) {
            $kitchen_times[$k_id] = 0;
        }

        $kitchen_times[$k_id] += $tiempo_total_item;

        // =========================
        // DESCONTAR STOCK PRODUCTO
        // =========================
        $stmtStock = $pdo->prepare("
            UPDATE products
            SET stock_disponible = stock_disponible - ?
            WHERE id_product = ?
        ");

        $stmtStock->execute([
            (int)$p['quantity'],
            $p['id_product']
        ]);

        // =========================
        // INGREDIENTES
        // =========================
        $stmtReceta = $pdo->prepare("
            SELECT
                pi.id_ingredient,
                pi.cant_us,
                i.tipo
            FROM product_ingredient pi
            JOIN ingredients i
                ON pi.id_ingredient = i.id_ingredients
            WHERE pi.id_product = ?
        ");

        $stmtReceta->execute([$p['id_product']]);

        $receta = $stmtReceta->fetchAll(PDO::FETCH_ASSOC);

        foreach ($receta as $ing) {

            $cantidadGasto =
                (float)$ing['cant_us'] *
                (int)$p['quantity'];

            if ($cantidadGasto <= 0) {
                continue;
            }

            // =========================
            // DESCONTAR INGREDIENTES
            // =========================
            $stmtIng = $pdo->prepare("
                UPDATE ingredients
                SET stock_act = stock_act - ?
                WHERE id_ingredients = ?
            ");

            $stmtIng->execute([
                $cantidadGasto,
                $ing['id_ingredient']
            ]);

            // =========================
            // BOTELLAS
            // =========================
            if ($ing['tipo'] === 'botella') {

                $restante = $cantidadGasto;

                while ($restante > 0) {

                    $stmtB = $pdo->prepare("
                        SELECT
                            id_bottle,
                            (peso_actual - peso_envase) as neto
                        FROM ingredient_bottles
                        WHERE ingredient_id = ?
                        AND estado = 'abierta'
                        AND (peso_actual - peso_envase) > 0
                        ORDER BY neto ASC
                        LIMIT 1
                    ");

                    $stmtB->execute([
                        $ing['id_ingredient']
                    ]);

                    $bot = $stmtB->fetch(PDO::FETCH_ASSOC);

                    if (!$bot) {
                        break;
                    }

                    $contenidoDisponible =
                        (float)$bot['neto'];

                    if ($contenidoDisponible > $restante) {

                        $stmtUpdateBottle = $pdo->prepare("
                            UPDATE ingredient_bottles
                            SET peso_actual = peso_actual - ?
                            WHERE id_bottle = ?
                        ");

                        $stmtUpdateBottle->execute([
                            $restante,
                            $bot['id_bottle']
                        ]);

                        $restante = 0;

                    } else {

                        $stmtFinishBottle = $pdo->prepare("
                            UPDATE ingredient_bottles
                            SET
                                peso_actual = peso_envase,
                                estado = 'finalizada'
                            WHERE id_bottle = ?
                        ");

                        $stmtFinishBottle->execute([
                            $bot['id_bottle']
                        ]);

                        $restante -= $contenidoDisponible;
                    }
                }
            }
            // =========================
// FRACCIONADOS
// =========================
if ($ing['tipo'] === 'fraccionado') {

    $restante = $cantidadGasto;

    while ($restante > 0) {

        $stmtF = $pdo->prepare("
            SELECT
                id_fraction,
                cantidad_actual
            FROM ingredient_fractions
            WHERE ingredient_id = ?
            AND estado = 'abierto'
            AND cantidad_actual > 0
           ORDER BY created_at ASC
            LIMIT 1
        ");

        $stmtF->execute([
            $ing['id_ingredient']
        ]);

        $fraction = $stmtF->fetch(PDO::FETCH_ASSOC);

        // No hay más fracciones disponibles
        if (!$fraction) {
            break;
        }

        $disponible =
            (float)$fraction['cantidad_actual'];

        // La fracción alcanza
        if ($disponible > $restante) {

            $stmtUpdateFraction = $pdo->prepare("
                UPDATE ingredient_fractions
                SET cantidad_actual = cantidad_actual - ?
                WHERE id_fraction = ?
            ");

            $stmtUpdateFraction->execute([
                $restante,
                $fraction['id_fraction']
            ]);

            $restante = 0;

        } else {

            // Consumir toda la fracción
            $stmtFinishFraction = $pdo->prepare("
                UPDATE ingredient_fractions
                SET
                    cantidad_actual = 0,
                    estado = 'finalizado'
                WHERE id_fraction = ?
            ");

            $stmtFinishFraction->execute([
                $fraction['id_fraction']
            ]);

            $restante -= $disponible;
        }
    }
}
        }
    }

    // =========================
    // TIEMPO ESTIMADO
    // =========================
    $max_estimated_time =
        !empty($kitchen_times)
        ? max($kitchen_times)
        : 0;

    // =========================
    // CREAR ORDEN
    // =========================
    $stmtOrder = $pdo->prepare("
        INSERT INTO orders
        (
            table_id,
            user_id,
            order_date,
            status,
            estimated_time
        )
        VALUES (?, ?, NOW(), 'open', ?)
    ");

    $stmtOrder->execute([
        $id_table,
        $id_user,
        $max_estimated_time
    ]);

    $order_id = $pdo->lastInsertId();
$stmtHist = $pdo->prepare("
INSERT INTO historial_mesa
(
 order_id,
 user_id,
 accion,
 observacion
)
VALUES
(
 ?,?,
 'abrir',
 'Mesa abierta'
)
");

$stmtHist->execute([
   $order_id,
   $id_user
]);
    // =========================
    // DETALLE ORDEN
    // =========================
    // ⚠️ TOTAL_PRICE ELIMINADO
    // porque es GENERATED
    $stmtDetail = $pdo->prepare("
        INSERT INTO order_details
        (
            order_id,
            product_id,
            kitchen_id,
            quantity,
            unit_price,
            status,
            process_status,
            notes,
            sides
        )
        VALUES (?, ?, ?, ?, ?, 'pending','new', ?, ?)
    ");
foreach ($products as $p) {

    $stmtK = $pdo->prepare("
        SELECT kitchen_id
        FROM product_kitchen
        WHERE product_id = ?
        LIMIT 1
    ");

    $stmtK->execute([
        $p['id_product']
    ]);

    $k_id = $stmtK->fetchColumn() ?: null;

    $cantidad = (int)$p['quantity'];

    for($i=0; $i<$cantidad; $i++){

        $stmtDetail->execute([
            $order_id,
            $p['id_product'],
            $k_id,
            1, // <-- SIEMPRE 1
            $p['price'],
            $p['notes'] ?? '',
            $p['sides'] ?? ''
        ]);

    }

}

    // =========================
    // ACTUALIZAR MESA
    // =========================
    $stmtMesa = $pdo->prepare("
        UPDATE cafe_tables
        SET estado = 'Pendiente'
        WHERE id_table = ?
    ");

    $stmtMesa->execute([$id_table]);

    $pdo->commit();

    echo json_encode([
        "error" => 0,
        "message" => "Pedido creado.",
        "id_order" => $order_id,
        "estimated" => $max_estimated_time
    ]);

} catch (Exception $e) {

    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    echo json_encode([
        "error" => 1,
        "message" => $e->getMessage()
    ]);
}