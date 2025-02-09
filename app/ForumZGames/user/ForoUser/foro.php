<?php
// Conectar a la base de datos
session_start();
$conn = pg_connect("host=db dbname=ForumZGames user=postgres password=root");

// Verificar si ya existe una cookie de "Recuérdame" y no hay sesión activa
if (isset($_COOKIE['rememberme']) && !isset($_SESSION['username'])) {
    $username = $_COOKIE['rememberme'];
    $query = "SELECT username FROM usuario WHERE username = $1";
    $result = pg_query_params($conn, $query, array($username));

    if ($result && pg_num_rows($result) == 1) {
        $_SESSION['username'] = $username;
        header("Location: index.php");
        exit();
    } else {
        setcookie('rememberme', '', time() - 3600, "/");
    }
}

$base_path_imagenes = '../../imagenes/foros/';
$base_path_imagenes_autor = '../../imagenes/uploads/';

$loggedIn = isset($_SESSION['username']);
$username = $loggedIn ? $_SESSION['username'] : null;

// Define pagination variables
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 3;
$offset = ($page - 1) * $limit;

// Query to count total forums for pagination
$query_total = "SELECT COUNT(*) as total FROM foro";
$result_total = pg_query($conn, $query_total);
$total_foros = pg_fetch_assoc($result_total)['total'];
$total_paginas = ceil($total_foros / $limit);

$query_foros = "
    SELECT f.id_foro, f.titulo, f.fecha, f.id_usuario, f.imagen, u.url_foto_perfil,
           (SELECT MAX(m.fecha) FROM mensaje m WHERE m.id_foro = f.id_foro AND m.mostrar = true) AS fecha_ultimo_mensaje,
           (SELECT COUNT(DISTINCT m.id_usuario) FROM mensaje m WHERE m.id_foro = f.id_foro) AS participantes
    FROM foro f
    JOIN usuario u ON f.id_usuario = u.username
    ORDER BY f.fecha DESC, f.id_foro ASC
    LIMIT $1 OFFSET $2;
";

$result_foros = pg_query_params($conn, $query_foros, array($limit, $offset));
$foros = pg_fetch_all($result_foros);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Foros</title>
    <link rel="stylesheet" href="stylesForo.css?v=<?php echo time(); ?>">
    <link href="https://fonts.googleapis.com/css2?family=Bungee&family=Press+Start+2P&family=Monoton&family=Lobster&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Jersey+10&display=swap" rel="stylesheet">
    <link rel="icon" href="../.././imagenes/images/Logo_v1.png" type="image/x-icon">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <script src="buscar.js"></script> <!-- Load buscar.js for search functionality -->
