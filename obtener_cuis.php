<?php
/**
 * REGISTRO DE PUNTO DE VENTA 1 - MODO FORZADO (NO-WSDL)
 */

$token = "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzUxMiJ9.eyJzdWIiOiJtaXh0dXJhaW1wdWVzdG9zQGdtYWlsLmNvbSIsImNvZGlnb1Npc3RlbWEiOiIzNzFGN0ZGMUY4RkVCQUU5NDg4RSIsIm5pdCI6Ikg0c0lBQUFBQUFBQUFETXhOckEwTURNMU1MUUFBSHNkd2dvS0FBQUEiLCJpZCI6NTEzNjQwMSwiZXhwIjoxODA0OTc3MDY3LCJpYXQiOjE3NzM0NTU0MzcsIm5pdERlbGVnYWRvIjo0MzA5MDY1MDE4LCJzdWJzaXN0ZW1hIjoiU0ZFIn0.Dfqive7zgGp2QtSv2F8NV8MvOOwdwuQ20eJsp6zvoBKWiPSCpwajD8CQJGCOgII7t3RmbgnWxY_jVTRjJP6iGw";

// ¡IMPORTANTE! Pega aquí el CUIS que ya tienes del Punto 0 (Casa Matriz)
$cuis_matriz = "PON_AQUI_TU_CUIS_DEL_PUNTO_0"; 

$endpoint = "https://pilotosiatservicios.impuestos.gob.bo/v2/ServiceOperaciones";

try {
    $options = [
        'location' => $endpoint,
        'uri'      => "https://siat.impuestos.gob.bo/", // Namespace obligatorio para el SIAT
        'trace'    => 1,
        'stream_context' => stream_context_create([
            'http' => [
                'header' => "apikey: TokenApi $token\r\nContent-Type: text/xml; charset=utf-8",
                'user_agent' => 'PHPSoapClient'
            ],
            'ssl' => [
                'verify_peer' => false,
                'verify_peer_name' => false
            ]
        ])
    ];

    // Iniciamos el cliente en modo NULL (No-WSDL)
    $client = new SoapClient(null, $options);

    $datos = [
        'codigoAmbiente'       => 2,
        'codigoModalidad'      => 2,
        'codigoSistema'        => '371F7FF1F8FEBAE9488E',
        'codigoSucursal'       => 0,
        'codigoTipoPuntoVenta' => 5, // Punto de Venta Fijo
        'cuis'                 => $cuis_matriz,
        'descripcion'          => 'Punto 1 Certificacion',
        'nit'                  => 4309065018,
        'nombrePuntoVenta'     => 'Punto de Venta 1'
    ];

    // Empaquetamos el objeto con el nombre que el SIAT espera
    $params = new SoapVar($datos, SOAP_ENC_OBJECT, null, null, 'SolicitudRegistroPuntoVenta', "https://siat.impuestos.gob.bo/");

    $res = $client->__soapCall('registroPuntoVenta', [$params]);

    echo "<h1>Estado de Registro</h1>";

    if (isset($res->RespuestaRegistroPuntoVenta->transaccion) && $res->RespuestaRegistroPuntoVenta->transaccion == "true") {
        echo "<div style='color:green; font-weight:bold;'>";
        echo "✅ ¡PUNTO 1 CREADO EXITOSAMENTE!<br>";
        echo "Código: " . $res->RespuestaRegistroPuntoVenta->codigoPuntoVenta;
        echo "</div>";
        echo "<p>Ahora ya puedes ir al script de CUIS y te dará el código sin errores.</p>";
    } else {
        $msg = $res->RespuestaRegistroPuntoVenta->mensajesList;
        $error = is_array($msg) ? $msg[0]->descripcion : $msg->descripcion;
        echo "<div style='color:red;'>❌ ERROR SIAT: " . $error . "</div>";
    }

} catch (Exception $e) {
    echo "<div style='color:orange;'>⚠️ ERROR TÉCNICO: " . $e->getMessage() . "</div>";
}
?>