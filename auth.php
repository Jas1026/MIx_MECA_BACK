<?php
function validateToken($pdo) {

    $headers = getallheaders();
    $token = $headers['Authorization'] ?? '';

    if (!$token) {
        echo json_encode(["error" => 1, "message" => "Token requerido"]);
        exit;
    }

    $token = str_replace("Bearer ", "", $token);

    $stmt = $pdo->prepare("SELECT * FROM user WHERE api_token = ?");
    $stmt->execute([$token]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        echo json_encode(["error" => 1, "message" => "Token inválido"]);
        exit;
    }

    return $user; // retorna usuario autenticado
}