<?php
session_start();

// Verificar si el usuario está autenticado
if (!isset($_SESSION['usuario_autenticado']) || !$_SESSION['usuario_autenticado']) {
    header('Location: base.php');
    exit();
}

// Conexión a la base de datos
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "appdatabase";

$conexion = new mysqli($servername, $username, $password, $dbname);

if ($conexion->connect_error) {
    die("Conexión fallida: " . $conexion->connect_error);
}

// Manejar la carga del PDF
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['pdf']) && isset($_POST['alumno_id']) && isset($_POST['tramite_id'])) {
    $alumno_id = intval($_POST['alumno_id']);
    $tramite_id = intval($_POST['tramite_id']);
    $pdf = $_FILES['pdf'];

    if ($pdf['error'] === UPLOAD_ERR_OK && $pdf['type'] === 'application/pdf') {
        $nombreArchivo = basename($pdf['name']);
        $rutaDestino = 'pdf/' . $nombreArchivo;

        if (move_uploaded_file($pdf['tmp_name'], $rutaDestino)) {
            // Actualizar el estado del trámite a "Listo" y guardar la ruta del PDF
            $sql = "UPDATE tramites_alumnos SET estado = 'Listo', pdf = ? WHERE alumno_id = ? AND tramite_id = ?";
            $stmt = $conexion->prepare($sql);
            $stmt->bind_param("sii", $rutaDestino, $alumno_id, $tramite_id);

            if ($stmt->execute()) {
                // Obtener el monto del concepto de pago basado en el trámite
                $monto = 0;
                $concepto_nombre = '';

                if ($tramite_id == 1) { // Suponiendo que el ID 1 es para "Constancia"
                    $monto = 60;
                    $concepto_nombre = 'Constancia';
                } elseif ($tramite_id == 2) { // Suponiendo que el ID 2 es para "Kardex"
                    $monto = 30;
                    $concepto_nombre = 'Kardex';
                }

                // Insertar el concepto de pago en la tabla conceptos_pago_alumnos
                $sql = "INSERT INTO conceptos_pago (nombre, monto) VALUES (?, ?)";
                $stmt = $conexion->prepare($sql);
                $stmt->bind_param("sd", $concepto_nombre, $monto);
                $stmt->execute();
                $concepto_id = $stmt->insert_id; // Obtener el ID del concepto recién insertado

                // Asignar el concepto al alumno
                $sql = "INSERT INTO conceptos_pago_alumnos (concepto_id, alumno_id, fecha_asignacion) VALUES (?, ?, NOW())";
                $stmt = $conexion->prepare($sql);
                $stmt->bind_param("ii", $concepto_id, $alumno_id);

                if ($stmt->execute()) {
                    $_SESSION['mensaje'] = "PDF cargado, trámite actualizado y concepto de pago asignado.";
                    $_SESSION['tipo'] = 'exito'; // Mensaje de éxito
                } else {
                    $_SESSION['mensaje'] = "Error al asignar el concepto de pago: " . $stmt->error;
                    $_SESSION['tipo'] = 'error'; // Mensaje de error
                }
            } else {
                $_SESSION['mensaje'] = "Error al actualizar el trámite: " . $stmt->error;
                $_SESSION['tipo'] = 'error'; // Mensaje de error
            }
        } else {
            $_SESSION['mensaje'] = "Error al mover el archivo PDF.";
            $_SESSION['tipo'] = 'error';
        }
    } else {
        $_SESSION['mensaje'] = "El archivo no es un PDF o hubo un error en la carga.";
        $_SESSION['tipo'] = 'error';
    }

    header('Location: tramites_admin.php');
    exit();
}

// Obtener los trámites solicitados por los alumnos
$sql = "
    SELECT ta.alumno_id, a.nombre AS alumno_nombre, t.nombre AS tramite_nombre, ta.tramite_id, ta.estado, ta.pdf
    FROM tramites_alumnos ta
    JOIN alumnos a ON ta.alumno_id = a.id
    JOIN tramites t ON ta.tramite_id = t.id
    WHERE ta.estado = 'Pendiente'";
$tramitesSolicitados = $conexion->query($sql);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Administrar Trámites</title>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/styles.css">
    <link href="img/logo.png" rel="icon">
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
            opacity: 10%;
        }

        table {
    width: 100%; /* Ocupa todo el ancho del contenedor */
    background-color: #fff; /* Fondo blanco para la tabla */
    border-collapse: collapse; /* Elimina los espacios entre celdas */
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1); /* Sombra ligera para la tabla */
}

table th, table td {
    border: 1px solid #ddd; /* Bordes en celdas */
    padding: 12px; /* Espaciado interno */
    text-align: left; /* Alineación de texto a la izquierda */
    background-color: #fff; /* Fondo blanco para las celdas de la tabla */
}

