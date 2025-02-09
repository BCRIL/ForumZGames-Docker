<?php
// register.php

session_start(); // Inicia la sesión

// Configuración de la base de datos
$host = "db"; // Cambia esto si es necesario
$dbname = "ForumZGames"; // Nombre de tu base de datos
$user = "postgres"; // Tu usuario de PostgreSQL
$password = "root"; // Tu contraseña de PostgreSQL

// Crear conexión
$conn = pg_connect("host=$host dbname=$dbname user=$user password=$password");

// Verificar conexión
if (!$conn) {
    die("Error en la conexión: " . pg_last_error());
}

$error_message = ''; // Variable para almacenar mensajes de error

// Comprobar si el formulario ha sido enviado
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Recuperar y sanitizar la entrada del usuario
    $username = pg_escape_string(trim($_POST['username']));
    $fullname = pg_escape_string(trim($_POST['fullname']));
    $email = pg_escape_string(trim($_POST['email']));
    $password = pg_escape_string(trim($_POST['password']));
    $confirm_password = pg_escape_string(trim($_POST['confirm_password']));

    // Comprobar si las contraseñas coinciden
    if ($password !== $confirm_password) {
        $error_message = "Las contraseñas no coinciden. Por favor, inténtalo de nuevo.";
    } else {
        // Verificar si el nombre de usuario ya existe
        $check_username_query = "SELECT username FROM usuario WHERE username = '$username'";
        $check_username_result = pg_query($conn, $check_username_query);

        if (pg_num_rows($check_username_result) > 0) {
            $error_message = "El nombre de usuario ya está en uso. Por favor, elige otro.";
        } else {
            // Hash de la contraseña para mayor seguridad
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);

            // Obtener la fecha de registro actual
            $registration_date = date('Y-m-d H:i:s');

            // Ruta de la imagen de perfil predeterminada
            $default_profile_pic = 'imagenperfildefault.png'; // Cambia esto si la ruta es diferente

            // Insertar datos del usuario en la base de datos
            $sql = "INSERT INTO usuario (username, fullname, email, password, registration_date, url_foto_perfil) 
                    VALUES ('$username', '$fullname', '$email', '$hashed_password', '$registration_date', '$default_profile_pic')";

            $result = pg_query($conn, $sql);

            if ($result) {
                $_SESSION['username'] = $username; // Guardar el nombre de usuario en la sesión
                header("Location: .././user/PerfilUser/perfil.php"); // Redirigir a la página de inicio
                exit();
            } else {
                $error_message = "Error al insertar datos. Por favor, inténtalo de nuevo más tarde.";
            }
        }
    }
}

// Cerrar la conexión
pg_close($conn);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registro</title>
    <style>
        /* Estilos para el pop-up */
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            justify-content: center;
            align-items: center;
        }
        .modal-content {
            background-color: white;
            padding: 20px;
            border-radius: 10px;
            width: 90%;
            max-width: 400px;
            text-align: center;
            position: relative;
        }
        .modal-content h2 {
            margin: 0;
            color: #333;
        }
        .modal-content p {
            margin: 15px 0;
            color: #555;
        }
        .close-btn {
            position: absolute;
            top: 10px;
            right: 10px;
            background: red;
            color: white;
            border: none;
            border-radius: 50%;
            width: 30px;
            height: 30px;
            font-size: 20px;
            cursor: pointer;
            line-height: 28px;
            text-align: center;
        }
    </style>
</head>
<body>
    <?php if (!empty($error_message)): ?>
    <div class="modal" id="errorModal">
        <div class="modal-content">
            <button class="close-btn" onclick="closeModal()">×</button>
            <h2>Error</h2>
            <p><?php echo $error_message; ?></p>
        </div>
    </div>
    <script>
        // Mostrar el modal automáticamente
        document.getElementById('errorModal').style.display = 'flex';

        // Función para cerrar el modal
        function closeModal() {
            document.getElementById('errorModal').style.display = 'none';
            window.location.href = 'Registrarse.php'; // Redirige a la página de registro
        }
    </script>
    <?php endif; ?>
</body>
</html>
