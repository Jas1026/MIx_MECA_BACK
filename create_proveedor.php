<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json");

include "dbconnect.php";

$id = $_POST['id_proveedor'] ?? null;

$nombre_empresa = $_POST['nombre_empresa'] ?? '';
$nit = $_POST['nit'] ?? '';
$rubro = $_POST['rubro'] ?? '';

$nombre_contacto = $_POST['nombre_contacto'] ?? '';
$cargo_contacto = $_POST['cargo_contacto'] ?? '';

$telefono = $_POST['telefono'] ?? '';
$telefono_secundario = $_POST['telefono_secundario'] ?? '';
$correo = $_POST['correo'] ?? '';

$direccion = $_POST['direccion'] ?? '';
$ciudad = $_POST['ciudad'] ?? '';
$pais = $_POST['pais'] ?? 'Bolivia';

$banco = $_POST['banco'] ?? '';
$numero_cuenta = $_POST['numero_cuenta'] ?? '';
$tipo_cuenta = $_POST['tipo_cuenta'] ?? '';
$titular_cuenta = $_POST['titular_cuenta'] ?? '';

$tiempo_entrega_dias = $_POST['tiempo_entrega_dias'] ?? 1;
$nivel_concurrencia = $_POST['nivel_concurrencia'] ?? 'Media';

$horario_atencion = $_POST['horario_atencion'] ?? '';
$dia_usual_visita = $_POST['dia_usual_visita'] ?? '';

$comentarios = $_POST['comentarios'] ?? '';

$system = $_POST['system'] ?? 'mecapos';

if (empty($nombre_empresa)) {
    echo json_encode([
        "error" => 1,
        "message" => "El nombre de la empresa es obligatorio"
    ]);
    exit;
}

try {

    $pdo->exec("USE `$system`");

    if ($id) {

        // EDICIÓN
        $stmt = $pdo->prepare("
            UPDATE proveedor SET

                nombre_empresa = ?,
                nit = ?,
                rubro = ?,

                nombre_contacto = ?,
                cargo_contacto = ?,

                telefono = ?,
                telefono_secundario = ?,
                correo = ?,

                direccion = ?,
                ciudad = ?,
                pais = ?,

                banco = ?,
                numero_cuenta = ?,
                tipo_cuenta = ?,
                titular_cuenta = ?,

                tiempo_entrega_dias = ?,
                nivel_concurrencia = ?,

                horario_atencion = ?,
                dia_usual_visita = ?,

                comentarios = ?

            WHERE id_proveedor = ?
        ");

        $stmt->execute([

            $nombre_empresa,
            $nit,
            $rubro,

            $nombre_contacto,
            $cargo_contacto,

            $telefono,
            $telefono_secundario,
            $correo,

            $direccion,
            $ciudad,
            $pais,

            $banco,
            $numero_cuenta,
            $tipo_cuenta,
            $titular_cuenta,

            $tiempo_entrega_dias,
            $nivel_concurrencia,

            $horario_atencion,
            $dia_usual_visita,

            $comentarios,

            $id
        ]);

        $msg = "Proveedor actualizado con éxito";

    } else {

        // CREACIÓN
        $stmt = $pdo->prepare("
            INSERT INTO proveedor (

                nombre_empresa,
                nit,
                rubro,

                nombre_contacto,
                cargo_contacto,

                telefono,
                telefono_secundario,
                correo,

                direccion,
                ciudad,
                pais,

                banco,
                numero_cuenta,
                tipo_cuenta,
                titular_cuenta,

                tiempo_entrega_dias,
                nivel_concurrencia,

                horario_atencion,
                dia_usual_visita,

                estado,
                comentarios

            ) VALUES (

                ?, ?, ?,
                ?, ?,
                ?, ?, ?,
                ?, ?, ?,
                ?, ?, ?, ?,
                ?, ?,
                ?, ?,
                'Activo',
                ?
            )
        ");

        $stmt->execute([

            $nombre_empresa,
            $nit,
            $rubro,

            $nombre_contacto,
            $cargo_contacto,

            $telefono,
            $telefono_secundario,
            $correo,

            $direccion,
            $ciudad,
            $pais,

            $banco,
            $numero_cuenta,
            $tipo_cuenta,
            $titular_cuenta,

            $tiempo_entrega_dias,
            $nivel_concurrencia,

            $horario_atencion,
            $dia_usual_visita,

            $comentarios
        ]);

        $msg = "Proveedor creado con éxito";
    }

    echo json_encode([
        "error" => 0,
        "message" => $msg
    ]);

} catch (PDOException $e) {

    echo json_encode([
        "error" => 1,
        "message" => $e->getMessage()
    ]);
}
?>