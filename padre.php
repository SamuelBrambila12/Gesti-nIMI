<?php
session_start();

// Verificar si el padre está autenticado
if (!isset($_SESSION['padre_autenticado']) || !$_SESSION['padre_autenticado']) {
    header('Location: login_padre.php');
    exit();
}

// Cerrar sesión si se ha solicitado
if (isset($_GET['cerrar_sesion'])) {
    session_unset();
    session_destroy();
    header('Location: login_padre.php');
    exit();
}

// Conexión a la base de datos
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "appdatabase";

$conexion = new mysqli($servername, $username, $password, $dbname);
$conexion->set_charset("utf8mb4");

// Verificar la conexión
if ($conexion->connect_error) {
    die("Conexión fallida: " . $conexion->connect_error);
}

// Verificar si las columnas necesarias existen, si no, agregarlas
$columnas_necesarias = [
    'curp' => "VARCHAR(18)",
    'fecha_nacimiento' => "DATE",
    'foto' => "VARCHAR(800)",
    'lugar_nacimiento' => "VARCHAR(255)",
    'direccion' => "TEXT",
    'genero' => "VARCHAR(10)",
    'edad' => "INT",
    'grado' => "VARCHAR(255)",
    'grupo' => "VARCHAR(10)",
    'correo' => "VARCHAR(255)",
    'telefono' => "VARCHAR(20)",
    'tipo_sangre' => "VARCHAR(5)",
    'alergias' => "TEXT",
    'condiciones_medicas' => "TEXT",
    'fecha_registro' => "DATETIME",
    'observaciones' => "TEXT"
];

foreach ($columnas_necesarias as $columna => $tipo) {
    $resultado_columna = $conexion->query("SHOW COLUMNS FROM alumnos LIKE '$columna'");
    if ($resultado_columna->num_rows == 0) {
        $conexion->query("ALTER TABLE alumnos ADD COLUMN $columna $tipo");
    } else {
        // Actualizar el tipo de columna si es necesario
        $conexion->query("ALTER TABLE alumnos MODIFY COLUMN $columna $tipo");
    }
}

// Obtener el ID del padre autenticado
$id_padre = $_SESSION['id_padre']; // Asumiendo que guardas el ID del padre en la sesión

// Obtener el alumno asociado al padre
$sql_alumno = "SELECT * FROM alumnos WHERE id = ?";
$stmt = $conexion->prepare($sql_alumno);
$stmt->bind_param("i", $id_padre);
$stmt->execute();
$resultado_alumno = $stmt->get_result();

if ($resultado_alumno->num_rows > 0) {
    $alumno = $resultado_alumno->fetch_assoc();
} else {
    echo "<p>No se encontró información del alumno.</p>";
    echo "<a href='login_padre.php'>Volver al login</a>";
    exit();
}

