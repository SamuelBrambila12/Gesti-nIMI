<?php
session_start();

// Verificar si el usuario está autenticado
if (!isset($_SESSION['usuario_autenticado']) || !$_SESSION['usuario_autenticado']) {
    echo json_encode(['success' => false, 'message' => 'Usuario no autenticado']);
    exit();
}

// Conexión a la base de datos
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "appdatabase";

$conexion = new mysqli($servername, $username, $password, $dbname);

if ($conexion->connect_error) {
    echo json_encode(['success' => false, 'message' => 'Conexión fallida: ' . $conexion->error]);
    exit();
}

// Obtener parámetros de la solicitud POST
$data = json_decode(file_get_contents('php://input'), true);
$alumno_id = isset($data['alumno_id']) ? intval($data['alumno_id']) : null;
$concepto_id = isset($data['concepto_id']) ? $data['concepto_id'] : null; // Puede ser 'ALL' para pagar todo
$monto = isset($data['monto']) ? floatval($data['monto']) : null;
$opcion_pago = isset($data['opcion_pago']) ? $data['opcion_pago'] : null;

if (!$alumno_id || (!$monto && $concepto_id !== 'ALL')) {
    echo json_encode(['success' => false, 'message' => 'Datos de pago incompletos.']);
    exit();
}

// Función para actualizar el estado del pago de un concepto específico
function actualizarEstadoPago($alumno_id, $concepto_id, $conexion) {
    $sql = "UPDATE conceptos_pago_alumnos 
            SET estado_pago = 'Pagado', fecha_pago = NOW() 
            WHERE alumno_id = ? AND concepto_id = ?";
    
    $stmt = $conexion->prepare($sql);
    $stmt->bind_param("ii", $alumno_id, $concepto_id);
    $stmt->execute();
    return $stmt->affected_rows > 0;
}

// Función para actualizar el estado de todos los pagos pendientes
function pagarTodosLosConceptos($alumno_id, $conexion) {
    $sql = "UPDATE conceptos_pago_alumnos 
            SET estado_pago = 'Pagado', fecha_pago = NOW() 
            WHERE alumno_id = ? AND estado_pago = 'Pendiente'";
    
    $stmt = $conexion->prepare($sql);
    $stmt->bind_param("i", $alumno_id);
    $stmt->execute();
    return $stmt->affected_rows > 0;
}

// Verificar si se seleccionó la opción de "Pagar todo"
if ($concepto_id === 'ALL') {
    // Se seleccionó "Pagar todo"
    if (pagarTodosLosConceptos($alumno_id, $conexion)) {
        echo json_encode(['success' => true, 'message' => 'Todos los pagos pendientes fueron actualizados como pagados.']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error al actualizar el estado de todos los pagos.']);
    }
} else {
    // Pagar un concepto específico
    if (actualizarEstadoPago($alumno_id, $concepto_id, $conexion)) {
        echo json_encode(['success' => true, 'message' => 'El pago fue procesado correctamente.']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error al actualizar el estado del pago.']);
    }
}

$conexion->close();
?>
