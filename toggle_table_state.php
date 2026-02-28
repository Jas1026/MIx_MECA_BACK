<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json");
include "dbconnect.php";

$id = $_POST['id_table'] ?? '';
$system = $_POST['system'] ?? '';

try {
    $pdo->exec("USE `$system` "); 

    // 1. Primero consultamos el estado actual de la mesa
    $check = $pdo->prepare("SELECT estado FROM cafe_tables WHERE id_table = ?");
    $check->execute([$id]);
    $mesa = $check->fetch(PDO::FETCH_ASSOC);

    if ($mesa) {
        $estadoActual = $mesa['estado'];
        
        // 2. Definimos el nuevo estado
        // Si está Libre, pasa a Ocupada. Si está Ocupada (o cualquier otro), vuelve a Libre.
        $nuevoEstado = ($estadoActual === 'Libre') ? 'Ocupada' : 'Libre';

        // 3. Actualizamos
        $stmt = $pdo->prepare("UPDATE cafe_tables SET estado = ? WHERE id_table = ?");
        $stmt->execute([$nuevoEstado, $id]);

        echo json_encode([
            "error" => 0, 
            "nuevo_estado" => $nuevoEstado,
            "message" => "Mesa ahora está $nuevoEstado"
        ]);
    } else {
        echo json_encode(["error" => 1, "message" => "Mesa no encontrada"]);
    }

} catch (PDOException $e) {
    echo json_encode(["error" => 1, "message" => $e->getMessage()]);
}