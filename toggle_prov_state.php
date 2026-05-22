<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json");

include "dbconnect.php";

$id = $_POST['id_proveedor'] ?? '';
$estado = $_POST['estado'] ?? '';
$system = $_POST['system'] ?? 'mecapos';

try {

    $pdo->exec("USE `$system`");

    $stmt = $pdo->prepare("
        UPDATE proveedor
        SET estado = ?
        WHERE id_proveedor = ?
    ");

    $stmt->execute([
        $estado,
        $id
    ]);

    echo json_encode([
        "error" => 0,
        "message" => "Estado actualizado"
    ]);

} catch (PDOException $e) {

    echo json_encode([
        "error" => 1,
        "message" => $e->getMessage()
    ]);
}
?>