<?php
session_start();

// Conectar a la base de datos
$conn = pg_connect("host=db dbname=ForumZGames user=postgres password=root");

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

$admin_data = pg_fetch_assoc($result_admin);
$admin_fullname = $admin_data['admin_fullname'];
$admin_url_foto_perfil = $admin_data['admin_url_foto_perfil'];

$base_path_imagenes = '../../imagenes/foros/';
$base_path_imagenes_autor = '../../imagenes/uploads/';

// Definir la página actual y el número de foros por página
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 6;
$offset = ($page - 1) * $limit;

// Consulta para obtener los foros
$query_foros = "
    SELECT f.id_foro, f.titulo, f.descripcion, f.fecha, f.id_usuario, f.imagen, u.url_foto_perfil,
           (SELECT MAX(m.fecha) FROM mensaje m WHERE m.id_foro = f.id_foro AND m.mostrar = true) AS fecha_ultimo_mensaje,
           (SELECT COUNT(DISTINCT m.id_usuario) FROM mensaje m WHERE m.id_foro = f.id_foro) AS participantes
    FROM foro f
    JOIN usuario u ON f.id_usuario = u.username
    ORDER BY f.fecha DESC, f.id_foro ASC
    LIMIT $1 OFFSET $2;
";

$result_foros = pg_query_params($conn, $query_foros, array($limit, $offset));

if ($result_foros === false) {
    echo "Error en la consulta de foros: " . pg_last_error($conn);
    exit();
}

$foros = pg_fetch_all($result_foros);

// Consulta para contar el total de foros para la paginación
$query_total = "
    SELECT COUNT(*) as total
    FROM foro f
";

$result_total = pg_query($conn, $query_total);

if ($result_total === false) {
    echo "Error en la consulta del total de foros: " . pg_last_error($conn);
    exit();
}

