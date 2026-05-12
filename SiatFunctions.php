
<?php
date_default_timezone_set('America/La_Paz');
require_once 'vendor/autoload.php';
use RobRichards\XMLSecLibs\XMLSecurityDSig;
use RobRichards\XMLSecLibs\XMLSecurityKey;

class SiatFunctions {
    private $config;

    public function __construct($config) {
        $this->config = $config;
    }

    private function getContext() {
        return stream_context_create([
            'http' => [
                'header' => "apikey: TokenApi " . $this->config['token'] . "\r\n" .
                            "Authorization: TokenApi " . $this->config['token'] . "\r\n"
            ],
            'ssl' => [
                'verify_peer' => false, 
                'verify_peer_name' => false,
                'allow_self_signed' => true
            ]
        ]);
    }

    public function obtenerCufd() {
        // Usamos la URL de Piloto que te funcionó
        $wsdl = "https://pilotosiatservicios.impuestos.gob.bo/v2/FacturacionCodigos?wsdl";
        
        $client = new SoapClient($wsdl, [
            'stream_context' => $this->getContext(),
            'cache_wsdl' => WSDL_CACHE_NONE,
            'trace' => 1,
            'exceptions' => true,
            'location' => "https://pilotosiatservicios.impuestos.gob.bo/v2/FacturacionCodigos"
        ]);

        $params = [
            'SolicitudCufd' => [
                'codigoAmbiente'   => $this->config['ambiente'],
                'codigoModalidad'  => $this->config['modalidad'],
                'codigoPuntoVenta' => $this->config['puntoVenta'],
                'codigoSistema'    => $this->config['codigoSistema'],
                'codigoSucursal'   => $this->config['sucursal'],
                'cuis'             => $this->config['cuis'],
                'nit'              => $this->config['nit']
            ]
        ];

        return $client->cufd($params);
    }
    public function verificarFirma() {
    $p12cert = file_get_contents($this->config['firma']['archivo']);
    if (!openssl_pkcs12_read($p12cert, $certs, $this->config['firma']['pass'])) {
        throw new Exception("Error: No se pudo leer la firma digital con la contraseña proporcionada.");
    }
    return true;
}

public function generarXmlFactura($data, $cuf, $cufd, $fechaEnvio, $numFactura){

    $xml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?> 
    <facturaComputarizadaCompraVenta xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:noNamespaceSchemaLocation="facturaComputarizadaCompraVenta.xsd"> 
    <cabecera> 
        <nitEmisor>' . $this->config['nit'] . '</nitEmisor> 
        <razonSocialEmisor>VALERIA ALEJANDRA SALINAS CAMACHO</razonSocialEmisor> 
        <municipio>La Paz</municipio> 
        <telefono>2222222</telefono> 
        <numeroFactura>' . $numFactura . '</numeroFactura> 
        <cuf>' . $cuf . '</cuf> 
        <cufd>' . $cufd . '</cufd> 
        <codigoSucursal>' . $this->config['sucursal'] . '</codigoSucursal> 
        <direccion>Direccion del Establecimiento</direccion> 
        <codigoPuntoVenta>' . $this->config['puntoVenta'] . '</codigoPuntoVenta> 
        <fechaEmision>' . $fechaEnvio . '</fechaEmision> 
        <nombreRazonSocial>' . htmlspecialchars($data->razonSocial) . '</nombreRazonSocial>
        <codigoTipoDocumentoIdentidad>1</codigoTipoDocumentoIdentidad>
        <numeroDocumento>' . $data->nit . '</numeroDocumento>
        <complemento xsi:nil="true"/>
        <codigoCliente>' . $data->nit . '</codigoCliente>
        <codigoMetodoPago>' . $data->metodoPago . '</codigoMetodoPago>
        <numeroTarjeta xsi:nil="true"/>
        <montoTotal>' . $data->montoTotal . '</montoTotal>
        <montoTotalSujetoIva>' . $data->montoTotal . '</montoTotalSujetoIva>
        <codigoMoneda>1</codigoMoneda>
        <tipoCambio>1</tipoCambio>
        <montoTotalMoneda>' . $data->montoTotal . '</montoTotalMoneda>
        <montoGiftCard xsi:nil="true"/>
        <descuentoAdicional>0</descuentoAdicional>
        <codigoExcepcion xsi:nil="true"/>
        <cafc xsi:nil="true"/>
        <leyenda>1</leyenda>
        <usuario>SISTEMA</usuario>
        <codigoDocumentoSector>1</codigoDocumentoSector>
    </cabecera>';

    foreach ($data->detalles as $item) {
        $xml .= '<detalle>
            <actividadEconomica>560000</actividadEconomica>
            <codigoProductoSin>99100</codigoProductoSin>
            <codigoProducto>' . $item->descripcion . '</codigoProducto>
            <descripcion>' . $item->descripcion . '</descripcion>
            <cantidad>' . $item->cantidad . '</cantidad>
            <unidadMedida>58</unidadMedida>
            <precioUnitario>' . $item->precio . '</precioUnitario>
            <montoDescuento>0</montoDescuento>
            <subTotal>' . ($item->precio * $item->cantidad) . '</subTotal>
            <numeroSerie xsi:nil="true"/>
            <numeroImei xsi:nil="true"/>
        </detalle>';
    }
    $xml .= '</facturaComputarizadaCompraVenta>';
    return $xml;
}
// Cambia la definición de la función
public function enviarFactura($xml, $cufd, $fechaEnvio) { 
    $gzData = gzencode($xml, 9);
    $archivoSoap = new SoapVar($gzData, XSD_BASE64BINARY);
    $archivoHash = hash("sha256", $gzData);

    $wsdl = "https://pilotosiatservicios.impuestos.gob.bo/v2/ServicioFacturacionCompraVenta?wsdl";
    $client = new SoapClient($wsdl, [
        'stream_context' => $this->getContext(),
        'cache_wsdl' => WSDL_CACHE_NONE,
        'trace' => 1,
        'exceptions' => true,
        'location' => "https://pilotosiatservicios.impuestos.gob.bo/v2/ServicioFacturacionCompraVenta"
    ]);

    $params = [
        'SolicitudServicioRecepcionFactura' => [
            'codigoAmbiente' => $this->config['ambiente'],
            'codigoDocumentoSector' => 1,
            'codigoEmision' => 1,
            'codigoModalidad' => $this->config['modalidad'],
            'codigoPuntoVenta' => $this->config['puntoVenta'],
            'codigoSistema' => $this->config['codigoSistema'],
            'codigoSucursal' => $this->config['sucursal'],
            'cufd' => $cufd,
            'cuis' => $this->config['cuis'],
            'nit' => $this->config['nit'],
            'tipoFacturaDocumento' => 1,
            'archivo' => $archivoSoap,
            'fechaEnvio' => $fechaEnvio, // <--- USA LA FECHA PASADA POR PARÁMETRO
            'hashArchivo' => $archivoHash
        ]
    ];
    return $client->recepcionFactura($params);
}
public function calcularCuf($numFactura, $fecha, $codigoControl){
    // Aseguramos que el NIT sea solo el número, sin el token
    $nitNum = preg_replace('/[^0-9]/', '', (string)$this->config['nit']);
    $nit = str_pad($nitNum, 13, "0", STR_PAD_LEFT);
    
    $sucursal = str_pad($this->config['sucursal'], 4, "0", STR_PAD_LEFT);
    $modalidad = $this->config['modalidad'];
    $tipoEmision = 1;
    $tipoFactura = 1;
    $tipoSector = str_pad(1, 2, "0", STR_PAD_LEFT);
    $numeroFactura = str_pad($numFactura, 10, "0", STR_PAD_LEFT);
    $puntoVenta = str_pad($this->config['puntoVenta'], 4, "0", STR_PAD_LEFT);

    $cadena = $nit . $fecha . $sucursal . $modalidad . $tipoEmision . $tipoFactura . $tipoSector . $numeroFactura . $puntoVenta;

    // Modulo 11
    $sum = 0; $weight = 2;
    for ($i = strlen($cadena) - 1; $i >= 0; $i--) {
        $sum += intval($cadena[$i]) * $weight;
        $weight++;
        if ($weight > 9) $weight = 2;
    }
    $mod = $sum % 11;
    $digito = ($mod >= 10) ? 0 : $mod;
    $cufFinal = $cadena . $digito;

    // Conversión Base 16
    $hexCuf = "";
    $decimal = $cufFinal;
    while (bccomp($decimal, "0") > 0) {
        $last = bcmod($decimal, "16");
        $hexCuf = dechex($last) . $hexCuf;
        $decimal = bcdiv($decimal, "16", 0);
    }

    // Retornamos el HEX y el CUFD (limpio de cualquier espacio o token)
return strtoupper($hexCuf . $codigoControl);
}


public function firmarXml($xmlString) {

    $doc = new DOMDocument();
    $doc->preserveWhiteSpace = false;
    $doc->formatOutput = false;

    $doc->loadXML($xmlString);

    $objDSig = new XMLSecurityDSig();

    $objDSig->setCanonicalMethod(
        XMLSecurityDSig::EXC_C14N
    );

    $objDSig->addReference(
        $doc,
        XMLSecurityDSig::SHA256,
        ['http://www.w3.org/2000/09/xmldsig#enveloped-signature']
    );

    openssl_pkcs12_read(
        file_get_contents($this->config['firma']['archivo']),
        $certs,
        $this->config['firma']['pass']
    );

    $objKey = new XMLSecurityKey(
        XMLSecurityKey::RSA_SHA256,
        ['type' => 'private']
    );

    $objKey->loadKey($certs['pkey']);

    $objDSig->sign($objKey);

    $objDSig->add509Cert($certs['cert']);

    $objDSig->appendSignature(
        $doc->documentElement
    );

    return $doc->saveXML();
}
}
