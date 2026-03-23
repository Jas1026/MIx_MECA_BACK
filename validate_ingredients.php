<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
 
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json");
 
include('dbconnect.php');

$products = json_decode($_POST['products'], true) ?? [];
$system = $_POST['system'] ?? 'mixtura';

try {
    $pdo->exec("USE `$system` ");
    
    foreach ($products as $p) {
        // Buscamos los ingredientes de cada producto del carrito
        $stmt = $pdo->prepare("
            SELECT pi.id_ingredient, pi.cant_us, i.tipo, i.nombre 
            FROM product_ingredient pi 
            INNER JOIN ingredients i ON pi.id_ingredient = i.id_ingredients 
            WHERE pi.id_product = ?
        ");
        $stmt->execute([$p['id_product']]);
        $receta = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($receta as $ing) {
            $necesario = (float)$ing['cant_us'] * (int)$p['quantity'];

            if ($ing['tipo'] === 'botella') {
                // Sumamos el contenido de TODAS las botellas abiertas de ese ingrediente
                $stmtSum = $pdo->prepare("SELECT SUM(peso_actual) as total FROM ingredient_bottles WHERE ingredient_id = ? AND estado = 'abierta'");
                $stmtSum->execute([$ing['id_ingredient']]);
                $disponible = (float)$stmtSum->fetch(PDO::FETCH_ASSOC)['total'];
            } else {
                // Ingrediente normal
                $stmtIng = $pdo->prepare("SELECT stock_act FROM ingredients WHERE id_ingredients = ?");
                $stmtIng->execute([$ing['id_ingredient']]);
                $disponible = (float)$stmtIng->fetch(PDO::FETCH_ASSOC)['stock_act'];
            }

            if ($disponible < $necesario) {
                echo json_encode([
                    "error" => 3, 
                    "message" => "Insumos insuficientes para '" . $p['name'] . "'. Falta: " . $ing['nombre'] . " (Disponible: $disponible)"
                ]);
                exit;
            }
        }
    }
    echo json_encode(["error" => 0, "message" => "Stock OK"]);
} catch (Exception $e) {
    echo json_encode(["error" => 1, "message" => $e->getMessage()]);
}