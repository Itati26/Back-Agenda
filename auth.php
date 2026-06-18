<?php
// auth.php
// POST ?action=login    → inicia sesión, devuelve token
// POST ?action=register → registra usuario, devuelve token

header("Access-Control-Allow-Origin: http://localhost:5173");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Content-Type: application/json");

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') { http_response_code(200); exit; }

require "conexion.php";

$action = $_GET['action'] ?? '';
$datos  = json_decode(file_get_contents("php://input"), true);

$correo   = $datos["correo"]    ?? "";
$password = $datos["contrasena"] ?? "";

if (empty($correo) || empty($password)) {
    echo json_encode(["error" => "Correo y contraseña son obligatorios"]);
    exit;
}

// ── Función para crear un token simple (base64 del payload) ──────
// No es JWT firmado, pero es suficiente para un proyecto sencillo.
// El token guarda el id_login para poder leerlo en cada petición.
function crearToken($id_login, $correo) {
    $payload = json_encode([
        "id_login" => $id_login,
        "correo"   => $correo,
        "ts"       => time()
    ]);
    return rtrim(strtr(base64_encode($payload), '+/', '-_'), '=');
}

// ── REGISTRAR ────────────────────────────────────────────────────
if ($action == 'register') {

    // Verificar si el correo ya existe
    $buscar = mysqli_query($conn, "SELECT id_login FROM login WHERE correo = '$correo'");
    if (mysqli_num_rows($buscar) > 0) {
        echo json_encode(["error" => "Ese correo ya está registrado"]);
        exit;
    }

    $hash = password_hash($password, PASSWORD_DEFAULT);

    $ok = mysqli_query($conn,
        "INSERT INTO login (correo, contrasena) VALUES ('$correo', '$hash')"
    );

    if ($ok) {
        $id_login = mysqli_insert_id($conn);
        $token    = crearToken($id_login, $correo);
        echo json_encode([
            "ok"       => true,
            "id_login" => $id_login,
            "correo"   => $correo,
            "token"    => $token
        ]);
    } else {
        echo json_encode(["error" => "No se pudo registrar"]);
    }
    exit;
}

// ── LOGIN ────────────────────────────────────────────────────────
if ($action == 'login') {

    $resultado = mysqli_query($conn, "SELECT * FROM login WHERE correo = '$correo'");
    $usuario   = mysqli_fetch_assoc($resultado);

    if (!$usuario || !password_verify($password, $usuario["contrasena"])) {
        echo json_encode(["error" => "Correo o contraseña incorrectos"]);
        exit;
    }

    $token = crearToken($usuario["id_login"], $usuario["correo"]);

    echo json_encode([
        "ok"       => true,
        "id_login" => $usuario["id_login"],
        "correo"   => $usuario["correo"],
        "is_pro"   => (bool)$usuario["is_pro"],
        "token"    => $token
    ]);
    exit;
}

echo json_encode(["error" => "Acción no válida"]);
?>
