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


// Obtener parámetros de la solicitud POST
$alumno_id = isset($_POST['alumno_id']) ? intval($_POST['alumno_id']) : null;
$concepto_id = isset($_POST['concepto_id']) ? intval($_POST['concepto_id']) : null;
$monto = isset($_POST['monto']) ? floatval($_POST['monto']) : null;
$pagar_todo = isset($_POST['pagar_todo']) && $_POST['pagar_todo'] === 'true';

if (!$alumno_id || (!$pagar_todo && !$concepto_id)) {
    die("Datos de pago incompletos.");
}


// Función para actualizar el estado del pago
function actualizarEstadoPago($alumno_id, $concepto_id, $conexion) {
    $sql = "UPDATE conceptos_pago_alumnos 
            SET estado_pago = 'Pagado', fecha_pago = NOW() 
            WHERE alumno_id = ? AND concepto_id = ?";
    
    $stmt = $conexion->prepare($sql);
    $stmt->bind_param("ii", $alumno_id, $concepto_id);
    $stmt->execute();
    return $stmt->affected_rows > 0;
}

// Función para actualizar el estado de todos los pagos pendientes
function pagarTodosLosConceptos($alumno_id, $conexion) {
    $sql = "UPDATE conceptos_pago_alumnos 
            SET estado_pago = 'Pagado', fecha_pago = NOW() 
            WHERE alumno_id = ? AND estado_pago = 'Pendiente'";
    
    $stmt = $conexion->prepare($sql);
    $stmt->bind_param("i", $alumno_id);
    $stmt->execute();
    return $stmt->affected_rows > 0;
}

