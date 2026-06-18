<?php
// conexion.php

// 1. PERMISOS CORS (Indispensables para Vercel)
header("Access-Control-Allow-Origin: *"); 
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");
header("Access-Control-Allow-Credentials: true");

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    exit(0);
}

// 2. CONFIGURACIÓN DE LA BASE DE DATOS
// Usaremos variables de entorno por seguridad, o puedes hardcodear los datos que te dé el hosting
$host     = isset($_ENV['DB_HOST']) ? $_ENV['DB_HOST'] : 'localhost';
$dbname   = isset($_ENV['DB_NAME']) ? $_ENV['DB_NAME'] : 'agenda_db';
$user     = isset($_ENV['DB_USER']) ? $_ENV['DB_USER'] : 'root';
$password = isset($_ENV['DB_PASS']) ? $_ENV['DB_PASS'] : '';

$conn = mysqli_connect($host, $user, $password, $dbname);

if (!$conn) {
    echo json_encode(["error" => "No se pudo conectar a la BD: " . mysqli_connect_error()]);
    exit;
}

mysqli_set_charset($conn, "utf8mb4");
?>