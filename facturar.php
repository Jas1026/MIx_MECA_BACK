<?php

require_once __DIR__ . '/vendor/autoload.php';

// ======================================
// CORS
// ======================================

header("Access-Control-Allow-Origin: http://localhost:8100");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS, PUT, DELETE");
header("Access-Control-Allow-Headers: Content-Type, Authorization, system, X-Requested-With");
header("Access-Control-Allow-Credentials: true");
header("Content-Type: application/json");

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit();
}

// ======================================
// BUFFER
// ======================================

ob_start();

// ======================================
// INCLUDES
// ======================================

include('dbconnect.php');
require_once 'SiatFunctions.php';

// ======================================
// CONFIG SIAT
// ======================================

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
        "archivo" =>
            __DIR__ .
            DIRECTORY_SEPARATOR .
            "VALERIA ALEJANDRA SALINAS CAMACHO.p12",

        "pass" => "4309065"
    ]
];

// ======================================
// RECIBIR JSON
// ======================================

$json = file_get_contents('php://input');

$request = json_decode($json);

if (!$request) {

    echo json_encode([
        "success" => false,
        "message" => "JSON inválido"
    ]);

    exit;
}

if (!isset($request->pagos)) {

    echo json_encode([
        "success" => false,
        "message" => "No llegaron pagos"
    ]);

    exit;
}

// ======================================
// FORMATO IMPRESIÓN
// ======================================

$formato = $request->formato ?? '80';

if ($formato == '58') {

    $size = [58, 250];
    $fontBase = 7;
    $logoSize = 14;

} elseif ($formato == 'carta') {

    $size = 'LETTER';
    $fontBase = 10;
    $logoSize = 28;

} else {

    $size = [80, 250];
    $fontBase = 8;
    $logoSize = 18;
}

// ======================================
// RESULTADOS
// ======================================

$resultados = [];

// ======================================
// FACTURAR CADA PAGO
// ======================================

