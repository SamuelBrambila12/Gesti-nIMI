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
    die("Conexión fallida: " . $conexion->error);
}

// Obtener ID del alumno desde la URL
$alumno_id = isset($_GET['alumno']) ? intval($_GET['alumno']) : null;

if (!$alumno_id) {
    die("ID del alumno no proporcionado.");
}

// Función para obtener los conceptos de pago asignados al alumno
function obtenerConceptosDePago($alumno_id, $conexion) {
    $sql = "SELECT cp.id, cp.nombre, cp.monto, cp.descuento, cpa.fecha_asignacion, cpa.fecha_pago, cpa.estado_pago
            FROM conceptos_pago cp
            JOIN conceptos_pago_alumnos cpa ON cp.id = cpa.concepto_id
            WHERE cpa.alumno_id = ?";
    
    $stmt = $conexion->prepare($sql);
    $stmt->bind_param("i", $alumno_id);
    $stmt->execute();
    return $stmt->get_result();
}

// Función para obtener los conceptos de pago pendientes asignados al alumno
function obtenerConceptosPendientes($alumno_id, $conexion) {
    $sql = "SELECT cp.id, cp.nombre, cp.monto, cp.descuento
            FROM conceptos_pago cp
            JOIN conceptos_pago_alumnos cpa ON cp.id = cpa.concepto_id
            WHERE cpa.alumno_id = ? AND cpa.estado_pago = 'Pendiente'";
    
    $stmt = $conexion->prepare($sql);
    $stmt->bind_param("i", $alumno_id);
    $stmt->execute();
    return $stmt->get_result();
}

// Función para actualizar el estado del pago
function actualizarEstadoPago($alumno_id, $concepto_id, $conexion) {
    $fecha_pago = date('Y-m-d');
    $sql = "UPDATE conceptos_pago_alumnos 
            SET estado_pago = 'Pagado', fecha_pago = ? 
            WHERE alumno_id = ? AND concepto_id = ?";
    
    $stmt = $conexion->prepare($sql);
    $stmt->bind_param("sii", $fecha_pago, $alumno_id, $concepto_id);
    return $stmt->execute();
}

// Obtener conceptos de pago asignados al alumno
$conceptos_pago = obtenerConceptosDePago($alumno_id, $conexion);

// Obtener conceptos de pago pendientes
$conceptos_pendientes = obtenerConceptosPendientes($alumno_id, $conexion);

// Procesar el pago si se recibe una solicitud POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['pagar_todo'])) {
        // Si el usuario elige pagar todos los conceptos pendientes
        while ($concepto = $conceptos_pendientes->fetch_assoc()) {
            actualizarEstadoPago($alumno_id, $concepto['id'], $conexion);
        }
        $mensaje = "Todos los pagos pendientes han sido realizados con éxito.";
        // Actualizar la lista de conceptos de pago
        $conceptos_pago = obtenerConceptosDePago($alumno_id, $conexion);
    } elseif (isset($_POST['pagar'])) {
        // Si el usuario elige pagar un solo concepto
        $concepto_id = intval($_POST['concepto_id']);
        if (actualizarEstadoPago($alumno_id, $concepto_id, $conexion)) {
            $mensaje = "Pago realizado con éxito.";
            // Actualizar la lista de conceptos de pago
            $conceptos_pago = obtenerConceptosDePago($alumno_id, $conexion);
        } else {
            $mensaje = "Error al procesar el pago.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pagos del Alumno</title>
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

        .table {
            background-color: #fff;
            border-radius: 15px;
            box-shadow: 0px 4px 8px rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }

        .table th {
            background-color: #007bff;
            color: white;
            text-align: center;
        }

        .table td {
            text-align: center;
        }

        .alert {
            font-weight: bold;
            border-radius: 10px;
            text-align: center;
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
<div class="container mt-5">
    <h1>Pagos Asignados al Alumno</h1>

    <?php if (isset($mensaje)): ?>
        <div class="alert alert-info"><?php echo htmlspecialchars($mensaje); ?></div>
    <?php endif; ?>

    <!-- Tabla de conceptos de pago -->
    <table class="table table-striped">
        <thead>
            <tr>
                <th>Concepto</th>
                <th>Monto</th>
                <th>Descuento</th>
                <th>Monto con Descuento</th>
                <th>Fecha Asignación</th>
                <th>Fecha Pago</th>
                <th>Estado</th>
                <th>Acción</th>
            </tr>
        </thead>
        <tbody>
            <?php if ($conceptos_pago->num_rows > 0): ?>
                <?php while ($row = $conceptos_pago->fetch_assoc()): 
                    $monto_con_descuento = $row['monto'] * (1 - $row['descuento'] / 100);
                    $estado_pago = htmlspecialchars($row['estado_pago']);
                ?>
                <tr>
                    <td><?php echo htmlspecialchars($row['nombre']); ?></td>
                    <td>$<?php echo number_format($row['monto'], 2); ?></td>
                    <td><?php echo htmlspecialchars($row['descuento']); ?>%</td>
                    <td>$<?php echo number_format($monto_con_descuento, 2); ?></td>
                    <td><?php echo htmlspecialchars($row['fecha_asignacion']); ?></td>
                    <td><?php echo $row['fecha_pago'] ? htmlspecialchars($row['fecha_pago']) : 'N/A'; ?></td>
                    <td><?php echo $estado_pago; ?></td>
                    <td>
                        <?php if ($estado_pago === 'Pendiente'): ?>
                            <form method="POST" action="pasarela/procesar_pago.php" class="pagos-pendientes">
                                <input type="hidden" name="concepto_id" value="<?php echo $row['id']; ?>">
                                <input type="hidden" name="alumno_id" value="<?php echo $alumno_id; ?>"> <!-- ID del alumno -->
                                <input type="hidden" name="monto" value="<?php echo number_format($monto_con_descuento, 2, '.', ''); ?>"> <!-- Monto con descuento -->
                                <button type="submit" name="pagar" class="btn-pagar">Pagar</button>
                            </form>
                        <?php else: ?>
                            <span class="text-success">Pagado</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr>
                    <td colspan="8" class="text-center">No hay conceptos de pago asignados.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>

    <!-- Botón para pagar todos los conceptos pendientes -->
<?php
$total_monto_pendiente = 0; // Inicializar la variable para almacenar el total
if ($conceptos_pendientes->num_rows > 0): 
    while ($concepto_pendiente = $conceptos_pendientes->fetch_assoc()):
        // Calcular el monto con descuento
        $monto_con_descuento_pendiente = $concepto_pendiente['monto'] * (1 - $concepto_pendiente['descuento'] / 100);
        // Sumar el monto con descuento al total
        $total_monto_pendiente += $monto_con_descuento_pendiente;
    endwhile;
?>
    <form method="POST" action="pasarela/procesar_pago.php">
        <input type="hidden" name="alumno_id" value="<?php echo $alumno_id; ?>">
        <input type="hidden" name="monto" value="<?php echo number_format($total_monto_pendiente, 2, '.', ''); ?>"> <!-- Sumar el monto total -->
        <button type="submit" name="pagar_todo" value="true" class="btn btn-primary">Pagar Todo</button>
    </form>
<?php endif; ?>

</div>


<script src="https://code.jquery.com/jquery-3.2.1.slim.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.11.0/umd/popper.min.js"></script>
<script src="https://maxcdn.com/bootstrap/4.0.0/js/bootstrap.min.js"></script>

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

</body>
</html>


<?php
$conexion->close();
?>
