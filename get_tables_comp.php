<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json");
include "dbconnect.php";

$system = $_GET['system'] ?? 'mecapos';
try {
    $pdo->exec("USE `$system` "); 
    // Join con flats para saber el nombre del piso
   $sql = "

SELECT

t.*,

f.Name as flat_name

FROM cafe_tables t

LEFT JOIN flats f

ON t.id_flats = f.Id_flats

WHERE t.estado <> 'Eliminada'

ORDER BY t.id_table DESC

";
    $stmt = $pdo->query($sql);
    echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
} catch (PDOException $e) {
    echo json_encode(["error" => $e->getMessage()]);
}