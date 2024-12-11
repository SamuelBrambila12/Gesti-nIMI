<?php
// Incluir la librería TCPDF
require_once('tcpdf/tcpdf.php');

// Conexión a la base de datos (reemplaza con tus datos)
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "appdatabase";

$conn = new mysqli($servername, $username, $password, $dbname);

// Verificar la conexión
if ($conn->connect_error) {
    die("Conexión fallida: " . $conn->connect_error);
}

// Obtener datos del registro específico (reemplaza con la lógica para obtener el registro deseado)
$id_registro = $_GET['id']; // Recupera el ID del registro desde la URL o de otra fuente
$sql = "SELECT nombre, edad, genero, grado, nombre_padre, correo, telefono FROM alumnos WHERE id = $id_registro";
$resultado = $conn->query($sql);

if ($resultado->num_rows > 0) {
    $fila = $resultado->fetch_assoc();
    $nombre = $fila['nombre'];
    $edad = $fila['edad'];
    $genero = $fila['genero'];
    $grado = $fila['grado'];
    $nombre_padre = $fila['nombre_padre'];
    $correo = $fila['correo'];
    $telefono = $fila['telefono'];
} else {
    die("Registro no encontrado");
}

// Crear nueva instancia de TCPDF
$pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

// Establecer información del documento
$pdf->SetCreator(PDF_CREATOR);
$pdf->SetAuthor('Tu Nombre');
$pdf->SetTitle('Registro de Alumno');
$pdf->SetSubject('Registro de Alumno');
$pdf->SetKeywords('Registro, Alumno, PDF');

// Establecer márgenes
$pdf->SetMargins(15, PDF_MARGIN_TOP, PDF_MARGIN_RIGHT); // Ajustar margen izquierdo a 15 unidades
$pdf->SetHeaderMargin(PDF_MARGIN_HEADER);
$pdf->SetFooterMargin(PDF_MARGIN_FOOTER);

// Establecer modo de subida automática de página (márgenes)
$pdf->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);

// Establecer fuente estilizada
$pdf->SetFont('dejavuserif', 12);

// Agregar página
$pdf->AddPage();

// Agregar imagen de encabezado más grande
$image_file = 'img/colegio-imi.jpg'; // Ruta de la imagen de encabezado
$pdf->Image($image_file, 50, 30, 120, '', 'JPEG', '', 'T', false, 300, '', false, false, 0, false, false, false);

// Agregar título
$pdf->Ln(40); // Saltar 40 unidades hacia abajo
$pdf->SetFont('dejavuserif', 'B', 24);
$pdf->Cell(0, 20, 'Registro de Aspirante', 0, 1, 'C'); // Ajustar tamaño de celda y centrado ('C')


// Agregar datos del alumno en formato de tabla con margen a la izquierda y centrado
$pdf->Ln(10); // Saltar 10 unidades hacia abajo
$html = '<style>
            table {
                border-collapse: collapse;
                width: 100%;
                margin: 10px 0;
            }
            table td {
                border: 1px solid #ddd;
                padding: 12px;
                text-align: left;
                font-size: 12px;
            }
            table th {
                background-color: #4CAF50;
                color: white;
                border-bottom: 3px solid #ddd;
            }
            table tr:nth-child(even) {
                background-color: #f2f2f2;
            }
            table tr:hover {
                background-color: #ddd;
            }
            table tr td {
                font-size: 14px;
                border-bottom: 1px solid #ddd;
                font-weight: normal;
            }
            table tr th {
                font-size: 14px;
                font-weight: normal;
            }
            .signature {
                margin-top: 50px;
                text-align: center;
                font-size: 14px;
            }
        </style>
        <table>
            <tr>
                <th width="50%"><b>Nombre del Alumno</b></th>
                <td>' . $nombre . '</td>
            </tr>
            <tr>
                <th><b>Edad</b></th>
                <td>' . $edad . '</td>
            </tr>
            <tr>
                <th><b>Género</b></th>
                <td>' . $genero . '</td>
            </tr>
            <tr>
                <th><b>Grado</b></th>
                <td>' . $grado . '</td>
            </tr>
            <tr>
                <th><b>Nombre del Padre/Madre</b></th>
                <td>' . $nombre_padre . '</td>
            </tr>
            <tr>
                <th><b>Correo Electrónico</b></th>
                <td>' . $correo . '</td>
            </tr>
            <tr>
                <th><b>Teléfono</b></th>
                <td>' . $telefono . '</td>
            </tr>
        </table>';

$pdf->writeHTML($html, true, false, true, false, '');

// Agregar espacio para firma y texto
$pdf->Ln(50); // Saltar 10 unidades hacia abajo
$pdf->SetFont('dejavuserif', '', 12); // Establecer fuente normal ('') y tamaño 12
$pdf->Cell(0, 10, '__________________________________', 0, 1, 'C'); // Línea para firma
$pdf->Cell(0, 10, 'Firma de padre, madre, tutor', 0, 1, 'C'); // Texto de firma

// Nombre del archivo PDF
$nombre_pdf = 'registro_alumno_' . $id_registro . '.pdf';

// Salida del documento
$pdf->Output($nombre_pdf, 'D');

$conn->close();
?>
