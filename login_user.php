<?php
ini_set('display_errors', 0);
error_reporting(0);

header("Access-Control-Allow-Origin: http://localhost:8101");
header("Access-Control-Allow-Headers: Content-Type, Authorization, System");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Allow-Credentials: true");
header("Content-Type: application/json");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

include('dbconnect.php');

$code = $_POST['code'] ?? '';
$password = $_POST['password'] ?? '';

if (empty($code) || empty($password)) {
    echo json_encode(["error" => 1, "message" => "Datos incompletos"]);
    exit;
}

try {

    $stmt = $pdo->prepare("SELECT * FROM user WHERE code = ? AND state = 1");
    $stmt->execute([$code]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        echo json_encode(["error" => 1, "message" => "Usuario no encontrado"]);
        exit;
    }


    if ($password !== $user['password']) {

        echo json_encode(["error" => 1, "message" => "Contraseña incorrecta"]);
        exit;
    }

    echo json_encode([
        "error" => 0,
        "id" => $user['id'],
        "name" => $user['name'],
    ]);

} catch (PDOException $e) {
    echo json_encode(["error" => 1, "message" => "Error interno"]);
}