foreach ($request->pagos as $data) {

    try {

        // ======================================
        // SIAT
        // ======================================

        $siat = new SiatFunctions($config);

        $resCufd = $siat->obtenerCufd();

        $cufd = trim(
            $resCufd->RespuestaCufd->codigo
        );

        $codigoControl = trim(
            $resCufd->RespuestaCufd->codigoControl
        );

        $numFactura = rand(1, 999999);

        $t = microtime(true);

        $micro = sprintf(
            "%03d",
            ($t - floor($t)) * 1000
        );

        $fechaBase = date('YmdHis', $t);

        $fechaParaCUF =
            $fechaBase .
            $micro;

        $fechaParaXML =
            date('Y-m-d\TH:i:s', $t)
            . '.' .
            $micro;

        // ======================================
        // CUF
        // ======================================

        $cuf = $siat->calcularCuf(
            $numFactura,
            $fechaParaCUF,
            $codigoControl
        );

        // ======================================
        // XML
        // ======================================

        $xml = $siat->generarXmlFactura(
            $data,
            $cuf,
            $cufd,
            $fechaParaXML,
            $numFactura
        );

        // ======================================
        // ENVIAR SIAT
        // ======================================

        $respuestaSiat = $siat->enviarFactura(
            $xml,
            $cufd,
            $fechaParaXML
        );

        // ======================================
        // PDF
        // ======================================

        $pdf = new TCPDF(
            'P',
            'mm',
            $size,
            true,
            'UTF-8',
            false
        );

        $pdf->SetCreator('MIXURA');
        $pdf->SetAuthor('MIXURA');
        $pdf->SetTitle('Factura SIAT');
        $pdf->SetMargins(5, 5, 5);
        $pdf->SetAutoPageBreak(true, 10);

        $pdf->AddPage();

        // ======================================
        // COLORES
        // ======================================

        $colorPrincipal = [25, 25, 25];
        $colorSecundario = [90, 90, 90];
        $colorLinea = [220, 220, 220];

        // ======================================
        // HEADER BONITO
        // ======================================

        $pdf->SetFillColor(25, 25, 25);

        $pdf->RoundedRect(
            5,
            5,
            $pdf->GetPageWidth() - 10,
            20,
            3,
            '1111',
            'F'
        );

        $pdf->SetTextColor(255, 255, 255);

        $pdf->SetFont('helvetica', 'B', $logoSize);

        $pdf->Cell(
            0,
            8,
            'MIXURA',
            0,
            1,
            'C'
        );

        $pdf->SetFont('helvetica', '', $fontBase);

        $pdf->Cell(
            0,
            5,
            'FACTURA ELECTRONICA',
            0,
            1,
            'C'
        );

        $pdf->Ln(6);

        // ======================================
        // RESET COLOR
        // ======================================

        $pdf->SetTextColor(0, 0, 0);

        // ======================================
        // INFO NEGOCIO
        // ======================================

        $pdf->SetFont('helvetica', '', $fontBase);

        $pdf->Cell(
            0,
            5,
            'NIT: ' . $config['nit'],
            0,
            1
        );

        $pdf->Cell(
            0,
            5,
            'FACTURA NRO: ' . $numFactura,
            0,
            1
        );

        $pdf->Cell(
            0,
            5,
            'FECHA: ' . date('d/m/Y H:i'),
            0,
            1
        );

        // ======================================
        // LINEA
        // ======================================

        $pdf->Ln(1);

        $pdf->SetDrawColor(
            $colorLinea[0],
            $colorLinea[1],
            $colorLinea[2]
        );

        $pdf->Line(
            5,
            $pdf->GetY(),
            $pdf->GetPageWidth() - 5,
            $pdf->GetY()
        );

        $pdf->Ln(3);

        // ======================================
        // CLIENTE
        // ======================================

        $pdf->SetFont('helvetica', 'B', $fontBase + 1);

        $pdf->Cell(
            0,
            5,
            'DATOS CLIENTE',
            0,
            1
        );

        $pdf->SetFont('helvetica', '', $fontBase);

        $pdf->SetTextColor(
            $colorSecundario[0],
            $colorSecundario[1],
            $colorSecundario[2]
        );

        $pdf->Cell(
            0,
            5,
            'Nombre: ' . $data->razonSocial,
            0,
            1
        );

        $pdf->Cell(
            0,
            5,
            'NIT/CI: ' . $data->nit,
            0,
            1
        );

        $pdf->SetTextColor(0, 0, 0);

        $pdf->Ln(3);

        // ======================================
        // TABLA HEADER
        // ======================================

        $pdf->SetFillColor(35, 35, 35);
        $pdf->SetTextColor(255, 255, 255);

        $pdf->SetFont('helvetica', 'B', $fontBase);

        if ($formato == 'carta') {

            $w1 = 90;
            $w2 = 25;
            $w3 = 35;
            $w4 = 35;

        } elseif ($formato == '58') {

            $w1 = 20;
            $w2 = 8;
            $w3 = 10;
            $w4 = 10;

        } else {

            $w1 = 38;
            $w2 = 10;
            $w3 = 12;
            $w4 = 15;
        }

        $pdf->Cell($w1, 7, 'PRODUCTO', 0, 0, 'L', true);
        $pdf->Cell($w2, 7, 'CANT', 0, 0, 'C', true);
        $pdf->Cell($w3, 7, 'P/U', 0, 0, 'R', true);
        $pdf->Cell($w4, 7, 'SUBT', 0, 1, 'R', true);

        // ======================================
        // ITEMS
        // ======================================

        $pdf->SetFont('helvetica', '', $fontBase);
        $pdf->SetTextColor(0, 0, 0);

        $fill = false;

        foreach ($data->detalles as $item) {

            $subtotal =
                $item->cantidad *
                $item->precio;

            if ($fill) {
                $pdf->SetFillColor(248, 248, 248);
            } else {
                $pdf->SetFillColor(255, 255, 255);
            }

            $pdf->Cell(
                $w1,
                6,
                $item->descripcion,
                0,
                0,
                'L',
                true
            );

            $pdf->Cell(
                $w2,
                6,
                $item->cantidad,
                0,
                0,
                'C',
                true
            );

            $pdf->Cell(
                $w3,
                6,
                number_format(
                    $item->precio,
                    2
                ),
                0,
                0,
                'R',
                true
            );

            $pdf->Cell(
                $w4,
                6,
                number_format(
                    $subtotal,
                    2
                ),
                0,
                1,
                'R',
                true
            );

            $fill = !$fill;
        }

        // ======================================
        // TOTAL
        // ======================================

        $pdf->Ln(4);

        $pdf->SetFont(
            'helvetica',
            'B',
            $fontBase + 3
        );

        $pdf->SetTextColor(20, 20, 20);

        $pdf->Cell(
            0,
            8,
            'TOTAL Bs ' .
            number_format(
                $data->montoTotal,
                2
            ),
            0,
            1,
            'R'
        );

        // ======================================
        // MENSAJE
        // ======================================

        $pdf->Ln(2);

        $pdf->SetFont(
            'helvetica',
            'I',
            $fontBase
        );

        $pdf->SetTextColor(90, 90, 90);

        $pdf->Cell(
            0,
            5,
            'Gracias por su preferencia',
            0,
            1,
            'C'
        );

        // ======================================
        // QR
        // ======================================

        $qr =
            'https://pilotosiat.impuestos.gob.bo/consulta/QR?nit=' .
            $config['nit'] .
            '&cuf=' .
            $cuf .
            '&numero=' .
            $numFactura .
            '&t=2';

        $pdf->Ln(4);

        $qrSize =
            $formato == 'carta'
            ? 45
            : 30;

        $xQr =
            ($pdf->GetPageWidth() - $qrSize) / 2;

        $pdf->write2DBarcode(
            $qr,
            'QRCODE,L',
            $xQr,
            $pdf->GetY(),
            $qrSize,
            $qrSize
        );

        $pdf->Ln($qrSize + 2);

        // ======================================
        // CUF
        // ======================================

        $pdf->SetFont(
            'helvetica',
            '',
            $fontBase - 1
        );

        $pdf->SetTextColor(80, 80, 80);

        $pdf->MultiCell(
            0,
            4,
            'CUF: ' . $cuf,
            0,
            'C'
        );

        // ======================================
        // LEYENDA FINAL
        // ======================================

        $pdf->Ln(2);

        $pdf->SetFont(
            'helvetica',
            '',
            $fontBase - 1
        );

        $pdf->MultiCell(
            0,
            4,
            'ESTA FACTURA CONTRIBUYE AL DESARROLLO DEL PAIS.',
            0,
            'C'
        );

        // ======================================
        // CARPETA FACTURAS
        // ======================================

        if (!file_exists(__DIR__ . '/facturas')) {

            mkdir(
                __DIR__ . '/facturas',
                0777,
                true
            );
        }

        // ======================================
        // GUARDAR PDF
        // ======================================

        $pdfName =
            'factura_' .
            $numFactura .
            '.pdf';

        $pdfPath =
            __DIR__ .
            '/facturas/' .
            $pdfName;

        $pdf->Output(
            $pdfPath,
            'F'
        );

        // ======================================
        // RESPUESTA
        // ======================================

        $resultados[] = [

            "cliente" =>
                $data->razonSocial,

            "pdf" =>
                'http://localhost/api/facturas/' .
                $pdfName,

            "cuf" =>
                $cuf,

            "factura" =>
                $numFactura
        ];

    } catch (Exception $e) {

        $resultados[] = [

            "cliente" =>
                $data->razonSocial ?? 'SIN NOMBRE',

            "error" =>
                $e->getMessage()
        ];
    }
}

// ======================================
// LIMPIAR BUFFER
// ======================================

ob_clean();

// ======================================
// RESPUESTA FINAL
// ======================================

echo json_encode([

    "success" => true,

    "facturas" => $resultados
]);

exit;

?>