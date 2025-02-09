<?php
session_start(); // Iniciar sesión

// Conectar a la base de datos
$conn_string = "host=db dbname=ForumZGames user=postgres password=root"; // Cambia esto
$conn = pg_connect($conn_string);

if (!$conn) {
    die("Error en la conexión a la base de datos.");
}

// Comprobar si el usuario ha iniciado sesión
if (!isset($_SESSION['username'])) {
    header("Location: .././login/login.php"); // Redirigir a la página de inicio de sesión
    exit();
}

// Obtener el nombre de usuario
$username = $_SESSION['username'];
$error_message = ""; // Variable para el mensaje de error

// Comprobar si el formulario ha sido enviado
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $current_password = pg_escape_string(trim($_POST['current_password']));
    $new_password = pg_escape_string(trim($_POST['new_password']));
    $confirm_password = pg_escape_string(trim($_POST['confirm_password']));

    // Comprobar si las nuevas contraseñas coinciden
    if ($new_password !== $confirm_password) {
        $error_message = "Las contraseñas nuevas no coinciden.";
    } else {
        // Recuperar la contraseña actual del usuario
        $query = "SELECT password FROM usuario WHERE username = $1";
        $result = pg_prepare($conn, "get_current_password", $query);
        $result = pg_execute($conn, "get_current_password", array($username));

        if ($row = pg_fetch_assoc($result)) {
            $hashed_password = $row['password'];

            // Verificar si la contraseña actual ingresada es correcta
            if (!password_verify($current_password, $hashed_password)) {
                $error_message = "La contraseña actual es incorrecta.";
            } else {
                // Hash de la nueva contraseña
                $new_hashed_password = password_hash($new_password, PASSWORD_DEFAULT);

                // Actualizar la contraseña en la base de datos
                $update_query = "UPDATE usuario SET password = $1 WHERE username = $2";
                $update_result = pg_prepare($conn, "update_password", $update_query);
                $update_result = pg_execute($conn, "update_password", array($new_hashed_password, $username));

                if ($update_result) {
                    header("Location: .././user/PerfilUser/perfil.php"); // Redirigir al perfil
                    exit();
                } else {
                    $error_message = "Error al cambiar la contraseña. Por favor, inténtalo de nuevo más tarde.";
                }
            }
        } else {
            $error_message = "Error al recuperar la contraseña actual.";
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
    <title>Cambiar Contraseña</title>
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
            window.location.href = '.././user/PerfilUser/perfil.php'; // Redirige a la pantalla de perfil
        }
    </script>
    <?php endif; ?>
</body>
</html>
