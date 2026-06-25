<?php

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

header("Content-Type: application/json");

require_once 'dbconnect.php';

$input = json_decode(file_get_contents("php://input"), true);

$id_pago = $input['id_pago'] ?? 0;
$system  = $input['system'] ?? 'mixtura';

try {

    $pdo->exec("USE `$system`");

    $pdo->beginTransaction();

    // =====================================
    // OBTENER PAGO
    // =====================================

    $stmt = $pdo->prepare("
        SELECT *
        FROM pagos_realizados
        WHERE id_pago = ?
    ");

    $stmt->execute([$id_pago]);

    $pago = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$pago) {
        throw new Exception("Pago no encontrado");
    }

    $items = json_decode(
        $pago['items_ids'],
        true
    );

    // =====================================
    // RESTAURAR ITEMS
    // =====================================

    if (!empty($items)) {

        foreach ($items as $detail_id => $cant_pagada) {

            $stmtItem = $pdo->prepare("
                SELECT *
                FROM order_details
                WHERE detail_id = ?
            ");

            $stmtItem->execute([
                $detail_id
            ]);

            $item = $stmtItem->fetch(
                PDO::FETCH_ASSOC
            );

            if (!$item) {
                continue;
            }

            // =========================
            // PAGADO COMPLETO
            // =========================

            if (
                $item['estado_pago']
                ===
                'pagado'
            ) {

                $pdo->prepare("
                    UPDATE order_details
                    SET estado_pago='pendiente'
                    WHERE detail_id=?
                ")
                ->execute([
                    $detail_id
                ]);

            }

            // =========================
            // SPLIT DE CANTIDADES
            // =========================

            else {

                $stmtSplit = $pdo->prepare("
                    SELECT *
                    FROM order_details
                    WHERE
                    order_id = ?
                    AND product_id = ?
                    AND estado_pago='pagado'
                    ORDER BY detail_id DESC
                    LIMIT 1
                ");

                $stmtSplit->execute([

                    $item['order_id'],

                    $item['product_id']

                ]);

                $split = $stmtSplit->fetch(
                    PDO::FETCH_ASSOC
                );

                if ($split) {

                    $nuevaCantidad =

                        $item['quantity']

                        +

                        $cant_pagada;

                    $pdo->prepare("
                        UPDATE order_details
                        SET
                        quantity=?,
                        total_price=unit_price * ?
                        WHERE detail_id=?
                    ")
                    ->execute([

                        $nuevaCantidad,

                        $nuevaCantidad,

                        $detail_id

                    ]);

                    $pdo->prepare("
                        DELETE
                        FROM order_details
                        WHERE detail_id=?
                    ")
                    ->execute([
                        $split['detail_id']
                    ]);
                }
            }
        }
    }

    // =====================================
    // ELIMINAR PAGO
    // =====================================

    $pdo->prepare("
        DELETE FROM pagos_realizados
        WHERE id_pago = ?
    ")
    ->execute([
        $id_pago
    ]);

    // =====================================
    // REABRIR MESA SOLO SI HAY ITEMS
    // PENDIENTES
    // =====================================

    $stmtCheck = $pdo->prepare("
        SELECT COUNT(*)
        FROM order_details
        WHERE
        order_id = ?
        AND estado_pago='pendiente'
        AND status!='canceled'
    ");

    $stmtCheck->execute([
        $pago['order_id']
    ]);

    $pendientes = (int)
        $stmtCheck->fetchColumn();

    if ($pendientes > 0) {

        $pdo->prepare("
            UPDATE cafe_tables
            SET estado='Ocupada'
            WHERE id_table = (
                SELECT table_id
                FROM orders
                WHERE order_id=?
                LIMIT 1
            )
        ")
        ->execute([
            $pago['order_id']
        ]);
    }

    // =====================================
    // HISTORIAL
    // =====================================

    $pdo->prepare("
        INSERT INTO historial_mesa
        (
            order_id,
            user_id,
            accion,
            observacion
        )
        VALUES
        (
            ?,
            ?,
            'eliminar_pago',
            'Pago parcial eliminado'
        )
    ")
    ->execute([

        $pago['order_id'],

        $pago['user_id']

    ]);

    $pdo->commit();

    echo json_encode([

        "error" => 0,

        "message" =>
            "Pago eliminado y items restaurados"

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