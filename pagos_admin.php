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
    die("Conexión fallida: " . $conexion->error);
}

if (isset($_SESSION['mensaje'])) {
    $mensaje = $_SESSION['mensaje'];
    // Determina si es un mensaje de éxito o error
    $clase = strpos($mensaje, 'Error') !== false ? 'error' : 'exito';
    // Limpia el mensaje de la sesión después de mostrarlo
    unset($_SESSION['mensaje']);
}

// Verificar si la tabla 'conceptos_pago' existe, si no, crearla
$sql_check_table = "SHOW TABLES LIKE 'conceptos_pago'";
$result = $conexion->query($sql_check_table);

if ($result->num_rows == 0) {
    $sql_create_table = "CREATE TABLE conceptos_pago (
        id INT AUTO_INCREMENT PRIMARY KEY,
        nombre VARCHAR(100) NOT NULL,
        descripcion TEXT,
        monto DECIMAL(10, 2) NOT NULL,
        descuento DECIMAL(5, 2) DEFAULT 0
    )";

    if ($conexion->query($sql_create_table) !== TRUE) {
        die("Error al crear la tabla conceptos_pago: " . $conexion->error);
    }
}

// Verificar si la tabla 'conceptos_pago_alumnos' existe, si no, crearla
$sql_check_table = "SHOW TABLES LIKE 'conceptos_pago_alumnos'";
$result = $conexion->query($sql_check_table);

if ($result->num_rows == 0) {
    $sql_create_table = "CREATE TABLE conceptos_pago_alumnos (
        id INT AUTO_INCREMENT PRIMARY KEY,
        concepto_id INT,
        alumno_id INT,
        fecha_asignacion DATE,
        fecha_pago DATE,
        estado_pago ENUM('Pendiente', 'Pagado') DEFAULT 'Pendiente',
        CONSTRAINT fk_concepto FOREIGN KEY (concepto_id) REFERENCES conceptos_pago(id),
        CONSTRAINT fk_alumno FOREIGN KEY (alumno_id) REFERENCES alumnos(id)
    )";

    if ($conexion->query($sql_create_table) !== TRUE) {
        die("Error al crear la tabla conceptos_pago_alumnos: " . $conexion->error);
    }
}

// Obtener la lista de alumnos
function obtenerAlumnos($conexion) {
    $sql = "SELECT id, nombre FROM alumnos";
    $resultado = $conexion->query($sql);
    return $resultado->fetch_all(MYSQLI_ASSOC);
}

$alumnos = obtenerAlumnos($conexion);

// Función para asignar concepto de pago a alumno(s)
function asignarConceptoPago($concepto_id, $alumno_ids, $conexion) {
    $fecha_asignacion = date('Y-m-d');
    $valores = [];
    foreach ($alumno_ids as $alumno_id) {
        $valores[] = "($concepto_id, $alumno_id, '$fecha_asignacion', 'Pendiente')";
    }
    $valores_str = implode(', ', $valores);
    $sql = "INSERT INTO conceptos_pago_alumnos (concepto_id, alumno_id, fecha_asignacion, estado_pago) VALUES $valores_str";
    return $conexion->query($sql);
}

// Función para limpiar los datos del formulario
function limpiar_datos_formulario() {
    unset($_POST['nombre']);
    unset($_POST['descripcion']);
    unset($_POST['monto']);
    unset($_POST['descuento']);
    unset($_POST['alumno_ids']);
}

// Función para agregar un nuevo concepto de pago
function agregarConceptoPago($nombre, $descripcion, $monto, $descuento, $alumno_ids, $conexion) {
    $nombre = $conexion->real_escape_string($nombre);
    $descripcion = $conexion->real_escape_string($descripcion);
    $monto = floatval($monto);
    $descuento = floatval($descuento);

    $sql = "INSERT INTO conceptos_pago (nombre, descripcion, monto, descuento) VALUES ('$nombre', '$descripcion', $monto, $descuento)";
    if ($conexion->query($sql)) {
        $concepto_id = $conexion->insert_id;
        return asignarConceptoPago($concepto_id, $alumno_ids, $conexion);
    }
    return false;
}

