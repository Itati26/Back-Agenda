<?php
// notas.php
// GET    → lista las notas del usuario
// POST   → crea una nota nueva
// PUT    → edita una nota
// DELETE → borra una nota

header("Access-Control-Allow-Origin: http://localhost:5173");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Content-Type: application/json");

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') { http_response_code(200); exit; }

require "conexion.php";
require "leer_token.php"; // deja $id_login listo, o corta con error

$metodo = $_SERVER['REQUEST_METHOD'];
$datos  = json_decode(file_get_contents("php://input"), true) ?? [];
$id     = $_GET["id"] ?? 0;

// ── LISTAR ───────────────────────────────────────────────────────
if ($metodo == "GET") {
    $resultado = mysqli_query($conn,
        "SELECT * FROM notas WHERE id_login = $id_login ORDER BY creado_en DESC"
    );
    $notas = [];
    while ($fila = mysqli_fetch_assoc($resultado)) {
        $notas[] = $fila;
    }
    echo json_encode($notas);
}

// ── CREAR ─────────────────────────────────────────────────────────
elseif ($metodo == "POST") {
    $titulo = $datos["titulo"]      ?? "";
    $desc   = $datos["descripcion"] ?? "";

    if (empty($titulo)) {
        echo json_encode(["error" => "El título es obligatorio"]);
        exit;
    }

    $ok = mysqli_query($conn,
        "INSERT INTO notas (id_login, titulo, descripcion)
         VALUES ($id_login, '$titulo', '$desc')"
    );

    if ($ok) {
        echo json_encode(["ok" => true, "id_nota" => mysqli_insert_id($conn)]);
    } else {
        echo json_encode(["error" => "No se pudo guardar"]);
    }
}

// ── EDITAR ────────────────────────────────────────────────────────
elseif ($metodo == "PUT") {
    $titulo = $datos["titulo"]      ?? "";
    $desc   = $datos["descripcion"] ?? "";

    $ok = mysqli_query($conn,
        "UPDATE notas SET titulo='$titulo', descripcion='$desc'
         WHERE id_nota=$id AND id_login=$id_login"
    );

    echo json_encode(["ok" => (bool)$ok]);
}

// ── BORRAR ────────────────────────────────────────────────────────
elseif ($metodo == "DELETE") {
    $ok = mysqli_query($conn,
        "DELETE FROM notas WHERE id_nota=$id AND id_login=$id_login"
    );
    echo json_encode(["ok" => (bool)$ok]);
}
?>
