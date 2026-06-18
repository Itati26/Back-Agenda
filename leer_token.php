<?php
function obtenerIdLogin() {
    $header = $_SERVER["HTTP_AUTHORIZATION"] ?? $_SERVER["REDIRECT_HTTP_AUTHORIZATION"] ?? (getallheaders()["Authorization"] ?? "");
    $token = str_replace("Bearer ", "", $header);
    if (empty($token)) return 0;
    $token = strtr($token, "-_", "+/");
    $padding = strlen($token) % 4;
    if ($padding) $token .= str_repeat("=", 4 - $padding);
    $payload = json_decode(base64_decode($token), true);
    return $payload["id_login"] ?? 0;
}
$id_login = obtenerIdLogin();
if (!$id_login) { echo json_encode(["error" => "No has iniciado sesion"]); exit; }
$validar = mysqli_query($conn, "SELECT id_login FROM login WHERE id_login = $id_login");
if (!$validar || mysqli_num_rows($validar) === 0) {
    echo json_encode(["error" => "No has iniciado sesion"]);
    exit;
}
?>