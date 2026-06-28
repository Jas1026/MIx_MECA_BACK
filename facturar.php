<?php

require_once __DIR__ . '/vendor/autoload.php';

// ======================================
// CORS (Dinámico para Localhost e IP)
// ======================================

$allowed_origins = [
    "http://localhost:8100",
    "https://192.168.0.25",
    "http://192.168.0.25"
];

$origin = $_SERVER['HTTP_ORIGIN'] ?? '';

if (in_array($origin, $allowed_origins)) {
    header("Access-Control-Allow-Origin: " . $origin);
}

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
        "archivo" => __DIR__ . DIRECTORY_SEPARATOR . "VALERIA ALEJANDRA SALINAS CAMACHO.p12",
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
function obtenerNumeroFactura($conn)
{
    mysqli_begin_transaction($conn);

    $sql = "SELECT numero_factura
            FROM factura_correlativo
            WHERE id = 1
            FOR UPDATE";

    $res = mysqli_query($conn, $sql);

    $row = mysqli_fetch_assoc($res);

    $nuevoNumero = $row['numero_factura'] + 1;

    mysqli_query(
        $conn,
        "UPDATE factura_correlativo
         SET numero_factura = $nuevoNumero
         WHERE id = 1"
    );

    mysqli_commit($conn);

    return $nuevoNumero;
}
foreach ($request->pagos as $data) {
    try {
        // ======================================
        // SIAT
        // ======================================

        $siat = new SiatFunctions($config);
        $resCufd = $siat->obtenerCufd();

        $cufd = trim($resCufd->RespuestaCufd->codigo);
        file_put_contents(
    __DIR__ . '/debug_cufd.txt',
    print_r($resCufd, true)
);
        $codigoControl = trim($resCufd->RespuestaCufd->codigoControl);

        $numFactura = rand(1,999999);
        $t = microtime(true);
        $micro = sprintf("%03d", ($t - floor($t)) * 1000);

        $fechaBase = date('YmdHis', $t);
        $fechaParaCUF = $fechaBase . $micro;
        $fechaParaXML = date('Y-m-d\TH:i:s', $t) . '.' . $micro;

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
file_put_contents(
    __DIR__ . '/xml_generado.xml',
    $xml
);
        // ======================================
        // ENVIAR SIAT
        // ======================================

$respuestaSiat = $siat->enviarFactura(
    $xml,
    $cufd,
    $fechaParaXML
);
file_put_contents(
    __DIR__.'/respuesta_siat.txt',
    print_r($respuestaSiat, true)
);

        // ======================================
        // PDF
        // ======================================

        // ======================================
        // PDF (DISEÑO MEJORADO Y ESTILIZADO)
        // ======================================

        $pdf = new TCPDF(
            'P',
            'mm',
            $size,
            true,
            'UTF-8',
            false
        );

        $pdf->SetCreator('MIXTURA');
        $pdf->SetAuthor('MIXTURA');
        $pdf->SetTitle('Factura SIAT');
        
        // Márgenes limpios para evitar saltos de página accidentales
        $pdf->SetMargins(6, 6, 6);
        $pdf->SetAutoPageBreak(true, 8);
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);
        $pdf->AddPage();

        // Configuración de Paleta de Colores Corporativa
        $colorDark        = [33, 37, 41];     // Negro Antracita moderno para títulos
        $colorText        = [65, 70, 75];     // Gris oscuro balanceado para lectura suave
        $colorMuted       = [120, 125, 130];  // Gris claro para etiquetas secundarias
        $colorBorderLight = [230, 232, 235];  // Línea divisoria sutil
        $colorZebra       = [248, 249, 250];  // Fondo alterno de la tabla

        // ======================================
        // HEADER ELEGANTE CON BORDE REDONDEADO SUTIL
        // ======================================
        $pdf->SetFillColor($colorDark[0], $colorDark[1], $colorDark[2]);
        $pdf->RoundedRect(
            6,
            6,
            $pdf->GetPageWidth() - 12,
            18,
            2,        // Radio de curvatura moderno
            '1111',
            'F'
        );

        $pdf->SetTextColor(255, 255, 255);
        $pdf->SetFont('helvetica', 'B', $logoSize);
        $pdf->Cell(0, 10, 'M I X U R A', 0, 1, 'C', false, '', 1); // Trackeo sutil

        $pdf->SetFont('helvetica', 'B', $fontBase - 1);
        $pdf->Cell(0, 4, 'FACTURA ELECTRÓNICA', 0, 1, 'C');
        $pdf->Ln(5);

        // ======================================
        // INFORMACIÓN DE LA EMPRESA Y DOSIFICACIÓN
        // ======================================
        $pdf->SetTextColor($colorText[0], $colorText[1], $colorText[2]);
        $pdf->SetFont('helvetica', '', $fontBase);
        
        // Bloque Izquierdo: NIT y Datos de Emisión
        $pdf->Cell(($pdf->GetPageWidth() - 12) * 0.5, 4.5, 'NIT: ' . $config['nit'], 0, 0, 'L');
        $pdf->SetFont('helvetica', 'B', $fontBase);
        $pdf->Cell(($pdf->GetPageWidth() - 12) * 0.5, 4.5, 'FACTURA N°: ' . $numFactura, 0, 1, 'R');
        
        $pdf->SetFont('helvetica', '', $fontBase);
        $pdf->Cell(($pdf->GetPageWidth() - 12) * 0.5, 4.5, 'FECHA: ' . date('d/m/Y H:i'), 0, 0, 'L');
        $pdf->SetTextColor($colorMuted[0], $colorMuted[1], $colorMuted[2]);
        $pdf->Cell(($pdf->GetPageWidth() - 12) * 0.5, 4.5, 'Ambiente: Digital / En Línea', 0, 1, 'R');

        // Línea Divisoria Estética Avanzada
        $pdf->Ln(2);
        $pdf->SetDrawColor($colorBorderLight[0], $colorBorderLight[1], $colorBorderLight[2]);
        $pdf->SetLineWidth(0.3);
        $pdf->Line(6, $pdf->GetY(), $pdf->GetPageWidth() - 6, $pdf->GetY());
        $pdf->Ln(3);

        // ======================================
        // SECCIÓN CLIENTE CON TIPOGRAFÍA COMPACTA
        // ======================================
        $pdf->SetTextColor($colorDark[0], $colorDark[1], $colorDark[2]);
        $pdf->SetFont('helvetica', 'B', $fontBase);
        $pdf->Cell(0, 5, 'DATOS DEL CLIENTE', 0, 1, 'L');

        $pdf->SetFont('helvetica', '', $fontBase);
        $pdf->SetTextColor($colorText[0], $colorText[1], $colorText[2]);
        
        // Fila Nombre
        $pdf->SetTextColor($colorMuted[0], $colorMuted[1], $colorMuted[2]);
        $pdf->Cell(20, 4.5, 'Razón Social:', 0, 0, 'L');
        $pdf->SetTextColor($colorDark[0], $colorDark[1], $colorDark[2]);
        $pdf->Cell(0, 4.5, $data->razonSocial, 0, 1, 'L');

        // Fila NIT/CI
        $pdf->SetTextColor($colorMuted[0], $colorMuted[1], $colorMuted[2]);
        $pdf->Cell(20, 4.5, 'NIT / CI:', 0, 0, 'L');
        $pdf->SetTextColor($colorDark[0], $colorDark[1], $colorDark[2]);
        $pdf->Cell(0, 4.5, $data->nit, 0, 1, 'L');
        
        $pdf->Ln(4);

        // ======================================
        // TABLA DE ITEMS MINIMALISTA Y LIMPIA
        // ======================================
        $pdf->SetFillColor($colorDark[0], $colorDark[1], $colorDark[2]);
        $pdf->SetTextColor(255, 255, 255);
        $pdf->SetFont('helvetica', 'B', $fontBase - 0.5);

        // Calcular anchos proporcionales según formato seleccionado
        if ($formato == 'carta') {
            $w1 = 110; $w2 = 20; $w3 = 30; $w4 = 30;
        } elseif ($formato == '58') {
            $w1 = 22;  $w2 = 7;  $w3 = 8;  $w4 = 9;
        } else { // Formato 80mm
            $w1 = 36;  $w2 = 8;  $w3 = 11; $w4 = 13;
        }

        // Encabezados planos con relleno oscuro sin bordes toscos
        $pdf->Cell($w1, 6.5, ' DETALLE', 0, 0, 'L', true);
        $pdf->Cell($w2, 6.5, 'CANT', 0, 0, 'C', true);
        $pdf->Cell($w3, 6.5, 'P.UNIT ', 0, 0, 'R', true);
        $pdf->Cell($w4, 6.5, 'SUBTOTAL ', 0, 1, 'R', true);

        $pdf->SetFont('helvetica', '', $fontBase - 0.5);
        $pdf->SetTextColor($colorText[0], $colorText[1], $colorText[2]);

        $fill = false;
        foreach ($data->detalles as $item) {
            $subtotal = $item->cantidad * $item->precio;

            // Renderizado de fondo cebra suave para separar los ítems visiblemente
            $pdf->SetFillColor($colorZebra[0], $colorZebra[1], $colorZebra[2]);
            
            // Celdas limpias sin bordes pesados (uso de padding sutil usando espacios o posiciones)
            $pdf->Cell($w1, 6, ' ' . $item->descripcion, 0, 0, 'L', $fill);
            $pdf->Cell($w2, 6, $item->cantidad, 0, 0, 'C', $fill);
            $pdf->Cell($w3, 6, number_format($item->precio, 2) . ' ', 0, 0, 'R', $fill);
            $pdf->Cell($w4, 6, number_format($subtotal, 2) . ' ', 0, 1, 'R', $fill);

            $fill = !$fill;
        }

        // Borde inferior de la tabla sutil
        $pdf->SetDrawColor($colorBorderLight[0], $colorBorderLight[1], $colorBorderLight[2]);
        $pdf->Line(6, $pdf->GetY(), $pdf->GetPageWidth() - 6, $pdf->GetY());
        $pdf->Ln(2);

        // ======================================
        // SECCIÓN TOTAL ENMARCADA CON ÉNFASIS
        // ======================================
        $pdf->SetFont('helvetica', 'B', $fontBase + 2);
        $pdf->SetTextColor($colorDark[0], $colorDark[1], $colorDark[2]);
        
        // Caja contenedora sutil para resaltar el total general
        $pdf->SetFillColor($colorZebra[0], $colorZebra[1], $colorZebra[2]);
        $pdf->Cell(0, 9, 'TOTAL Bs ' . number_format($data->montoTotal, 2) . ' ', 0, 1, 'R', true);

        // ======================================
        // CÓDIGO QR REDISEÑADO (Centrado Exacto)
        // ======================================
        $qr = 'https://pilotosiat.impuestos.gob.bo/consulta/QR?nit=' . $config['nit'] . '&cuf=' . $cuf . '&numero=' . $numFactura . '&t=2';

        $pdf->Ln(4);
        $qrSize = ($formato == 'carta') ? 40 : 28; // Reducción de tamaño para balance óptimo
        $xQr = ($pdf->GetPageWidth() - $qrSize) / 2;

        // Estilo de barras QR limpio (L = Low Correction para escaneo inmediato)
        $pdf->write2DBarcode($qr, 'QRCODE,L', $xQr, $pdf->GetY(), $qrSize, $qrSize);
        $pdf->Ln($qrSize + 2);

        // ======================================
        // PIE DE FACTURA OFICIAL SIAT
        // ======================================
        $pdf->SetFont('helvetica', '', $fontBase - 1.5);
        $pdf->SetTextColor($colorMuted[0], $colorMuted[1], $colorMuted[2]);
        
        // CUF en MultiCell para evitar desbordes laterales si el código es largo
        $pdf->MultiCell(0, 3, "CÓDIGO ÚNICO DE FACTURACIÓN (CUF):\n" . $cuf, 0, 'C', false, 1, '', '', true, 0, false, true, 0, 'T', false);
        
        $pdf->Ln(2);
        $pdf->SetFont('helvetica', 'I', $fontBase - 1.5);
        $pdf->SetTextColor($colorText[0], $colorText[1], $colorText[2]);
        $pdf->MultiCell(0, 3.5, '"ESTA FACTURA CONTRIBUYE AL DESARROLLO DEL PAÍS, EL USO ILÍCITO DE ESTE DERECHO SERÁ SANCIONADO DE ACUERDO A LA LEY."', 0, 'C');
        // ======================================
        // CARPETA FACTURAS
        // ======================================

        if (!file_exists(__DIR__ . '/facturas')) {
            mkdir(__DIR__ . '/facturas', 0777, true);
        }

        // ======================================
        // GUARDAR PDF
        // ======================================

        $pdfName = 'factura_' . $numFactura . '.pdf';
        $pdfPath = __DIR__ . '/facturas/' . $pdfName;

        $pdf->Output($pdfPath, 'F');

        // ======================================
        // RESPUESTA
        // ======================================

        $resultados[] = [
            "cliente" => $data->razonSocial,
            "pdf" => 'http://localhost/api/facturas/' . $pdfName,
            "cuf" => $cuf,
            "factura" => $numFactura
        ];

    } catch (Exception $e) {
        $resultados[] = [
            "cliente" => $data->razonSocial ?? 'SIN NOMBRE',
            "error" => $e->getMessage()
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