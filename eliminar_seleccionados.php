<?php
// Iniciar la sesión
session_start();

// Verificar si el usuario está autenticado
if (!isset($_SESSION['usuario_autenticado']) || !$_SESSION['usuario_autenticado']) {
    http_response_code(403); // Prohibido
    echo json_encode(['success' => false, 'message' => 'No autorizado']);
    exit();
}

// Obtener los datos enviados en la solicitud POST
$data = json_decode(file_get_contents('php://input'), true);
if (!isset($data['ids']) || !is_array($data['ids'])) {
    http_response_code(400); // Solicitud incorrecta
    echo json_encode(['success' => false, 'message' => 'Datos inválidos']);
    exit();
}

// Conexión a la base de datos (cambia los valores según tu configuración)
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "appdatabase";

$conexion = new mysqli($servername, $username, $password, $dbname);

// Verificar la conexión
if ($conexion->connect_error) {
    http_response_code(500); // Error interno del servidor
    echo json_encode(['success' => false, 'message' => 'Error en la conexión con la base de datos']);
    exit();
}

// Preparar la consulta SQL para eliminar los registros
$ids = implode(',', array_map('intval', $data['ids']));
$sql_delete = "DELETE FROM alumnos WHERE id IN ($ids)";

if ($conexion->query($sql_delete) === TRUE) {
    echo json_encode(['success' => true]);
} else {
    http_response_code(500); // Error interno del servidor
    echo json_encode(['success' => false, 'message' => 'Error al eliminar los registros: ' . $conexion->error]);
}

$conexion->close();
?>