$total_foros = pg_fetch_assoc($result_total)['total'];
$total_paginas = ceil($total_foros / $limit);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Foros administrador</title>
    <link rel="stylesheet" href="styleadmin_foro.css?v=<?php echo time(); ?>">
    <link href="https://fonts.googleapis.com/css2?family=Bungee&family=Press+Start+2P&family=Monoton&family=Lobster&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Jersey+10&display=swap" rel="stylesheet">
    <link href='https://unpkg.com/boxicons@2.1.1/css/boxicons.min.css' rel='stylesheet'>
    <link rel="icon" href="../.././imagenes/images/Logo_v1.png" type="image/x-icon">
    <script src="buscar.js"></script>
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
            <li><a href="../.././admin/NoticiasAdmin/admin_noticias.php">Noticias</a></li>
            <hr class="separator-sup">
            <li><a href="admin_foro.php">Foros</a></li>
            <hr class="separator-inf">
            <li><a href="../.././admin/NotificacionesAdmin/admin_notificaciones.php">Denuncias</a></li>
        </ul>
        <div class="logo">
            <img src="../.././imagenes/images/Forum_ZGames.jpeg" alt="Logo" />
        </div>
    </nav>

    <script>
        function buscarForos() {
            const query = document.getElementById("search-bar").value;
            const resultadosBusqueda = document.getElementById("resultados-busqueda");

            if (query.length === 0) {
                resultadosBusqueda.innerHTML = "";
                return;
            }

            // Realizar la solicitud AJAX a buscar_foros.php
            const xhr = new XMLHttpRequest();
            xhr.open("GET", "buscar_foros.php?q=" + encodeURIComponent(query), true);
            xhr.onreadystatechange = function() {
                if (xhr.readyState === 4 && xhr.status === 200) {
                    resultadosBusqueda.innerHTML = xhr.responseText;
                }
            };
            xhr.send();
        }
    </script>

    <!-- Barra de búsqueda -->
    <div class="search-wrapper">
        <div class="search-container">
            <input type="text" id="search-bar" placeholder="¿Qué es lo que buscas?" onkeyup="buscarForos()" class="search-input">
            <button class="search-button">
                <i class='bx bx-search-alt-2'></i>
            </button>
            <div id="resultados-busqueda" class="resultados-container"></div>
        </div>
    </div>


   <!-- Lista de Foros -->
   <div class="foro-list">
        <?php if ($foros): ?>
            <?php foreach ($foros as $foro_ind): ?>
                <div class="highlighted-foro">
                    <div class="background-box"></div>
                    <a href="adminchatForo.php?id_foro=<?php echo htmlspecialchars($foro_ind['id_foro']); ?>" class="foreground-box">
                        <img src="<?php echo htmlspecialchars($base_path_imagenes . $foro_ind['imagen']); ?>" alt="Imagen del Foro" class="foro-imagen">
                        <div class="titulo-foro"><?php echo htmlspecialchars($foro_ind['titulo']); ?></div>
                    </a>
                    <!-- Botón de eliminación para el administrador -->
                    <div class="delete-icon">
                        <a href="eliminarForo.php?id_foro=<?php echo htmlspecialchars($foro_ind['id_foro']); ?>" onclick="return confirm('¿Estás seguro de que deseas eliminar este foro?');">
                            <img src="../.././imagenes/uploads/basura.png" alt="Eliminar foro" class="icono-basura">
                        </a>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <p>No se encontraron foros.</p>
        <?php endif; ?>
    </div>

    <!-- Paginación -->
    <div class="pagination">
        <?php if ($page > 1): ?>
            <a href="admin_foro.php?page=<?php echo $page - 1; ?>">&laquo; Anterior</a>
        <?php endif; ?>

        <?php for ($i = 1; $i <= $total_paginas; $i++): ?>
            <a href="admin_foro.php?page=<?php echo $i; ?>" class="<?php echo ($i == $page) ? 'active' : ''; ?>"><?php echo $i; ?></a>
        <?php endfor; ?>

        <?php if ($page < $total_paginas): ?>
            <a href="admin_foro.php?page=<?php echo $page + 1; ?>">Siguiente &raquo;</a>
        <?php endif; ?>
    </div>

    <!-- Footer de la pagina -->
    <div class="footer">
        <h2 class="footer-title">ForumZGames</h2>
        <div class="footer-content">
            <div class="footer-column">
                <h3>Tienda</h3>
                <p><a href="https://store.steampowered.com/" class="small-link">Steam</a></p>
                <p><a href="https://www.epicgames.com/site/es-ES/home" class="small-link">Epic Games</a></p>
                <p><a href="https://www.instant-gaming.com/es/?utm_source=google&utm_medium=cpc&utm_campaign=1070713600&utm_content=58202324531&utm_term=instant%20gaming&gad_source=1&gclid=EAIaIQobChMIueLhoLDXiQMVR9YWBR3UtjRQEAAYASAAEgKVh_D_BwE" class="small-link">Instant Gaming</a></p>
                <p><a href="https://www.g2a.com/es/?adid=GA-ES_PB_MIX_SN_PURE_BRAND-Spanish&id=35&gad_source=1&gclid=EAIaIQobChMI5JKfqbDXiQMVtnJBAh1jqhyEEAAYASAAEgL_-fD_BwE&gclsrc=aw.ds" class="small-link">G2A</a></p>
            </div>
            <div class="footer-column">
                <h3>Llámanos</h3>
                <p>+34 612 443 809</p>
            </div>
            <div class="footer-column">
                <h3>Enlaces de interés</h3>
                <p><a href="../.././admin/FaqsAdmin/admin_faqs.php" class="small-link">FAQs y Ayuda</a></p>
            </div>            
            <div class="footer-column">
                <h3>Redes Sociales</h3>
                <a href="https://www.instagram.com" target="_blank">
                    <img src="../.././imagenes/images/Instagram.png" alt="Instagram" />
                </a>
                <a href="https://twitter.com" target="_blank">
                    <img src="../.././imagenes/images/Twitter.png" alt="Twitter" />
                </a>
                <a href="https://www.youtube.com" target="_blank">
                    <img src="../.././imagenes/images/Youtube.png" alt="Youtube" />
                </a>
                <a href="https://www.facebook.com" target="_blank">
                    <img src="../.././imagenes/images/Facebook.png" alt="Facebook" />
                </a>
            </div>       
        </div>
    </div>

</body>
</html>