// Función para editar un concepto de pago existente
function editarConceptoPago($id, $nombre, $descripcion, $monto, $descuento, $conexion) {
    $id = intval($id);
    $nombre = $conexion->real_escape_string($nombre);
    $descripcion = $conexion->real_escape_string($descripcion);
    $monto = floatval($monto);
    $descuento = floatval($descuento);

    $sql = "UPDATE conceptos_pago SET nombre = '$nombre', descripcion = '$descripcion', monto = $monto, descuento = $descuento WHERE id = $id";
    return $conexion->query($sql);
}

// Proceso de eliminar un solo concepto
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['eliminar_concepto'])) {
    $id = $_POST['id'];
    $result = eliminarConceptoPago($id, $conexion);
    if ($result) {
        $_SESSION['mensaje'] = "Concepto de pago eliminado correctamente.";
        echo json_encode(['success' => true, 'message' => $_SESSION['mensaje']]);
    } else {
        $_SESSION['mensaje'] = "Error al eliminar el concepto de pago.";
        echo json_encode(['success' => false, 'message' => $_SESSION['mensaje']]);
    }
    exit;
}

function eliminarConceptoPago($id, $conexion) {
    $id = intval($id); // Asegúrate de que el ID sea un número entero
    $query = "DELETE FROM conceptos_pago_alumnos WHERE id = ?";
    $stmt = $conexion->prepare($query);
    if ($stmt === false) {
        return false;
    }
    $stmt->bind_param("i", $id);
    $result = $stmt->execute();
    if ($result === false) {
        return false;
    }
    return $result;
}



// Función actualizada para marcar un pago como pagado
function marcarComoPagado($id, $conexion) {
    $conexion->begin_transaction();
    try {
        // Obtener el concepto_id y alumno_id asociados al pago
        $sql_select = "SELECT concepto_id, alumno_id FROM conceptos_pago_alumnos WHERE id = ?";
        $stmt_select = $conexion->prepare($sql_select);
        $stmt_select->bind_param("i", $id);
        $stmt_select->execute();
        $result = $stmt_select->get_result();
        if ($result->num_rows == 0) {
            throw new Exception("No se encontró el pago con ID $id.");
        }
        $row = $result->fetch_assoc();
        $concepto_id = $row['concepto_id'];
        $alumno_id = $row['alumno_id'];

        // Actualizar el estado del pago a 'Pagado'
        $sql_update = "UPDATE conceptos_pago_alumnos SET estado_pago = 'Pagado', fecha_pago = ? WHERE id = ?";
        $stmt_update = $conexion->prepare($sql_update);
        $fecha_pago = date('Y-m-d');
        $stmt_update->bind_param("si", $fecha_pago, $id);
        $stmt_update->execute();

        // Verificar si la actualización fue exitosa
        if ($stmt_update->affected_rows == 0) {
            throw new Exception("No se pudo actualizar el pago con ID $id.");
        }

        // Eliminar cualquier concepto duplicado pendiente
        $sql_delete = "DELETE FROM conceptos_pago_alumnos WHERE alumno_id = ? AND concepto_id = ? AND estado_pago = 'Pendiente'";
        $stmt_delete = $conexion->prepare($sql_delete);
        $stmt_delete->bind_param("ii", $alumno_id, $concepto_id);
        $stmt_delete->execute();

        // Confirmar la transacción
        $conexion->commit();
        return true;
    } catch (Exception $e) {
        // Si hay un error, revertir la transacción
        $conexion->rollback();
        error_log("Error al marcar como pagado: " . $e->getMessage());
        return false;
    }
}

// Función para filtrar pagos con paginación
function filtrarPagos($conexion, $items_por_pagina, $offset, $nombre = null, $fecha_inicio = null, $fecha_fin = null) {
    $sql = "SELECT cpa.id, cp.nombre, cp.monto, cp.descuento, a.nombre AS alumno_nombre, cpa.fecha_asignacion, cpa.fecha_pago, cpa.estado_pago 
            FROM conceptos_pago_alumnos cpa
            JOIN conceptos_pago cp ON cpa.concepto_id = cp.id
            JOIN alumnos a ON cpa.alumno_id = a.id
            WHERE 1=1";
    
    if ($nombre) {
        $nombre = $conexion->real_escape_string($nombre);
        $sql .= " AND (cp.nombre LIKE '%$nombre%' OR a.nombre LIKE '%$nombre%')";
    }
    
    if ($fecha_inicio && $fecha_fin) {
        $sql .= " AND cpa.fecha_asignacion BETWEEN '$fecha_inicio' AND '$fecha_fin'";
    }
    
    $sql .= " ORDER BY cpa.fecha_asignacion DESC";
    
    // Agregar LIMIT y OFFSET para paginación
    $sql .= " LIMIT $items_por_pagina OFFSET $offset";
    
    return $conexion->query($sql);
}

