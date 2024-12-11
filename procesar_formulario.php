<?php
// Verifica que se haya enviado un formulario por POST
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Recolecta los datos del formulario
    $nombre = $_POST['nombre'];
    $edad = $_POST['edad'];
    $genero = $_POST['genero'];
    $grado = $_POST['grado'];
    $nombre_padre = $_POST['nombre_padre'];
    $correo = $_POST['correo'];
    $telefono = $_POST['telefono'];
    $fecha_registro = $_POST['fecha_registro'];

    // Validación y escape de datos (para prevenir inyecciones SQL)
    $nombre = htmlspecialchars($nombre);
    $edad = htmlspecialchars($edad);
    $genero = htmlspecialchars($genero);
    $grado = htmlspecialchars($grado);
    $nombre_padre = htmlspecialchars($nombre_padre);
    $correo = htmlspecialchars($correo);
    $telefono = htmlspecialchars($telefono);
    $fecha_registro = htmlspecialchars($fecha_registro);

    // Conexión a la base de datos (reemplaza con tus propios detalles de conexión)
    $servername = "localhost";
    $username = "root";
    $password = "";
    $dbname = "appdatabase";

    // Crea la conexión
    $conn = new mysqli($servername, $username, $password, $dbname);

    // Verifica la conexión
    if ($conn->connect_error) {
        die("Conexión fallida: " . $conn->connect_error);
    }

    // Verificar si la columna 'grado' existe
    $sql_check_column = "SHOW COLUMNS FROM alumnos LIKE 'grado'";
    $result = $conn->query($sql_check_column);

    // Si la columna no existe, agregarla
    if ($result->num_rows == 0) {
        $sql_add_column = "ALTER TABLE alumnos ADD COLUMN grado VARCHAR(255)";
        if ($conn->query($sql_add_column) === TRUE) {
            echo "Columna 'grado' agregada correctamente.<br>";
        } else {
            echo "Error al agregar columna 'grado': " . $conn->error . "<br>";
        }
    }

    // Prepara la consulta SQL para insertar los datos en la tabla 'alumnos'
    $sql_insert = "INSERT INTO alumnos (nombre, edad, genero, grado, nombre_padre, correo, telefono, fecha_registro)
    VALUES ('$nombre', '$edad', '$genero', '$grado', '$nombre_padre', '$correo', '$telefono', '$fecha_registro')";

    // Ejecuta la consulta y verifica si fue exitosa
    if ($conn->query($sql_insert) === TRUE) {
        echo "Datos insertados correctamente.<br>";
    } else {
        echo "Error al insertar datos: " . $conn->error . "<br>";
    }

    // Cierra la conexión
    $conn->close();
}
?>
