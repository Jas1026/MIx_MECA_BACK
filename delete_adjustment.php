<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') exit;

require_once 'dbconnect.php';

try {
    $detail_id = $_POST['detail_id'] ?? null;
    $ingredient_id = $_POST['ingredient_id'] ?? null;
    $system = $_POST['system'] ?? null;

    if (!$detail_id || !$ingredient_id || !$system) throw new Exception("Datos incompletos");

    $pdo->exec("USE `$system` ");
    $pdo->beginTransaction();

    // 1. Obtener la cantidad que se había ajustado para devolverla al stock
    $st = $pdo->prepare("SELECT adjustment_qty FROM order_detail_adjustments WHERE detail_id = ? AND ingredient_id = ?");
    $st->execute([$detail_id, $ingredient_id]);
    $adj = $st->fetch(PDO::FETCH_ASSOC);

    if ($adj) {
        $qty_to_restore = $adj['adjustment_qty'];

        // 2. Devolver al stock principal
        $upd = $pdo->prepare("UPDATE ingredients SET stock_act = stock_act + ? WHERE id_ingredients = ?");
        $upd->execute([$qty_to_restore, $ingredient_id]);

        // 3. Borrar el registro del ajuste
        $del = $pdo->prepare("DELETE FROM order_detail_adjustments WHERE detail_id = ? AND ingredient_id = ?");
        $del->execute([$detail_id, $ingredient_id]);
    }

    $pdo->commit();
    echo json_encode(["error" => 0, "message" => "Ajuste eliminado y stock restaurado"]);

} catch (Throwable $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    echo json_encode(["error" => 1, "message" => $e->getMessage()]);
}