<?php
// emitir_factura_cucu.php
$data = json_decode(file_get_contents("php://input"), true);

// 1. Configurar los datos para CUCU
$payload = [
    "apiKey" => "TU_API_KEY_DE_CUCU",
    "razonSocial" => $data['razonSocial'],
    "nit" => $data['nit'],
    "codigoMetodoPago" => 1, // 1 = Efectivo
    "montoTotal" => $total,
    "detalles" => []
];

// Mapear tus productos a lo que Cucu espera
foreach($data['items'] as $item) {
    $payload['detalles'][] = [
        "codigoProducto" => "99000", // Código SIN genérico o específico
        "descripcion" => $item['nombre_producto'],
        "cantidad" => $item['quantity'],
        "precioUnitario" => $item['unit_price'],
        "subTotal" => $item['total_price']
    ];
}

// 2. Enviar a Cucu vía CURL
$ch = curl_init("https://api.cucu.bo/v1/facturas"); // URL según doc de Cucu
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
// ... ejecutar y devolver respuesta a Ionic