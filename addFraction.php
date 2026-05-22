<?php

ini_set('display_errors', 1);
error_reporting(E_ALL);

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Content-Type: application/json");

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    exit(0);
}

/*
|--------------------------------------------------------------------------
| LEER JSON
|--------------------------------------------------------------------------
*/

$data = json_decode(file_get_contents("php://input"), true);

if (!$data) {

    echo json_encode([
        "error" => 1,
        "message" => "No llegaron datos"
    ]);

    exit;
}

/*
|--------------------------------------------------------------------------
| PASAR SYSTEM A dbconnect
|--------------------------------------------------------------------------
*/

if (isset($data['system'])) {
    $_POST['system'] = $data['system'];
}

/*
|--------------------------------------------------------------------------
| CONEXION
|--------------------------------------------------------------------------
*/

include "dbconnect.php";

/*
|--------------------------------------------------------------------------
| DATOS
|--------------------------------------------------------------------------
*/

$ingredient_id = intval($data['ingredient_id'] ?? 0);

$cantidad_total = intval($data['cantidad_total'] ?? 0);

$cantidad_actual = intval(
    $data['cantidad_actual'] ?? $cantidad_total
);

$descripcion = trim($data['descripcion'] ?? '');

$location_id = (
    isset($data['location_id']) &&
    $data['location_id'] !== ''
)
? intval($data['location_id'])
: null;

/*
|--------------------------------------------------------------------------
| ESTADO
|--------------------------------------------------------------------------
*/

$estado = ($cantidad_actual <= 0)
    ? 'agotado'
    : 'abierto';

/*
|--------------------------------------------------------------------------
| VALIDACIONES
|--------------------------------------------------------------------------
*/

if (!$ingredient_id) {

    echo json_encode([
        "error" => 1,
        "message" => "ingredient_id faltante"
    ]);

    exit;
}

if (!$cantidad_total) {

    echo json_encode([
        "error" => 1,
        "message" => "cantidad_total faltante"
    ]);

    exit;
}

/*
|--------------------------------------------------------------------------
| INSERT
|--------------------------------------------------------------------------
*/

try {

    $sql = "
    INSERT INTO ingredient_fractions (

        ingredient_id,
        cantidad_total,
        cantidad_actual,
        estado,
        descripcion,
        location_id

    )
    VALUES (

        :ingredient_id,
        :cantidad_total,
        :cantidad_actual,
        :estado,
        :descripcion,
        :location_id

    )
    ";

    $stmt = $pdo->prepare($sql);

    $stmt->execute([

        ":ingredient_id" => $ingredient_id,
        ":cantidad_total" => $cantidad_total,
        ":cantidad_actual" => $cantidad_actual,
        ":estado" => $estado,
        ":descripcion" => $descripcion,
        ":location_id" => $location_id

    ]);

    echo json_encode([
        "error" => 0,
        "message" => "✅ Fracción registrada"
    ]);

} catch (PDOException $e) {

    echo json_encode([
        "error" => 1,
        "message" => $e->getMessage()
    ]);

}