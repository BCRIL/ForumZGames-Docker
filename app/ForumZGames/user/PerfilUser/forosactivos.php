<?php
session_start(); // Inicia la sesión


// Conectar a la base de datos
$conn = pg_connect("host=db dbname=ForumZGames user=postgres password=root");

// Comprobar si el usuario ha iniciado sesión
if (!isset($_SESSION['username'])) {
    header("Location: .././login/login.php"); // Redirige al login si no está autenticado
    exit();
}

// Verificar si existe una cookie "Recuérdame" y no hay una sesión activa
if (isset($_COOKIE['rememberme']) && !isset($_SESSION['username'])) {
    $username = $_COOKIE['rememberme'];

    if ($conn) {
        // Buscar al usuario en la base de datos usando la cookie
        $query = "SELECT username FROM usuario WHERE username = $1";
        $result = pg_query_params($conn, $query, array($username));

        // Restaurar la sesión si el usuario existe
        if ($result && pg_num_rows($result) == 1) {
            $_SESSION['username'] = $username;
            header("Location: index.php"); // Redirigir a la página de inicio
            exit();
        } else {
            // Borrar la cookie si el usuario no existe
            setcookie('rememberme', '', time() - 3600, "/");
        }

        // Cerrar la conexión a la base de datos
        pg_close($conn);
    }
}

// Verificar si el usuario ha iniciado sesión
$loggedIn = isset($_SESSION['username']);
$username = $loggedIn ? $_SESSION['username'] : null;

if (!$conn) {
    die("Error en la conexión a la base de datos."); // Muestra mensaje de error si falla la conexión
}

// Consulta para obtener los foros donde el usuario ha creado o participado
$query = "
    SELECT DISTINCT f.id_foro, f.titulo AS nombre, f.descripcion, 
           CASE WHEN f.id_usuario = $1 THEN 'creador' ELSE 'participante' END AS rol
    FROM foro f
    LEFT JOIN mensaje m ON m.id_foro = f.id_foro
    WHERE f.id_usuario = $1 OR m.id_usuario = $1
    ORDER BY f.titulo ASC
";

// Preparar y ejecutar la consulta con el nombre de usuario
$result = pg_prepare($conn, "get_active_forums", $query);
$result = pg_execute($conn, "get_active_forums", array($username));

if ($result === false) {
    die("Error en la consulta: " . pg_last_error($conn)); // Muestra mensaje si ocurre un error en la consulta
}

// Cerrar la conexión a la base de datos
pg_close($conn);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Foros Activos</title>
    <link rel="stylesheet" href="styleForosActivos.css?v=<?php echo time(); ?>">
    <link rel="icon" href="../.././imagenes/images/Logo_v1.png" type="image/x-icon">
    <link href="https://fonts.googleapis.com/css2?family=Jersey+10&display=swap" rel="stylesheet">
</head>
<body>
    <!-- Barra de navegación -->
    <nav class="nav-bar">
        <ul class="nav-left">
            <li><a href="forosactivos.php">Foros Activos</a></li>
        </ul>

        <!-- Logo centrado -->
        <div class="logo">
            <img src="../.././imagenes/images/Forum_ZGames.jpeg" alt="Logo" />
        </div>

        <!-- Elementos de navegación derecha -->
        <ul class="nav-right">
            <li><a href="../.././index.php">Inicio</a></li>
            <li><a href="../.././user/JuegoUser/juegos.php">Juegos</a></li>
            <li><a href="../.././user/ForoUser/foro.php">Foros</a></li>
            <li><a href="../.././user/NoticiasUser/noticias.php">Noticias</a></li>
            <?php if ($loggedIn): ?>
                <li><a href="../.././user/PerfilUser/perfil.php">Perfil (<?php echo htmlspecialchars($username); ?>)</a></li>
            <?php else: ?>
                <li><a href="../.././login/login.php">Cuenta</a></li>
            <?php endif; ?>
        </ul>
    </nav>

    <!-- Contenedor de foros activos -->
    <div class="foros-activos-container">
        <div class="title-container">
            <h1 class="title-styled">Foros activos</h1>
        </div>
        <div class="foros-list">
            <?php if (pg_num_rows($result) > 0): ?>
                <?php while ($row = pg_fetch_assoc($result)): ?>
                    <div class="foro-item">
                        <h2><?php echo htmlspecialchars($row['nombre']); ?></h2>
                        <p><?php echo htmlspecialchars($row['descripcion']); ?></p>
                        <p class="rol">
                            <?php echo ($row['rol'] === 'creador') ? 'Creado por ti' : 'Participando'; ?>
                        </p>
                        <a href="../.././user/ForoUser/chatForo.php?id_foro=<?php echo urlencode($row['id_foro']); ?>">Ir al foro</a>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <p>No has participado en ningún foro todavía.</p> <!-- Mensaje si no hay participación en foros -->
            <?php endif; ?>
        </div>
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
