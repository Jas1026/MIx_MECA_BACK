<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json");

include "dbconnect.php";

$id       = $_POST['id'] ?? null;
$role     = $_POST['role'] ?? '';
$name     = $_POST['name'] ?? '';
$code     = $_POST['code'] ?? '';
$password = $_POST['password'] ?? '';
$system   = $_POST['system'] ?? '';

if (empty($system)) {
    echo json_encode([
        "error" => 1,
        "message" => "Sistema no especificado"
    ]);
    exit;
}

try {

    // Seleccionar base de datos
    $pdo->exec("USE `$system`");

    if ($id) {

        // =========================
        // MODO EDICIÓN
        // =========================

        if (!empty($password)) {

            $stmt = $pdo->prepare("
                UPDATE user 
                SET role = ?, name = ?, code = ?, password = ?
                WHERE id = ?
            ");

            $stmt->execute([
                $role,
                $name,
                $code,
                $password,
                $id
            ]);

        } else {

            $stmt = $pdo->prepare("
                UPDATE user 
                SET role = ?, name = ?, code = ?
                WHERE id = ?
            ");

            $stmt->execute([
                $role,
                $name,
                $code,
                $id
            ]);
        }

    } else {

        // =========================
        // MODO CREACIÓN
        // =========================

        $stmt = $pdo->prepare("
            INSERT INTO user
            (role, name, code, password, state, logged)
            VALUES (?, ?, ?, ?, 1, 0)
        ");

        $stmt->execute([
            $role,
            $name,
            $code,
            $password
        ]);
    }

    echo json_encode([
        "error" => 0
    ]);

} catch (PDOException $e) {

    echo json_encode([
        "error" => 1,
        "message" => $e->getMessage()
    ]);
}