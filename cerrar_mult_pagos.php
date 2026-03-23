<?php
require_once 'dbconnect.php';

$input = json_decode(file_get_contents("php://input"), true);

$order_id = $input['order_id'];
$system   = $input['system'];

try {

    $pdo->exec("USE `$system`");

    // validar si todo está ready
    $stmt = $pdo->prepare("
        SELECT COUNT(*) 
        FROM order_details 
        WHERE order_id = ?
        AND status != 'ready'
    ");
    $stmt->execute([$order_id]);
    $pendientes = $stmt->fetchColumn();

    if ($pendientes > 0) {
        throw new Exception("Aún hay productos en preparación");
    }

    // validar pagos
    $stmt = $pdo->prepare("
        SELECT COUNT(*) 
        FROM order_details 
        WHERE order_id = ?
        AND estado_pago != 'pagado'
    ");
    $stmt->execute([$order_id]);
    $sinPagar = $stmt->fetchColumn();

    if ($sinPagar > 0) {
        throw new Exception("Aún hay productos sin pagar");
    }

    // cerrar orden
    $pdo->prepare("
        UPDATE orders SET status = 'closed'
        WHERE order_id = ?
    ")->execute([$order_id]);

    // liberar mesa
    $pdo->prepare("
        UPDATE cafe_tables
        SET estado = 'Libre'
        WHERE id_table = (
            SELECT table_id FROM orders WHERE order_id = ?
        )
    ")->execute([$order_id]);

    echo json_encode([
        "error" => 0,
        "message" => "Mesa cerrada correctamente"
    ]);

} catch (Exception $e) {

    echo json_encode([
        "error" => 1,
        "message" => $e->getMessage()
    ]);
}