<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Content-Type: application/json; charset=UTF-8");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once 'dbconnect.php';

try {

    $input = json_decode(file_get_contents("php://input"), true);

    $detail_id = $input['detail_id'] ?? null;
    $adjustments = $input['adjustments'] ?? [];
    $system = $input['system'] ?? ($_POST['system'] ?? null);

    if (!$detail_id) {
        throw new Exception("detail_id requerido");
    }

    if (!$system) {
        throw new Exception("system requerido");
    }

    // 🔥 Cambiar base de datos según sistema
    $pdo->exec("USE `$system`");

    $pdo->beginTransaction();

    foreach ($adjustments as $adj) {

        $ingredient_id = $adj['ingredient_id'] ?? null;
        $qty = $adj['qty'] ?? 0;

        if (!$ingredient_id) continue;

        $stmt = $pdo->prepare("
            INSERT INTO order_detail_adjustments
            (detail_id, ingredient_id, adjustment_qty)
            VALUES (?, ?, ?)
        ");

        $stmt->execute([
            $detail_id,
            $ingredient_id,
            $qty
        ]);
    }

    $pdo->commit();

    echo json_encode([
        "error" => 0,
        "message" => "Ajustes guardados correctamente"
    ]);

} catch (Throwable $e) {

    if ($pdo && $pdo->inTransaction()) {
        $pdo->rollBack();
    }

    echo json_encode([
        "error" => 1,
        "message" => $e->getMessage()
    ]);
}