<?php
// conexion.php

// 1. PERMISOS CORS
header("Access-Control-Allow-Origin: *"); 
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit(0);
}

// 2. CONFIGURACIÓN MEDIANTE MYSQL_URL EXTERNA
$database_url = getenv('MYSQL_URL');

if ($database_url) {
    // Parseamos la URL para extraer los datos de conexión automáticamente
    $url = parse_url($database_url);
    
    $host     = $url["host"] ?? 'localhost';
    $user     = $url["user"] ?? 'root';
    $password = $url["pass"] ?? '';
    $dbname   = substr($url["path"], 1) ?? 'railway';
    $port     = $url["port"] ?? 3306;
    
    $conn = mysqli_connect($host, $user, $password, $dbname, $port);
} else {
    // Respaldo para tu localhost
    $conn = mysqli_connect('localhost', 'root', '', 'railway');
}

if (!$conn) {
    header("Content-Type: application/json");
    echo json_encode(["error" => "No se pudo conectar a la BD: " . mysqli_connect_error()]);
    exit;
}

mysqli_set_charset($conn, "utf8mb4");
?>