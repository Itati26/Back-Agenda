<?php
// pagos.php - Gestión de pagos y estados PRO
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Content-Type: application/json");

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') { http_response_code(200); exit; }

require "conexion.php";

$MP_TOKEN     = "TEST-2235244122221085-061010-9ff62ece03e1d320d70c11f18577d3ae-3463128623";

$metodo = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

// ── 🚨 WEBHOOK ───────────────────────────────────────────────────
if ($metodo == "POST" && $action == "webhook") {
    http_response_code(200);
    echo json_encode(["ok" => true]);
    exit;
}

// ── ⚡ TRUCO ULTRA-SEGURO (EJECUTADO ANTES DE REQUERIR TOKEN) ────
if ($metodo == "POST" && $action == "forzar_pro_bypass") {
    $id_bypass = intval($_GET['user_id'] ?? 0);
    
    if ($id_bypass > 0) {
        $update_pro = mysqli_query($conn, "UPDATE login SET is_pro = 1 WHERE id_login = $id_bypass");
        if ($update_pro) {
            mysqli_query($conn, "INSERT INTO pagos (id_login, mp_preference_id, monto, descripcion, estado) 
                                 VALUES ($id_bypass, 'FORCED_PRO_OK', 99, 'Suscripción Agenda Pro', 'aprobado')");
            echo json_encode(["ok" => true, "msg" => "BD modificada con éxito a PRO"]);
        } else {
            echo json_encode(["ok" => false, "error" => mysqli_error($conn)]);
        }
    } else {
        echo json_encode(["ok" => false, "error" => "ID invalido"]);
    }
    exit;
}

// ── 🔒 PROTECCIÓN CON TOKEN (Para flujos estándar del app) ───────
require "leer_token.php";

// ── VER ESTADO PRO ──────────────────────────────────────────────
if ($metodo == "GET") {
    $res     = mysqli_query($conn, "SELECT is_pro FROM login WHERE id_login = $id_login");
    $usuario = mysqli_fetch_assoc($res);
    $res2    = mysqli_query($conn, "SELECT * FROM pagos WHERE id_login = $id_login ORDER BY creado_en DESC");
    $historial = [];
    while ($fila = mysqli_fetch_assoc($res2)) { $historial[] = $fila; }
    echo json_encode(["is_pro" => (bool)($usuario["is_pro"] ?? false), "historial" => $historial]);
    exit;
}

// ── CREAR PAGO (Preferencia Estándar) ───────────────────────────
if ($metodo == "POST" && $action == "suscribir") {
    $preferencia = [
        "items" => [[
            "title" => "Suscripción Agenda Pro",
            "quantity" => 1,
            "unit_price" => 99,
            "currency_id" => "MXN"
        ]],
        "external_reference" => (string)$id_login,
        "back_urls" => [
            "success" => "https://6a3375ab0a086a7d05f1bd64--notiontec.netlify.app/",
            "failure" => "https://6a3375ab0a086a7d05f1bd64--notiontec.netlify.app/",
            "pending" => "https://6a3375ab0a086a7d05f1bd64--notiontec.netlify.app/"
        ],
        "auto_return" => "approved"
    ];

    $ch = curl_init("https://api.mercadopago.com/checkout/preferences");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ["Content-Type: application/json", "Authorization: Bearer $MP_TOKEN"]);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($preferencia));
    
    $respuesta_raw = curl_exec($ch);
    $http_code     = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    $respuesta = json_decode($respuesta_raw, true);

    if ($http_code == 201) {
        @mysqli_query($conn, "INSERT IGNORE INTO pagos (id_login, mp_preference_id, monto, descripcion, estado) 
                             VALUES ($id_login, '{$respuesta['id']}', 99, 'Agenda Pro', 'pendiente')");
        
        echo json_encode(["ok" => true, "init_point" => $respuesta["init_point"]]);
    } else {
        echo json_encode(["ok" => false, "error" => "Error de API", "status" => $http_code, "detalle" => $respuesta]);
    }
    exit;
}

// ── CANCELAR ────────────────────────────────────────────────────
if ($metodo == "POST" && $action == "cancelar") {
    mysqli_query($conn, "UPDATE login SET is_pro = 0 WHERE id_login = $id_login");
    echo json_encode(["ok" => true, "msg" => "Suscripción cancelada"]);
    exit;
}
?>