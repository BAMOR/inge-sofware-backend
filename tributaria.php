<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE");
header("Access-Control-Allow-Headers: Content-Type");

// ============================================
// CONFIGURACIÓN
// ============================================

// URL de la API 1 (personas) — cambia la IP por la de tu compañero
define('API_PERSONAS_URL', 'http://mxx.60c.mytemp.website/projecto/api/persona.php');

// Base de datos propia
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
    echo json_encode(["error" => "Error de conexión BD: " . $e->getMessage()]);
    exit();
}

$method = $_SERVER['REQUEST_METHOD'];
$body   = json_decode(file_get_contents("php://input"), true) ?? [];

// ============================================
// FUNCIÓN: Consumir API 1 de Personas
// ============================================
function obtenerPersonaDesdeAPI($id_persona = null) {
    $url = API_PERSONAS_URL;
    if ($id_persona) {
        $url .= "?id_persona=" . intval($id_persona);
    }

    // Llamada HTTP a la API 1 usando cURL
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    $response   = curl_exec($ch);
    $httpCode   = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError  = curl_error($ch);
    curl_close($ch);

    if ($curlError) {
        return ["error" => "No se pudo conectar a la API de personas: $curlError"];
    }

    if ($httpCode !== 200) {
        return ["error" => "API de personas respondió con código $httpCode"];
    }

    return json_decode($response, true);
}

// ============================================
// FUNCIÓN: Calcular impuesto
// Fórmula: base_imponible = ingresos - egresos
//          impuesto = base_imponible * tasa / 100
// ============================================
function calcularImpuesto($ingresos, $egresos, $regimen) {
    $base = max(0, $ingresos - $egresos);

    // Tasas según régimen (Guatemala)
    $tasa = match($regimen) {
        'General'               => 12.00,  // ISR régimen general 12%
        'Pequeño Contribuyente' => 5.00,   // Pequeño contribuyente 5%
        'Opcional Simplificado' => 7.00,   // Opcional simplificado 7%
        default                 => 12.00,
    };

    $impuesto = round($base * $tasa / 100, 2);

    return [
        'base_imponible'     => $base,
        'tasa_impuesto'      => $tasa,
        'impuesto_calculado' => $impuesto,
    ];
}

