<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json");

require_once 'dbconnect.php';
try {
    // Cambiamos la consulta para que busque empleados/meseros
    // Ajusta 'users' y 'username' según los nombres reales de tu tabla
    $stmt = $pdo->query("
        SELECT id,code, name AS nombre 
        FROM user 

        ORDER BY name ASC
    ");

    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        "error" => 0,
        "data" => $data
    ]);
} 
catch (PDOException $e) {

    echo json_encode([
        "error" => 1,
        "message" => $e->getMessage()
    ]);
}