<?php
session_start();

// Verificar si el usuario está autenticado
if (!isset($_SESSION['usuario_autenticado']) || !$_SESSION['usuario_autenticado']) {
    header('Location: login_padre.php');
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

// Función para crear tablas si no existen
function crearTablas($conexion) {
    $sql = "
    CREATE TABLE IF NOT EXISTS `alumnos` (
        `id` int(11) unsigned AUTO_INCREMENT PRIMARY KEY,
        `nombre` varchar(100) NOT NULL
    );";
    $conexion->query($sql);

    $sql = "
    CREATE TABLE IF NOT EXISTS `tramites` (
        `id` int(11) AUTO_INCREMENT PRIMARY KEY,
        `nombre` varchar(255) NOT NULL
    );";
    $conexion->query($sql);

    $sql = "
    CREATE TABLE IF NOT EXISTS `tramites_alumnos` (
        `alumno_id` int(11) unsigned NOT NULL,
        `tramite_id` int(11) NOT NULL,
        `fecha_solicitud` DATE NOT NULL,
        `estado` VARCHAR(50) NOT NULL,
        `pdf` VARCHAR(255) DEFAULT NULL,
        PRIMARY KEY (`alumno_id`, `tramite_id`),
        FOREIGN KEY (`alumno_id`) REFERENCES `alumnos`(`id`) ON DELETE CASCADE,
        FOREIGN KEY (`tramite_id`) REFERENCES `tramites`(`id`) ON DELETE CASCADE
    );";
    $conexion->query($sql);
}

// Función para insertar trámites si no existen
function insertarTramites($conexion) {
    $tramites = [
        "Constancia de Estudios",
        "Kardex"
    ];

    foreach ($tramites as $tramite) {
        $sql = "SELECT id FROM tramites WHERE nombre = ?";
        $stmt = $conexion->prepare($sql);
        $stmt->bind_param("s", $tramite);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 0) {
            $sql = "INSERT INTO tramites (nombre) VALUES (?)";
            $stmt = $conexion->prepare($sql);
            $stmt->bind_param("s", $tramite);
            $stmt->execute();
        }
    }
}

// Crear las tablas si no existen
crearTablas($conexion);

// Insertar trámites predeterminados
insertarTramites($conexion);

// Manejar la solicitud de trámite
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['alumno_id']) && isset($_POST['tramite_id'])) {
    $alumno_id = intval($_POST['alumno_id']);
    $tramite_id = intval($_POST['tramite_id']);
    $fecha_solicitud = date('Y-m-d'); // Fecha actual

    if (isset($_POST['accion'])) {
        $accion = $_POST['accion'];

        if ($accion === 'cancelar') {
            $sql = "DELETE FROM tramites_alumnos WHERE alumno_id = ? AND tramite_id = ? AND estado = 'Pendiente'";
            $stmt = $conexion->prepare($sql);
            $stmt->bind_param("ii", $alumno_id, $tramite_id);
            if ($stmt->execute()) {
                $_SESSION['mensaje'] = "Solicitud de trámite cancelada con éxito.";
                $_SESSION['tipo'] = 'exito';
            } else {
                $_SESSION['mensaje'] = "Error al cancelar la solicitud: " . $stmt->error;
                $_SESSION['tipo'] = 'error';
            }
        } elseif ($accion === 'recibido') {
            $sql = "DELETE FROM tramites_alumnos WHERE alumno_id = ? AND tramite_id = ? AND estado = 'Listo'";
            $stmt = $conexion->prepare($sql);
            $stmt->bind_param("ii", $alumno_id, $tramite_id);
            if ($stmt->execute()) {
                $_SESSION['mensaje'] = "Trámite marcado como recibido con éxito.";
                $_SESSION['tipo'] = 'exito';
            } else {
                $_SESSION['mensaje'] = "Error al marcar el trámite como recibido: " . $stmt->error;
                $_SESSION['tipo'] = 'error';
            }
        }
    } else {
        $sql = "SELECT * FROM tramites_alumnos WHERE alumno_id = ? AND tramite_id = ?";
        $stmt = $conexion->prepare($sql);
        $stmt->bind_param("ii", $alumno_id, $tramite_id);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 0) {
            $sql = "INSERT INTO tramites_alumnos (alumno_id, tramite_id, fecha_solicitud, estado) VALUES (?, ?, ?, 'Pendiente')";
            $stmt = $conexion->prepare($sql);
            $stmt->bind_param("iis", $alumno_id, $tramite_id, $fecha_solicitud);

            if ($stmt->execute()) {
                $_SESSION['mensaje'] = "Trámite solicitado con éxito.";
                $_SESSION['tipo'] = 'exito';
            } else {
                $_SESSION['mensaje'] = "Error al solicitar el trámite: " . $stmt->error;
                $_SESSION['tipo'] = 'error';
            }
        } else {
            $_SESSION['mensaje'] = "Ya has solicitado este trámite.";
            $_SESSION['tipo'] = 'error';
        }
    }

    header('Location: tramites_padre.php?alumno=' . urlencode($alumno_id));
    exit();
}

