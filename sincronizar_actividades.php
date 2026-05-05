<?php
/**
 * ETAPA II: SINCRONIZACIÓN DE ACTIVIDADES
 */

$token = "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzUxMiJ9.eyJzdWIiOiJtaXh0dXJhaW1wdWVzdG9zQGdtYWlsLmNvbSIsImNvZGlnb1Npc3RlbWEiOiIzNzFGN0ZGMUY4RkVCQUU5NDg4RSIsIm5pdCI6Ikg0c0lBQUFBQUFBQUFETXhOckEwTURNMU1MUUFBSHNkd2dvS0FBQUEiLCJpZCI6NTEzNjQwMSwiZXhwIjoxODA0OTc3MDY3LCJpYXQiOjE3NzM0NTU0MzcsIm5pdERlbGVnYWRvIjo0MzA5MDY1MDE4LCJzdWJzaXN0ZW1hIjoiU0ZFIn0.Dfqive7zgGp2QtSv2F8NV8MvOOwdwuQ20eJsp6zvoBKWiPSCpwajD8CQJGCOgII7t3RmbgnWxY_jVTRjJP6iGw";

// Usa el CUIS del Punto de Venta 0 (el que ya tienes)
$cuis = "COLOCA_AQUI_EL_CUIS_DEL_PUNTO_0"; 

$url_wsdl = "https://pilotosiatservicios.impuestos.gob.bo/v2/FacturacionSincronizacion?wsdl";

try {
    $client = new SoapClient($url_wsdl, [
        'stream_context' => stream_context_create([
            'http' => [ 'header' => "apikey: TokenApi $token" ]
        ]),
        'cache_wsdl' => WSDL_CACHE_NONE,
        'trace' => 1
    ]);

    $params = [
        "SolicitudSincronizacion" => [
            "codigoAmbiente"   => 2,
            "codigoSistema"    => "371F7FF1F8FEBAE9488E",
            "codigoSucursal"   => 0,
            "nit"              => 4309065018,
            "cuis"             => $cuis
        ]
    ];

    $res = $client->sincronizarActividades($params);

    echo "<h2>Actividades Sincronizadas</h2>";

    if ($res->RespuestaListaActividades->transaccion) {
        $lista = $res->RespuestaListaActividades->listaActividades;
        
        echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr style='background: #eee;'><th>Código CAEB</th><th>Descripción</th><th>Tipo Actividad</th></tr>";
        
        // Si solo hay una actividad, a veces no viene como array, por eso validamos
        $actividades = is_array($lista) ? $lista : [$lista];
        
        foreach ($actividades as $act) {
            echo "<tr>";
            echo "<td>" . $act->codigoCaeb . "</td>";
            echo "<td>" . $act->descripcion . "</td>";
            echo "<td>" . $act->tipoActividad . "</td>";
            echo "</tr>";
        }
        echo "</table>";
        echo "<p style='color:green;'>✅ Datos obtenidos correctamente. ¡Ya puedes usar estos códigos para tus facturas!</p>";
    } else {
        echo "<p style='color:red;'>Error: " . json_encode($res->RespuestaListaActividades->mensajesList) . "</p>";
    }

} catch (Exception $e) {
    echo "<p style='color:orange;'>⚠️ El servidor sigue con problemas (Error 500) o el CUIS es inválido.</p>";
}
?>