// Función para obtener el total de resultados (para paginación)
function obtenerTotalResultados($conexion, $nombre = null, $fecha_inicio = null, $fecha_fin = null) {
    $sql = "SELECT COUNT(*) as total
            FROM conceptos_pago_alumnos cpa
            JOIN conceptos_pago cp ON cpa.concepto_id = cp.id
            JOIN alumnos a ON cpa.alumno_id = a.id
            WHERE 1=1";
    
    if ($nombre) {
        $nombre = $conexion->real_escape_string($nombre);
        $sql .= " AND (cp.nombre LIKE '%$nombre%' OR a.nombre LIKE '%$nombre%')";
    }
    
    if ($fecha_inicio && $fecha_fin) {
        $sql .= " AND cpa.fecha_asignacion BETWEEN '$fecha_inicio' AND '$fecha_fin'";
    }
    
    $resultado = $conexion->query($sql);
    $fila = $resultado->fetch_assoc();
    return $fila['total'];
}

// Función para eliminar múltiples conceptos de pago
function eliminarConceptosSeleccionados($ids, $conexion) {
    $ids = array_map('intval', $ids);
    $ids_string = implode(',', $ids);
    $sql = "DELETE FROM conceptos_pago_alumnos WHERE id IN ($ids_string)";
    return $conexion->query($sql);
}

// Manejo de solicitudes AJAX
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['marcar_como_pagado'])) {
    $id = $_POST['id'];
    $result = marcarComoPagado($id, $conexion);
    if ($result) {
        $_SESSION['mensaje'] = "Pago marcado como pagado correctamente.";
        echo json_encode(['success' => true, 'message' => $_SESSION['mensaje']]);
    } else {
        $_SESSION['mensaje'] = "Error al marcar el pago como pagado. Revise los logs para más detalles.";
        echo json_encode(['success' => false, 'message' => $_SESSION['mensaje']]);
    }
    exit;
}

// Manejo de solicitudes AJAX para eliminar conceptos seleccionados
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['eliminar_seleccionados'])) {
    $ids = $_POST['ids'];
    $result = eliminarConceptosSeleccionados($ids, $conexion);
    if ($result) {
        $_SESSION['mensaje'] = "Conceptos eliminados correctamente.";
        echo json_encode(['success' => true, 'message' => $_SESSION['mensaje']]);
    } else {
        $_SESSION['mensaje'] = "Error al eliminar los conceptos. Revise los logs para más detalles.";
        echo json_encode(['success' => false, 'message' => $_SESSION['mensaje']]);
    }
    exit;
}

// Procesar formularios
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['agregar_concepto'])) {
        $nombre = $_POST['nombre'];
        $descripcion = $_POST['descripcion'];
        $monto = $_POST['monto'];
        $descuento = $_POST['descuento'];
        $alumno_ids = isset($_POST['alumno_ids']) ? $_POST['alumno_ids'] : [];
        if (in_array('todos', $alumno_ids)) {
            $alumno_ids = array_column($alumnos, 'id');
        }
        if (agregarConceptoPago($nombre, $descripcion, $monto, $descuento, $alumno_ids, $conexion)) {
            $_SESSION['mensaje'] = "Concepto de pago agregado y asignado correctamente.";
        } else {
            $_SESSION['mensaje'] = "Error al agregar el concepto de pago: " . $conexion->error;
        }
        header('Location: pagos_admin.php'); // Redirige a pagos_admin.php
        exit(); // Asegúrate de detener la ejecución del script después de redirigir
    } elseif (isset($_POST['editar_concepto'])) {
        $id = $_POST['id'];
        $nombre = $_POST['nombre'];
        $descripcion = $_POST['descripcion'];
        $monto = $_POST['monto'];
        $descuento = $_POST['descuento'];
        if (editarConceptoPago($id, $nombre, $descripcion, $monto, $descuento, $conexion)) {
            $mensaje = "Concepto de pago editado correctamente.";
        } else {
            $mensaje = "Error al editar el concepto de pago: " . $conexion->error;
        }
    } elseif (isset($_POST['eliminar_concepto'])) {
        $id = $_POST['id'];
        if (eliminarConceptoPago($id, $conexion)) {
            echo json_encode(['success' => true, 'message' => "Concepto de pago eliminado correctamente."]);
        } else {
            echo json_encode(['success' => false, 'message' => "Error al eliminar el concepto de pago."]);
        }
        exit;
    }
}

