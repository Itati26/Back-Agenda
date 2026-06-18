<?php
// pagos.php - Gestión de suscripciones y estados PRO
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Content-Type: application/json");

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') { http_response_code(200); exit; }

require "conexion.php";

$MP_TOKEN     = "TEST-2235244122221085-061010-9ff62ece03e1d320d70c11f18577d3ae-3463128623";
// Usamos google.com como back_url para evitar restricciones de dominios privados de Vercel
$FRONTEND_URL = "https://tu-agenda-real.vercel.app";

$metodo = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

// ── 🚨 WEBHOOK (Sin protección de token) ─────────────────────────
if ($metodo == "POST" && $action == "webhook") {
    $tipo    = $_GET["type"]    ?? "";
    $data_id = $_GET["data_id"] ?? "";

    if ($tipo == "subscription_preapproval" && $data_id) {
        $ch = curl_init("https://api.mercadopago.com/preapproval/$data_id");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ["Authorization: Bearer $MP_TOKEN"]);
        $sub = json_decode(curl_exec($ch), true);
        curl_close($ch);

        $estado_mp  = $sub["status"]             ?? "";
        $id_usuario = $sub["external_reference"] ?? 0;

        if ($estado_mp == "authorized") {
            mysqli_query($conn, "UPDATE pagos SET estado='aprobado' WHERE mp_preference_id='$data_id'");
            mysqli_query($conn, "UPDATE login SET is_pro=1 WHERE id_login=$id_usuario");
        } elseif ($estado_mp == "cancelled") {
            mysqli_query($conn, "UPDATE pagos SET estado='cancelado' WHERE mp_preference_id='$data_id'");
            mysqli_query($conn, "UPDATE login SET is_pro=0 WHERE id_login=$id_usuario");
        }
    }
    http_response_code(200);
    echo json_encode(["ok" => true]);
    exit;
}

// ── 🔒 PROTECCIÓN CON TOKEN ───────────────────────────────────────
require "leer_token.php";

// ── VER ESTADO PRO Y PAGOS ────────────────────────────────────────
if ($metodo == "GET") {
    $res     = mysqli_query($conn, "SELECT is_pro FROM login WHERE id_login = $id_login");
    $usuario = mysqli_fetch_assoc($res);
    $res2    = mysqli_query($conn, "SELECT * FROM pagos WHERE id_login = $id_login ORDER BY creado_en DESC");
    $historial = [];
    while ($fila = mysqli_fetch_assoc($res2)) { $historial[] = $fila; }

    echo json_encode(["is_pro" => (bool)($usuario["is_pro"] ?? false), "historial" => $historial]);
    exit;
}

// ── CREAR SUSCRIPCIÓN (Con escudo protector) ──────────────────────
if ($metodo == "POST" && $action == "suscribir") {
    $suscripcion = [
    "reason"             => "Agenda Pro - Plan mensual",
    "external_reference" => "$id_login",
    "payer_email"        => "test_user_46945293@testuser.com", 
    "auto_recurring"     => [
        "frequency" => 1, 
        "frequency_type" => "months", 
        "transaction_amount" => 99, 
        "currency_id" => "MXN"
    ],
    "back_url" => $FRONTEND_URL, // <--- ¡ESTA LÍNEA ES OBLIGATORIA!
    "status"   => "pending"
];

    $ch = curl_init("https://api.mercadopago.com/preapproval");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ["Content-Type: application/json", "Authorization: Bearer $MP_TOKEN"]);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($suscripcion));
    $respuesta_raw = curl_exec($ch);
    curl_close($ch);

    $respuesta = json_decode($respuesta_raw, true);

    if (isset($respuesta["id"])) {
        // Usamos INSERT IGNORE para que errores de base de datos no rompan el proceso
        @mysqli_query($conn, "INSERT IGNORE INTO pagos (id_login, mp_preference_id, monto, descripcion, estado) 
                             VALUES ($id_login, '{$respuesta['id']}', 99, 'Agenda Pro - 1 mes gratis', 'pendiente')");
        
        echo json_encode([
            "ok" => true,
            "init_point" => $respuesta["init_point"] ?? null,
            "sandbox_init_point" => $respuesta["sandbox_init_point"] ?? null
        ]);
    } else {
        echo json_encode(["ok" => false, "error" => "Error de MercadoPago", "detalle" => $respuesta]);
    }
    exit;
}

// ── CANCELAR SUSCRIPCIÓN ──────────────────────────────────────────
if ($metodo == "POST" && $action == "cancelar") {
    $res = mysqli_query($conn, "SELECT mp_preference_id FROM pagos WHERE id_login=$id_login AND estado='aprobado' LIMIT 1");
    $pago = mysqli_fetch_assoc($res);

    if ($pago) {
        $sub_id = $pago["mp_preference_id"];
        $ch = curl_init("https://api.mercadopago.com/preapproval/$sub_id");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PUT");
        curl_setopt($ch, CURLOPT_HTTPHEADER, ["Content-Type: application/json", "Authorization: Bearer $MP_TOKEN"]);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(["status" => "cancelled"]));
        curl_exec($ch);
        curl_close($ch);
        mysqli_query($conn, "UPDATE pagos SET estado='cancelado' WHERE mp_preference_id='$sub_id'");
        mysqli_query($conn, "UPDATE login SET is_pro=0 WHERE id_login=$id_login");
        echo json_encode(["ok" => true]);
    } else {
        echo json_encode(["ok" => false, "error" => "No tienes suscripción"]);
    }
    exit;
}