// Obtener ID del alumno desde la URL
$alumno_id = isset($_GET['alumno']) ? intval($_GET['alumno']) : null;

if (!$alumno_id) {
    die("ID del alumno no proporcionado.");
}

// Función para obtener todos los trámites disponibles
function obtenerTodosLosTramites($conexion) {
    $sql = "SELECT id, nombre FROM tramites";
    return $conexion->query($sql);
}

// Función para obtener los trámites solicitados por un alumno
function obtenerTramitesSolicitados($conexion, $alumno_id) {
    $sql = "SELECT t.id AS tramite_id, t.nombre, ta.estado, ta.pdf FROM tramites_alumnos ta
            JOIN tramites t ON ta.tramite_id = t.id
            WHERE ta.alumno_id = ?";
    $stmt = $conexion->prepare($sql);
    $stmt->bind_param("i", $alumno_id);
    $stmt->execute();
    return $stmt->get_result();
}


// Obtener todos los trámites disponibles
$tramitesDisponibles = obtenerTodosLosTramites($conexion);

// Obtener los trámites solicitados por el alumno
$tramitesSolicitados = obtenerTramitesSolicitados($conexion, $alumno_id);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Trámites del Alumno</title>
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

        #mensaje {
            position: fixed;
            top: 100px;
            right: 20px;
            padding: 15px;
            border-radius: 5px;
            color: #fff;
            font-size: 1rem;
            z-index: 1000;
            opacity: 1;
            transition: opacity 0.5s ease;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        #mensaje.exito {
            background-color: #28a745;
        }
        #mensaje.error {
            background-color: #dc3545 !important;
        }
        .barra-progreso {
            position: absolute;
            bottom: 0;
            left: 0;
            width: 100%;
            height: 5px;
            background-color: rgba(255, 255, 255, 0.3);
            border-radius: 0 0 5px 5px;
            overflow: hidden;
        }
        .barra {
            height: 100%;
            width: 0;
            background-color: #fff;
            transition: width 5s linear;
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
                    <li><a href="login_padre.php"><i class="fas fa-home"></i> Inicio</a></li>
                    <li><a href="pagos_padre.php?alumno=<?php echo urlencode($alumno_id); ?>"><i class="fas fa-money-bill-wave"></i> Pagos</a></li>
                    <li><a href="tramites_padre.php?alumno=<?php echo urlencode($alumno_id); ?>"><i class="fa-regular fa-file-lines"></i> Trámites</a></li>
                </ul>
            </nav>
        </div>
        <nav class="user-nav">
            <ul>
                <li>
                    <form action="login_padre.php" method="GET">
                        <button type="submit" name="cerrar_sesion" value="true" class="btn btn-danger">Cerrar Sesión</button>
                    </form>
                </li>
            </ul>
        </nav>
    </div>
</header>
<div id="mensaje" class="mensaje oculto">
        <div id="mensaje-texto"></div>
        <div class="barra-progreso">
            <div id="barra" class="barra"></div>
        </div>
    </div>
<div class="container mt-5">
    <!-- Sección de trámites disponibles -->
    <div class="card mb-4">
        <div class="card-header">
            <h1>Trámites Disponibles</h1>
        </div>
        <div class="card-body">
            <h2>Solicitar Trámites</h2>
            <h3>*Si anteriormente se solicitó uno de estos documentos y no se marcó como Recibido, no permitirá tramitar de nuevo, primero marque como Recibido su trámite anterior para proceder a tramitar dicho documento de nuevo</h3>
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>Trámite</th>
                        <th>Acción</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($tramitesDisponibles->num_rows > 0): ?>
                        <?php while ($row = $tramitesDisponibles->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($row['nombre']); ?></td>
                            <td>
                                <form action="tramites_padre.php" method="POST">
                                    <input type="hidden" name="alumno_id" value="<?php echo $alumno_id; ?>">
                                    <input type="hidden" name="tramite_id" value="<?php echo $row['id']; ?>">
                                    <button type="submit" class="boton-solicitud">Solicitar</button>
                                </form>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="2" class="text-center">No hay trámites disponibles.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Sección de trámites solicitados -->
    <div class="card">
        <div class="card-header">
            <h1>Trámites Solicitados</h1>
        </div>
        <div class="card-body">
            <h3>*El cobro del trámite será cargado en el apartado de Pagos</h3>
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>Trámite</th>
                        <th>Estado</th>
                        <th>Archivo</th>
                        <th>Acción</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($tramitesSolicitados->num_rows > 0): ?>
                        <?php while ($row = $tramitesSolicitados->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($row['nombre']); ?></td>
                            <td><?php echo htmlspecialchars($row['estado']); ?></td>
                            <td>
                                <?php if ($row['pdf']): ?>
                                    <a href="<?php echo htmlspecialchars($row['pdf']); ?>" target="_blank">Ver PDF</a>
                                <?php else: ?>
                                    No disponible
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($row['estado'] === 'Pendiente'): ?>
                                    <form action="tramites_padre.php" method="POST" style="display:inline;">
                                        <input type="hidden" name="alumno_id" value="<?php echo $alumno_id; ?>">
                                        <input type="hidden" name="tramite_id" value="<?php echo $row['tramite_id']; ?>">
                                        <input type="hidden" name="accion" value="cancelar">
                                        <button type="submit" class="boton-cancelar">Cancelar</button>
                                    </form>
                                <?php elseif ($row['estado'] === 'Listo'): ?>
                                    <form action="tramites_padre.php" method="POST" style="display:inline;">
                                        <input type="hidden" name="alumno_id" value="<?php echo $alumno_id; ?>">
                                        <input type="hidden" name="tramite_id" value="<?php echo $row['tramite_id']; ?>">
                                        <input type="hidden" name="accion" value="recibido">
                                        <button type="submit" class="boton-recibido">Recibido</button>
                                    </form>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="4" class="text-center">No has solicitado ningún trámite.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>


<script src="https://code.jquery.com/jquery-3.2.1.slim.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.11.0/umd/popper.min.js"></script>
<script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0/js/bootstrap.min.js"></script>
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

        document.addEventListener('DOMContentLoaded', (event) => {
            var mensaje = "<?php echo isset($_SESSION['mensaje']) ? addslashes($_SESSION['mensaje']) : ''; ?>";
            var tipo = "<?php echo isset($_SESSION['tipo']) ? addslashes($_SESSION['tipo']) : ''; ?>";
            
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
                }, 10);
                
                setTimeout(function() {
                    mensajeDiv.style.display = 'none';
                }, 5000);
            }
        });
</script>
</body>
</html>
<?php
// Limpiar el mensaje después de mostrarlo
unset($_SESSION['mensaje']);
unset($_SESSION['tipo']);
?>
