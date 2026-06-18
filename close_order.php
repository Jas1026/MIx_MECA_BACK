<?php

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Content-Type: application/json");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit();
}

require_once 'dbconnect.php';

$order_id = $_POST['order_id'] ?? null;

$user_id = $_POST['user_id'] ?? null;

$system = $_POST['system'] ?? 'mixtura';

if (!$order_id) {

    echo json_encode([
        "error" => 1,
        "message" => "Falta order_id"
    ]);

    exit;
}

try {

    $pdo->exec("USE `$system`");

    $pdo->beginTransaction();

    // Obtener datos de la orden
    $stmtTime = $pdo->prepare("
        SELECT
            table_id,
            TIMESTAMPDIFF(
                SECOND,
                order_date,
                NOW()
            ) as segundos_totales
        FROM orders
        WHERE order_id = ?
    ");

    $stmtTime->execute([$order_id]);

    $orderData = $stmtTime->fetch(PDO::FETCH_ASSOC);

    if (!$orderData) {

        throw new Exception(
            "Orden no encontrada"
        );

    }

    $table_id = $orderData['table_id'];

    $segundos_totales =
        (int)$orderData['segundos_totales'];

    // Convertir a formato 1.40
    $minutos =
        floor($segundos_totales / 60);

    $segundos_restantes =
        $segundos_totales % 60;

    $tiempo_visual =
        $minutos +
        ($segundos_restantes / 100);

    // Actualizar orden
    $stmtUpdate = $pdo->prepare("
        UPDATE orders
        SET
            status = 'closed',
            actual_time = ?
        WHERE order_id = ?
    ");

    $stmtUpdate->execute([

        $tiempo_visual,

        $order_id

    ]);

    // Liberar mesa

    $stmtMesa = $pdo->prepare("
        UPDATE cafe_tables
        SET estado = 'Libre'
        WHERE id_table = ?
    ");

    $stmtMesa->execute([

        $table_id

    ]);

    // Guardar historial

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
            ?,
            ?,
            'cerrar',
            'Mesa cerrada'
        )
    ");

    $stmtHist->execute([

        $order_id,

        $user_id

    ]);

    $pdo->commit();

    echo json_encode([

        "error" => 0,

        "message" => "Pedido entregado",

        "tiempo_registrado" =>
            number_format(
                $tiempo_visual,
                2
            )

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