<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json");

include('dbconnect.php');

$input = json_decode(file_get_contents("php://input"), true);
$system = $input['system'] ?? 'mixtura'; 

try {
    $pdo->exec("USE `$system` ");
    
    // 1. OBTENER TOKEN DESDE LA BD
    $stmtSiat = $pdo->query("SELECT * FROM siat_conexion LIMIT 1");
    $siat_config = $stmtSiat->fetch(PDO::FETCH_ASSOC);

    if (!$siat_config) {
        throw new Exception("Configuración SIAT no encontrada en la BD.");
    }

    $token = $siat_config['codigo_control_sistema'];
    $cufd_actual = $siat_config['cufd'];
    $fecha_vigencia = $siat_config['fecha_vigencia_cufd'];

    // 2. VERIFICAR SI EL CUFD SIGUE VIGENTE (Paso Extra del que hablamos)
    // Si no hay CUFD o ya venció, aquí deberías llamar a una función que use SOAP
    // Por ahora, si está vacío, pondremos un placeholder o lanzaremos error
    if (empty($cufd_actual) || strtotime($fecha_vigencia) < time()) {
        // Aquí iría la llamada SOAP: $cufd_actual = solicitarNuevoCUFD($token);
        // Para que puedas probar tu flujo, usaremos uno temporal si no has implementado SOAP aún
        $cufd_actual = "CUFD-TEMPORAL-" . date('Ymd');
    }

    $pdo->beginTransaction();

    $order_id = $input['order_id'];
    $nit_cliente = $input['nit'];
    $razon_social = $input['razonSocial'];
    $total = $input['total'];

    // 3. GENERAR CUF (Usando el algoritmo legal que ya te pasé)
    $fecha_actual = date("YmdHisv");
    $nro_factura = $order_id;
    
    // Cadena base para el CUF: NIT + Fecha + Sucursal + Modalidad + Emision + Factura + TipoDoc + NroFactura
    $cadena_cuf = "123456789" . $fecha_actual . "0" . "2" . "1" . "1" . "1" . $nro_factura;
    $cuf = calcularCUF($cadena_cuf);

    // 4. INSERTAR EN TABLA FACTURAS
    $stmtFactura = $pdo->prepare("
        INSERT INTO facturas 
        (order_id, cuf, cufd, numero_factura, nit_cliente, razon_social, monto_total, fecha_emision) 
        VALUES (?, ?, ?, ?, ?, ?, ?, NOW())
    ");
    $stmtFactura->execute([$order_id, $cuf, $cufd_actual, $nro_factura, $nit_cliente, $razon_social, $total]);
    $id_factura = $pdo->lastInsertId();

    // 5. CERRAR ORDEN Y LIBERAR MESA
    $pdo->prepare("UPDATE orders SET status = 'closed' WHERE order_id = ?")->execute([$order_id]);
    
    $stmtTable = $pdo->prepare("SELECT table_id FROM orders WHERE order_id = ?");
    $stmtTable->execute([$order_id]);
    $table = $stmtTable->fetch(PDO::FETCH_ASSOC);
    if($table) {
        $pdo->prepare("UPDATE cafe_tables SET estado = 'Libre' WHERE id_table = ?")
            ->execute([$table['table_id']]);
    }

    $pdo->commit();

    echo json_encode([
        "error" => 0,
        "cuf" => $cuf,
        "id_factura" => $id_factura,
        "message" => "Factura registrada con éxito"
    ]);

} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    echo json_encode(["error" => 1, "message" => $e->getMessage()]);
}

// --- FUNCIONES DE APOYO SIAT ---
function calcularCUF($cadena) {
    $digito = mod11($cadena);
    return strtoupper(base_convert($cadena . $digito, 10, 16));
}

function mod11($num) {
    $sum = 0; $weight = 2;
    for ($i = strlen($num) - 1; $i >= 0; $i--) {
        $sum += $num[$i] * $weight;
        $weight = ($weight == 7) ? 2 : $weight + 1;
    }
    $mod = $sum % 11;
    return ($mod >= 10) ? 0 : $mod;
}
?>