// Configuración de la paginación
$items_por_pagina = 10;
$pagina_actual = isset($_GET['pagina']) ? intval($_GET['pagina']) : 1;
$offset = ($pagina_actual - 1) * $items_por_pagina;

// Filtrar pagos
$nombre_filtro = isset($_GET['nombre_filtro']) ? $_GET['nombre_filtro'] : null;
$fecha_inicio = isset($_GET['fecha_inicio']) ? $_GET['fecha_inicio'] : null;
$fecha_fin = isset($_GET['fecha_fin']) ? $_GET['fecha_fin'] : null;

$pagos_asignados = filtrarPagos($conexion, $items_por_pagina, $offset, $nombre_filtro, $fecha_inicio, $fecha_fin);
$total_items = obtenerTotalResultados($conexion, $nombre_filtro, $fecha_inicio, $fecha_fin);
$total_paginas = ceil($total_items / $items_por_pagina);

$conexion->close();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Pagos</title>
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
            opacity: 5%;
        }

        /* Estilos para la alerta */
        .alert {
            position: fixed;
            top: 100px;
            right: 20px;
            padding: 15px;
            border-radius: 5px;
            color: #fff;
            font-size: 1rem;
            z-index: 1000;
            opacity: 1;
            background-color: #28a745;
            transition: opacity 0.5s ease;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }

        .alert-dismissible {
            padding-right: 35px;
        }

        .alert-dismissible .btn-close {
            position: absolute;
            top: 0;
            right: 0;
            padding: 10px;
            color: #fff;
            background: transparent;
            border: none;
        }

        /* Estilos para la barra de progreso */
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
                        <button type="submit" name="cerrar_sesion" value="true" class="btn btn-danger">Cerrar Sesión</button>
                    </form>
                </li>
            </ul>
        </nav>
    </div>
</header>
<div class="container mt-4">
    <h1>Gestión de Pagos</h1>

    <?php if (isset($mensaje)): ?>
    <div class="alert alert-info alert-dismissible fade show" role="alert">
        <?php echo $mensaje; ?>
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
            barra.style.transition = 'width ' + alertDuration + 'ms linear';
            barra.style.width = '100%';

            // Automatically close the alert when the progress bar finishes
            setTimeout(function () {
                alert.classList.remove('show');
                alert.classList.add('fade');
                setTimeout(function () {
                    alert.remove(); // Remove the alert from the DOM
                }, 500); // Delay to allow fade effect
            }, alertDuration);
        });
    </script>
