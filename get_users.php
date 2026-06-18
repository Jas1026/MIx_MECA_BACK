<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json");
include "dbconnect.php";

$system = $_GET['system'] ?? '';
try {
    $pdo->exec("USE `$system` "); 
$sql = "
SELECT *
FROM user
WHERE state IN (0,1)
ORDER BY id DESC
";
    $stmt = $pdo->query($sql);
    echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
} catch (PDOException $e) {
    echo json_encode(["error" => $e->getMessage()]);
}