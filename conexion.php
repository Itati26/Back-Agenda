<?php
// --- CONFIGURACIÓN GLOBAL DE CORS ---
// Permite que el frontend en Vercel se comunique con este backend en Railway
$allowed_origin = "https://frontedn-agenda-z23o.vercel.app"; // Tu URL de Vercel
header("Access-Control-Allow-Origin: $allowed_origin");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");
header("Access-Control-Allow-Credentials: true");

// Responder inmediatamente a las peticiones OPTIONS (Preflight)
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit();
}

// --- CONEXIÓN A BASE DE DATOS ---
$database_url = getenv('MYSQL_URL');

if ($database_url) {
    $url = parse_url($database_url);
    
    $host     = $url["host"] ?? 'localhost';
    $user     = $url["user"] ?? 'root';
    $password = $url["pass"] ?? '';
    $dbname   = substr($url["path"], 1) ?? 'railway';
    $port     = $url["port"] ?? 3306;
    
    $conn = mysqli_connect($host, $user, $password, $dbname, $port);
} else {
    $conn = mysqli_connect('localhost', 'root', '', 'railway');
}

if (!$conn) {
    header("Content-Type: application/json");
    echo json_encode(["error" => "No se pudo conectar a la BD: " . mysqli_connect_error()]);
    exit;
}

mysqli_set_charset($conn, "utf8mb4");
?>