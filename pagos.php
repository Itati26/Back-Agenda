<?php
// Configuración de CORS estricta
header("Access-Control-Allow-Origin: https://frontedn-agenda.vercel.app");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Credentials: true");
header("Content-Type: application/json");

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') { http_response_code(200); exit; }

require "conexion.php";

// Usar variable de entorno para seguridad
$MP_TOKEN = getenv('MP_TOKEN'); 

$metodo = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

// --- 1. WEBHOOK: Mercado Pago llama a esta ruta para avisar que pagaron ---
if ($metodo == "POST" && $action == "webhook") {
    $input = file_get_contents("php://input");
    $data = json_decode($input, true);

    // Verificamos que sea una notificación de pago
    if (isset($data['type']) && $data['type'] == 'payment') {
        $payment_id = $data['data']['id'];
        
        // Consultar a la API de MP para confirmar el estado
        $ch = curl_init("https://api.mercadopago.com/v1/payments/$payment_id");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ["Authorization: Bearer $MP_TOKEN"]);
        $res = curl_exec($ch);
        curl_close($ch);
        
        $payment = json_decode($res, true);
        
        if ($payment['status'] == 'approved') {
            $id_login = $payment['external_reference'];
            mysqli_query($conn, "UPDATE login SET is_pro = 1 WHERE id_login = $id_login");
            mysqli_query($conn, "UPDATE pagos SET estado = 'aprobado' WHERE mp_preference_id = '{$payment['preference_id']}'");
        }
    }
    http_response_code(200);
    exit;
}

// --- 2. LÓGICA DE USUARIO ---
require "leer_token.php";

// GET: Obtener estado y historial
if ($metodo == "GET") {
    $res = mysqli_query($conn, "SELECT is_pro FROM login WHERE id_login = $id_login");
    $usuario = mysqli_fetch_assoc($res);
    
    $res2 = mysqli_query($conn, "SELECT * FROM pagos WHERE id_login = $id_login ORDER BY creado_en DESC");
    $historial = [];
    while ($fila = mysqli_fetch_assoc($res2)) { $historial[] = $fila; }
    
    echo json_encode(["is_pro" => (bool)($usuario["is_pro"] ?? false), "historial" => $historial]);
    exit;
}

// POST: Crear preferencia de pago
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
            "success" => "https://frontedn-agenda.vercel.app/pagos",
            "failure" => "https://frontedn-agenda.vercel.app/pagos",
            "pending" => "https://frontedn-agenda.vercel.app/pagos"
        ],
        "auto_return" => "approved"
    ];

    $ch = curl_init("https://api.mercadopago.com/checkout/preferences");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ["Content-Type: application/json", "Authorization: Bearer $MP_TOKEN"]);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($preferencia));
    
    $respuesta_raw = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    $respuesta = json_decode($respuesta_raw, true);

    if ($http_code == 201) {
        mysqli_query($conn, "INSERT INTO pagos (id_login, mp_preference_id, monto, descripcion, estado) 
                            VALUES ($id_login, '{$respuesta['id']}', 99, 'Agenda Pro', 'pendiente')");
        
        echo json_encode(["ok" => true, "init_point" => $respuesta["init_point"]]);
    } else {
        echo json_encode(["ok" => false, "error" => "Error de API", "detalle" => $respuesta]);
    }
    exit;
}
?>