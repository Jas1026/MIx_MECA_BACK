<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') { 
    exit; 
}

include("dbconnect.php");

// 📥 RECIBIR DATOS
$ingredient_id   = $_POST['ingredient_id']   ?? null;
$peso_envase     = $_POST['peso_envase']     ?? 0;
$capacidad_total = $_POST['capacidad_total'] ?? 0;
$peso_actual     = $_POST['peso_actual']     ?? 0;
$cantidad        = intval($_POST['cantidad'] ?? 1);
$location_id     = $_POST['location_id']     ?? null; // 👈 NUEVO
$system          = $_POST['system']          ?? 'mecapos';

// ❌ VALIDACIÓN
if(!$ingredient_id){
    echo json_encode([
        "error" => 1, 
        "message" => "Falta ID del ingrediente"
    ]);
    exit;
}

try {
    // 🔁 Seleccionar base de datos dinámica
    $pdo->exec("USE `$system` ");

    // 🧾 QUERY CON LOCATION
    $stmt = $pdo->prepare("
        INSERT INTO ingredient_bottles 
        (ingredient_id, peso_envase, capacidad_total, peso_actual, estado, location_id) 
        VALUES (?, ?, ?, ?, 'abierta', ?)
    ");

    // 🔒 TRANSACCIÓN (IMPORTANTE)
    $pdo->beginTransaction();

    for ($i = 0; $i < $cantidad; $i++) {
        $stmt->execute([
            $ingredient_id,
            $peso_envase,
            $capacidad_total,
            $peso_actual,
            $location_id // 👈 AQUÍ SE GUARDA
        ]);
    }

    $pdo->commit();

    echo json_encode([
        "error" => 0,
        "message" => "Insertadas $cantidad botellas correctamente",
        "location_id_usado" => $location_id // debug opcional
    ]);

} catch(Exception $e) {

    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    echo json_encode([
        "error" => 1,
        "message" => $e->getMessage()
    ]);
}
?>