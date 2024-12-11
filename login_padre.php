<?php
session_start();

// Cerrar sesión si se ha solicitado
if (isset($_GET['cerrar_sesion'])) {
    session_unset();
    session_destroy();
    header('Location: login_padre.php');
    exit();
}

// Verificar si el padre ya está autenticado
if (isset($_SESSION['padre_autenticado']) && $_SESSION['padre_autenticado']) {
    header('Location: padre.php');
    exit();
}

// Verificar si se está intentando iniciar sesión
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['usuario']) && isset($_POST['contrasena'])) {
    // Conexión a la base de datos
    $servername = "localhost";
    $username = "root";
    $password = "";
    $dbname = "appdatabase";

    $conexion = new mysqli($servername, $username, $password, $dbname);

    if ($conexion->connect_error) {
        die("Conexión fallida: " . $conexion->connect_error);
    }

    // Obtener el nombre del alumno proporcionado
    $usuario = $conexion->real_escape_string($_POST['usuario']);
    $contrasena = $conexion->real_escape_string($_POST['contrasena']);

    // Contraseña por defecto
    $contrasena_default = 'padre2024';

    // Buscar al alumno en la base de datos
    $sql = "SELECT id, nombre FROM alumnos WHERE nombre = '$usuario'";
    $resultado = $conexion->query($sql);

    if ($resultado->num_rows > 0) {
        $alumno = $resultado->fetch_assoc();
        $id_padre = $alumno['id'];

        if ($contrasena === $contrasena_default) {
            $_SESSION['padre_autenticado'] = true;
            $_SESSION['id_padre'] = $id_padre; // Guardar el ID del padre en la sesión
            header('Location: padre.php'); // Redireccionar para evitar reenvío de formulario
            exit();
        } else {
            $error_login = true;
        }
    } else {
        $error_login = true;
    }

    $conexion->close();
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Iniciar Sesión del Padre</title>
    <link href="img/logo.png" rel="icon">
    <link rel="stylesheet" href="https://necolas.github.io/normalize.css/8.0.1/normalize.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@200..1000&family=Poppins:wght@100..900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/login_styles.css">
</head>
<body>
    <main>
        <h1>Iniciar Sesión</h1>

        <?php
        // Mostrar mensaje de error si el inicio de sesión falló
        if (isset($error_login) && $error_login) {
            echo '<p class="error">Nombre del alumno o contraseña incorrectos</p>';
        }
        ?>

        <form action="login_padre.php" method="POST">
            <label for="usuario">Nombre del Alumno:</label>
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
                eyeIcon.src = "img/ojo-cerrado.png"; // Cambia la imagen a ojo cerrado
            } else {
                passwordInput.type = "password";
                eyeIcon.src = "img/ojo-abierto.png"; // Cambia la imagen a ojo abierto
            }
        }
    </script>
</body>
</html>
