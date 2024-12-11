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

// Verificar si la tabla 'profesores' existe, si no, crearla
$sql_check_table = "SHOW TABLES LIKE 'profesores'";
$result = $conexion->query($sql_check_table);

if ($result->num_rows == 0) {
    // La tabla no existe, vamos a crearla
    $sql_create_table = "CREATE TABLE profesores (
        id INT AUTO_INCREMENT PRIMARY KEY,
        nombre VARCHAR(100) NOT NULL,
        grado VARCHAR(255),
        grupo VARCHAR(255),
        nivel VARCHAR(255),
        grupo_clasificado VARCHAR(255),
        grado_clasificado VARCHAR(255)
    )";

    if ($conexion->query($sql_create_table) !== TRUE) {
        die("Error al crear la tabla: " . $conexion->error);
    }

    // Insertar datos de prueba
    $sql_insert_data = "INSERT INTO profesores (nombre) VALUES 
        ('Juan Pérez'),
        ('María García'),
        ('Carlos Rodríguez')";

    if ($conexion->query($sql_insert_data) !== TRUE) {
        die("Error al insertar datos de prueba: " . $conexion->error);
    }
} else {
    // La tabla existe, verificar si las columnas 'grupo_clasificado' y 'grado_clasificado' existen
    $sql_check_grupo = "SHOW COLUMNS FROM `profesores` LIKE 'grupo_clasificado'";
    $result_grupo = $conexion->query($sql_check_grupo);

    $sql_check_grado = "SHOW COLUMNS FROM `profesores` LIKE 'grado_clasificado'";
    $result_grado = $conexion->query($sql_check_grado);

    if ($result_grupo->num_rows == 0 || $result_grado->num_rows == 0) {
        // Una o ambas columnas no existen, agregarlas
        $sql_add_columns = "ALTER TABLE `profesores` 
            ADD COLUMN IF NOT EXISTS `grupo_clasificado` VARCHAR(255),
            ADD COLUMN IF NOT EXISTS `grado_clasificado` VARCHAR(255)";
        if ($conexion->query($sql_add_columns) !== TRUE) {
            die("Error al agregar las columnas: " . $conexion->error);
        }
    }
}

function obtenerGradosGruposDisponibles($conexion) {
    // Obtener grados y grupos únicos completos de la tabla alumnos
    $sql_grados_grupos = "SELECT DISTINCT grado, grupo FROM alumnos WHERE grupo IS NOT NULL AND grupo != '' ORDER BY grado, grupo";
    $resultado_grados_grupos = $conexion->query($sql_grados_grupos);

    if (!$resultado_grados_grupos) {
        die("Error en la consulta de grados y grupos: " . $conexion->error);
    }

    $grados_grupos = [];
    while ($row = $resultado_grados_grupos->fetch_assoc()) {
        $grados_grupos[] = ['grado' => $row['grado'], 'grupo' => $row['grupo']];
    }

    // Obtener grados y grupos ya asignados
    $sql_asignaciones = "SELECT grado, grupo FROM profesores WHERE grado IS NOT NULL AND grupo IS NOT NULL";
    $resultado_asignaciones = $conexion->query($sql_asignaciones);

    if (!$resultado_asignaciones) {
        die("Error en la consulta de asignaciones: " . $conexion->error);
    }

    $grados_grupos_asignados = [];
    while ($row = $resultado_asignaciones->fetch_assoc()) {
        $grados_grupos_asignados[] = ['grado' => $row['grado'], 'grupo' => $row['grupo']];
    }

    // Filtrar grados y grupos disponibles
    $grados_grupos_disponibles = array_filter($grados_grupos, function($gg) use ($grados_grupos_asignados) {
        foreach ($grados_grupos_asignados as $asignacion) {
            if ($asignacion['grado'] === $gg['grado'] && $asignacion['grupo'] === $gg['grupo']) {
                return false;
            }
        }
        return true;
    });

    return $grados_grupos_disponibles;
}