<?php endif; ?>


    <form method="POST" class="mb-4">
        <h2>Agregar Concepto de Pago</h2>
        <div class="form-group">
            <label for="nombre">Nombre</label>
            <input type="text" class="form-control" id="nombre" name="nombre" required>
        </div>
        <div class="form-group">
            <label for="descripcion">Descripción</label>
            <textarea class="form-control" id="descripcion" name="descripcion"></textarea>
        </div>
        <div class="form-group">
            <label for="monto">Monto</label>
            <input type="number" class="form-control" id="monto" name="monto" step="0.01" required>
        </div>
        <div class="form-group">
            <label for="descuento">Descuento</label>
            <input type="number" class="form-control" id="descuento" name="descuento" step="0.01" value="0">
        </div>
        <div class="form-group">
            <label for="alumno_nombre">Nombre del alumno</label>
            <input type="text" class="form-control" id="alumno_nombre" name="alumno_nombre">
        </div>
        <div class="form-group">
            <label>Asignar a alumnos</label>
            <div id="alumnos-list">
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" id="todos" name="alumno_ids[]" value="todos">
                    <label class="form-check-label" for="todos">Todos los alumnos</label>
                </div>
                <?php foreach ($alumnos as $alumno): ?>
                    <div class="form-check alumno-item">
                        <input class="form-check-input alumno-checkbox" type="checkbox" id="alumno_<?php echo $alumno['id']; ?>" name="alumno_ids[]" value="<?php echo $alumno['id']; ?>">
                        <label class="form-check-label" for="alumno_<?php echo $alumno['id']; ?>"><?php echo htmlspecialchars($alumno['nombre']); ?></label>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        <button type="submit" name="agregar_concepto" class="btn btn-primary">Agregar Concepto de Pago</button>
    </form>

    <h2>Pagos asignados</h2>

    <form method="GET" class="mb-4">
        <div class="form-row">
            <div class="form-group col-md-3">
                <label for="nombre_filtro">Nombre del alumno</label>
                <input type="text" class="form-control" id="nombre_filtro" name="nombre_filtro" value="<?php echo isset($_GET['nombre_filtro']) ? htmlspecialchars($_GET['nombre_filtro']) : ''; ?>">
            </div>
            <div class="form-group col-md-3">
                <label for="fecha_inicio">Fecha de inicio</label>
                <input type="date" class="form-control" id="fecha_inicio" name="fecha_inicio" value="<?php echo isset($_GET['fecha_inicio']) ? $_GET['fecha_inicio'] : ''; ?>">
            </div>
            <div class="form-group col-md-3">
                <label for="fecha_fin">Fecha de fin</label>
                <input type="date" class="form-control" id="fecha_fin" name="fecha_fin" value="<?php echo isset($_GET['fecha_fin']) ? $_GET['fecha_fin'] : ''; ?>">
            </div>
        </div>
        <div class="form-row">
            <div class="form-group col-md-3">
                <button type="submit" name="filtrar" class="btn btn-primary">Filtrar</button>
                <button type="button" id="limpiar-filtros" class="btn btn-secondary">Limpiar filtros</button>
            </div>
        </div>
    </form>

    <div class="mb-3">
        <button id="seleccionar-todos" class="btn btn-secondary">Seleccionar todos</button>
        <button id="eliminar-seleccionados" class="btn btn-danger d-none">Eliminar seleccionados</button>
    </div>

    <table class="table">
        <thead>
            <tr>
                <th>Sel.</th>
                <th>Nombre</th>
                <th>Monto</th>
                <th>Descuento</th>
                <th>Alumno</th>
                <th>Fecha de asignación</th>
                <th>Fecha de pago</th>
                <th>Estado</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody>
            <?php while ($fila = $pagos_asignados->fetch_assoc()): ?>
                <tr>
                    <td><input type="checkbox" class="checkbox-item" data-id="<?php echo $fila['id']; ?>"></td>
                    <td><?php echo htmlspecialchars($fila['nombre']); ?></td>
                    <td><?php echo htmlspecialchars($fila['monto']); ?></td>
                    <td><?php echo htmlspecialchars($fila['descuento']); ?></td>
                    <td><?php echo htmlspecialchars($fila['alumno_nombre']); ?></td>
                    <td><?php echo htmlspecialchars($fila['fecha_asignacion']); ?></td>
                    <td><?php echo empty($fila['fecha_pago']) ? 'N/A' : htmlspecialchars($fila['fecha_pago']); ?></td>
                    <td><?php echo htmlspecialchars($fila['estado_pago']); ?></td>
                    <td>
                        <?php if ($fila['estado_pago'] == 'Pendiente'): ?>
                            <button class="btn btn-success btn-sm marcar-pagado" data-id="<?php echo $fila['id']; ?>">Marcar como pagado</button>
                        <?php endif; ?>
                        <button class="btn btn-danger btn-sm eliminar-concepto" data-id="<?php echo $fila['id']; ?>">Eliminar</button>
                    </td>
                </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
