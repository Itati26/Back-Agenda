<?php
// tareas.php
// GET    → lista las tareas del usuario
// POST   → crea una tarea nueva
// PUT    → edita una tarea
// DELETE → borra una tarea

header("Access-Control-Allow-Origin: *");
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
        "SELECT * FROM tareas WHERE id_login = $id_login ORDER BY fecha_entrega ASC"
    );
    $tareas = [];
    while ($fila = mysqli_fetch_assoc($resultado)) {
        $tareas[] = $fila;
    }
    echo json_encode($tareas);
}

// ── CREAR ────────────────────────────────────────────────────────
elseif ($metodo == "POST") {
    $nombre = $datos["nombre_tarea"]  ?? "";
    $fecha  = $datos["fecha_entrega"] ?? null;
    $desc   = $datos["descripcion"]   ?? "";
    $status = $datos["status"]        ?? "P";

    if (empty($nombre)) {
        echo json_encode(["error" => "El nombre es obligatorio"]);
        exit;
    }

    $fecha_sql = $fecha ? "'$fecha'" : "NULL";

    $ok = mysqli_query($conn,
        "INSERT INTO tareas (id_login, nombre_tarea, fecha_entrega, descripcion, status)
         VALUES ($id_login, '$nombre', $fecha_sql, '$desc', '$status')"
    );

    if ($ok) {
        echo json_encode(["ok" => true, "id_tarea" => mysqli_insert_id($conn)]);
    } else {
        echo json_encode(["error" => "No se pudo guardar"]);
    }
}

// ── EDITAR ────────────────────────────────────────────────────────
elseif ($metodo == "PUT") {
    $nombre = $datos["nombre_tarea"]  ?? "";
    $fecha  = $datos["fecha_entrega"] ?? null;
    $desc   = $datos["descripcion"]   ?? "";
    $status = $datos["status"]        ?? "P";

    $fecha_sql = $fecha ? "'$fecha'" : "NULL";

    $ok = mysqli_query($conn,
        "UPDATE tareas
         SET nombre_tarea='$nombre', fecha_entrega=$fecha_sql, descripcion='$desc', status='$status'
         WHERE id_tarea=$id AND id_login=$id_login"
    );

    echo json_encode(["ok" => (bool)$ok]);
}

// ── BORRAR ────────────────────────────────────────────────────────
elseif ($metodo == "DELETE") {
    $ok = mysqli_query($conn,
        "DELETE FROM tareas WHERE id_tarea=$id AND id_login=$id_login"
    );
    echo json_encode(["ok" => (bool)$ok]);
}


?>


