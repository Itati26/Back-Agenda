<?php
// conexion.php

// 1. PERMISOS CORS (Indispensables para Vercel)
header("Access-Control-Allow-Origin: *"); 
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit(0);
}

// 2. CONFIGURACIÓN DE LA BASE DE DATOS (Usando getenv para Railway)
$host     = getenv('DB_HOST')     ?: 'localhost';
$dbname   = getenv('DB_NAME')     ?: 'railway'; // Cambiado a 'railway' según tu panel
$user     = getenv('DB_USER')     ?: 'root';
$password = getenv('DB_PASS')     ?: ''; // Lee la variable DB_PASS que tienes en Railway

$conn = mysqli_connect($host, $user, $password, $dbname);

if (!$conn) {
    header("Content-Type: application/json");
    echo json_encode(["error" => "No se pudo conectar a la BD: " . mysqli_connect_error()]);
    exit;
}

mysqli_set_charset($conn, "utf8mb4");
?>