table th {
    background-color: #4CAF50; /* Fondo verde para los encabezados */
    color: white; /* Color del texto del encabezado */
}

table tr:nth-child(even) {
    background-color: #f9f9f9; /* Fondo gris claro para las filas pares */
}

table tr:nth-child(odd) {
    background-color: #fff; /* Fondo blanco para las filas impares */
}

table tr:hover {
    background-color: #f1f1f1; /* Fondo gris claro al pasar el cursor */
}

table td button {
    background-color: #007bff; /* Botón de color primario */
    color: white;
    border: none;
    padding: 8px 12px;
    cursor: pointer;
}

table td button:hover {
    background-color: #0056b3; /* Cambio de color del botón al hacer hover */
}


    </style>
</head>
<body>
<div class="loader-container" id="loader">
        <div class="loader"></div>
    </div>
    <video autoplay muted loop id="background-video">
        <source src="img/Plexus.mp4" type="video/mp4">
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
                        <li><a href="base.php"><i class="fas fa-home"></i> Inicio</a></li>
                        <li><a href="pagos_admin.php"><i class="fas fa-money-bill-wave"></i> Pagos</a></li>
                        <li><a href="profesores.php"><i class="fas fa-chalkboard-teacher"></i> Profesores</a></li>
                        <li><a href="tramites_admin.php"><i class="fa-regular fa-file-lines"></i> Trámites</a></li>
                    </ul>
                </nav>
            </div>
            <nav class="user-nav">
                <ul>
                    <li>
                        <form action="base.php" method="GET">
                            <button type="submit" name="cerrar_sesion" value="true">Cerrar Sesión</button>
                        </form>
                    </li>
                </ul>
            </nav>
        </div>
    </header>

<div class="container mt-5">
    <h1>Administrar Trámites</h1>
    <div id="mensaje" class="mensaje oculto">
        <div id="mensaje-texto"></div>
        <div class="barra-progreso">
            <div id="barra" class="barra"></div>
        </div>
    </div>

    <!-- Mostrar el mensaje si existe en la sesión -->
    <?php if (isset($_SESSION['mensaje'])): ?>
        <script>
            document.addEventListener('DOMContentLoaded', (event) => {
                var mensaje = "<?php echo addslashes($_SESSION['mensaje']); ?>";
                var tipo = "<?php echo addslashes($_SESSION['tipo']); ?>";
                
                if (mensaje) {
                    var mensajeDiv = document.getElementById('mensaje');
                    var mensajeTexto = document.getElementById('mensaje-texto');
                    var barra = document.getElementById('barra');
                    
                    mensajeTexto.textContent = mensaje;
                    mensajeDiv.style.display = 'block';
                    
                    if (tipo === 'error') {
                        mensajeDiv.classList.add('error');
                        mensajeDiv.classList.remove('exito');
                    } else {
                        mensajeDiv.classList.add('exito');
                        mensajeDiv.classList.remove('error');
                    }
                    
                    // Mostrar la barra de progreso y ocultar el mensaje después de 5 segundos
                    setTimeout(function() {
                        barra.style.width = '100%';
                    }, 10); // Pequeño retraso para asegurar que la transición CSS se aplique correctamente
                    
                    setTimeout(function() {
                        mensajeDiv.style.display = 'none';
                    }, 5000); // Tiempo en milisegundos para coincidir con el tiempo de la barra
                }
            });
        </script>
        <?php unset($_SESSION['mensaje']); ?>
    <?php endif; ?>

    <!-- Mostrar trámites solicitados -->
    <table class="table table-striped">
        <thead>
            <tr>
                <th>Alumno</th>
                <th>Trámite</th>
                <th>Estado</th>
                <th>Subir PDF</th>
            </tr>
        </thead>
        <tbody>
            <?php if ($tramitesSolicitados->num_rows > 0): ?>
                <?php while ($row = $tramitesSolicitados->fetch_assoc()): ?>
                <tr>
                    <td><?php echo htmlspecialchars($row['alumno_nombre']); ?></td>
                    <td><?php echo htmlspecialchars($row['tramite_nombre']); ?></td>
                    <td><?php echo htmlspecialchars($row['estado']); ?></td>
                    <td>
                        <form action="tramites_admin.php" method="POST" enctype="multipart/form-data">
                            <input type="hidden" name="alumno_id" value="<?php echo $row['alumno_id']; ?>">
                            <input type="hidden" name="tramite_id" value="<?php echo $row['tramite_id']; ?>">
                            <input type="file" name="pdf" accept="application/pdf" required>
                            <button type="submit" class="btn btn-primary">Subir PDF</button>
                        </form>
                    </td>
                </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr>
                    <td colspan="4">No hay trámites pendientes.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>
<script>
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
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0/js/bootstrap.min.js"></script>
<script src="js/scripts.js"></script>
</body>
</html>
