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

// Obtener el ID del alumno
$id_alumno = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Consulta para obtener todos los datos del alumno
$sql = "SELECT * FROM alumnos WHERE id = $id_alumno";
$resultado = $conexion->query($sql);

if ($resultado->num_rows > 0) {
    $alumno = $resultado->fetch_assoc();
    // Obtener el nombre del profesor asignado basado en el grado y grupo del alumno
    $profesor_nombre = obtenerNombreProfesor($alumno['grado'], $alumno['grupo'], $conexion);
} else {
    echo "No se encontró el alumno.";
    exit();
}

$conexion->close();

// Verificar si se ha solicitado la descarga del PDF
if (isset($_GET['descargar_pdf'])) {
    require_once('tcpdf/tcpdf.php');

    // Crear una nueva instancia de TCPDF
    $pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
    $pdf->SetMargins(15, PDF_MARGIN_TOP, PDF_MARGIN_RIGHT); // Ajustar márgenes
    $pdf->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);

    // Establecer fuente
    $pdf->SetFont('Helvetica', '', 12);

    // Agregar una página
    $pdf->AddPage();

    $foto_path = !empty($alumno['foto']) ? $alumno['foto'] : 'img/default.jpg';
    // Crear una tabla para colocar la foto y el título en la misma línea
    $html = '
        <table>
            <tr>
                <td width="60%">
                    <h2>Ficha Completa del Alumno</h2>
                </td>
                <td width="40%" align="right">
                    <img src="' . $foto_path . '" width="100" height="100" />
                </td>
            </tr>
        </table>
        <br>';

    $pdf->writeHTML($html, true, false, true, false, '');

    // Información Personal
    $pdf->Ln();
    $pdf->SetFont('Helvetica', 'B', 14);
    $pdf->Cell(0, 10, 'Información Personal', 0, 1, 'L', false);
    $pdf->SetFont('Helvetica', '', 12);

    $html = '<style>
                table {
                    border-collapse: collapse;
                    width: 100%;
                }
                table td, table th {
                    border: 0.5px solid #000;
                    padding: 8px;
                    text-align: left;
                }
                table tr:nth-child(even) {
                    background-color: #f2f2f2;
                }
                table tr th {
                    font-size: 12px;
                    font-weight: bold;
                }
                table tr td {
                    font-size: 12px;
                }
                .section-title {
                    background-color: #eeeeee;
                    font-weight: bold;
                    padding: 5px;
                }
            </style>
            <table>
                <tr>
                    <th class="section-title">Nombre:</th>
                    <td>' . ($alumno['nombre'] ?? 'Sin información') . '</td>
                </tr>
                <tr>
                    <th class="section-title">Edad:</th>
                    <td>' . ($alumno['edad'] ?? 'Sin información') . '</td>
                </tr>
                <tr>
                    <th class="section-title">CURP:</th>
                    <td>' . ($alumno['curp'] ?? 'Sin información') . '</td>
                </tr>
                <tr>
                    <th class="section-title">Fecha de Nacimiento:</th>
                    <td>' . ($alumno['fecha_nacimiento'] ?? 'Sin información') . '</td>
                </tr>
                <tr>
                    <th class="section-title">Lugar de Nacimiento:</th>
                    <td>' . ($alumno['lugar_nacimiento'] ?? 'Sin información') . '</td>
                </tr>
            </table>';

    $pdf->writeHTML($html, true, false, true, false, '');

    // Información Académica
    $pdf->Ln(10);
    $pdf->SetFont('Helvetica', 'B', 14);
    $pdf->Cell(0, 10, 'Información Académica', 0, 1, 'L', false);
    $pdf->SetFont('Helvetica', '', 12);

    $html = '<style>
                table {
                    border-collapse: collapse;
                    width: 100%;
                }
                table td, table th {
                    border: 0.5px solid #000;
                    padding: 8px;
                    text-align: left;
                }
                table tr:nth-child(even) {
                    background-color: #f2f2f2;
                }
                table tr th {
                    font-size: 12px;
                    font-weight: bold;
                }
                table tr td {
                    font-size: 12px;
                }
                .section-title {
                    background-color: #eeeeee;
                    font-weight: bold;
                    padding: 5px;
                }
            </style>
            <table>
                <tr>
                    <th class="section-title">Grado:</th>
                    <td>' . ($alumno['grado'] ?? 'Sin información') . '</td>
                </tr>
                <tr>
                    <th class="section-title">Grupo:</th>
                    <td>' . ($alumno['grupo'] ?? 'Sin información') . '</td>
                </tr>
                <tr>
                    <th class="section-title">Profesor Asignado:</th>
                    <td>' . ($profesor_nombre ?? 'Sin información') . '</td>
                </tr>
            </table>';

    $pdf->writeHTML($html, true, false, true, false, '');

    // Información de Contacto
    $pdf->Ln(10);
    $pdf->SetFont('Helvetica', 'B', 14);
    $pdf->Cell(0, 10, 'Información de Contacto', 0, 1, 'L', false);
    $pdf->SetFont('Helvetica', '', 12);

    $html = '<style>
                table {
                    border-collapse: collapse;
                    width: 100%;
                }
                table td, table th {
                    border: 0.5px solid #000;
                    padding: 8px;
                    text-align: left;
                }
                table tr:nth-child(even) {
                    background-color: #f2f2f2;
                }
                table tr th {
                    font-size: 12px;
                    font-weight: bold;
                }
                table tr td {
                    font-size: 12px;
                }
                .section-title {
                    background-color: #eeeeee;
                    font-weight: bold;
                    padding: 5px;
                }
            </style>  
            <table>
                <tr>
                    <th class="section-title">Nombre del Padre/Madre/Tutor:</th>
                    <td>' . ($alumno['nombre_padre'] ?? 'Sin información') . '</td>
                </tr>
                <tr>
                    <th class="section-title">Correo Electrónico:</th>
                    <td>' . ($alumno['correo'] ?? 'Sin información') . '</td>
                </tr>
                <tr>
                    <th class="section-title">Teléfono:</th>
                    <td>' . ($alumno['telefono'] ?? 'Sin información') . '</td>
                </tr>
                <tr>
                    <th class="section-title">Dirección:</th>
                    <td>' . ($alumno['direccion'] ?? 'Sin información') . '</td>
                </tr>
            </table>';

    $pdf->writeHTML($html, true, false, true, false, '');

    // Salida del PDF
    $pdf->Output('ficha_alumno.pdf', 'D');
    exit();
}

