<?php

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS, PUT");
header("Access-Control-Allow-Headers: Content-Type");
header("Access-Control-Allow-Credentials: true");

$input = file_get_contents("php://input");
$data = json_decode($input, true);

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

if (isset($data['system'])) {
    $_POST['system'] = $data['system'];
}

header("Content-Type: application/json");

include "dbconnect.php";

if (!$data) {
    echo json_encode([
        "success" => false,
        "error" => "No se recibieron datos"
    ]);
    exit;
}

if (!isset($data["id_ingredients"])) {
    echo json_encode([
        "success" => false,
        "error" => "ID faltante"
    ]);
    exit;
}

try {

    $tipo = $data["tipo"] ?? "normal";

    // IMPORTANTE:
    // botella y fraccionado NO usan stock manual
    $stock = ($tipo === 'normal')
        ? ($data["stock_act"] ?? 0)
        : 0;

    // botella SIEMPRE gramos
    $unidad = ($tipo === 'botella')
        ? 'g'
        : ($data["unidad_med"] ?? '');

    $sql = "
        UPDATE ingredients
        SET
            nombre = :nombre,
            stock_act = :stock,
            unidad_med = :unidad,
            tipo = :tipo,
            peso_envase = :peso_envase,
            peso_actual = :peso_actual,
            capacidad_total = :capacidad_total,
            location_id = :location_id,
            proveedor_id = :proveedor_id
        WHERE id_ingredients = :id
    ";

    $stmt = $pdo->prepare($sql);

    $stmt->execute([

        ":id"              => $data["id_ingredients"],

        ":nombre"          => $data["nombre"] ?? "",

        ":stock"           => $stock,

        ":unidad"          => $unidad,

        ":tipo"            => $tipo,

        ":peso_envase"     => $data["peso_envase"] ?? null,

        ":peso_actual"     => $data["peso_actual"] ?? null,

        ":capacidad_total" => $data["capacidad_total"] ?? null,

        ":location_id"     => $data["location_id"] ?? null,

        ":proveedor_id"    => $data["provider_id"] ?? null

    ]);

    echo json_encode([
        "success" => true,
        "message" => "Actualizado correctamente"
    ]);

} catch (PDOException $e) {

    http_response_code(500);

    echo json_encode([
        "success" => false,
        "error" => $e->getMessage()
    ]);

}
?>