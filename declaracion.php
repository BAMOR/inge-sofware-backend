<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT");
header("Access-Control-Allow-Headers: Content-Type");

// ============================================
// CONFIGURACIÓN
// ============================================
define('API_PERSONAS_URL', 'http://mxx.60c.mytemp.website/projecto/api/persona.php');

$host     = "mysql-2063d834-paulojosueb58-d2dd.i.aivencloud.com";
$dbname   = "defaultdb";
$username = "avnadmin";
$password = "AVNS_AbOSGBZqmCMLd_ANW3e";
$port     = 21671;

try {
    $pdo = new PDO(
        "mysql:host=$host;port=$port;dbname=$dbname;charset=utf8mb4",
        $username,
        $password,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(["error" => "Error de conexión: " . $e->getMessage()]);
    exit();
}

$method = $_SERVER['REQUEST_METHOD'];
$body   = json_decode(file_get_contents("php://input"), true) ?? [];

// ============================================
// FUNCIÓN: Calcular impuesto según régimen
// ============================================
function calcularImpuesto($ingresos, $egresos, $regimen) {
    $base = max(0, floatval($ingresos) - floatval($egresos));

    $tasa = match($regimen) {
        'General'               => 12.00,
        'Pequeño Contribuyente' => 5.00,
        'Opcional Simplificado' => 7.00,
        default                 => 12.00,
    };

    return [
        'base_imponible'     => round($base, 2),
        'tasa_impuesto'      => $tasa,
        'impuesto_calculado' => round($base * $tasa / 100, 2),
    ];
}

// ============================================
// GET — Consultar declaraciones
// ============================================
// GET /declaracion.php?id_contribuyente=1
// GET /declaracion.php?id_declaracion=5
// GET /declaracion.php?id_contribuyente=1&periodo=2024-01
if ($method === 'GET') {

    try {
        if (!empty($_GET['id_declaracion'])) {
            $stmt = $pdo->prepare("SELECT * FROM declaracion WHERE id_declaracion = :id");
            $stmt->execute([':id' => intval($_GET['id_declaracion'])]);
            $dec = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$dec) {
                http_response_code(404);
                echo json_encode(["error" => "Declaración no encontrada"]);
                exit();
            }

            http_response_code(200);
            echo json_encode($dec);
            exit();
        }

        if (!empty($_GET['id_contribuyente'])) {
            $id = intval($_GET['id_contribuyente']);
            $sql = "SELECT * FROM declaracion WHERE id_contribuyente = :id";
            $params = [':id' => $id];

            if (!empty($_GET['periodo'])) {
                $sql .= " AND periodo = :periodo";
                $params[':periodo'] = $_GET['periodo'];
            }

            $sql .= " ORDER BY periodo DESC";
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $declaraciones = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Resumen total
            $total_impuesto  = array_sum(array_column($declaraciones, 'impuesto_calculado'));
            $total_pendiente = array_sum(array_column(
                array_filter($declaraciones, fn($d) => $d['estado'] === 'Pendiente'),
                'impuesto_calculado'
            ));
            $total_pagado = array_sum(array_column(
                array_filter($declaraciones, fn($d) => $d['estado'] === 'Pagado'),
                'impuesto_calculado'
            ));

            http_response_code(200);
            echo json_encode([
                "declaraciones"      => $declaraciones,
                "total_declaraciones"=> count($declaraciones),
                "resumen" => [
                    "total_impuesto"   => round($total_impuesto, 2),
                    "total_pendiente"  => round($total_pendiente, 2),
                    "total_pagado"     => round($total_pagado, 2),
                ]
            ]);
            exit();
        }

        // Todas las declaraciones
        $stmt = $pdo->prepare("SELECT d.*, c.nit, c.regimen FROM declaracion d INNER JOIN contribuyente c ON c.id_contribuyente = d.id_contribuyente ORDER BY d.fecha_declaracion DESC");
        $stmt->execute();
        echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));

    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(["error" => "Error al consultar: " . $e->getMessage()]);
    }
    exit();
}