$grados_grupos_disponibles = obtenerGradosGruposDisponibles($conexion);

function determinarGrupo($grupo) {
    if (preg_match('/^Blue$/', $grupo)) {
        return "Blue";
    } elseif (preg_match('/^Green$/', $grupo)) {
        return "Green";
    } else {
        return "Otro";
    }
}

function determinarNivel($grado) {
    if (preg_match('/^[1-6]° de Primaria$/', $grado)) {
        return "Primaria";
    } elseif (preg_match('/^[1-3]° de Secundaria$/', $grado)) {
        return "Secundaria";
    } elseif (preg_match('/^[1-3]° de Kinder$/', $grado)) {
        return "Kinder";
    } else {
        return "Maternal";
    }
}

function determinarGradoClasificado($grado) {
    // Función para clasificar el grado completo
    if (preg_match('/^[1-6]° de Primaria$/', $grado)) {
        return "$grado";
    } elseif (preg_match('/^[1-3]° de Secundaria$/', $grado)) {
        return "$grado";
    } elseif (preg_match('/^[1-3]° de Kinder$/', $grado)) {
        return "$grado";
    } else {
        return "$grado";
    }
}

function obtenerNombreProfesor($grado_clasificado, $grupo_clasificado, $conexion) {
    $sql_profesor = $conexion->prepare("SELECT nombre FROM profesores WHERE grado_clasificado = ? AND grupo_clasificado = ?");
    $sql_profesor->bind_param("ss", $grado_clasificado, $grupo_clasificado);
    $sql_profesor->execute();
    $resultado_profesor = $sql_profesor->get_result();

    if ($resultado_profesor->num_rows > 0) {
        $profesor = $resultado_profesor->fetch_assoc();
        return $profesor['nombre'];
    } else {
        return 'No asignado';
    }
}

function agregarProfesor($nombre, $conexion) {
    $nombre = $conexion->real_escape_string($nombre);
    $sql = "INSERT INTO profesores (nombre) VALUES ('$nombre')";
    if ($conexion->query($sql) === TRUE) {
        return true;
    } else {
        return false;
    }
}

function eliminarProfesor($id, $conexion) {
    $id = $conexion->real_escape_string($id);
    
    // Primero, liberar el grado-grupo asignado
    $sql_liberar = "UPDATE profesores SET grado = NULL, grupo = NULL, nivel = NULL, grupo_clasificado = NULL, grado_clasificado = NULL WHERE id = '$id'";
    $conexion->query($sql_liberar);
    
    // Luego, eliminar al profesor
    $sql = "DELETE FROM profesores WHERE id = '$id'";
    if ($conexion->query($sql) === TRUE) {
        return true;
    } else {
        return false;
    }
}

// Procesar la asignación de profesor
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['asignar_profesor'])) {
    $profesor_id = $_POST['profesor_id'];
    list($grado, $grupo) = explode('|', $_POST['grado_grupo']);
    $nivel = determinarNivel($grado);
    $grupo_clasificado = determinarGrupo($grupo);
    $grado_clasificado = determinarGradoClasificado($grado);

    // Verificar si el grado y grupo ya están asignados a otro profesor
    $sql_verificar = "SELECT id FROM profesores WHERE grado = '$grado' AND grupo = '$grupo' AND id != $profesor_id";
    $resultado_verificar = $conexion->query($sql_verificar);

    if ($resultado_verificar->num_rows > 0) {
        $mensaje = "Error: Este grado y grupo ya están asignados a otro profesor.";
    } else {
        // Actualizar la asignación del profesor
        $sql_asignar = "UPDATE profesores SET grado = '$grado', grupo = '$grupo', nivel = '$nivel', grupo_clasificado = '$grupo_clasificado', grado_clasificado = '$grado_clasificado' WHERE id = $profesor_id";

        if ($conexion->query($sql_asignar) === TRUE) {
            $mensaje = ["texto" => "Profesor asignado correctamente.", "tipo" => "success"];
        } else {
            $mensaje = ["texto" => "Error al asignar el profesor: " . $conexion->error, "tipo" => "danger"];
        }
    }
}