// Función para obtener el nombre del profesor basado en grado y grupo
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
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ficha Completa del Alumno</title>
    <link href="img/logo.png" rel="icon">
    <link rel="stylesheet" href="https://necolas.github.io/normalize.css/8.0.1/normalize.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@200..1000&family=Poppins:wght@100..900&display=swap" rel="stylesheet">
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
    </style>
</head>
<body>
<div class="loader-container" id="loader">
        <div class="loader"></div>
    </div>
    <header>
        <nav class="user-nav">
            <ul>
                <li>
                    <form action="base.php" method="GET">
                        <button type="submit" name="volver_registros">Volver a los registros</button>
                    </form>
                </li>
            </ul>
        </nav>
    </header>
    <main>
        <h1>Ficha Completa del Alumno</h1>
        <div class="ficha-alumno">
            <h2><?php echo htmlspecialchars($alumno['nombre'] ?? 'No se ha proporcionado'); ?></h2>
            
            <?php if (!empty($alumno['foto'])): ?>
                <img src="<?php echo htmlspecialchars($alumno['foto']); ?>" alt="Foto del alumno" class="foto-alumno">
            <?php else: ?>
                <img src="img/default.webp" alt="usuario-default">
            <?php endif; ?>
            
            <!-- Botón para descargar el PDF -->
            <div class="contenedor-descarga">
                <a href="?id=<?php echo htmlspecialchars($alumno['id']); ?>&descargar_pdf=true" class="btn-descargar">Descargar ficha</a>
            </div>

            <div class="info-container">
                <div class="info-column">
                    <h3>Información Personal</h3>
                    <p><strong>Edad:</strong> <?php echo htmlspecialchars($alumno['edad'] ?? 'No se ha proporcionado'); ?></p>
                    <p><strong>Género:</strong> <?php echo htmlspecialchars($alumno['genero'] ?? 'No se ha proporcionado'); ?></p>
                    <p><strong>CURP:</strong> <?php echo htmlspecialchars($alumno['curp'] ?? 'No se ha proporcionado'); ?></p>
                    <p><strong>Fecha de Nacimiento:</strong> <?php echo htmlspecialchars($alumno['fecha_nacimiento'] ?? 'No se ha proporcionado'); ?></p>
                    <p><strong>Lugar de Nacimiento:</strong> <?php echo htmlspecialchars($alumno['lugar_nacimiento'] ?? 'No se ha proporcionado'); ?></p>
                </div>

                <div class="info-column">
                    <h3>Información Académica</h3>
                    <p><strong>Grado:</strong> <?php echo htmlspecialchars($alumno['grado'] ?? 'No se ha proporcionado'); ?></p>
                    <p><strong>Grupo:</strong> <?php echo htmlspecialchars($alumno['grupo'] ?? 'No se ha proporcionado'); ?></p>
                    <p><strong>Profesor Asignado:</strong> <?php echo htmlspecialchars($profesor_nombre ?? 'No asignado'); ?></p>
                </div>

                <div class="info-column">
                    <h3>Información de Contacto</h3>
                    <p><strong>Nombre del Padre/Madre/Tutor:</strong> <?php echo htmlspecialchars($alumno['nombre_padre'] ?? 'No se ha proporcionado'); ?></p>
                    <p><strong>Correo Electrónico:</strong> <?php echo htmlspecialchars($alumno['correo'] ?? 'No se ha proporcionado'); ?></p>
                    <p><strong>Teléfono:</strong> <?php echo htmlspecialchars($alumno['telefono'] ?? 'No se ha proporcionado'); ?></p>
                    <p><strong>Dirección:</strong> <?php echo htmlspecialchars($alumno['direccion'] ?? 'No se ha proporcionado'); ?></p>
                </div>

                <div class="info-column">
                    <h3>Información Médica</h3>
                    <p><strong>Tipo de Sangre:</strong> <?php echo htmlspecialchars($alumno['tipo_sangre'] ?? 'No se ha proporcionado'); ?></p>
                    <p><strong>Alergias:</strong> <?php echo htmlspecialchars($alumno['alergias'] ?? 'No se ha proporcionado'); ?></p>
                    <p><strong>Condiciones Médicas:</strong> <?php echo htmlspecialchars($alumno['condiciones_medicas'] ?? 'No se ha proporcionado'); ?></p>
                </div>

                <div class="info-column">
                    <h3>Información Adicional</h3>
                    <p><strong>Fecha de Registro:</strong> <?php echo htmlspecialchars($alumno['fecha_registro'] ?? 'No se ha proporcionado'); ?></p>
                    <p><strong>Observaciones:</strong> <?php echo htmlspecialchars($alumno['observaciones'] ?? 'No se ha proporcionado'); ?></p>
                </div>
            </div>
        </div>
    </main>
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