// ============================================
// POST — Crear declaración y calcular impuesto
// ============================================
if ($method === 'POST') {

    $errores = [];
    if (empty($body['id_contribuyente'])) $errores[] = 'id_contribuyente';
    if (empty($body['periodo']))          $errores[] = 'periodo (formato: YYYY-MM)';
    if (!isset($body['ingresos']))        $errores[] = 'ingresos';
    if (!isset($body['egresos']))         $errores[] = 'egresos';

    if (!empty($errores)) {
        http_response_code(400);
        echo json_encode(["error" => "Datos requeridos: " . implode(", ", $errores)]);
        exit();
    }

    // Validar formato periodo YYYY-MM
    if (!preg_match('/^\d{4}-\d{2}$/', $body['periodo'])) {
        http_response_code(400);
        echo json_encode(["error" => "Formato de periodo incorrecto. Use YYYY-MM (ej: 2024-01)"]);
        exit();
    }

    $id_contribuyente = intval($body['id_contribuyente']);

    // Verificar que el contribuyente existe y obtener su régimen
    $stmt = $pdo->prepare("SELECT * FROM contribuyente WHERE id_contribuyente = :id AND activo = 1");
    $stmt->execute([':id' => $id_contribuyente]);
    $contribuyente = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$contribuyente) {
        http_response_code(404);
        echo json_encode(["error" => "Contribuyente no encontrado con id: $id_contribuyente"]);
        exit();
    }

    // Verificar que no exista ya una declaración para ese periodo
    $stmt = $pdo->prepare("SELECT id_declaracion FROM declaracion WHERE id_contribuyente = :id AND periodo = :periodo");
    $stmt->execute([':id' => $id_contribuyente, ':periodo' => $body['periodo']]);
    if ($stmt->fetch()) {
        http_response_code(400);
        echo json_encode(["error" => "Ya existe una declaración para el periodo {$body['periodo']}"]);
        exit();
    }

    // ★ CÁLCULO TRIBUTARIO
    $calculo = calcularImpuesto($body['ingresos'], $body['egresos'], $contribuyente['regimen']);

    try {
        $stmt = $pdo->prepare("
            INSERT INTO declaracion
                (id_contribuyente, periodo, ingresos, egresos, base_imponible, tasa_impuesto, impuesto_calculado, estado)
            VALUES
                (:id_contribuyente, :periodo, :ingresos, :egresos, :base_imponible, :tasa_impuesto, :impuesto_calculado, 'Pendiente')
        ");
        $stmt->execute([
            ':id_contribuyente'  => $id_contribuyente,
            ':periodo'           => $body['periodo'],
            ':ingresos'          => floatval($body['ingresos']),
            ':egresos'           => floatval($body['egresos']),
            ':base_imponible'    => $calculo['base_imponible'],
            ':tasa_impuesto'     => $calculo['tasa_impuesto'],
            ':impuesto_calculado'=> $calculo['impuesto_calculado'],
        ]);

        $id_dec = $pdo->lastInsertId();

        $pdo->prepare("INSERT INTO bitacora (accion, tabla_afectada, id_registro, descripcion, ip_origen)
            VALUES ('CREATE', 'declaracion', :id, :desc, :ip)")
            ->execute([':id' => $id_dec, ':desc' => json_encode($body), ':ip' => $_SERVER['REMOTE_ADDR'] ?? null]);

        http_response_code(200);
        echo json_encode([
            "id_declaracion"     => (int)$id_dec,
            "periodo"            => $body['periodo'],
            "regimen"            => $contribuyente['regimen'],
            "ingresos"           => floatval($body['ingresos']),
            "egresos"            => floatval($body['egresos']),
            "base_imponible"     => $calculo['base_imponible'],
            "tasa_impuesto"      => $calculo['tasa_impuesto'] . "%",
            "impuesto_calculado" => $calculo['impuesto_calculado'],
            "estado"             => "Pendiente",
            "mensaje"            => "Declaración creada y cálculo tributario realizado exitosamente",
        ]);

    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(["error" => "Error al crear declaración: " . $e->getMessage()]);
    }
    exit();
}

// ============================================
// PUT — Actualizar declaración (marcar como Pagado)
// ============================================
if ($method === 'PUT') {

    if (empty($body['id_declaracion']) || !is_numeric($body['id_declaracion'])) {
        http_response_code(400);
        echo json_encode(["error" => "id_declaracion requerido"]);
        exit();
    }

    $id = intval($body['id_declaracion']);
    $stmt = $pdo->prepare("SELECT * FROM declaracion WHERE id_declaracion = :id");
    $stmt->execute([':id' => $id]);
    $dec = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$dec) {
        http_response_code(404);
        echo json_encode(["error" => "Declaración no encontrada con id: $id"]);
        exit();
    }

    // Recalcular si cambian ingresos o egresos
    $ingresos = $body['ingresos'] ?? $dec['ingresos'];
    $egresos  = $body['egresos']  ?? $dec['egresos'];

    // Obtener régimen del contribuyente
    $stmt2 = $pdo->prepare("SELECT regimen FROM contribuyente WHERE id_contribuyente = :id");
    $stmt2->execute([':id' => $dec['id_contribuyente']]);
    $contrib = $stmt2->fetch(PDO::FETCH_ASSOC);

    $calculo = calcularImpuesto($ingresos, $egresos, $contrib['regimen']);

    $estado     = $body['estado'] ?? $dec['estado'];
    $fecha_pago = ($estado === 'Pagado' && $dec['estado'] !== 'Pagado') ? date('Y-m-d H:i:s') : $dec['fecha_pago'];

    try {
        $pdo->prepare("
            UPDATE declaracion SET
                ingresos           = :ingresos,
                egresos            = :egresos,
                base_imponible     = :base_imponible,
                tasa_impuesto      = :tasa_impuesto,
                impuesto_calculado = :impuesto_calculado,
                estado             = :estado,
                fecha_pago         = :fecha_pago
            WHERE id_declaracion = :id
        ")->execute([
            ':ingresos'           => floatval($ingresos),
            ':egresos'            => floatval($egresos),
            ':base_imponible'     => $calculo['base_imponible'],
            ':tasa_impuesto'      => $calculo['tasa_impuesto'],
            ':impuesto_calculado' => $calculo['impuesto_calculado'],
            ':estado'             => $estado,
            ':fecha_pago'         => $fecha_pago,
            ':id'                 => $id,
        ]);

        $pdo->prepare("INSERT INTO bitacora (accion, tabla_afectada, id_registro, descripcion, ip_origen)
            VALUES ('UPDATE', 'declaracion', :id, :desc, :ip)")
            ->execute([':id' => $id, ':desc' => json_encode($body), ':ip' => $_SERVER['REMOTE_ADDR'] ?? null]);

        http_response_code(200);
        echo json_encode([
            "id_declaracion"     => $id,
            "ingresos"           => floatval($ingresos),
            "egresos"            => floatval($egresos),
            "base_imponible"     => $calculo['base_imponible'],
            "tasa_impuesto"      => $calculo['tasa_impuesto'] . "%",
            "impuesto_calculado" => $calculo['impuesto_calculado'],
            "estado"             => $estado,
            "fecha_pago"         => $fecha_pago,
            "mensaje"            => "Declaración actualizada correctamente",
        ]);

    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(["error" => "Error al actualizar: " . $e->getMessage()]);
    }
    exit();
}

http_response_code(405);
echo json_encode(["error" => "Método no permitido"]);
?>