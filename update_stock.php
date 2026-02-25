<?php
// 1. Headers de CORS COMPLETOS
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS, PUT");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");
header("Access-Control-Allow-Credentials: true");

// 2. Responder a la petición de prueba (OPTIONS) - ESTO QUITA EL ERROR DE CORS
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

header("Content-Type: application/json");

// 3. Leer JSON y aplicar parche de sistema
$input = file_get_contents("php://input");
$data = json_decode($input, true);

if (isset($data['system'])) {
    $_POST['system'] = $data['system'];
}

// 4. Conexión
include "dbconnect.php"; // Aquí se crea $pdo

if (!$data) {
    echo json_encode(["success" => false, "error" => "No data"]);
    exit;
}

// 5. Lógica de Update
try {
    $id = $data["id_ingredient"];
    $newStock = $data["stock_act"];

    $sql = "UPDATE ingredients SET stock_act = :stock WHERE id_ingredients = :id";

    // USAMOS $pdo porque así se llama en tu dbconnect.php
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(":stock", $newStock);
    $stmt->bindParam(":id", $id);

    if($stmt->execute()){
        echo json_encode(["success" => true]);
    } else {
        echo json_encode(["success" => false]);
    }
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(["success" => false, "error" => $e->getMessage()]);
}
?>