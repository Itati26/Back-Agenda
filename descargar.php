<?php
require "conexion.php"; // Aquí verificas la BD y el estado Pro

// 1. Recibir el ID de la tarea
$id = $_GET['id'] ?? null;

// 2. Verificar sesión y estado Pro (esto depende de cómo manejes tu auth)
// Ejemplo: $user = verificarUsuarioActual();
// if (!$user || $user['status'] !== 'pro') {
//     http_response_code(403);
//     echo json_encode(["error" => "Debes ser usuario Pro"]);
//     exit;
// }

// 3. Buscar la ruta del archivo en tu base de datos
$query = "SELECT archivo_pdf FROM tareas WHERE id_tarea = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$tarea = $result->fetch_assoc();

if ($tarea && file_exists($tarea['archivo_pdf'])) {
    // 4. Enviar el archivo de forma segura
    header('Content-Type: application/pdf');
    header('Content-Disposition: attachment; filename="tarea_adjunto.pdf"');
    readfile($tarea['archivo_pdf']);
    exit;
} else {
    http_response_code(404);
    echo "Archivo no encontrado.";
}
?>