<?php
session_start();

// Verificar si se está intentando iniciar sesión
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['usuario']) && isset($_POST['contrasena'])) {
    $usuario_valido = ($_POST['usuario'] == 'admin' && $_POST['contrasena'] == 'imi2024');

    if ($usuario_valido) {
        $_SESSION['usuario_autenticado'] = true;
        header('Location: base.php');
        exit();
    } else {
        $error_login = true;
    }
}

// Cerrar sesión si se ha solicitado
if (isset($_GET['cerrar_sesion'])) {
    session_unset();
    session_destroy();
    header('Location: base.php');
    exit();
}

// Verificar si el usuario está autenticado
$usuario_autenticado = isset($_SESSION['usuario_autenticado']) && $_SESSION['usuario_autenticado'];

// Si no está autenticado, mostrar formulario de inicio de sesión
if (!$usuario_autenticado) {
    echo '<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Iniciar Sesión</title>
    <link href="img/logo.png" rel="icon">
    <link rel="stylesheet" href="https://necolas.github.io/normalize.css/8.0.1/normalize.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@200..1000&family=Poppins:wght@100..900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/login_styles.css">
</head>
<body>
    <main>
        <h1>Inicio de sesión</h1>';

    if (isset($error_login) && $error_login) {
        echo '<p class="error">Usuario o contraseña incorrectos</p>';
    }

    echo '<form action="base.php" method="POST">
            <label for="usuario">Usuario:</label>
            <input type="text" id="usuario" name="usuario" required><br><br>
            <label for="contrasena">Contraseña:</label>
            <div style="position: relative;">
                <input type="password" id="contrasena" name="contrasena" required>
                <img id="eye-icon" src="img/ojo-abierto.png" alt="Mostrar Contraseña" onclick="togglePassword()" style="position: absolute; right: 30px; top: 50%; transform: translateY(-70%); cursor: pointer; width: 30px; height: 30px;">
            </div><br><br>
            <button class="boton-inicio-sesion" type="submit">Iniciar Sesión</button>
        </form>
    </main>
    <script>
        function togglePassword() {
            var passwordInput = document.getElementById("contrasena");
            var eyeIcon = document.getElementById("eye-icon");

            if (passwordInput.type === "password") {
                passwordInput.type = "text";
                eyeIcon.src = "img/ojo-cerrado.png";
            } else {
                passwordInput.type = "password";
                eyeIcon.src = "img/ojo-abierto.png";
            }
        }
    </script>
</body>
</html>';

    exit();
}

