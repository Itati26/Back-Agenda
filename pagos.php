<?php
// pagos.php
// GET                      → historial de pagos y estado pro
// POST ?action=suscribir   → crea suscripción con 1 mes gratis
// POST ?action=cancelar    → cancela la suscripción
// POST ?action=webhook     → MercadoPago avisa cambios de estado

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Content-Type: application/json");

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') { http_response_code(200); exit; }

require "conexion.php";

$MP_TOKEN     = "TEST-2235244122221085-061010-9ff62ece03e1d320d70c11f18577d3ae-3463128623";
$FRONTEND_URL = "https://tu-agenda-vercel.vercel.app";

$metodo = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

// ── 🚨 EXCEPCIÓN DEL WEBHOOK (MercadoPago no necesita Token) ──────
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
    echo json_encode(["ok" => true, "mensaje" => "Webhook procesado"]);
    exit;
}

// ── 🔒 PARA TODO LO DEMÁS SÍ PROTEGEMOS CON TOKEN ──────────────────
require "leer_token.php"; // Deja $id_login listo, o corta con error si el usuario no inició sesión

// ── VER si el usuario es Pro ──────────────────────────────────────
if ($metodo == "GET") {
    $res     = mysqli_query($conn, "SELECT is_pro FROM login WHERE id_login = $id_login");
    $usuario = mysqli_fetch_assoc($res);

    $res2      = mysqli_query($conn, "SELECT * FROM pagos WHERE id_login = $id_login ORDER BY creado_en DESC");
    $historial = [];
    while ($fila = mysqli_fetch_assoc($res2)) {
        $historial[] = $fila;
    }

    echo json_encode([
        "is_pro"    => (bool)($usuario["is_pro"] ?? false),
        "historial" => $historial
    ]);
    exit;
}

// ── CREAR SUSCRIPCIÓN con 1 mes gratis ───────────────────────────
if ($metodo == "POST" && $action == "suscribir") {

    // 💡 Mantenemos la consulta por seguridad, pero usaremos el correo de prueba para MercadoPago
    $res     = mysqli_query($conn, "SELECT correo FROM login WHERE id_login = $id_login");
    $usuario = mysqli_fetch_assoc($res);

    $suscripcion = [
        "reason"             => "Agenda Pro - Plan mensual",
        "external_reference" => "$id_login",
        // 🔥 ATAJO: Correo comprador de prueba oficial para evitar bloqueos por autocompra
        "payer_email"        => "test_user_46945293@testuser.com", 
        "auto_recurring"     => [
            "frequency"          => 1,
            "frequency_type"     => "months",
            "transaction_amount" => 99,
            "currency_id"        => "MXN",
            "free_trial"         => [
                "frequency"      => 1,
                "frequency_type" => "months"
            ]
        ],
        "back_url" => $FRONTEND_URL,
        "status"   => "pending"
    ];

    $ch = curl_init("https://api.mercadopago.com/preapproval");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "Content-Type: application/json",
        "Authorization: Bearer $MP_TOKEN"
    ]);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($suscripcion));
    $respuesta_raw = curl_exec