// Procesar el formulario si se ha enviado
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $curp = $_POST['curp'];
    $fecha_nacimiento = $_POST['fecha_nacimiento'];
    $lugar_nacimiento = $_POST['lugar_nacimiento'];
    $direccion = $_POST['direccion'];
    $nombre = $_POST['nombre'];
    $genero = $_POST['genero'];
    $edad = intval($_POST['edad']);
    $grado = $_POST['grado'];
    $grupo = $_POST['grupo'];
    $correo = $_POST['correo'];
    $telefono = $_POST['telefono'];
    $tipo_sangre = $_POST['tipo_sangre'];
    $alergias = $_POST['alergias'];
    $condiciones_medicas = $_POST['condiciones_medicas'];
    $observaciones = $_POST['observaciones'];

    // Verificar si el directorio 'uploads' existe, si no, crear el directorio
    if (!file_exists('uploads')) {
        mkdir('uploads', 0777, true);
    }

    // Manejar la carga de archivos
    $foto_actual = $alumno['foto'];
    if (isset($_FILES['foto']) && $_FILES['foto']['error'] === UPLOAD_ERR_OK) {
        $foto_tmp_name = $_FILES['foto']['tmp_name'];
        $foto_name = basename($_FILES['foto']['name']);
        $foto_destino = 'uploads/' . $foto_name;

        // Verificar tipo de archivo y tamaño
        $allowed_types = ['image/jpeg']; // Solo JPG
        $file_info = getimagesize($foto_tmp_name);
        $file_type = $file_info['mime'];
        $file_size = $_FILES['foto']['size'];

        if (in_array($file_type, $allowed_types) && $file_size <= 10 * 1024 * 1024) { // 10 MB max
            move_uploaded_file($foto_tmp_name, $foto_destino);
            $foto_actual = $foto_destino;
        } else {
            echo "Solo se permiten archivos JPG o el archivo es demasiado grande.";
        }
    }


    // Consulta SQL de actualización
    $sql_update = "UPDATE alumnos SET 
    nombre = ?, curp = ?, fecha_nacimiento = ?, lugar_nacimiento = ?,
    direccion = ?, foto = ?, genero = ?, edad = ?, grado = ?, grupo = ?,
    correo = ?, telefono = ?, tipo_sangre = ?, alergias = ?,
    condiciones_medicas = ?, observaciones = ?
    WHERE id = ?";

    // Preparar la declaración
    $stmt = $conexion->prepare($sql_update);

    // Vincular parámetros: tipos de datos y variables correspondientes
    $stmt->bind_param(
        "sssssssissssssssi",
        $nombre, $curp, $fecha_nacimiento, $lugar_nacimiento,
        $direccion, $foto_actual, $genero, $edad, $grado, $grupo,
        $correo, $telefono, $tipo_sangre, $alergias,
        $condiciones_medicas, $observaciones, $alumno['id']
    );

    // Ejecutar la declaración
    if ($stmt->execute()) {
        $_SESSION['mensaje'] = "Información actualizada con éxito.";
        // Recargar la información del alumno
        $stmt = $conexion->prepare($sql_alumno);
        $stmt->bind_param("i", $id_padre);
        $stmt->execute();
        $resultado_alumno = $stmt->get_result();
        $alumno = $resultado_alumno->fetch_assoc();
    } else {
        $_SESSION['mensaje'] = "Error al actualizar la información: " . $stmt->error;
    }
}

