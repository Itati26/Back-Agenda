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

// 2. CONFIGURACIÓN DE LA BASE DE DATOS
$host     = getenv('DB_HOST')     ?: 'localhost';
$dbname   = getenv('DB_NAME')     ?: 'railway'; 
$user     = getenv('DB_USER')     ?: 'root';
$password = getenv('DB_PASS')     ?: ''; 
// Agregamos el puerto de Railway (por defecto interno suele ser 3306)
$port     = getenv('DB_PORT')     ?: '3306'; 

// Pasamos el $port como quinto parámetro para forzar la conexión interna correcta
$conn = mysqli_connect($host, $user, $password, $dbname, $port);

if (!$conn) {
    header("Content-Type: application/json");
    echo json_encode(["error" => "No se pudo conectar a la BD: " . mysqli_connect_error()]);
    exit;
}

mysqli_set_charset($conn, "utf8mb4");
?>