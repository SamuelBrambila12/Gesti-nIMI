<?php
// Conectar a la base de datos
$conexion = new mysqli('localhost', 'root', '', 'appdatabase');

// Verificar conexión
if ($conexion->connect_error) {
    die("Conexión fallida: " . $conexion->connect_error);
}

// Obtener el ID y grado del alumno
$id_alumno = isset($_GET['id']) ? $_GET['id'] : '';
$grado_alumno = isset($_GET['grado']) ? urldecode($_GET['grado']) : '';

// Manejar asignación de grupo a un estudiante
if (isset($_POST['asignar_grupo'])) {
    $grupo = $_POST['grupo'];
    $sql_asignar = "UPDATE alumnos SET grupo = ? WHERE id = ?";
    $stmt_asignar = $conexion->prepare($sql_asignar);
    $stmt_asignar->bind_param('si', $grupo, $id_alumno);
    if ($stmt_asignar->execute()) {
        $mensaje = "<div class='mensaje exito'><p>Grupo asignado correctamente.</p><div class='barra-progreso'><div class='barra'></div></div></div>";
    } else {
        $mensaje = "<div class='mensaje error'><p>Error al asignar el grupo: " . $conexion->error . "</p><div class='barra-progreso'><div class='barra'></div></div></div>";
    }
}

$conexion->close();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Asignar Grupo al Alumno</title>
    <link href="img/logo.jpeg" rel="icon">
    <link rel="stylesheet" href="css/styles.css">
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const mensaje = document.querySelector('.mensaje');
            if (mensaje) {
                // Configura el tiempo de ocultamiento del mensaje
                setTimeout(() => {
                    const barra = mensaje.querySelector('.barra');
                    if (barra) {
                        barra.style.width = '100%'; // Rellenar la barra de progreso
                    }
                    setTimeout(() => {
                        mensaje.style.opacity = '0';
                        setTimeout(() => {
                            mensaje.style.display = 'none';
                        }, 500);
                    }, 5000); // El mensaje se oculta después de 5 segundos
                }, 0); // Comienza inmediatamente la animación
            }
        });
    </script>
</head>
<body>
    <h1>Asignar Grupo al Alumno</h1>
    
    <form method="post">
        <input type="hidden" name="id_alumno" value="<?php echo htmlspecialchars($id_alumno); ?>">
        <label for="grupo">Grupo para el alumno de <?php echo htmlspecialchars($grado_alumno); ?>:</label>
        <select name="grupo" id="asignar-grupo" required>
            <option value="">Selecciona un grupo</option>
            <option value="Green">Green</option>
            <option value="Blue">Blue</option>
        </select>
        <input type="submit" class="box" name="asignar_grupo" value="Asignar Grupo">
    </form>

    <?php if (isset($mensaje)) echo $mensaje; ?>

    <a id="retorno" href="base.php">Volver a la lista de alumnos</a>
</body>
</html>
