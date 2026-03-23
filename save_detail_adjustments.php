<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit;
}

require_once 'dbconnect.php';

try {
    // Recibimos por POST normal (FormData)
    $detail_id = $_POST['detail_id'] ?? null;
    $system = $_POST['system'] ?? null;
    $adjustments_raw = $_POST['adjustments'] ?? '[]';
    
    // Decodificamos el string JSON que viene en el FormData
    $adjustments = json_decode($adjustments_raw, true);

    if (!$detail_id || !$system) {
        throw new Exception("Faltan datos obligatorios (detail_id o system)");
    }

    $pdo->exec("USE `$system` ");
    $pdo->beginTransaction();

    foreach ($adjustments as $adj) {
        $id = $adj['ingredient_id'];
        $qty = floatval($adj['qty']);

        if (!$id) continue;

        // 1. Validar Stock
        $st = $pdo->prepare("SELECT stock_act, nombre FROM ingredients WHERE id_ingredients = ?");
        $st->execute([$id]);
        $ing = $st->fetch(PDO::FETCH_ASSOC);

        if ($qty > 0 && $ing['stock_act'] < $qty) {
            throw new Exception("Stock insuficiente para: " . $ing['nombre']);
        }

        // 2. Insertar historial de ajuste
        $stmt = $pdo->prepare("INSERT INTO order_detail_adjustments (detail_id, ingredient_id, adjustment_qty) VALUES (?, ?, ?)");
        $stmt->execute([$detail_id, $id, $qty]);

        // 3. Descontar stock real
        $upd = $pdo->prepare("UPDATE ingredients SET stock_act = stock_act - ? WHERE id_ingredients = ?");
        $upd->execute([$qty, $id]);
    }

    $pdo->commit();
    echo json_encode(["error" => 0, "message" => "Ajustes guardados con éxito"]);

} catch (Throwable $e) {
    if (isset($pdo) && $pdo->inTransaction()) $pdo->rollBack();
    echo json_encode(["error" => 1, "message" => $e->getMessage()]);
}