<?php

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
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
        "error" => "No data received"
    ]);

    exit;
}

try {

    $check = $pdo->prepare("
        SELECT id_ingredients
        FROM ingredients
        WHERE nombre = :nombre
        LIMIT 1
    ");

    $check->execute([
        ":nombre" => $data["nombre"]
    ]);

    if ($check->fetch()) {

        echo json_encode([
            "success" => false,
            "error" => "El ingrediente ya existe"
        ]);

        exit;
    }

    $tipo = $data["tipo"] ?? "normal";

    // SOLO normal usa stock manual
    $stock = ($tipo === 'normal')
        ? ($data["stock_act"] ?? 0)
        : 0;

    // botella usa gramos obligatoriamente
    $unidad = ($tipo === 'botella')
        ? 'g'
        : ($data["unidad_med"] ?? '');

    $sql = "
        INSERT INTO ingredients
        (
            nombre,
            stock_act,
            unidad_med,
            tipo,
            peso_envase,
            peso_actual,
            capacidad_total,
            location_id,
            proveedor_id
        )
        VALUES
        (
            :nombre,
            :stock,
            :unidad,
            :tipo,
            :peso_envase,
            :peso_actual,
            :capacidad_total,
            :location_id,
            :proveedor_id
        )
    ";

    $stmt = $pdo->prepare($sql);

    $stmt->execute([

        ":nombre"          => $data["nombre"] ?? null,

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
        "success" => true
    ]);

} catch (PDOException $e) {

    http_response_code(500);

    echo json_encode([
        "success" => false,
        "error" => $e->getMessage()
    ]);

}
?>