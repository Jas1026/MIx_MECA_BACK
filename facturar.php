<?php
ob_start(); 
header("Access-Control-Allow-Origin: http://localhost:8100");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, system");
header("Access-Control-Allow-Credentials: true");

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    header("HTTP/1.1 200 OK");
    ob_end_clean();
    exit;
}

include('./dbconnect.php'); 
require_once 'SiatFunctions.php';

$json = file_get_contents('php://input');
$data = json_decode($json);

// --- CONFIGURACIÓN QUE TE FUNCIONÓ ---
$config = [
    "token" => "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzUxMiJ9.eyJzdWIiOiJtaXh0dXJhaW1wdWVzdG9zQGdtYWlsLmNvbSIsImNvZGlnb1Npc3RlbWEiOiIzNzFGN0ZGMUY4RkVCQUU5NDg4RSIsIm5pdCI6Ikg0c0lBQUFBQUFBQUFETXhOckEwTURNMU1MUUFBSHNkd2dvS0FBQUEiLCJpZCI6NTEzNjQwMSwiZXhwIjoxODA0OTc3MDY3LCJpYXQiOjE3NzM0NTU0MzcsIm5pdERlbGVnYWRvIjo0MzA5MDY1MDE4LCJzdWJzaXN0ZW1hIjoiU0ZFIn0.Dfqive7zgGp2QtSv2F8NV8MvOOwdwuQ20eJsp6zvoBKWiPSCpwajD8CQJGCOgII7t3RmbgnWxY_jVTRjJP6iGw",
    "nit" => 4309065018,
    "codigoSistema" => "371F7FF1F8FEBAE9488E",
    "cuis" => "2FF14015", 
    "ambiente" => 2, // Opción B exitosa
    "modalidad" => 2,
    "sucursal" => 0,
    "puntoVenta" => 0
];

try {
    $siat = new SiatFunctions($config);
    $res = $siat->obtenerCufd();
    
    // El SIAT devuelve RespuestaCufd
    if ($res->RespuestaCufd->transaccion) {
        echo json_encode([
            "success" => true,
            "message" => "CUFD Generado",
            "data" => [
                "codigo" => $res->RespuestaCufd->codigo,
                "codigoControl" => $res->RespuestaCufd->codigoControl,
                "fechaVigencia" => $res->RespuestaCufd->fechaVigencia
            ]
        ]);
    } else {
        echo json_encode([
            "success" => false, 
            "message" => $res->RespuestaCufd->mensajesList->descripcion
        ]);
    }

} catch (Exception $e) {
    echo json_encode([
        "success" => false,
        "message" => "Error: " . $e->getMessage()
    ]);
}

ob_end_flush();
