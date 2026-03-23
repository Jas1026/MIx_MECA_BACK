<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
require_once 'dbconnect.php';

$from  = $_POST['from_system'];
$to    = $_POST['to_system'];
$type  = $_POST['type'];
$items = json_decode($_POST['items'], true);

try {
    $pdo->beginTransaction();

    foreach ($items as $item) {
        $nombre = $item['nombre'];
        $qty    = (float)$item['qty'];

        if ($type === 'ingredient') {
            // --- 1. OBTENER DATOS DEL ORIGEN ---
            $pdo->exec("USE `$from` ");
            $stmtOrig = $pdo->prepare("SELECT * FROM ingredients WHERE nombre = ?");
            $stmtOrig->execute([$nombre]);
            $dataOrig = $stmtOrig->fetch(PDO::FETCH_ASSOC);

            if (!$dataOrig) continue; // Si no existe en origen, saltar

            // Descontar del ORIGEN
            $pdo->prepare("UPDATE ingredients SET stock_act = stock_act - ? WHERE nombre = ?")
                ->execute([$qty, $nombre]);

            // --- 2. INSERTAR O ACTUALIZAR EN DESTINO ---
            $pdo->exec("USE `$to` ");
            $stmtDest = $pdo->prepare("SELECT id_ingredients FROM ingredients WHERE nombre = ?");
            $stmtDest->execute([$nombre]);
            $exists = $stmtDest->fetch();

            if ($exists) {
                $pdo->prepare("UPDATE ingredients SET stock_act = stock_act + ? WHERE nombre = ?")
                    ->execute([$qty, $nombre]);
            } else {
                // AUTO-CREACIÓN: Copiamos unidad_med y tipo del origen
                $ins = $pdo->prepare("INSERT INTO ingredients (nombre, unidad_med, stock_act, tipo) VALUES (?, ?, ?, ?)");
                $ins->execute([$nombre, $dataOrig['unidad_med'], $qty, $dataOrig['tipo']]);
            }

        } else {
            // --- LÓGICA PARA PRODUCTOS ---
            $pdo->exec("USE `$from` ");
            $stmtOrig = $pdo->prepare("SELECT * FROM products WHERE nombre_producto = ?");
            $stmtOrig->execute([$nombre]);
            $dataOrig = $stmtOrig->fetch(PDO::FETCH_ASSOC);

            if (!$dataOrig) continue;

            // Descontar del ORIGEN
            $pdo->prepare("UPDATE products SET stock_disponible = stock_disponible - ? WHERE nombre_producto = ?")
                ->execute([$qty, $nombre]);

            // --- 2. INSERTAR O ACTUALIZAR EN DESTINO ---
            $pdo->exec("USE `$to` ");
            $stmtDest = $pdo->prepare("SELECT id_product FROM products WHERE nombre_producto = ?");
            $stmtDest->execute([$nombre]);
            $exists = $stmtDest->fetch();

            if ($exists) {
                $pdo->prepare("UPDATE products SET stock_disponible = stock_disponible + ? WHERE nombre_producto = ?")
                    ->execute([$qty, $nombre]);
            } else {
                // AUTO-CREACIÓN: Copiamos precio, categoría y estado. 
                // Nota: La categoría se pone en 1 (o una por defecto) porque los IDs de categoría pueden variar.
                $ins = $pdo->prepare("INSERT INTO products (nombre_producto, price, stock_disponible, id_category, state) VALUES (?, ?, ?, ?, ?)");
                $ins->execute([$nombre, $dataOrig['price'], $qty, 1, 'active']);
            }
        }
    }

    $pdo->commit();
    echo json_encode(["error" => 0, "message" => "Préstamo procesado. Los items nuevos fueron creados automáticamente."]);

} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    echo json_encode(["error" => 1, "message" => $e->getMessage()]);
}