?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registros Existentes</title>
    <link href="img/logo.png" rel="icon">
    <link rel="stylesheet" href="https://necolas.github.io/normalize.css/8.0.1/normalize.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@200..1000&family=Poppins:wght@100..900&display=swap" rel="stylesheet">
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
    <main>
        <div class="header-options">
            <h1>Registros Existentes</h1>
            <div class="options">
                <form action="base.php" method="GET">
                    <label for="filtro_nombre">Filtrar por Nombre:</label>
                    <input type="text" id="filtro_nombre" name="filtro_nombre" value="<?php echo isset($_GET['filtro_nombre']) ? htmlspecialchars($_GET['filtro_nombre']) : ''; ?>">

                    <label for="filtro_grado">Filtrar por Grado:</label>
                    <select id="filtro_grado" name="filtro_grado">
                        <option value="">Todos</option>
                        <?php
                        // Conexión a la base de datos (cambia los valores según tu configuración)
                        $servername = "localhost";
                        $username = "root";
                        $password = "";
                        $dbname = "appdatabase";

                        $conexion = new mysqli($servername, $username, $password, $dbname);

                        // Verificar la conexión
                        if ($conexion->connect_error) {
                            die("Conexión fallida: " . $conexion->connect_error);
                        }

                        // Configuración de la paginación
                        $registros_por_pagina = 20;
                        $pagina_actual = isset($_GET['pagina']) ? intval($_GET['pagina']) : 1;
                        $offset = ($pagina_actual - 1) * $registros_por_pagina;

                        // Obtener los grados únicos de la tabla
                        $sql_grados = "SELECT DISTINCT grado FROM alumnos";
                        $resultado_grados = $conexion->query($sql_grados);

                        if ($resultado_grados->num_rows > 0) {
                            while ($fila_grado = $resultado_grados->fetch_assoc()) {
                                $selected = isset($_GET['filtro_grado']) && $_GET['filtro_grado'] == $fila_grado["grado"] ? 'selected' : '';
                                echo '<option value="' . htmlspecialchars($fila_grado["grado"]) . '" ' . $selected . '>' . htmlspecialchars($fila_grado["grado"]) . '</option>';
                            }
                        }
                        ?>
                    </select>

                    <label for="filtro_grupo">Filtrar por Grupo:</label>
                    <select id="filtro_grupo" name="filtro_grupo">
                        <option value="">Todos</option>
                        <?php
                        // Obtener los grupos únicos de la tabla en función del grado seleccionado
                        $filtro_grado = isset($_GET['filtro_grado']) ? $_GET['filtro_grado'] : '';
                        if (!empty($filtro_grado)) {
                            $sql_grupos = "SELECT DISTINCT grupo FROM alumnos WHERE grado = '" . $conexion->real_escape_string($filtro_grado) . "'";
                            $resultado_grupos = $conexion->query($sql_grupos);

                            if ($resultado_grupos->num_rows > 0) {
                                while ($fila_grupo = $resultado_grupos->fetch_assoc()) {
                                    $selected = isset($_GET['filtro_grupo']) && $_GET['filtro_grupo'] == $fila_grupo["grupo"] ? 'selected' : '';
                                    echo '<option value="' . htmlspecialchars($fila_grupo["grupo"]) . '" ' . $selected . '>' . htmlspecialchars($fila_grupo["grupo"]) . '</option>';
                                }
                            }
                        }
                        ?>
                    </select>

                    <label for="filtro_fecha_inicio">Fecha de Registro (Desde):</label>
                    <input type="date" id="filtro_fecha_inicio" name="filtro_fecha_inicio" value="<?php echo isset($_GET['filtro_fecha_inicio']) ? htmlspecialchars($_GET['filtro_fecha_inicio']) : ''; ?>">

                    <label for="filtro_fecha_fin">Fecha de Registro (Hasta):</label>
                    <input type="date" id="filtro_fecha_fin" name="filtro_fecha_fin" value="<?php echo isset($_GET['filtro_fecha_fin']) ? htmlspecialchars($_GET['filtro_fecha_fin']) : ''; ?>">

                    <button class="boton-filtrar" type="submit">Filtrar</button>
                    <button class="boton-limpiar" type="button" onclick="limpiarFiltros()">Limpiar filtros</button>
                </form>
                <div class="acciones-masivas">
                    <button id="eliminarSeleccionados" class="boton-eliminar-seleccionados" style="display: none;">Eliminar seleccionados</button>
                    <button id="seleccionarTodos" class="boton-seleccionar-todos">Seleccionar Todos</button>
                    <a href="exportar_excel.php" class="boton-descargar-excel">
                        <img class="logo-excel" src="img/logo-excel.webp" alt="logo-excel">
                        Descargar Excel
                    </a>
                </div>
            </div>
        </div>
        
        <?php
        // Construir la consulta SQL en función de los filtros seleccionados
        $filtro_nombre = isset($_GET['filtro_nombre']) ? $_GET['filtro_nombre'] : '';
        $filtro_grado = isset($_GET['filtro_grado']) ? $_GET['filtro_grado'] : '';
        $filtro_grupo = isset($_GET['filtro_grupo']) ? $_GET['filtro_grupo'] : '';
        $filtro_fecha_inicio = isset($_GET['filtro_fecha_inicio']) ? $_GET['filtro_fecha_inicio'] : '';
        $filtro_fecha_fin = isset($_GET['filtro_fecha_fin']) ? $_GET['filtro_fecha_fin'] : '';

        $sql = "SELECT id, nombre, edad, genero, grado, grupo, nombre_padre, correo, telefono, id_padre, DATE_FORMAT(fecha_registro, '%Y-%m-%d') AS fecha_registro FROM alumnos WHERE 1";

        if (!empty($filtro_nombre)) {
            $sql .= " AND nombre LIKE '%" . $conexion->real_escape_string($filtro_nombre) . "%'";
        }

        if (!empty($filtro_grado)) {
            $sql .= " AND grado = '" . $conexion->real_escape_string($filtro_grado) . "'";
        }

        if (!empty($filtro_grupo)) {
            $sql .= " AND grupo = '" . $conexion->real_escape_string($filtro_grupo) . "'";
        }

        if (!empty($filtro_fecha_inicio) && !empty($filtro_fecha_fin)) {
            $fecha_inicio = date('Y-m-d', strtotime($filtro_fecha_inicio));
            $fecha_fin = date('Y-m-d', strtotime($filtro_fecha_fin));
            $sql .= " AND fecha_registro BETWEEN '" . $conexion->real_escape_string($fecha_inicio) . "' AND '" . $conexion->real_escape_string($fecha_fin) . "'";
        }

        // Obtener el número total de registros
        $sql_count = "SELECT COUNT(*) as total FROM alumnos WHERE 1";

        if (!empty($filtro_nombre)) {
            $sql_count .= " AND nombre LIKE '%" . $conexion->real_escape_string($filtro_nombre) . "%'";
        }

        if (!empty($filtro_grado)) {
            $sql_count .= " AND grado = '" . $conexion->real_escape_string($filtro_grado) . "'";
        }

        if (!empty($filtro_grupo)) {
            $sql_count .= " AND grupo = '" . $conexion->real_escape_string($filtro_grupo) . "'";
        }

        if (!empty($filtro_fecha_inicio) && !empty($filtro_fecha_fin)) {
            $fecha_inicio = date('Y-m-d', strtotime($filtro_fecha_inicio));
            $fecha_fin = date('Y-m-d', strtotime($filtro_fecha_fin));
            $sql_count .= " AND fecha_registro BETWEEN '" . $conexion->real_escape_string($fecha_inicio) . "' AND '" . $conexion->real_escape_string($fecha_fin) . "'";
        }
        
        $result_count = $conexion->query($sql_count);
        $row_count = $result_count->fetch_assoc();
        $total_registros = $row_count['total'];

        // Calcular el número total de páginas
        $total_paginas = ceil($total_registros / $registros_por_pagina);

        // Añadir LIMIT y OFFSET a la consulta principal
        $sql .= " LIMIT $registros_por_pagina OFFSET $offset";

        $resultado = $conexion->query($sql);

        if ($resultado->num_rows > 0) {
            echo '<table >';
            echo '<thead><tr><th class="columna-seleccion">Sel.</th><th>Nombre</th><th>Edad</th><th>Género</th><th>Grado</th><th>Grupo</th><th>Nombre del Padre/Madre</th><th>Correo</th><th>Teléfono</th><th>Fecha de Registro</th><th class="acciones">Acciones</th></tr></thead>';
            echo '<tbody>';
            // Mostrar datos de cada fila
            while ($fila = $resultado->fetch_assoc()) {
                echo '<tr>';
                echo '<td class="columna-seleccion"><input type="checkbox" class="seleccionar-fila" data-id="' . $fila["id"] . '"></td>';
                echo '<td>' . htmlspecialchars($fila["nombre"]) . '</td>';
                echo '<td>' . htmlspecialchars($fila["edad"]) . '</td>';
                echo '<td>' . htmlspecialchars($fila["genero"]) . '</td>';
                echo '<td>' . htmlspecialchars($fila["grado"]) . '</td>';
                echo '<td>' . (empty($fila["grupo"]) ? 'No asignado' : htmlspecialchars($fila["grupo"])) . '</td>';
                echo '<td>' . htmlspecialchars($fila["nombre_padre"]) . '</td>';
                echo '<td>' . htmlspecialchars($fila["correo"]) . '</td>';
                echo '<td>' . htmlspecialchars($fila["telefono"]) . '</td>';
                echo '<td>' . htmlspecialchars($fila["fecha_registro"]) . '</td>';
                echo '<td class="acciones">';
                echo '<div class="acciones-fila">';
                echo '<form action="ver_ficha.php" method="GET" style="display:inline;">';
                echo '<input type="hidden" name="id" value="' . $fila["id"] . '">';
                echo '<button class="boton-ver-ficha" type="submit">Ver ficha</button>';
                echo '</form>';
                echo '<form action="generar_pdf.php" method="GET" style="display:inline;">';
                echo '<input type="hidden" name="id" value="' . $fila["id"] . '">';
                echo '<button class="boton-generar-pdf" type="submit">Generar PDF</button>';
                echo '</form>';
                echo '</div>';
                echo '<div class="acciones-fila">';
                echo '<form action="base.php" method="POST" style="display:inline;">';
                echo '<input type="hidden" name="id_eliminar" value="' . $fila["id"] . '">';
                echo '<button class="boton-eliminar" type="submit" name="eliminar_registro" onclick="return confirm(\'¿Estás seguro de que deseas eliminar este registro?\')">Eliminar</button>';
                echo '</form>';
                echo '<form action="asignar_grupo.php" method="GET" style="display:inline;">';
                echo '<input type="hidden" name="id" value="' . $fila["id"] . '">';
                echo '<input type="hidden" name="grado" value="' . $fila["grado"] . '">';
                echo '<button class="boton-asignar-grupo" type="submit">Asignar Grupo</button>';
                echo '</form>';
                echo '</div>';
                echo '</td>';
                echo '</tr>';
            }
            echo '</tbody>';
            echo '</table>';
        } else {
            echo '<p>No se encontraron registros.</p>';
        }

        // Procesar la eliminación si se envió el formulario
        if (isset($_POST['eliminar_registro'])) {
            $id_eliminar = $_POST['id_eliminar'];
            $sql_delete = "DELETE FROM alumnos WHERE id = $id_eliminar";

            if ($conexion->query($sql_delete) === TRUE) {
                echo '<p class="success">Registro eliminado correctamente</p>';
                echo '<script>window.location.href = "base.php";</script>';
            } else {
                echo '<p class="error">Error al eliminar el registro: ' . $conexion->error . '</p>';
            }
        }


        // Mostrar la paginación
        if ($total_paginas > 1) {
            echo '<div class="paginacion">';
            if ($pagina_actual > 1) {
                echo '<a href="?pagina=1' . 
                    (isset($_GET['filtro_nombre']) ? '&filtro_nombre=' . urlencode($_GET['filtro_nombre']) : '') . 
                    (isset($_GET['filtro_grado']) ? '&filtro_grado=' . urlencode($_GET['filtro_grado']) : '') . 
                    (isset($_GET['filtro_grupo']) ? '&filtro_grupo=' . urlencode($_GET['filtro_grupo']) : '') . 
                    (isset($_GET['filtro_fecha_inicio']) ? '&filtro_fecha_inicio=' . urlencode($_GET['filtro_fecha_inicio']) : '') . 
                    (isset($_GET['filtro_fecha_fin']) ? '&filtro_fecha_fin=' . urlencode($_GET['filtro_fecha_fin']) : '') . 
                    '">&laquo; Primera</a>';
                echo '<a href="?pagina=' . ($pagina_actual - 1) . 
                    (isset($_GET['filtro_nombre']) ? '&filtro_nombre=' . urlencode($_GET['filtro_nombre']) : '') . 
                    (isset($_GET['filtro_grado']) ? '&filtro_grado=' . urlencode($_GET['filtro_grado']) : '') . 
                    (isset($_GET['filtro_grupo']) ? '&filtro_grupo=' . urlencode($_GET['filtro_grupo']) : '') . 
                    (isset($_GET['filtro_fecha_inicio']) ? '&filtro_fecha_inicio=' . urlencode($_GET['filtro_fecha_inicio']) : '') . 
                    (isset($_GET['filtro_fecha_fin']) ? '&filtro_fecha_fin=' . urlencode($_GET['filtro_fecha_fin']) : '') . 
                    '">&lsaquo; Anterior</a>';
            }

            $rango = 2;
            for ($i = max(1, $pagina_actual - $rango); $i <= min($total_paginas, $pagina_actual + $rango); $i++) {
                if ($i == $pagina_actual) {
                    echo '<span class="pagina-actual">' . $i . '</span>';
                } else {
                    echo '<a href="?pagina=' . $i . 
                        (isset($_GET['filtro_nombre']) ? '&filtro_nombre=' . urlencode($_GET['filtro_nombre']) : '') . 
                        (isset($_GET['filtro_grado']) ? '&filtro_grado=' . urlencode($_GET['filtro_grado']) : '') . 
                        (isset($_GET['filtro_grupo']) ? '&filtro_grupo=' . urlencode($_GET['filtro_grupo']) : '') . 
                        (isset($_GET['filtro_fecha_inicio']) ? '&filtro_fecha_inicio=' . urlencode($_GET['filtro_fecha_inicio']) : '') . 
                        (isset($_GET['filtro_fecha_fin']) ? '&filtro_fecha_fin=' . urlencode($_GET['filtro_fecha_fin']) : '') . 
                        '">' . $i . '</a>';
                }
            }

            if ($pagina_actual < $total_paginas) {
                echo '<a href="?pagina=' . ($pagina_actual + 1) . 
                    (isset($_GET['filtro_nombre']) ? '&filtro_nombre=' . urlencode($_GET['filtro_nombre']) : '') . 
                    (isset($_GET['filtro_grado']) ? '&filtro_grado=' . urlencode($_GET['filtro_grado']) : '') . 
                    (isset($_GET['filtro_grupo']) ? '&filtro_grupo=' . urlencode($_GET['filtro_grupo']) : '') . 
                    (isset($_GET['filtro_fecha_inicio']) ? '&filtro_fecha_inicio=' . urlencode($_GET['filtro_fecha_inicio']) : '') . 
                    (isset($_GET['filtro_fecha_fin']) ? '&filtro_fecha_fin=' . urlencode($_GET['filtro_fecha_fin']) : '') . 
                    '">Siguiente &rsaquo;</a>';
                echo '<a href="?pagina=' . $total_paginas . 
                    (isset($_GET['filtro_nombre']) ? '&filtro_nombre=' . urlencode($_GET['filtro_nombre']) : '') . 
                    (isset($_GET['filtro_grado']) ? '&filtro_grado=' . urlencode($_GET['filtro_grado']) : '') . 
                    (isset($_GET['filtro_grupo']) ? '&filtro_grupo=' . urlencode($_GET['filtro_grupo']) : '') . 
                    (isset($_GET['filtro_fecha_inicio']) ? '&filtro_fecha_inicio=' . urlencode($_GET['filtro_fecha_inicio']) : '') . 
                    (isset($_GET['filtro_fecha_fin']) ? '&filtro_fecha_fin=' . urlencode($_GET['filtro_fecha_fin']) : '') . 
                    '">Última &raquo;</a>';
            }
            echo '</div>';
        }

        $conexion->close();
        ?>

        <script>
        function limpiarFiltros() {
            document.getElementById('filtro_nombre').value = '';
            document.getElementById('filtro_grado').value = '';
            document.getElementById('filtro_grupo').value = '';
            document.getElementById('filtro_fecha_inicio').value = '';
            document.getElementById('filtro_fecha_fin').value = '';
            
            // Enviar el formulario para actualizar la página sin filtros
            document.getElementById('filtroForm').submit();
        }
        
        // Función para activar / desactivar todos los checkboxes
        document.addEventListener('DOMContentLoaded', function() {
            var seleccionarTodosBtn = document.getElementById('seleccionarTodos');
            var checkboxes = document.querySelectorAll('.seleccionar-fila');
            var eliminarSeleccionadosBtn = document.getElementById('eliminarSeleccionados');

            // Seleccionar todos los checkboxes
            seleccionarTodosBtn.addEventListener('click', function() {
                var seleccionTodos = seleccionarTodosBtn.innerText === 'Seleccionar Todos';
                checkboxes.forEach(function(checkbox) {
                    checkbox.checked = seleccionTodos;
                });
                seleccionarTodosBtn.innerText = seleccionTodos ? 'Deseleccionar Todos' : 'Seleccionar Todos';
                eliminarSeleccionadosBtn.style.display = seleccionTodos ? 'block' : 'none';
            });
        });

        // Función para eliminar todas las selecciones
        document.addEventListener('DOMContentLoaded', function() {
            const eliminarSeleccionadosBtn = document.getElementById('eliminarSeleccionados');
            const checkboxes = document.querySelectorAll('.seleccionar-fila');

            // Función para actualizar la visibilidad del botón de eliminar
            function actualizarBotonEliminar() {
                const haySeleccionados = Array.from(checkboxes).some(cb => cb.checked);
                eliminarSeleccionadosBtn.style.display = haySeleccionados ? 'inline-block' : 'none';
            }

            // Actualizar la visibilidad del botón cuando se cambian los checkboxes
            checkboxes.forEach(checkbox => {
                checkbox.addEventListener('change', actualizarBotonEliminar);
            });

            // Actualizar la visibilidad del botón al cargar la página
            actualizarBotonEliminar();

            // Eliminar los seleccionados al hacer clic en el botón
            eliminarSeleccionadosBtn.addEventListener('click', function() {
                if (confirm('¿Estás seguro de que deseas eliminar los registros seleccionados?')) {
                    const idsSeleccionados = Array.from(checkboxes)
                        .filter(cb => cb.checked)
                        .map(cb => cb.dataset.id);

                    // Enviar los IDs al servidor para eliminarlos
                    fetch('eliminar_seleccionados.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify({ ids: idsSeleccionados })
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            // Recargar la página o actualizar la tabla
                            location.reload();
                        } else {
                            alert('Hubo un error al eliminar los registros: ' + data.message);
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        alert('Hubo un error al procesar la solicitud.');
                    });
                }
            });
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
    </main>
</body>
</html>