</head>
<body>
    <nav class="nav-bar">
        <ul class="nav-left">
            <li><a href="foro.php">Foros</a></li>
        </ul>

        <div class="logo">
            <img src="../.././imagenes/images/Forum_ZGames.jpeg" alt="Logo" />
        </div>

        <ul class="nav-right">
            <li><a href="../.././index.php">Inicio</a></li>
            <li><a href="../.././user/NoticiasUser/noticias.php">Noticias</a></li>
            <li><a href="../.././user/JuegoUser/juegos.php">Juegos</a></li>
            <?php if ($loggedIn): ?>
                <li><a href="../.././user/PerfilUser/perfil.php">Perfil (<?php echo htmlspecialchars($username); ?>)</a></li>
            <?php else: ?>
                <li><a href="../.././login/login.php">Cuenta</a></li>
            <?php endif; ?>
        </ul>
    </nav>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Botones de foro
            const foroLinks = document.querySelectorAll('.foro-item a');
            const addForumButton = document.getElementById('add-forum-button');
            const loggedIn = <?php echo json_encode($loggedIn); ?>; // Obtener el estado de sesión PHP

            // Modal de login
            const loginModal = document.getElementById('login-modal');
            const closeLoginModal = document.getElementById('close-login-modal');

            foroLinks.forEach(link => {
                link.addEventListener('click', function(event) {
                    if (!loggedIn) {
                        event.preventDefault(); // Prevenir que el enlace redirija
                        loginModal.style.display = 'block';
                    }
                });
            });

            // Manejar el botón "Añadir Foro"
            if (addForumButton) {
                addForumButton.addEventListener('click', function(event) {
                    if (!loggedIn) {
                        event.preventDefault();
                        loginModal.style.display = 'block';
                    }
                });
            }

            // Cerrar el modal
            closeLoginModal.addEventListener('click', function() {
                loginModal.style.display = 'none';
            });

            // Cerrar el modal si se hace clic fuera de él
            window.addEventListener('click', function(event) {
                if (event.target === loginModal) {
                    loginModal.style.display = 'none';
                }
            });
        });

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


    <!-- Modal para iniciar sesión o registrarse -->
    <div id="login-modal" class="modal">
        <div class="modal-content">
            <span id="close-login-modal" class="close">&times;</span>
            <h2>Iniciar Sesión o Registrarse</h2>
            <p>Necesitas iniciar sesión o registrarte para acceder al foro.</p>
            <div class="modal-buttons">
                <a href="../.././login/login.php" class="modal-button">Iniciar Sesión</a>
                <a href="../.././login/register.php" class="modal-button">Registrarse</a>
            </div>
        </div>
    </div>

    <!-- Botón para añadir nuevos foros -->
    <?php if ($loggedIn): ?>
        <a href="anyadirForo.php" class="anyadir-button">
            <i class='bx bx-plus'></i> Añadir foro
        </a>
    <?php else: ?>
        <button id="add-forum-button" class="anyadir-button">
            <i class='bx bx-plus'></i> Añadir foro
        </button>
    <?php endif; ?>

    <!-- Foros list section -->
    <div class="new-list">
        <?php if ($foros): ?>
            <?php foreach ($foros as $foros_ind): ?>
                <div class="foro-item">
                    <div class="image-container">
                        <a href="chatForo.php?id_foro=<?php echo htmlspecialchars($foros_ind['id_foro']); ?>">
                            <div class="blue-square">
                                <div class="texto-cuadro-azul">
                                    <span class="titulo-cuadro-azul">
                                        <?php echo htmlspecialchars($foros_ind['titulo']); ?>
                                    </span><br><br>
                                    Autor: <?php echo htmlspecialchars($foros_ind['id_usuario']); ?><br>
                                    Último Mensaje:
                                    <?php 
                                        echo $foros_ind['fecha_ultimo_mensaje'] 
                                            ? htmlspecialchars(date('Y-m-d H:i', strtotime($foros_ind['fecha_ultimo_mensaje']))) 
                                            : 'No hay mensajes';
                                    ?><br>
                                    Participantes: <?php echo htmlspecialchars($foros_ind['participantes']); ?> Personas<br>
                                </div>
                                <img src="<?php echo htmlspecialchars($base_path_imagenes_autor . $foros_ind['url_foto_perfil']); ?>" alt="Imagen del autor" class="avatar-image">
                            </div>
                        </a>
                        <img src="<?php echo htmlspecialchars($base_path_imagenes . $foros_ind['imagen']); ?>" alt="Imagen del foro" class="foro-image">
                    </div>
                    
                    <!-- Mostrar el icono de basura solo si el usuario logueado es el creador del foro -->
                    <?php if ($loggedIn && $username === $foros_ind['id_usuario']): ?>
                        <div class="delete-icon">
                            <a href="eliminarForo.php?id_foro=<?php echo htmlspecialchars($foros_ind['id_foro']); ?>" onclick="return confirm('¿Estás seguro de que deseas eliminar este foro?');">
                                <img src="../.././imagenes/uploads/basura.png" alt="Eliminar foro" class="icono-basura">
                            </a>
                        </div>
                    <?php endif; ?>

                    <hr class="full-width-line">
                </div>
            <?php endforeach; ?>

        <?php else: ?>
            <p>No se encontraron foros.</p>
        <?php endif; ?>
    </div>

    <!-- Pagination -->
    <div class="pagination">
        <?php if ($page > 1): ?>
            <a href="foro.php?page=<?php echo $page - 1; ?>">&laquo; Anterior</a>
        <?php endif; ?>

        <?php for ($i = 1; $i <= $total_paginas; $i++): ?>
            <a href="foro.php?page=<?php echo $i; ?>" class="<?php echo ($i == $page) ? 'active' : ''; ?>"><?php echo $i; ?></a>
        <?php endfor; ?>

        <?php if ($page < $total_paginas): ?>
            <a href="foro.php?page=<?php echo $page + 1; ?>">Siguiente &raquo;</a>
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
                    <p><a href="../.././user/Faqs/faqs.php" class="small-link">FAQs y Ayuda</a></p>
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

<?php
// Close the database connection
pg_close($conn);
?>
