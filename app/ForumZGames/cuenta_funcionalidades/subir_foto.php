<?php
session_start();

// Comprobar si el usuario ha iniciado sesión
if (!isset($_SESSION['username'])) {
    header("Location: .././login/login.php");
    exit();
}

// Procesar el formulario para subir una foto
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['foto_perfil'])) {
    // Conectar a la base de datos
    $conn_string = "host=db dbname=ForumZGames user=postgres password=root";
    $conn = pg_connect($conn_string);

    if (!$conn) {
        die("Error en la conexión a la base de datos.");
    }

    // Rutas base para las imágenes
    $base_path_imagenes = '../../imagenes/uploads/';
    $directorioDestino = __DIR__ . '/../imagenes/uploads/';

    // Verificar si el directorio existe, si no, crearlo
    if (!is_dir($directorioDestino)) {
        if (!mkdir($directorioDestino, 0777, true)) {
            die("Error: No se pudo crear el directorio $directorioDestino. Verifica los permisos.");
        }
    }

    // Generar un nombre único para la imagen para evitar conflictos
    $nombreImagen = uniqid() . '_' . basename($_FILES['foto_perfil']['name']);
    $rutaCompleta = $directorioDestino . $nombreImagen;

    // Validar si el archivo es una imagen
    $check = getimagesize($_FILES['foto_perfil']['tmp_name']);
    if ($check === false) {
        echo "El archivo no es una imagen.";
        exit();
    }

    // Mover el archivo subido al directorio de destino
    if (move_uploaded_file($_FILES['foto_perfil']['tmp_name'], $rutaCompleta)) {
        // Ruta relativa para almacenar en la base de datos
        $ruta_imagen_db = $base_path_imagenes . $nombreImagen;

        // Usar una consulta preparada para actualizar la foto de perfil en la base de datos
        $username = $_SESSION['username'];
        $query = "UPDATE usuario SET url_foto_perfil = $1 WHERE username = $2";
        $result = pg_query_params($conn, $query, array($ruta_imagen_db, $username));

        if ($result) {
            header("Location: .././user/PerfilUser/perfil.php");
            exit();
        } else {
            echo "Error al actualizar la foto de perfil: " . pg_last_error($conn);
        }
    } else {
        echo "Error al mover la imagen.";
    }

    // Cerrar la conexión
    pg_close($conn);
}
?>
