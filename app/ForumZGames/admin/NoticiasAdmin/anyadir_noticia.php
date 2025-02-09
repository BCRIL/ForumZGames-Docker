<?php
session_start();

// Conectar a la base de datos
$conn = pg_connect("host=db dbname=ForumZGames user=postgres password=root");

if (!$conn) {
    die("Error en la conexión: " . pg_last_error());
}

// Comprobar si el usuario ha iniciado sesión
if (!isset($_SESSION['admin_username'])) {
    // Redirigir a la página de inicio de sesión si no hay sesión activa
    header("Location: ../.././login/login.php");
    exit();
}

// Obtener datos del administrador
$username = $_SESSION['admin_username'];
$query_admin = "SELECT admin_fullname, admin_url_foto_perfil FROM administrador WHERE admin_username = $1";
$result_admin = pg_query_params($conn, $query_admin, array($username));

if (!$result_admin || pg_num_rows($result_admin) === 0) {
    echo "Error al obtener los datos del administrador.";
    exit();
}

// Rutas base para las imágenes
$base_path_imagenes_autor = '../../imagenes/uploads/';
$admin_data = pg_fetch_assoc($result_admin);
$admin_fullname = $admin_data['admin_fullname'];
$admin_url_foto_perfil = $admin_data['admin_url_foto_perfil'];
$show_modal = false;

// Procesar el formulario si se envió
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Datos del formulario
    $titulo = $_POST['titulo'];
    $url_noticia = $_POST['url_noticia'];
    $juego = $_POST['selected-game-id'];
    $publication_date = date('Y-m-d H:i:s');

    // Subir imagen
    $imagenRuta = '';
    if (isset($_FILES['imagen']) && $_FILES['imagen']['error'] === 0) {
        $nombreImagen = uniqid() . '_' . basename($_FILES['imagen']['name']); // Generar un nombre único para evitar colisiones
        $directorioDestino = __DIR__ . '/../../imagenes/noticias/'; // Ruta relativa al directorio "noticias"

        // Verificar si el directorio existe, si no, crearlo
        if (!is_dir($directorioDestino)) {
            mkdir($directorioDestino, 0777, true); // Crear el directorio con permisos de escritura
        }

        // Ruta completa para mover el archivo
        $rutaCompleta = $directorioDestino . $nombreImagen;
        if (move_uploaded_file($_FILES['imagen']['tmp_name'], $rutaCompleta)) {
            $imagenRuta = '../../imagenes/noticias/' . $nombreImagen; // Ruta relativa para almacenar en la base de datos
        } else {
            echo "Error al mover la imagen.";
            exit();
        }
    }

    // Insertar noticia en la base de datos
    $query = "INSERT INTO noticia (titulo, url_noticia, fechapublicacion, id_videojuego, url_imagen) VALUES ($1, $2, $3, $4, $5)";
    $result = pg_query_params($conn, $query, array($titulo, $url_noticia, $publication_date, $juego, $imagenRuta));

    $modal_message = '';

    if ($result) {
        $modal_message = "Noticia guardada correctamente.";
    } else {
        $modal_message = "Error al guardar la noticia: " . pg_last_error($conn);
    }
    $show_modal = true;
}

?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Añadir noticia</title>
    <link rel="stylesheet" href="styleanyadir_noticia.css?v=<?php echo time(); ?>">
    <link href="https://fonts.googleapis.com/css2?family=Bungee&family=Press+Start+2P&family=Monoton&family=Lobster&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Jersey+10&display=swap" rel="stylesheet">
    <link href='https://unpkg.com/boxicons@2.1.1/css/boxicons.min.css' rel='stylesheet'>
    <link rel="icon" href="../.././imagenes/images/Logo_v1.png" type="image/x-icon">

    <script src="buscar.js"></script> <!-- Archivo JS para manejar la búsqueda -->
</head>
<body>
    <nav class="sidebar">
        <ul class="nav-left">
            <a href="../.././admin/PerfilAdmin/admin_perfil.php">
            <div class="admin-perfil">
                <img src=<?php echo $base_path_imagenes_autor . $admin_url_foto_perfil; ?> alt="Logo" class="imagen-admin">
                <span class="usr-admin"><?php echo $username; ?></span>
            </div></a>
            <hr class="separator">
            <li><a href="../.././admin/IndexAdmin/admin_index.php">Inicio</a></li>
            <li><a href="../.././admin/JuegoAdmin/admin_juegos.php">Juegos</a></li>
            <li><a href="../.././admin/ForoAdmin/admin_foro.php">Foros</a></li>
            <hr class="separator-sup">
            <li><a href="admin_noticias.php">Noticias</a>
            </li>
            <hr class="separator-inf">
            <li><a href="../.././admin/NotificacionesAdmin/admin_notificaciones.php">Denuncias</a></li>
        </ul>
        <div class="logo">
            <img src="../.././imagenes/images/Forum_ZGames.jpeg" alt="Logo" />
        </div>
    </nav>

    <div class="no-sidebar">
        <!-- Formulario para introducir datos -->
        <div class="form-container">
            <h2>Insertar Noticia</h2>
            <form id="noticiaForm" action="anyadir_noticia.php" method="POST" enctype="multipart/form-data">
                <label for="titulo">Título de la Noticia:</label>
                <input type="text" id="titulo" name="titulo" required>

                <label for="url">URL de la Noticia:</label>
                <input type="url" id="url_noticia" name="url_noticia" required>

                <label for="imagen">Subir Imagen:</label>
                <input type="file" id="imagen" name="imagen" accept="image/*" required>

                <label for="juego">Seleccionar Juego:</label>
                <div class="autocomplete-box">
                    <input type="text" id="search-game" placeholder="Buscar juego..." oninput="seleccionarJuegos()">
                    <div id="search-results" class="resultados-container"></div>
                    <input type="hidden" id="selected-game-id" name="selected-game-id">
                </div>
                <button class="save-button" type="submit">Guardar Noticia</button>
            </form>
        </div>

        <!-- Modal -->
        <div id="myModal" class="modal">
            <div class="modal-content">
                <p id="modalMessage"><?php echo $modal_message; ?></p>
                <button class="close-btn" onclick="redirectToPage()">Aceptar</button>
            </div>
        </div>  

        <script>
            function showModal() {
                document.getElementById("myModal").style.display = "flex";
            }

            function closeModal() {
                document.getElementById("myModal").style.display = "none";
            }

            function redirectToPage() {
                // Redirige a una página específica después de cerrar el modal
                window.location.href = 'admin_noticias.php';
            }

            <?php if (!empty($modal_message)) : ?>
                showModal();
            <?php endif; ?>
        </script>
    </div>
</body>
</html>
