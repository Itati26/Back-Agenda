<?php
// conexion.php

// CONFIGURACIÓN MEDIANTE MYSQL_URL EXTERNA
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