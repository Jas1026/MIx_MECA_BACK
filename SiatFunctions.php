
<?php
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


public function generarXmlFactura($data, $cuf, $cufd) {
    $fechaEnvio = date('Y-m-d\TH:i:s.000');
    
    // Estructura básica de Factura Computarizada en Línea
    $xml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
    <facturaElectronicaCompraVenta xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:noNamespaceSchemaLocation="facturaElectronicaCompraVenta.xsd">
        <cabecera>
            <nitEmisor>' . $this->config['nit'] . '</nitEmisor>
            <razonSocialEmisor>VALERIA ALEJANDRA SALINAS CAMACHO</razonSocialEmisor>
            <municipio>La Paz</municipio>
            <telefono>2222222</telefono>
            <numeroFactura>' . rand(1, 1000) . '</numeroFactura>
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
            <codigoLeyenda>1</codigoLeyenda>
            <usuario>SISTEMA</usuario>
            <codigoDocumentoSector>1</codigoDocumentoSector>
        </cabecera>';

    foreach ($data->detalles as $item) {
        $xml .= '
        <detalle>
            <actividadEconomica>561000</actividadEconomica>
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

    $xml .= '</facturaElectronicaCompraVenta>';
    return $xml;
}
// Dentro de la clase SiatFunctions en SiatFunctions.php
public function enviarFactura($xmlFirmado, $cufd) {

    // Comprimir XML
    $gzData = gzencode($xmlFirmado, 9);

    // HASH DEL XML ORIGINAL
    $archivoHash = hash("sha256", $xmlFirmado);

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

            'archivo' => $gzData,

            'fechaEnvio' => date('Y-m-d\TH:i:s.000'),

            'hashArchivo' => $archivoHash
        ]
    ];

    return $client->recepcionFactura($params);
}

// Función para calcular el CUF (Código Único de Factura) simplificado para pruebas
public function calcularCuf($numFactura, $fecha, $cufd) {
    // El algoritmo real es una cadena larga + Modulo 11. 
    // Para probar comunicación, enviaremos una cadena construida:
    $cadena = $this->config['nit'] . $fecha . $this->config['sucursal'] . $this->config['modalidad'] . "1" . "1" . "1" . $numFactura . "0";
    // Nota: El CUF real debe generarse con el algoritmo oficial.
    return strtoupper(hash('sha256', $cadena . $cufd)); 
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
