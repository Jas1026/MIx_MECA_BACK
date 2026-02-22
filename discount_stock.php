<?php
include('dbconnect.php');

$detail_id = $_POST['detail_id'];

try {

    $pdo->beginTransaction();

    $stmt = $pdo->prepare("
        SELECT product_id, quantity
        FROM order_details
        WHERE detail_id = ?
    ");
    $stmt->execute([$detail_id]);
    $detail = $stmt->fetch();

    $product_id = $detail['product_id'];
    $qty = $detail['quantity'];

    $stmtIng = $pdo->prepare("
        SELECT id_ingredient, cant_us
        FROM product_ingredient
        WHERE id_product = ?
    ");
    $stmtIng->execute([$product_id]);

    while ($ing = $stmtIng->fetch()) {

        $total_used = $ing['cant_us'] * $qty;

        $pdo->prepare("
            UPDATE ingredients
            SET stock_act = stock_act - ?
            WHERE id_ingredients = ?
        ")->execute([$total_used, $ing['id_ingredient']]);
    }

    $pdo->commit();

    echo json_encode(["error"=>0]);

} catch (Exception $e) {
    $pdo->rollBack();
    echo json_encode(["error"=>1]);
}