// ============================================
// GET — Consultar contribuyente + datos persona
// ============================================
// GET /tributaria.php               → todos los contribuyentes
// GET /tributaria.php?id_persona=5  → por id_persona (consume API 1)
// GET /tributaria.php?id_contribuyente=3 → por id propio
if ($method === 'GET') {

    try {
        // Buscar por id_persona
        if (!empty($_GET['id_persona'])) {
            if (!is_numeric($_GET['id_persona'])) {
                http_response_code(400);
                echo json_encode(["error" => "id_persona debe ser numérico"]);
                exit();
            }

            $id_persona = intval($_GET['id_persona']);

            // 1. Consumir API 1 para obtener datos personales
            $persona = obtenerPersonaDesdeAPI($id_persona);
            if (isset($persona['error'])) {
                http_response_code(502);
                echo json_encode(["error" => "Error al obtener persona: " . $persona['error']]);
                exit();
            }

            // 2. Buscar contribuyente en nuestra BD
            $stmt = $pdo->prepare("
                SELECT * FROM contribuyente
                WHERE id_persona = :id_persona AND activo = 1
            ");
            $stmt->execute([':id_persona' => $id_persona]);
            $contribuyente = $stmt->fetch(PDO::FETCH_ASSOC);

            // 3. Si existe, traer sus declaraciones
            $declaraciones = [];
            if ($contribuyente) {
                $stmt2 = $pdo->prepare("
                    SELECT * FROM declaracion
                    WHERE id_contribuyente = :id
                    ORDER BY periodo DESC
                ");
                $stmt2->execute([':id' => $contribuyente['id_contribuyente']]);
                $declaraciones = $stmt2->fetchAll(PDO::FETCH_ASSOC);
            }

            // 4. Respuesta combinada: datos de persona + datos tributarios
            $respuesta = [
                "datos_personales" => $persona,
                "contribuyente"    => $contribuyente ?: null,
                "declaraciones"    => $declaraciones,
                "total_declaraciones" => count($declaraciones),
                "total_impuesto_pendiente" => array_sum(
                    array_column(
                        array_filter($declaraciones, fn($d) => $d['estado'] === 'Pendiente'),
                        'impuesto_calculado'
                    )
                )
            ];

            // Bitácora
            $pdo->prepare("INSERT INTO bitacora (accion, tabla_afectada, id_registro, descripcion, ip_origen)
                VALUES ('READ', 'contribuyente', :id, 'Consulta por id_persona', :ip)")
                ->execute([':id' => $id_persona, ':ip' => $_SERVER['REMOTE_ADDR'] ?? null]);

            http_response_code(200);
            echo json_encode($respuesta);
            exit();
        }

        // Buscar por id_contribuyente
        if (!empty($_GET['id_contribuyente'])) {
            if (!is_numeric($_GET['id_contribuyente'])) {
                http_response_code(400);
                echo json_encode(["error" => "id_contribuyente debe ser numérico"]);
                exit();
            }

            $id = intval($_GET['id_contribuyente']);
            $stmt = $pdo->prepare("SELECT * FROM contribuyente WHERE id_contribuyente = :id AND activo = 1");
            $stmt->execute([':id' => $id]);
            $contribuyente = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$contribuyente) {
                http_response_code(404);
                echo json_encode(["error" => "Contribuyente no encontrado con id: $id"]);
                exit();
            }

            // Consumir API 1
            $persona = obtenerPersonaDesdeAPI($contribuyente['id_persona']);

            $stmt2 = $pdo->prepare("SELECT * FROM declaracion WHERE id_contribuyente = :id ORDER BY periodo DESC");
            $stmt2->execute([':id' => $id]);
            $declaraciones = $stmt2->fetchAll(PDO::FETCH_ASSOC);

            http_response_code(200);
            echo json_encode([
                "datos_personales" => $persona,
                "contribuyente"    => $contribuyente,
                "declaraciones"    => $declaraciones,
            ]);
            exit();
        }

        // Sin filtro: traer todos los contribuyentes activos
        $stmt = $pdo->prepare("SELECT * FROM contribuyente WHERE activo = 1 ORDER BY id_contribuyente");
        $stmt->execute();
        $contribuyentes = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Para cada uno consumir API 1 y adjuntar nombre
        foreach ($contribuyentes as &$c) {
            $persona = obtenerPersonaDesdeAPI($c['id_persona']);
            $c['nombre_completo'] = isset($persona['primer_nombre'])
                ? trim("{$persona['primer_nombre']} {$persona['segundo_nombre']} {$persona['primer_apellido']} {$persona['segundo_apellido']}")
                : 'No disponible';
            $c['correo'] = $persona['correo'] ?? null;
        }

        http_response_code(200);
        echo json_encode($contribuyentes);

    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(["error" => "Error al consultar: " . $e->getMessage()]);
    }
    exit();
}

// ============================================
// POST — Registrar nuevo contribuyente
// ============================================
// Primero verifica que la persona exista en API 1
// luego la registra como contribuyente en nuestra BD
if ($method === 'POST' && empty($_GET['accion'])) {

    $errores = [];
    if (empty($body['id_persona']))  $errores[] = 'id_persona';
    if (empty($body['nit']))         $errores[] = 'nit';

    if (!empty($errores)) {
        http_response_code(400);
        echo json_encode(["error" => "Datos requeridos: " . implode(", ", $errores)]);
        exit();
    }

    // Verificar que la persona existe en API 1
    $persona = obtenerPersonaDesdeAPI($body['id_persona']);
    if (isset($persona['error'])) {
        http_response_code(404);
        echo json_encode(["error" => "Persona no encontrada en API 1: " . $persona['error']]);
        exit();
    }

    // Verificar que no esté ya registrada
    $stmt = $pdo->prepare("SELECT id_contribuyente FROM contribuyente WHERE id_persona = :id");
    $stmt->execute([':id' => $body['id_persona']]);
    if ($stmt->fetch()) {
        http_response_code(400);
        echo json_encode(["error" => "Esta persona ya está registrada como contribuyente"]);
        exit();
    }

    try {
        $stmt = $pdo->prepare("
            INSERT INTO contribuyente (id_persona, nit, tipo_contribuyente, regimen)
            VALUES (:id_persona, :nit, :tipo, :regimen)
        ");
        $stmt->execute([
            ':id_persona' => intval($body['id_persona']),
            ':nit'        => trim($body['nit']),
            ':tipo'       => $body['tipo_contribuyente'] ?? 'Natural',
            ':regimen'    => $body['regimen']            ?? 'Pequeño Contribuyente',
        ]);

        $id = $pdo->lastInsertId();

        $pdo->prepare("INSERT INTO bitacora (accion, tabla_afectada, id_registro, descripcion, ip_origen)
            VALUES ('CREATE', 'contribuyente', :id, :desc, :ip)")
            ->execute([':id' => $id, ':desc' => json_encode($body), ':ip' => $_SERVER['REMOTE_ADDR'] ?? null]);

        http_response_code(200);
        echo json_encode([
            "id_contribuyente" => (int)$id,
            "mensaje"          => "Contribuyente registrado exitosamente",
            "persona"          => [
                "nombre" => "{$persona['primer_nombre']} {$persona['primer_apellido']}",
                "correo" => $persona['correo'] ?? null,
            ]
        ]);

    } catch (PDOException $e) {
        if ($e->getCode() === '23000') {
            http_response_code(400);
            echo json_encode(["error" => "El NIT ya está registrado"]);
            exit();
        }
        http_response_code(500);
        echo json_encode(["error" => "Error al registrar contribuyente"]);
    }
    exit();
}

// ============================================
// PUT — Actualizar contribuyente
// ============================================
if ($method === 'PUT' && empty($_GET['accion'])) {

    if (empty($body['id_contribuyente']) || !is_numeric($body['id_contribuyente'])) {
        http_response_code(400);
        echo json_encode(["error" => "id_contribuyente requerido"]);
        exit();
    }

    $id = intval($body['id_contribuyente']);
    $stmt = $pdo->prepare("SELECT id_contribuyente FROM contribuyente WHERE id_contribuyente = :id AND activo = 1");
    $stmt->execute([':id' => $id]);
    if (!$stmt->fetch()) {
        http_response_code(404);
        echo json_encode(["error" => "Contribuyente no encontrado con id: $id"]);
        exit();
    }

    $campos = ['nit', 'tipo_contribuyente', 'regimen'];
    $sets   = [];
    $params = [':id' => $id];

    foreach ($campos as $campo) {
        if (isset($body[$campo])) {
            $sets[]            = "$campo = :$campo";
            $params[":$campo"] = $body[$campo];
        }
    }

    if (empty($sets)) {
        http_response_code(400);
        echo json_encode(["error" => "No se enviaron campos para actualizar"]);
        exit();
    }

    try {
        $pdo->prepare("UPDATE contribuyente SET " . implode(", ", $sets) . " WHERE id_contribuyente = :id")
            ->execute($params);

        $pdo->prepare("INSERT INTO bitacora (accion, tabla_afectada, id_registro, descripcion, ip_origen)
            VALUES ('UPDATE', 'contribuyente', :id, :desc, :ip)")
            ->execute([':id' => $id, ':desc' => json_encode($body), ':ip' => $_SERVER['REMOTE_ADDR'] ?? null]);

        http_response_code(200);
        echo json_encode(["id_contribuyente" => $id, "mensaje" => "Actualizado correctamente"]);

    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(["error" => "Error al actualizar: " . $e->getMessage()]);
    }
    exit();
}

// ============================================
// DELETE — Eliminar contribuyente (lógico)
// ============================================
if ($method === 'DELETE') {

    if (empty($body['id_contribuyente']) || !is_numeric($body['id_contribuyente'])) {
        http_response_code(400);
        echo json_encode(["error" => "id_contribuyente requerido"]);
        exit();
    }

    $id = intval($body['id_contribuyente']);
    $stmt = $pdo->prepare("SELECT id_contribuyente FROM contribuyente WHERE id_contribuyente = :id AND activo = 1");
    $stmt->execute([':id' => $id]);
    if (!$stmt->fetch()) {
        http_response_code(404);
        echo json_encode(["error" => "Contribuyente no encontrado o ya eliminado"]);
        exit();
    }

    try {
        $pdo->prepare("UPDATE contribuyente SET activo = 0 WHERE id_contribuyente = :id")
            ->execute([':id' => $id]);

        $pdo->prepare("INSERT INTO bitacora (accion, tabla_afectada, id_registro, descripcion, ip_origen)
            VALUES ('DELETE', 'contribuyente', :id, 'Eliminación lógica', :ip)")
            ->execute([':id' => $id, ':ip' => $_SERVER['REMOTE_ADDR'] ?? null]);

        http_response_code(200);
        echo json_encode(["id_contribuyente" => $id, "activo" => 0, "mensaje" => "Contribuyente eliminado"]);

    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(["error" => "Error al eliminar: " . $e->getMessage()]);
    }
    exit();
}

http_response_code(405);
echo json_encode(["error" => "Método no permitido"]);
?>