// Procesar el pago según la opción seleccionada
if (isset($_POST['opcion_pago'])) {
    $opcion_pago = $_POST['opcion_pago'];
    $exito = false;

    switch ($opcion_pago) {
        case 'SPEI':
            // Lógica para SPEI (simulación)
            $exito = true;
            break;
        case 'OXXO':
            // Lógica para OXXO (simulación)
            $exito = true;
            break;
        case 'MercadoPago':
            // Lógica para Mercado Pago (simulación)
            $exito = true;
            break;
        case 'PayPal':
            // Lógica para PayPal (simulación)
            $exito = true;
            break;
        case 'PagoEnLinea':
            // Lógica para Pago en Línea (simulación)
            $exito = true;
            break;
        default:
            die("Opción de pago no válida.");
    }

    if ($exito) {
        if ($pagar_todo) {
            // Si se seleccionó "Pagar todo", se actualizan todos los conceptos pendientes
            if (pagarTodosLosConceptos($alumno_id, $conexion)) {
                header('Location: pagos_padre.php?alumno=' . $alumno_id);
                exit();
            } else {
                die("Error al procesar el pago de todos los conceptos.");
            }
        } else {
            // Si se seleccionó pagar un solo concepto
            if (actualizarEstadoPago($alumno_id, $concepto_id, $conexion)) {
                header('Location: pagos_padre.php?alumno=' . $alumno_id);
                exit();
            } else {
                die("Error al actualizar el estado del pago.");
            }
        }
    } else {
        die("Error en el proceso de pago.");
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Procesar Pago</title>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/styles.css">
    <link href="img/logo.png" rel="icon">
    <style>
        #paypal-button-container {
            display: none; /* Ocultar el contenedor de PayPal por defecto */
            margin-top: 30px;
        }
        .paypal-button-container {
            display: flex;
            flex-direction: column;
            align-items: center;
        }
        .payment-option {
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 20px;
            text-align: center;
            cursor: pointer;
            margin: 10px 0;
            transition: background-color 0.3s;
        }
        .payment-option:hover {
            background-color: #f8f9fa;
        }
        .selected {
            border-color: #007bff;
            background-color: #e9ecef;
        }
        .payment-option i {
            font-size: 3rem;
        }
        
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
<div class="container mt-5">
    <h1>Seleccionar Método de Pago</h1>

    <form id="payment-form" action="procesar_pago.php" method="POST">
        <input type="hidden" name="alumno_id" value="<?php echo htmlspecialchars($alumno_id); ?>">
        <input type="hidden" name="concepto_id" value="<?php echo htmlspecialchars($concepto_id); ?>">
        <input type="hidden" name="monto" value="<?php echo htmlspecialchars($monto); ?>">
        
        <input type="hidden" name="opcion_pago" value="">
        <div class="form-group">
            <div class="row">
                <div class="col-md-4">
                    <div class="payment-option" data-method="SPEI">
                        <i class="fas fa-university"></i>
                        <h5>SPEI</h5>
                        <p>Pago electrónico interbancario.</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="payment-option" data-method="OXXO">
                        <i class="fas fa-store"></i>
                        <h5>OXXO</h5>
                        <p>Pago en efectivo en tiendas OXXO.</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="payment-option" data-method="MercadoPago">
                        <i class="fas fa-credit-card"></i>
                        <h5>Mercado Pago</h5>
                        <p>Pago con tarjeta o efectivo.</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="payment-option" data-method="PayPal">
                        <i class="fab fa-paypal"></i>
                        <h5>PayPal</h5>
                        <p>Pago con PayPal o tarjeta.</p>
                    </div>
                </div>
            </div>
        </div>
        <button type="submit" class="btn btn-primary" id="submit-button" disabled style="display:none">Procesar Pago</button>
    </form>

    <div id="paypal-button-container"></div>
    <div id="result-message"></div>

</div>

<script src="https://code.jquery.com/jquery-3.2.1.slim.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.11.0/umd/popper.min.js"></script>
<script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0/js/bootstrap.min.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        var alumnoId = document.querySelector('input[name="alumno_id"]').value;
        var pagarTodo = <?php echo $pagar_todo ? 'true' : 'false'; ?>;
        var conceptoId = document.querySelector('input[name="concepto_id"]').value;
        var monto = document.querySelector('input[name="monto"]').value;
        var paymentOptions = document.querySelectorAll('.payment-option');
        var submitButton = document.getElementById('submit-button');
        var paypalButtonContainer = document.getElementById('paypal-button-container');
        var resultMessage = document.getElementById('result-message');
        var optionInput = document.querySelector('input[name="opcion_pago"]');

        paymentOptions.forEach(function(option) {
            option.addEventListener('click', function() {
                paymentOptions.forEach(function(opt) {
                    opt.classList.remove('selected');
                });
                this.classList.add('selected');
                optionInput.value = this.getAttribute('data-method');
                submitButton.disabled = false; // Habilitar el botón de envío
                if (this.getAttribute('data-method') === 'PayPal') {
                    paypalButtonContainer.style.display = 'block';
                    if (!document.querySelector('script[src*="paypal.com/sdk/js"]')) {
                        var script = document.createElement('script');
                        script.src = "https://www.paypal.com/sdk/js?client-id=Aa9DiDk3VceqUJt7euSw-0cA5u-Cv_owcgKztrQZbLyLICTGEl1uhv_-P9kk19MYk1PYN3CaM_V8WhGS&buyer-country=MX&currency=MXN&components=buttons&enable-funding=venmo";
                        script.dataset.sdkIntegrationSource = "developer-studio";
                        document.body.appendChild(script);

                        script.onload = function() {
                            paypal.Buttons({
                                createOrder: function(data, actions) {
                                    return actions.order.create({
                                        purchase_units: [{
                                            amount: {
                                                value: monto
                                            }
                                        }]
                                    });
                                },
                                onApprove: function(data, actions) {
                                    return actions.order.capture().then(function(details) {
                                        // Mostrar mensaje de agradecimiento
                                        resultMessage.style.padding = '15px';
                                        resultMessage.style.borderRadius = '5px';
                                        resultMessage.style.color = '#fff';
                                        resultMessage.style.backgroundColor = '#28a745'; // Verde para éxito
                                        resultMessage.style.textAlign = 'center';
                                        resultMessage.style.fontSize = '16px';
                                        resultMessage.style.fontWeight = 'bold';
                                        resultMessage.style.marginTop = '20px';
                                        resultMessage.style.display = 'block';
                                        resultMessage.innerText = 'Pago realizado con éxito. Gracias, ' + details.payer.name.given_name + '!';
                                        
                                        // Esperar 3 segundos antes de redirigir
                                        setTimeout(function() {
                                            if (pagarTodo) {
                                                // Obtener todos los conceptos pendientes para este alumno
                                                fetch('confirmar_pago.php', {
                                                    method: 'POST',
                                                    headers: {
                                                        'Content-Type': 'application/json'
                                                    },
                                                    body: JSON.stringify({
                                                    alumno_id: alumnoId,
                                                    concepto_id: 'ALL',
                                                    monto: monto,
                                                    opcion_pago: 'PayPal'
                                                    })
                                                })
                                                .then(response => response.json())
                                                .then(data => {
                                                    if (data.success) {
                                                        window.location.href = '../pagos_padre.php?alumno=' + alumnoId;
                                                    } else {
                                                        window.location.href = '../pagos_padre.php?alumno=' + alumnoId;
                                                        resultMessage.style.backgroundColor = '#dc3545'; // Rojo para errores
                                                        resultMessage.innerText = 'Error al actualizar el estado del pago.';
                                                    }
                                                })
                                                .catch(error => {
                                                    console.error('Error al confirmar el pago:', error);
                                                    resultMessage.style.backgroundColor = '#dc3545'; // Rojo para errores
                                                    resultMessage.innerText = 'Error al confirmar el pago.';
                                                });
                                        } else {
                                            // Si no es "pagar_todo", sigue con el flujo normal de un solo concepto
                                            fetch('confirmar_pago.php', {
                                                method: 'POST',
                                                headers: {
                                                    'Content-Type': 'application/json'
                                                },
                                                body: JSON.stringify({
                                                    alumno_id: alumnoId,
                                                    concepto_id: conceptoId,
                                                    monto: monto,
                                                    opcion_pago: 'PayPal'
                                                })
                                            })
                                            .then(response => response.json())
                                            .then(data => {
                                                if (data.success) {
                                                    window.location.href = '../pagos_padre.php?alumno=' + alumnoId;
                                                } else {
                                                    window.location.href = '../pagos_padre.php?alumno=' + alumnoId;
                                                    resultMessage.style.backgroundColor = '#dc3545'; // Rojo para errores
                                                    resultMessage.innerText = 'Error al actualizar el estado del pago.';
                                                }
                                            })
                                            .catch(error => {
                                                console.error('Error al confirmar el pago:', error);
                                                resultMessage.style.backgroundColor = '#dc3545'; // Rojo para errores
                                                resultMessage.innerText = 'Error al confirmar el pago.';
                                            });
                                        }
                                    }, 3000); // Esperar 3 segundos (3000 milisegundos)
                                });
                                },
                                onError: function(err) {
                                    resultMessage.style.padding = '15px';
                                    resultMessage.style.borderRadius = '5px';
                                    resultMessage.style.color = '#fff';
                                    resultMessage.style.backgroundColor = '#dc3545'; // Rojo para errores
                                    resultMessage.style.textAlign = 'center';
                                    resultMessage.style.fontSize = '16px';
                                    resultMessage.style.fontWeight = 'bold';
                                    resultMessage.style.marginTop = '20px';
                                    resultMessage.style.display = 'block';
                                    resultMessage.innerText = 'Error en el proceso de pago: ' + err.message;
                                }
                            }).render('#paypal-button-container');
                        };
                    }
                } else {
                    paypalButtonContainer.style.display = 'none';
                }
            });
        });

        document.getElementById('payment-form').addEventListener('submit', function(e) {
            var selectedMethod = optionInput.value;
            if (selectedMethod === 'PayPal') {
                e.preventDefault(); // Evitar el envío del formulario si es PayPal
                document.querySelector('#paypal-button-container').style.display = 'block';
            }
        });
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
