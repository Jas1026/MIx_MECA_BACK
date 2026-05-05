<?php
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
}