// Procesar la desasignación de profesor
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['desasignar_profesor'])) {
    $profesor_id = $_POST['profesor_id'];

    // Desasignar profesor
    $sql_desasignar = "UPDATE profesores SET grado = NULL, grupo = NULL, nivel = NULL, grupo_clasificado = NULL, grado_clasificado = NULL WHERE id = $profesor_id";

    if ($conexion->query($sql_desasignar) === TRUE) {
        $mensaje = ["texto" => "Profesor desasignado correctamente.", "tipo" => "success"];
        $grados_grupos_disponibles = obtenerGradosGruposDisponibles($conexion);
    } else {
        $mensaje = ["texto" => "Error al desasignar el profesor: " . $conexion->error, "tipo" => "danger"];
    }
}

// Procesar la adición de un nuevo profesor
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['agregar_profesor'])) {
    $nombre_profesor = $_POST['nombre_profesor'];
    if (agregarProfesor($nombre_profesor, $conexion)) {
        $mensaje = ["texto" => "Profesor agregado correctamente.", "tipo" => "success"];
    } else {
        $mensaje = ["texto" => "Error al agregar el profesor: " . $conexion->error, "tipo" => "danger"];
    }
}

// Procesar la eliminación de un profesor
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['eliminar_profesor'])) {
    $profesor_id = $_POST['profesor_id'];
    if (eliminarProfesor($profesor_id, $conexion)) {
        $mensaje = ["texto" => "Profesor eliminado correctamente.", "tipo" => "success"];
        $grados_grupos_disponibles = obtenerGradosGruposDisponibles($conexion);
    } else {
        $mensaje = ["texto" => "Error al eliminar el profesor: " . $conexion->error, "tipo" => "danger"];
    }
}

// Obtener todos los profesores y sus asignaciones
$sql_profesores = "SELECT id, nombre, grado, grupo, nivel, grado_clasificado FROM profesores";
$resultado_profesores = $conexion->query($sql_profesores);

