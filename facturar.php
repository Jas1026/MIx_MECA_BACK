


    <?php
// 1. CABECERAS CORS AGRESIVAS (Deben ir al principio del archivo)
header("Access-Control-Allow-Origin: http://localhost:8100");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS, PUT, DELETE");
header("Access-Control-Allow-Headers: Content-Type, Authorization, system, X-Requested-With");
header("Access-Control-Allow-Credentials: true");

// 2. RESPONDER AL PREFLIGHT (Obligatorio para Angular)
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    header("HTTP/1.1 200 OK");
    exit();
}

// 3. CARGAR CONEXIÓN Y LÓGICA
ob_start(); // Atrapa cualquier warning de PHP para que no dañe el JSON
include('dbconnect.php'); 
require_once 'SiatFunctions.php';

// 4. CONFIGURACIÓN COMPLETA (SIAT + FIRMA)
$config = [
        "token" => "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzUxMiJ9.eyJzdWIiOiJtaXh0dXJhaW1wdWVzdG9zQGdtYWlsLmNvbSIsImNvZGlnb1Npc3RlbWEiOiIzNzFGN0ZGMUY4RkVCQUU5NDg4RSIsIm5pdCI6Ikg0c0lBQUFBQUFBQUFETXhOckEwTURNMU1MUUFBSHNkd2dvS0FBQUEiLCJpZCI6NTEzNjQwMSwiZXhwIjoxODA0OTc3MDY3LCJpYXQiOjE3NzM0NTU0MzcsIm5pdERlbGVnYWRvIjo0MzA5MDY1MDE4LCJzdWJzaXN0ZW1hIjoiU0ZFIn0.Dfqive7zgGp2QtSv2F8NV8MvOOwdwuQ20eJsp6zvoBKWiPSCpwajD8CQJGCOgII7t3RmbgnWxY_jVTRjJP6iGw",
  
    "nit" => 4309065018,
    "codigoSistema" => "371F7FF1F8FEBAE9488E",
    "cuis" => "2FF14015", 
    "ambiente" => 2,
    "modalidad" => 2,
    "sucursal" => 0,
    "puntoVenta" => 0,
    "firma" => [
        "archivo" => __DIR__ . DIRECTORY_SEPARATOR . "VALERIA ALEJANDRA SALINAS CAMACHO.p12",
        "pass" => "4309065"
    ]
];

// 5. PROCESAR PETICIÓN DE ANGULAR
$json = file_get_contents('php://input');
$data = (object)[
    "razonSocial" => "CLIENTE PRUEBA",
    "nit" => "1234567",
    "metodoPago" => 1,
    "montoTotal" => 100,
    "detalles" => [
        (object)[
            "descripcion" => "Pizza",
            "cantidad" => 2,
            "precio" => 50
        ]
    ]
]; 
//$data = json_decode($json);


if (!$data) {
    echo json_encode(["success" => false, "message" => "No se recibieron datos"]);
    exit;
}

try {
    $siat = new SiatFunctions($config);
    
    // Verificamos que la firma cargue correctamente
    $siat->verificarFirma(); 
    
// Obtenemos el CUFD
$resCufd = $siat->obtenerCufd();

$cufd = $resCufd->RespuestaCufd->codigo;

// Número factura temporal
$numFactura = rand(1, 9999);

// Fecha para CUF
$fecha = date("YmdHisv");

// Generamos CUF temporal
$cuf = $siat->calcularCuf(
    $numFactura,
    $fecha,
    $cufd
);

// GENERAMOS XML
$xml = $siat->generarXmlFactura(
    $data,
    $cuf,
    $cufd
);
$xmlFirmado = $siat->firmarXml($xml);
$respuestaSiat = $siat->enviarFactura(
    $xmlFirmado,
    $cufd
);
$dom = new DOMDocument();
$dom->preserveWhiteSpace = false;
$dom->formatOutput = false;

$dom->loadXML($xmlFirmado);

$xmlFirmado = $dom->saveXML();
file_put_contents(
    "factura_firmada.xml",
    $xmlFirmado
);
// GUARDAMOS XML EN ARCHIVO
file_put_contents("factura.xml", $xml);

// RESPUESTA
echo json_encode([
    "success" => true,
    "respuestaSiat" => $respuestaSiat
]);

} catch (Exception $e) {
    echo json_encode([
        "success" => false,
        "message" => $e->getMessage()
    ]);
}
ob_end_flush();