$conexion->close();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Actualizar Información del Alumno</title>
    <link href="img/logo.png" rel="icon">
    <link rel="stylesheet" href="css/styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            background-color: #f0f0f0; /* Fondo gris para la página */
        }

        .loader-container {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5); /* Fondo gris oscuro con opacidad */
            display: flex;
            justify-content: center;
            align-items: center;
            z-index: 9999; /* Asegúrate de que el loader esté encima de otros contenidos */
        }

        .loader {
            width: fit-content;
            font-weight: bold;
            font-family: monospace;
            font-size: 30px;
            background: linear-gradient(90deg, #000 50%, #0000 0) right/200% 100%;
            animation: l21 2s infinite linear;
        }

        .loader::before {
            content: "Loading...";
            color: #0000;
            padding: 0 5px;
            background: inherit;
            background-image: linear-gradient(90deg, #fff 50%, #000 0);
            -webkit-background-clip: text;
            background-clip: text;
        }

        @keyframes l21 {
            100% { background-position: left; }
        }

        #background-video {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100vh;
            object-fit: cover;
            z-index: -1;
            opacity: 100%;
        }
    </style>
</head>
<body>
<div class="loader-container" id="loader">
        <div class="loader"></div>
    </div>
    <video autoplay muted loop id="background-video">
        <source src="img/Particles.mp4" type="video/mp4">
    </video>
<header>
        <div class="header-content">
            <div class="menu-container">
                <div class="menu-toggle" onclick="toggleMenu()">
                    <span></span>
                    <span></span>
                    <span></span>
                </div>
                <nav class="menu">
                    <ul>
                        <li><a href="padre.php"><i class="fas fa-home"></i> Inicio</a></li>
                        <li><a href="pagos_padre.php?alumno=<?php echo urlencode($alumno['id']); ?>"><i class="fas fa-money-bill-wave"></i> Pagos</a></li>
                        <li><a href="tramites_padre.php?alumno=<?php echo urlencode($alumno['id']); ?>"><i class="fa-regular fa-file-lines"></i> Trámites</a></li>
                    </ul>
                </nav>
            </div>
            <nav class="user-nav">
                <ul>
                    <li>
                        <form action="login_padre.php" method="GET">
                            <button type="submit" name="cerrar_sesion" value="true">Cerrar Sesión</button>
                        </form>
                    </li>
                </ul>
            </nav>
        </div>
    </header>
    <main>
        <h1>Actualizar Información del Alumno</h1>
        <div id="mensaje" class="mensaje oculto">
            <div id="mensaje-texto"></div>
            <div class="barra-progreso">
                <div id="barra" class="barra"></div>
            </div>
        </div>

        <form action="padre.php" method="POST" enctype="multipart/form-data">
            <div class="form-group">
                <label for="nombre">Nombre:</label>
                <input type="text" id="nombre" name="nombre" value="<?php echo htmlspecialchars($alumno['nombre'] ?? ''); ?>" required>
            </div>
            
            <div class="form-group">
                <label for="curp">CURP:</label>
                <input type="text" id="curp" name="curp" value="<?php echo htmlspecialchars($alumno['curp'] ?? ''); ?>" required>
            </div>
            
            <div class="form-group">
                <label for="fecha_nacimiento">Fecha de Nacimiento:</label>
                <input type="date" id="fecha_nacimiento" name="fecha_nacimiento" value="<?php echo htmlspecialchars($alumno['fecha_nacimiento'] ?? ''); ?>" required>
            </div>

            <div class="form-group">
                <label for="lugar_nacimiento">Lugar de Nacimiento:</label>
                <input type="text" id="lugar_nacimiento" name="lugar_nacimiento" value="<?php echo htmlspecialchars($alumno['lugar_nacimiento'] ?? ''); ?>" required>
            </div>

            <div class="form-group">
                <label for="direccion">Dirección:</label>
                <textarea id="direccion" name="direccion" required><?php echo htmlspecialchars($alumno['direccion'] ?? ''); ?></textarea>
            </div>

            <div class="form-group">
                <label for="genero">Género:</label>
                <input type="text" id="genero" name="genero" value="<?php echo htmlspecialchars($alumno['genero'] ?? ''); ?>" required>
            </div>

            <div class="form-group">
                <label for="edad">Edad:</label>
                <input type="number" id="edad" name="edad" value="<?php echo htmlspecialchars($alumno['edad'] ?? ''); ?>" required>
            </div>

            <div class="form-group">
                <label for="grado">Grado:</label>
                <input type="text" id="grado" name="grado" readonly="true" value="<?php echo htmlspecialchars($alumno['grado'] ?? ''); ?>">
            </div>

            <div class="form-group">
                <label for="grupo">Grupo:</label>
                <input type="text" id="grupo" name="grupo" readonly="true" value="<?php echo htmlspecialchars($alumno['grupo'] ?? ''); ?>">
            </div>

            <div class="form-group">
                <label for="correo">Correo Electrónico:</label>
                <input type="email" id="correo" name="correo" value="<?php echo htmlspecialchars($alumno['correo'] ?? ''); ?>" required>
            </div>

            <div class="form-group">
                <label for="telefono">Teléfono:</label>
                <input type="tel" id="telefono" name="telefono" value="<?php echo htmlspecialchars($alumno['telefono'] ?? ''); ?>" required>
            </div>

            <div class="form-group">
                <label for="tipo_sangre">Tipo de Sangre:</label>
                <input type="text" id="tipo_sangre" name="tipo_sangre" value="<?php echo htmlspecialchars($alumno['tipo_sangre'] ?? ''); ?>" required>
            </div>

            <div class="form-group">
                <label for="alergias">Alergias:</label>
                <textarea id="alergias" name="alergias"><?php echo htmlspecialchars($alumno['alergias'] ?? ''); ?></textarea>
            </div>

            <div class="form-group">
                <label for="condiciones_medicas">Condiciones Médicas:</label>
                <textarea id="condiciones_medicas" name="condiciones_medicas"><?php echo htmlspecialchars($alumno['condiciones_medicas'] ?? ''); ?></textarea>
            </div>

            <div class="form-group">
                <label for="observaciones">Observaciones:</label>
                <textarea id="observaciones" name="observaciones"><?php echo htmlspecialchars($alumno['observaciones'] ?? ''); ?></textarea>
            </div>

            <div class="form-group">
                <label for="">Foto del alumno:</label>
                <label for="foto" class="custom-file-upload">
                    Seleccione la foto (máximo 10 MB en JPG)
                </label>
                <input type="file" id="foto" name="foto" accept="image/*" style="display: none;">
                <div id="foto-preview">
                    <?php if (!empty($alumno['foto'])): ?>
                        <img src="<?php echo htmlspecialchars($alumno['foto']); ?>" alt="Foto del Alumno" style="max-width: 100px; height: auto;">
                    <?php endif; ?>
                </div>
            </div>

            <button class="boton-actualizar" type="submit">Actualizar Información</button>
        </form>
    </main>
    <script>
    document.addEventListener('DOMContentLoaded', (event) => {
        var textareas = document.querySelectorAll('form textarea');
        
        textareas.forEach(function(textarea) {
            function ajustarAltura() {
                textarea.style.height = 'auto'; // Resetea la altura para recalcular
                textarea.style.height = textarea.scrollHeight + 'px'; // Ajusta la altura al contenido
            }

            textarea.addEventListener('input', ajustarAltura); // Ajusta la altura al escribir
            ajustarAltura(); // Ajusta la altura al cargar la página
        });
    });

    document.addEventListener('DOMContentLoaded', (event) => {
        // Mostrar el mensaje si existe en la sesión
        var mensaje = "<?php echo isset($_SESSION['mensaje']) ? $_SESSION['mensaje'] : ''; ?>";
        var tipo = "<?php echo isset($_SESSION['tipo']) ? $_SESSION['tipo'] : ''; ?>";

        if (mensaje) {
            var mensajeDiv = document.getElementById('mensaje');
            var mensajeTexto = document.getElementById('mensaje-texto');
            var barra = document.getElementById('barra');

            mensajeTexto.textContent = mensaje;
            mensajeDiv.classList.remove('oculto');
            if (tipo === 'error') {
                mensajeDiv.classList.add('error');
            } else {
                mensajeDiv.classList.add('exito');
            }

            // Mostrar la barra de progreso y ocultar el mensaje después de 5 segundos
            setTimeout(function() {
                barra.style.width = '100%';
            }, 10); // Pequeño retraso para asegurar que la transición CSS se aplique correctamente

            setTimeout(function() {
                mensajeDiv.classList.add('oculto');
                // Limpiar el mensaje de la sesión
                <?php unset($_SESSION['mensaje']); ?>
            }, 5000); // Tiempo en milisegundos para coincidir con el tiempo de la barra
        }
    });

    document.getElementById('foto').addEventListener('change', function(event) {
    const preview = document.getElementById('foto-preview');
    preview.innerHTML = ''; // Limpiar vista previa anterior

    const file = event.target.files[0];
    if (file) {
        const fileType = file.type;
        
        // Verificar si el archivo es un JPG
        if (fileType === 'image/jpeg') {
            const reader = new FileReader();

            reader.onload = function(e) {
                const img = document.createElement('img');
                img.src = e.target.result;
                img.alt = 'Foto del Alumno';
                img.style.maxWidth = '100px';
                img.style.height = 'auto';
                preview.appendChild(img);
            };

            reader.readAsDataURL(file);
        } else {
            alert('Solo se permiten archivos JPG.');
            event.target.value = ''; // Limpiar el input si el archivo no es válido
        }
    }
});

    // Función para toggle del menú
    function toggleMenu() {
            const menu = document.querySelector('.menu');
            menu.classList.toggle('active');
        }
    
    // Cerrar el menú al hacer scroll
    window.addEventListener('scroll', function() {
        const menu = document.querySelector('.menu');
        if (menu.classList.contains('active')) {
            menu.classList.remove('active'); // Cierra el menú si está abierto
        }
    });
    
    // Función para ocultar el loader después de 2 segundos
    window.addEventListener('load', function() {
            setTimeout(function() {
                document.getElementById('loader').style.display = 'none';
            }, 2000);
        });   
</script>

</body>
</html>