</div>

    <!-- Paginación -->
    <nav aria-label="Navegación de páginas">
        <ul class="pagination justify-content-center">
            <?php for ($i = 1; $i <= $total_paginas; $i++): ?>
                <li class="page-item <?php echo $i == $pagina_actual ? 'active' : ''; ?>">
                    <a class="page-link" href="?pagina=<?php echo $i; ?><?php echo isset($_GET['filtrar']) ? '&filtrar=1&nombre_filtro=' . urlencode($_GET['nombre_filtro']) . '&fecha_inicio=' . $_GET['fecha_inicio'] . '&fecha_fin=' . $_GET['fecha_fin'] : ''; ?>"><?php echo $i; ?></a>
                </li>
            <?php endfor; ?>
        </ul>
    </nav>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
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
    
    $(document).ready(function() {
        // Marcar como pagado
        $('.marcar-pagado').click(function() {
            var id = $(this).data('id');
            $.post('pagos_admin.php', {marcar_como_pagado: true, id: id}, function(response) {
                var data = JSON.parse(response);
                if (data.success) {
                    location.reload();
                } else {
                }
            });
        });

        // Eliminar concepto individual
        $('.eliminar-concepto').click(function() {
        if (confirm('¿Está seguro de que desea eliminar este concepto?')) {
            var id = $(this).data('id');
            $.post('pagos_admin.php', { eliminar_concepto: true, id: id }, function(response) {
                var data = JSON.parse(response);
                if (data.success) {
                    location.reload();
                } else {
                }
            });
        }
    });

        // Seleccionar todos los checkboxes
        $('#checkbox-all').change(function() {
            $('.checkbox-item').prop('checked', $(this).prop('checked'));
            actualizarBotonEliminar();
        });

        // Actualizar botón de eliminar cuando se cambia un checkbox individual
        $('.checkbox-item').change(function() {
            actualizarBotonEliminar();
        });

        // Función para actualizar la visibilidad del botón de eliminar
        function actualizarBotonEliminar() {
            if ($('.checkbox-item:checked').length > 0) {
                $('#eliminar-seleccionados').removeClass('d-none');
            } else {
                $('#eliminar-seleccionados').addClass('d-none');
            }
        }

        // Eliminar conceptos seleccionados
        $('#eliminar-seleccionados').click(function() {
            if (confirm('¿Está seguro de que desea eliminar los conceptos seleccionados?')) {
                var ids = $('.checkbox-item:checked').map(function() {
                    return $(this).data('id');
                }).get();

                $.post('pagos_admin.php', {eliminar_seleccionados: true, ids: ids}, function(response) {
                    var data = JSON.parse(response);
                    if (data.success) {
                        location.reload();
                    } else {
                    }
                });
            }
        });

        // Filtrado de alumnos
        $('#alumno_nombre').on('input', function() {
            var searchText = $(this).val().toLowerCase();
            $('.alumno-item').each(function() {
                var alumnoName = $(this).find('label').text().toLowerCase();
                if (alumnoName.startsWith(searchText)) {
                    $(this).show();
                } else {
                    $(this).hide();
                }
            });

            // Siempre mostrar "Todos los alumnos"
            $('#todos').parent().show();
        });

        // Limpiar filtros
        $('#limpiar-filtros').click(function() {
            $('#nombre_filtro').val('');
            $('#fecha_inicio').val('');
            $('#fecha_fin').val('');
        });

        // Seleccionar todos los checkboxes de la tabla
        $('#seleccionar-todos').click(function() {
            $('.checkbox-item').prop('checked', true);
            actualizarBotonEliminar();
        });

        // Manejar el envío del formulario
        $('#formConcepto').submit(function(e) {
                e.preventDefault();
                $.ajax({
                    type: "POST",
                    url: "pagos_admin.php",
                    data: $(this).serialize(),
                    success: function(response) {
                        var data = JSON.parse(response);
                        if (data.success) {
                            alert(data.message);
                            // Limpiar campos del formulario
                            $('#formConcepto')[0].reset();
                            // Actualizar el token del formulario
                            $('input[name="form_token"]').val(data.newToken);
                            // Desmarcar todos los checkboxes
                            $('input[type="checkbox"]').prop('checked', false);
                            // Recargar la lista de conceptos sin recargar toda la página
                            // Aquí deberías implementar una función para actualizar la lista de conceptos
                            // Por ejemplo: actualizarListaConceptos();
                        } else {
                            alert(data.message);
                        }
                    },
                    error: function() {
                        alert("Error al procesar la solicitud");
                    }
                });
            });

            // Limpiar formulario al cargar la página
            function limpiarFormulario() {
                $('#formConcepto')[0].reset();
                $('input[type="checkbox"]').prop('checked', false);
            }

            // Llamar a limpiarFormulario cuando se carga la página
            limpiarFormulario();

            // Llamar a limpiarFormulario cada vez que se muestra la página (por ejemplo, al volver de otra pestaña)
            $(window).focus(limpiarFormulario);
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
                    