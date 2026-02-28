<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Content-Type: application/json");

include('dbconnect.php');

// Recibimos los datos
$nombre    = $_POST['nombre'] ?? null;
$capacidad = $_POST['capacidad'] ?? null;
$id_flats  = $_POST['id_flats'] ?? null;
$system    = $_POST['system'] ?? null;
$id_table  = $_POST['id_table'] ?? null; // Solo viene si editamos
$estado    = $_POST['estado'] ?? 'Libre';

// VALIDACIÓN DETALLADA: Esto nos dirá qué falta
if (!$nombre || !$capacidad || !$id_flats || !$system) {
    $faltantes = [];
    if (!$nombre) $faltantes[] = "nombre";
    if (!$capacidad) $faltantes[] = "capacidad";
    if (!$id_flats) $faltantes[] = "id_flats";
    if (!$system) $faltantes[] = "system";
    
    echo json_encode([
        "error" => 1, 
        "message" => "Datos incompletos: Faltan campos (" . implode(", ", $faltantes) . ")"
    ]);
    exit;
}

try {
    $pdo->exec("USE `$system` ");

    if ($id_table) {
        // MODO EDICIÓN
        $stmt = $pdo->prepare("UPDATE cafe_tables SET nombre=?, capacidad=?, id_flats=? WHERE id_table=?");
        $stmt->execute([$nombre, $capacidad, $id_flats, $id_table]);
        $msg = "Mesa actualizada";
    } else {
        // MODO CREACIÓN
        $stmt = $pdo->prepare("INSERT INTO cafe_tables (nombre, capacidad, id_flats, estado) VALUES (?, ?, ?, ?)");
        $stmt->execute([$nombre, $capacidad, $id_flats, $estado]);
        $msg = "Mesa creada";
    }

    echo json_encode(["error" => 0, "message" => $msg]);

} catch (PDOException $e) {
    echo json_encode(["error" => 1, "message" => "Error de BD: " . $e->getMessage()]);
}