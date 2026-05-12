<?php
header("Content-Type: application/json");
include('dbconnect.php');

$nit = "4309065018";
$codSistemaCorto = "371F7FF1F8FEBAE9488E";
$tokenLargo = "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzUxMiJ9.eyJzdWIiOiJtaXh0dXJhaW1wdWVzdG9zQGdtYWlsLmNvbSIsImNvZGlnb1Npc3RlbWEiOiIzNzFGN0ZGMUY4RkVCQUU5NDg4RSIsIm5pdCI6Ikg0c0lBQUFBQUFBQUFETXhOckEwTURNMU1MUUFBSHNkd2dvS0FBQUEiLCJpZCI6NTEzNjQwMSwiZXhwIjoxODA0OTc3MDY3LCJpYXQiOjE3NzM0NTU0MzcsIm5pdERlbGVnYWRvIjo0MzA5MDY1MDE4LCJzdWJzaXN0ZW1hIjoiU0ZFIn0.Dfqive7zgGp2QtSv2F8NV8MvOOwdwuQ20eJsp6zvoBKWiPSCpwajD8CQJGCOgII7t3RmbgnWxY_jVTRjJP6iGw";

$bases_de_datos = ["mixtura","mecapos"];
$urlCodigos = "https://pilotosiatservicios.impuestos.gob.bo/v2/FacturacionCodigos?wsdl";

$resultados = [];

try {
    if (!class_exists('SoapClient')) {
        throw new Exception("SOAP no está habilitado en PHP");
    }

    // Cabecera HTTP con Authorization
$context = stream_context_create([
    'http' => [
        'header' => "apikey: TokenDelegado $tokenLargo\r\n"
    ]
]);
    $client = new SoapClient($urlCodigos, [
        'cache_wsdl' => WSDL_CACHE_NONE,
        'trace' => 1,
        'exceptions' => true,
        'stream_context' => $context
    ]);

    foreach ($bases_de_datos as $db) {
        try {
            $pdo->exec("USE `$db`");

            $paramsCuis = [
                "SolicitudCuis" => [
                    "codigoAmbiente" => 2,
                    "codigoModalidad" => 2,
                    "codigoSistema" => $codSistemaCorto,
                    "nit" => (int)$nit,
                    "codigoSucursal" => 0,
                    "codigoPuntoVenta" => 0
                ]
            ];

            $resCuis = $client->cuis($paramsCuis);

            if ($resCuis->RespuestaCuis->transaccion) {
                $cuis = $resCuis->RespuestaCuis->codigo;

                $paramsCufd = [
                    "SolicitudCufd" => [
                        "codigoAmbiente" => 2,
                        "codigoModalidad" => 2,
                        "codigoSistema" => $codSistemaCorto,
                        "nit" => (int)$nit,
                        "codigoSucursal" => 0,
                        "codigoPuntoVenta" => 0,
                        "cuis" => $cuis
                    ]
                ];

                $resCufd = $client->cufd($paramsCufd);
                $cufd = $resCufd->RespuestaCufd->codigo;



                
                $fechaVig = str_replace(
                    'T',
                    ' ',
                    substr($resCufd->RespuestaCufd->fechaVigencia,0,19)
                );

                $stmt = $pdo->prepare("
                    UPDATE siat_conexion SET
                    cuis=?,
                    cufd=?,
                    fecha_vigencia_cufd=?,
                    codigo_control_sistema=?,
                    nit_emisor=?
                    WHERE id=1
                ");
                $stmt->execute([$cuis,$cufd,$fechaVig,$codSistemaCorto,$nit]);

                $resultados[$db] = "✅ CUIS generado: $cuis";

            } else {
                $msg = $resCuis->RespuestaCuis->mensajesList->descripcion ?? "Error";
                $resultados[$db] = "❌ SIAT: ".$msg;
            }

        } catch (Exception $e) {
            $resultados[$db] = "❌ Error ($db): ".$e->getMessage();
            $resultados[$db."_request"] = $client->__getLastRequest();
            $resultados[$db."_response"] = $client->__getLastResponse();
            $resultados[$db."_headers"] = $client->__getLastRequestHeaders();
        }
    }

    echo json_encode([
        "status"=>"process_completed",
        "results"=>$resultados
    ],JSON_PRETTY_PRINT);

} catch (Exception $e) {
    echo json_encode([
        "status"=>"critical_error",
        "message"=>$e->getMessage()
    ]);
}
