<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json; charset=UTF-8");

include "dbconnect.php";

// 1. Detectar el sistema dinámicamente
$system = $_GET['system'] ?? 'mixtura';
$id_product = $_GET['id_product'] ?? null;

if (!$id_product) {
    echo json_encode(["error" => 1, "message" => "ID de producto no proporcionado"]);
    exit;
}

try {
    // 2. Forzar el uso de la base de datos correcta
    $pdo->exec("USE `$system` ");

    // 3. Consulta con JOIN para traer los nombres reales de TU tabla 'ingredients'
    $sql = "SELECT 
                pi.id_ingredient, 
                pi.cant_us, 
                i.nombre, 
                i.unidad_med 
            FROM product_ingredient pi
            INNER JOIN ingredients i ON pi.id_ingredient = i.id_ingredients
            WHERE pi.id_product = :id";

    $stmt = $pdo->prepare($sql);
    $stmt->execute(['id' => $id_product]);
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // 4. Retornar los datos reales
    echo json_encode([
        "error" => 0, 
        "data" => $data,
        "debug_system" => $system // Para que verifiques en el console.log qué BD usó
    ]);

} catch (PDOException $e) {
    echo json_encode(["error" => 1, "message" => $e->getMessage()]);
}
?>