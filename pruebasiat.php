<?php
$token = "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzUxMiJ9.eyJzdWIiOiJtaXh0dXJhaW1wdWVzdG9zQGdtYWlsLmNvbSIsImNvZGlnb1Npc3RlbWEiOiIzNzFGN0ZGMUY4RkVCQUU5NDg4RSIsIm5pdCI6Ikg0c0lBQUFBQUFBQUFETXhOckEwTURNMU1MUUFBSHNkd2dvS0FBQUEiLCJpZCI6NTEzNjQwMSwiZXhwIjoxODA0OTc3MDY3LCJpYXQiOjE3NzM0NTU0MzcsIm5pdERlbGVnYWRvIjo0MzA5MDY1MDE4LCJzdWJzaXN0ZW1hIjoiU0ZFIn0.Dfqive7zgGp2QtSv2F8NV8MvOOwdwuQ20eJsp6zvoBKWiPSCpwajD8CQJGCOgII7t3RmbgnWxY_jVTRjJP6iGw";

// IMPORTANTE: El WSDL de Piloto
$wsdl = "https://pilotosiatservicios.impuestos.gob.bo/v2/FacturacionCodigos?wsdl";

$opts = [
    'http' => [
        'header' => "apikey: TokenApi $token\r\n" . // ESTE ES EL HEADER CORRECTO
                    "Authorization: TokenApi $token\r\n"
    ],
    'ssl' => [
        'verify_peer' => false,
        'verify_peer_name' => false,
    ]
];

$context = stream_context_create($opts);

try {
    $client = new SoapClient($wsdl, [
        'stream_context' => $context,
        'cache_wsdl' => WSDL_CACHE_NONE,
        'trace' => 1,
        'exceptions' => true,
        // IMPORTANTE: Location de PILOTO
        'location' => "https://pilotosiatservicios.impuestos.gob.bo/v2/FacturacionCodigos"
    ]);

    echo "<h2>1. Verificando Comunicación...</h2>";
    $ping = $client->verificarComunicacion();
    print_r($ping);

    echo "<h2>2. Solicitando CUIS...</h2>";
$params = [
    'SolicitudCuis' => [
        'codigoAmbiente'   => 2, // CAMBIA DE 1 A 2 (Sigue siendo Piloto por la URL)
        'codigoModalidad'  => 2, 
        'codigoPuntoVenta' => 0,
        'codigoSistema'    => "371F7FF1F8FEBAE9488E",
        'codigoSucursal'   => 0,
        'nit'              => 4309065018
    ]
];




    $response = $client->cuis($params);
    echo "<pre>"; print_r($response); echo "</pre>";

} catch (SoapFault $e) {
    echo "<h2 style='color:red;'>❌ Error: " . $e->getMessage() . "</h2>";
    echo "<b>Headers enviados:</b><pre>" . htmlspecialchars($client->__getLastRequestHeaders()) . "</pre>";
    echo "<b>Respuesta cruda:</b><pre>" . htmlspecialchars($client->__getLastResponse()) . "</pre>";
}