if (!$resultado_profesores) {
    die("Error en la consulta de profesores: " . $conexion->error);
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Profesores</title>
    <link href="img/logo.png" rel="icon">
    <link rel="stylesheet" href="https://colas.github.io/normalize.css/8.0.1/normalize.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/styles.css">
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

        /* Nuevo estilo para el recuadro */
        .content-box {
            border: 2px solid #ccc;
            padding: 20px;
            background-color: #fff;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            border-radius: 10px;
        }

        .alert {
            position: fixed;
            top: 100px;
            right: 20px;
            padding: 15px;
            border-radius: 5px;
            font-size: 1rem; /* Updated from 16px */
            font-weight: bold;
            z-index: 1000;
            opacity: 1;
            transition: opacity 0.5s ease;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            color: #fff; /* Set default color for text */
        }

        .alert-success {
            background-color: #28a745; /* Updated from #d4edda */
            color: #fff; /* Ensure text color contrasts well */
        }

        .alert-danger {
            background-color: #dc3545; /* Updated from #f8d7da */
            color: #fff; /* Ensure text color contrasts well */
        }

        .alert-info {
            background-color: #17a2b8; /* Updated from #d1ecf1 */
            color: #fff; /* Ensure text color contrasts well */
        }

        .alert-warning {
            background-color: #ffc107; /* Updated from #fff3cd */
            color: #fff; /* Ensure text color contrasts well */
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

    <!-- Contenedor con el recuadro -->
    <div class="container mt-5 content-box">
        <h1>Gestión de Profesores</h1>
        <?php if (isset($mensaje)) { ?>
            <div class="alert alert-<?php echo $mensaje['tipo']; ?> alert-dismissible fade show" role="alert">
                <?php echo $mensaje['texto']; ?>
                <div class="barra-progreso">
                    <div class="barra"></div>
                </div>
            </div>
            <script>
                document.addEventListener('DOMContentLoaded', function () {
                    var alert = document.querySelector('.alert');
                    var barra = alert.querySelector('.barra');
                    var alertDuration = 5000; // Duration in milliseconds

                    // Start the progress bar animation
                    barra.style.width = '100%';

                    // Automatically close the alert when the progress bar finishes
                    setTimeout(function () {
                        alert.classList.remove('show');
                        alert.classList.add('fade');
                    }, alertDuration);
                });
            </script>
        <?php } ?>


        <div class="mb-3">
            <h2>Asignar Profesor</h2>
            <form method="post" action="">
                <div class="mb-3">
                    <label for="profesor_id" class="form-label">Profesor</label>
                    <select id="profesor_id" name="profesor_id" class="form-select" required>
                        <?php
                        $sql_profesores_select = "SELECT id, nombre FROM profesores";
                        $resultado_profesores_select = $conexion->query($sql_profesores_select);
                        while ($row = $resultado_profesores_select->fetch_assoc()) {
                            echo "<option value=\"{$row['id']}\">{$row['nombre']}</option>";
                        }
                        ?>
                    </select>
                </div>
                <div class="mb-3">
                    <label for="grado_grupo" class="form-label">Grado y Grupo</label>
                    <select id="grado_grupo" name="grado_grupo" class="form-select" required>
                        <?php
                        foreach ($grados_grupos_disponibles as $gg) {
                            $grado = isset($gg['grado']) ? $gg['grado'] : 'No disponible';
                            $grupo = isset($gg['grupo']) ? $gg['grupo'] : 'No disponible';
                            echo "<option value=\"{$grado}|{$grupo}\">{$grado} - {$grupo}</option>";
                        }
                        ?>
                    </select>
                </div>
                <button type="submit" name="asignar_profesor" class="btn btn-primary">Asignar Profesor</button>
            </form>
        </div>

        <div class="mb-3">
            <h2>Desasignar Profesor</h2>
            <form method="post" action="">
                <div class="mb-3">
                    <label for="profesor_id" class="form-label">Profesor</label>
                    <select id="profesor_id" name="profesor_id" class="form-select" required>
                        <?php
                        $resultado_profesores_select = $conexion->query($sql_profesores_select);
                        while ($row = $resultado_profesores_select->fetch_assoc()) {
                            echo "<option value=\"{$row['id']}\">{$row['nombre']}</option>";
                        }
                        ?>
                    </select>
                </div>
                <button type="submit" name="desasignar_profesor" class="btn btn-warning">Desasignar Profesor</button>
            </form>
        </div>

        <div class="mb-3">
            <h2>Agregar Profesor</h2>
            <form method="post" action="">
                <div class="mb-3">
                    <label for="nombre_profesor" class="form-label">Nombre del Profesor</label>
                    <input type="text" id="nombre_profesor" name="nombre_profesor" class="form-control" required>
                </div>
                <button type="submit" name="agregar_profesor" class="btn btn-success">Agregar Profesor</button>
            </form>
        </div>

        <div class="mb-3">
            <h2>Eliminar Profesor</h2>
            <form method="post" action="">
                <div class="mb-3">
                    <label for="profesor_id" class="form-label">Profesor</label>
                    <select id="profesor_id" name="profesor_id" class="form-select" required>
                        <?php
                        $resultado_profesores_select = $conexion->query($sql_profesores_select);
                        while ($row = $resultado_profesores_select->fetch_assoc()) {
                            echo "<option value=\"{$row['id']}\">{$row['nombre']}</option>";
                        }
                        ?>
                    </select>
                </div>
                <button type="submit" name="eliminar_profesor" class="btn btn-danger" onclick="return confirm('¿Está seguro de que desea eliminar este profesor?');">Eliminar Profesor</button>
            </form>
        </div>

        <h2>Profesores Asignados</h2>
        <form method="get" class="mb-3">
            <div class="row mb-3">
                <div class="col-md-6">
                    <label for="filtro_grado" class="form-label">Filtrar por Grado:</label>
                    <select id="filtro_grado" name="filtro_grado" class="form-select">
                        <option value="">Todos los grados</option>
                        <?php
                        $sql_grados = "SELECT DISTINCT grado_clasificado FROM profesores WHERE grado_clasificado IS NOT NULL ORDER BY grado_clasificado";
                        $resultado_grados = $conexion->query($sql_grados);
                        while ($row = $resultado_grados->fetch_assoc()) {
                            $selected = (isset($_GET['filtro_grado']) && $_GET['filtro_grado'] == $row['grado_clasificado']) ? 'selected' : '';
                            echo "<option value='{$row['grado_clasificado']}' {$selected}>{$row['grado_clasificado']}</option>";
                        }
                        ?>
                    </select>
                </div>
                <div class="col-md-6">
                <label for="filtro_grupo" class="form-label">Filtrar por Grupo:</label>
                    <select id="filtro_grupo" name="filtro_grupo" class="form-select">
                        <option value="">Todos los grupos</option>
                        <?php
                        $sql_grupos = "SELECT DISTINCT grupo_clasificado FROM profesores WHERE grupo_clasificado IS NOT NULL ORDER BY grupo_clasificado";
                        $resultado_grupos = $conexion->query($sql_grupos);
                        while ($row = $resultado_grupos->fetch_assoc()) {
                            $selected = (isset($_GET['filtro_grupo']) && $_GET['filtro_grupo'] == $row['grupo_clasificado']) ? 'selected' : '';
                            echo "<option value='{$row['grupo_clasificado']}' {$selected}>{$row['grupo_clasificado']}</option>";
                        }
                        ?>
                    </select>
                </div>
            </div>
            <div class="row">
                <div class="col-md-12">
                    <button type="submit" class="btn btn-primary">Filtrar</button>
                    <a href="<?php echo $_SERVER['PHP_SELF']; ?>" class="btn btn-secondary ms-2">Limpiar filtros</a>
                </div>
            </div>
        </form>

        <table class="table table-striped">
            <thead>
                <tr>
                    <th>Nombre</th>
                    <th>Grado</th>
                    <th>Grupo</th>
                    <th>Nivel</th>
                </tr>
            </thead>
            <tbody>
                <?php
                // Construir la consulta SQL con los filtros
                $sql_profesores = "SELECT nombre, grado, grupo, nivel FROM profesores WHERE 1=1";
                
                if (isset($_GET['filtro_grado']) && !empty($_GET['filtro_grado'])) {
                    $filtro_grado = $conexion->real_escape_string($_GET['filtro_grado']);
                    $sql_profesores .= " AND grado_clasificado = '$filtro_grado'";
                }
                
                if (isset($_GET['filtro_grupo']) && !empty($_GET['filtro_grupo'])) {
                    $filtro_grupo = $conexion->real_escape_string($_GET['filtro_grupo']);
                    $sql_profesores .= " AND grupo_clasificado = '$filtro_grupo'";
                }
                
                $resultado_profesores = $conexion->query($sql_profesores);

                // Verificar si hay resultados
                if ($resultado_profesores && $resultado_profesores->num_rows > 0) {
                    while ($row = $resultado_profesores->fetch_assoc()) {
                        $grado = isset($row['grado']) ? $row['grado'] : 'No asignado';
                        $grupo = isset($row['grupo']) ? $row['grupo'] : 'No asignado';
                        $nivel = isset($row['nivel']) ? $row['nivel'] : 'No asignado';
                        echo "<tr>
                            <td>{$row['nombre']}</td>
                            <td>{$grado}</td>
                            <td>{$grupo}</td>
                            <td>{$nivel}</td>
                        </tr>";
                    }
                } else {
                    echo "<tr><td colspan='4'>No hay datos disponibles.</td></tr>";
                }
                ?>
            </tbody>
        </table>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.7/dist/umd/popper.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.min.js"></script>
    <script>
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

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>


<?php
$conexion->close();
?>