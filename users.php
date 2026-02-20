<?php
header("Content-Type: application/json");
include('dbconnect.php');
include('auth.php');

// validar token
$currentUser = validateToken($pdo);

// opcional: solo admin puede ver usuarios
if ($currentUser['role'] != 'admin') {
    echo json_encode(["error" => 1, "message" => "No autorizado"]);
    exit;
}

try {

    $stmt = $pdo->prepare("SELECT id, code, name, role, state FROM user");
    $stmt->execute();
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        "error" => 0,
        "data" => $users
    ]);

} catch (PDOException $e) {
    echo json_encode(["error" => 1, "message" => $e->getMessage()]);
}

















