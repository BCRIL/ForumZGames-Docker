<?php
session_start();
ob_start(); // Iniciar el buffer de salida

// Conectar a la base de datos
$conn = pg_connect("host=db dbname=ForumZGames user=postgres password=root");

if (!$conn) {
    die("Error en la conexión a la base de datos.");
}

// Obtener los datos del formulario
$id_videojuego = isset($_POST['id_videojuego']) ? (int)$_POST['id_videojuego'] : null;
$puntuacion = isset($_POST['puntuacion']) ? (int)$_POST['puntuacion'] : null;
$current_url = isset($_POST['current_url']) ? $_POST['current_url'] : 'juegos.php'; // URL de redirección por defecto

// Verificar si el usuario está logueado
if (!isset($_SESSION['username'])) {
    // Redirigir al login si no está logueado
    header("Location: ../.././login.php");
    exit();
}

// Obtener el nombre de usuario del usuario que está logueado
$user_name = $_SESSION['username'];

// Validar que el ID del videojuego y la puntuación no estén vacíos
if (empty($id_videojuego) || empty($puntuacion)) {
    // Redirigir con mensaje de error
    header("Location: " . $current_url . "?error=Datos incompletos");
    exit();
}

// Obtener la fecha actual
$fecha = date('Y-m-d H:i:s'); // Formato de fecha y hora

// Verificar si el usuario ya ha votado para este videojuego
$query_verificar = "SELECT * FROM valoracion WHERE id_videojuego = $1 AND id_usuario = $2";
$result_verificar = pg_query_params($conn, $query_verificar, array($id_videojuego, $user_name));

if ($result_verificar && pg_num_rows($result_verificar) > 0) {
    // Si ya existe una valoración, actualízala
    $query_actualizar = "UPDATE valoracion SET fecha = $1, puntuacion = $2 WHERE id_videojuego = $3 AND id_usuario = $4";
    $result_actualizar = pg_query_params($conn, $query_actualizar, array($fecha, $puntuacion, $id_videojuego, $user_name));

    if ($result_actualizar) {
        // Redirigir con mensaje de éxito
        header("Location: " . $current_url . "?success=Valoración actualizada con éxito");
    } else {
        // Redirigir con mensaje de error
        header("Location: " . $current_url . "?error=" . urlencode(pg_last_error($conn)));
    }
} else {
    // Inserción de la valoración en la base de datos
    $query_insertar = "INSERT INTO valoracion (id_videojuego, id_usuario, fecha, puntuacion) VALUES ($1, $2, $3, $4)";
    $result_insertar = pg_query_params($conn, $query_insertar, array($id_videojuego, $user_name, $fecha, $puntuacion));

    if ($result_insertar) {
        // Redirigir con mensaje de éxito
        header("Location: " . $current_url . "?success=Valoración enviada con éxito");
    } else {
        // Redirigir con mensaje de error
        header("Location: " . $current_url . "?error=" . urlencode(pg_last_error($conn)));
    }
}

// Cerrar la conexión a la base de datos
pg_close($conn);
ob_end_flush(); // Liberar el buffer de salida
exit();
