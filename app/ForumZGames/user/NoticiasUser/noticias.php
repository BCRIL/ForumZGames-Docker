<?php
session_start();

// Conectar a la base de datos
$conn = pg_connect("host=db dbname=ForumZGames user=postgres password=root");

// Verificar si ya existe una cookie de "Recuérdame" y no hay sesión activa
if (isset($_COOKIE['rememberme']) && !isset($_SESSION['username'])) {
    $username = $_COOKIE['rememberme'];

    // Buscar al usuario en la base de datos usando la cookie
    $query = "SELECT username FROM usuario WHERE username = $1";
    $result = pg_query_params($conn, $query, array($username));

    if ($result && pg_num_rows($result) == 1) {
        // Restaurar la sesión
        $_SESSION['username'] = $username;
        header("Location: index.php"); // Redirigir a la página de perfil
        exit();
    } else {
        // Si la cookie no es válida (usuario no existe), borrarla
        setcookie('rememberme', '', time() - 3600, "/");
    }
}

$base_path_imagenes = '../../imagenes/noticias/';

// Comprobar si el usuario ha iniciado sesión
$loggedIn = isset($_SESSION['username']);
$username = $loggedIn ? $_SESSION['username'] : null;

// Definir la página actual y el número de noticias por página (multiplo de 3)
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1; // Página actual
$limit = 12; // Número de noticias por página
$offset = ($page - 1) * $limit; // Desplazamiento

// Consulta para hallar las noticias mas recientes
$query_noticias = "
    SELECT 
        n.id_noticia,
        n.titulo, 
        n.url_noticia, 
        n.fechapublicacion,
        n.id_videojuego, 
        n.url_imagen
    FROM 
        noticia n
    ORDER BY 
        n.fechapublicacion DESC, 
        n.id_noticia ASC
    LIMIT $1 OFFSET $2; -- Limitamos a 12 noticias y aplicamos offset
";

// Ejecutar la consulta para las noticias
$result_noticias = pg_query_params($conn, $query_noticias, array($limit, $offset));

if ($result_noticias === false) {
    echo "Error en la consulta de noticias: " . pg_last_error($conn);
    exit();
}

// Obtener todas las noticias
$noticias = pg_fetch_all($result_noticias);

// Consulta para contar el total de noticias para la paginación
$query_total = "
    SELECT COUNT(*) as total
    FROM noticia n
";

// Ejecutar la consulta para obtener el total de noticias
$result_total = pg_query ($conn, $query_total);

if ($result_total === false) {
    echo "Error en la consulta del total de noticias: " . pg_last_error($conn);
    exit();
}

$total_noticias = pg_fetch_assoc($result_total)['total'];
$total_paginas = ceil($total_noticias / $limit); // Total de páginas
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Noticias</title>
    <link rel="stylesheet" href="styleNoticias.css?v=<?php echo time(); ?>">
    <link href="https://fonts.googleapis.com/css2?family=Bungee&family=Press+Start+2P&family=Monoton&family=Lobster&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Jersey+10&display=swap" rel="stylesheet">
    <link rel="icon" href="../../imagenes/images/Logo_v1.png" type="image/x-icon">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>

    <script src="buscar.js"></script> <!-- Archivo JS para manejar la búsqueda -->
</head>
<body>
    <nav class="nav-bar">
        <ul class="nav-left">
            <li><a href="noticias.php">Noticias</a></li>
        </ul>
    
        <div class="logo">
            <img src="../../imagenes/images/Forum_ZGames.jpeg" alt="Logo" />
        </div>
    
        <ul class="nav-right">
            <li><a href="../../index.php">Inicio</a></li>
            <li><a href="../../user/JuegoUser/juegos.php">Juegos</a></li>
            <li><a href="../../user/ForoUser/foro.php">Foros</a></li>
            <?php if ($loggedIn): ?>
                <li><a href="../../user/PerfilUser/perfil.php">Perfil (<?php echo htmlspecialchars($username); ?>)</a></li>
            <?php else: ?>
                <li><a href="../../login/login.php">Cuenta</a></li>
            <?php endif; ?>
        </ul>
    </nav>

    <!-- BUSCADOR -->
    <div class="search-wrapper">
        <div class="search-container">
            <input type="text" id="search-bar" placeholder="¿Qué es lo que buscas?" onkeyup="buscarNoticias()" class="search-input">
            <button class="search-button">
                <i class='bx bx-search-alt-2'></i>
            </button>
            <div id="resultados-busqueda" class="resultados-container"></div>
        </div>
    </div>
    

    <!-- Noticias -->
    <div class="new-list">
        <?php if ($noticias): ?>
            <?php foreach ($noticias as $noticia_ind): ?>
                <div class="highlighted-new">
                    <div class="background-box"></div> <!-- Caja de fondo negro -->
                    <a href="<?php echo htmlspecialchars($noticia_ind['url_noticia']); ?>" class="foreground-box">
                        <img src="<?php echo htmlspecialchars($base_path_imagenes . $noticia_ind['url_imagen']); ?>" alt="<?php echo htmlspecialchars($noticia_ind['titulo']); ?>" class="new-image">
                        <div class="titulo-noticia"><?php echo htmlspecialchars($noticia_ind['titulo']); ?></div>
                    </a>
                    
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <p>No se encontraron noticias.</p>
        <?php endif; ?>
    </div>

    <!-- Paginación -->
    <div class="pagination">
        <?php if ($page > 1): ?>
            <a href="noticias.php?page=<?php echo $page - 1; ?>">&laquo; Anterior</a>
        <?php endif; ?>

        <?php for ($i = 1; $i <= $total_paginas; $i++): ?>
            <a href="noticias.php?page=<?php echo $i; ?>" class="<?php echo ($i == $page) ? 'active' : ''; ?>"><?php echo $i; ?></a>
        <?php endfor; ?>

        <?php if ($page < $total_paginas): ?>
            <a href="noticias.php?page=<?php echo $page + 1; ?>">Siguiente &raquo;</a>
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
                <p><a href="../../user/Faqs/faqs.php" class="small-link">FAQs y Ayuda</a></p>
            </div>            
            <div class="footer-column">
                <h3>Redes Sociales</h3>
                <a href="https://www.instagram.com" target="_blank">
                    <img src="../../imagenes/images/Instagram.png" alt="Instagram" />
                </a>
                <a href="https://twitter.com" target="_blank">
                    <img src="../../imagenes/images/Twitter.png" alt="Twitter" />
                </a>
                <a href="https://www.youtube.com" target="_blank">
                    <img src="../../imagenes/images/Youtube.png" alt="Youtube" />
                </a>
                <a href="https://www.facebook.com" target="_blank">
                    <img src="../../imagenes/images/Facebook.png" alt="Facebook" />
                </a>
            </div>       
        </div>
    </div>
</body>
</html>