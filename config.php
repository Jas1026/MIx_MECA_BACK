<?php
// CONFIGURACIÓN GLOBAL DEL SISTEMA
return [
    'token'     => 'eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzUxMiJ9...', // Tu token completo
    'nit'       => 4309065018,
    'codigoSis' => '371F7FF1F8FEBAE9488E',
    'ambiente'  => 2, // 2 = Piloto
    'modalidad' => 2, // 2 = Computarizada en Línea
    
    // Estos códigos cambian por cada Punto de Venta (Se obtienen en Etapa I)
    'cuis_0'    => 'A1B2C3D4', // El CUIS que ya tienes del PV 0
    'cuis_1'    => 'E5F6G7H8', // El CUIS que obtendrás del PV 1 (cuando reviva el SIAT)
    
    // URLs de los servicios
    'ws_codigos'     => 'https://pilotosiatservicios.impuestos.gob.bo/v2/FacturacionCodigos?wsdl',
    'ws_sincroniza'  => 'https://pilotosiatservicios.impuestos.gob.bo/v2/FacturacionSincronizacion?wsdl',
    'ws_operaciones' => 'https://pilotosiatservicios.impuestos.gob.bo/v2/ServiceOperaciones?wsdl',
    'ws_facturas'    => 'https://pilotosiatservicios.impuestos.gob.bo/v2/ServicioFacturacionComputarizada?wsdl',
];