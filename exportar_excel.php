<?php
// Conexión a la base de datos (misma configuración que en base.php)
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "appdatabase";

$conexion = new mysqli($servername, $username, $password, $dbname);

// Verificar la conexión
if ($conexion->connect_error) {
    die("Conexión fallida: " . $conexion->connect_error);
}

// Consulta SQL para obtener todos los registros
$sql = "SELECT id, nombre, edad, genero, grado, grupo, nombre_padre, correo, telefono FROM alumnos";
$resultado = $conexion->query($sql);

if ($resultado->num_rows > 0) {
    // Configurar el encabezado para indicar que se descargará un archivo Excel
    header("Content-Type: application/vnd.ms-excel; charset=utf-8");
    header("Content-Disposition: attachment; filename=registros.xls");

    // Salida del archivo Excel
    echo '<html xmlns:x="urn:schemas-microsoft-com:office:excel">';
    echo '<head><meta http-equiv="Content-Type" content="text/html; charset=UTF-8"></head>';
    echo '<body>';
    echo '<table border="1">';
    echo '<tr><th>Nombre</th><th>Edad</th><th>Género</th><th>Grado</th><th>Grupo</th><th>Nombre del Padre/Madre</th><th>Correo</th><th>Teléfono</th></tr>';
    while ($fila = $resultado->fetch_assoc()) {
        echo '<tr>';
        echo '<td>' . htmlspecialchars($fila["nombre"], ENT_COMPAT | ENT_HTML401, 'UTF-8') . '</td>'; // Usar htmlspecialchars para escapar caracteres especiales
        echo '<td>' . $fila["edad"] . '</td>';
        echo '<td>' . htmlspecialchars($fila["genero"], ENT_COMPAT | ENT_HTML401, 'UTF-8') . '</td>';
        echo '<td>' . htmlspecialchars($fila["grado"], ENT_COMPAT | ENT_HTML401, 'UTF-8') . '</td>';
        echo '<td>' . (!empty($fila["grupo"]) ? htmlspecialchars($fila["grupo"], ENT_COMPAT | ENT_HTML401, 'UTF-8') : 'No asignado') . '</td>';
        echo '<td>' . htmlspecialchars($fila["nombre_padre"], ENT_COMPAT | ENT_HTML401, 'UTF-8') . '</td>';
        echo '<td>' . htmlspecialchars($fila["correo"], ENT_COMPAT | ENT_HTML401, 'UTF-8') . '</td>';
        echo '<td>' . $fila["telefono"] . '</td>';
        echo '</tr>';
    }
    echo '</table>';
    echo '</body>';
    echo '</html>';

    // Liberar el resultado
    $resultado->free();
} else {
    echo "No hay registros para exportar.";
}

// Cerrar la conexión
$conexion->close();
?>
