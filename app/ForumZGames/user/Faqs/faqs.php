<?php
session_start();
// Conexion a la base de datos
$conn = pg_connect('host=db dbname=ForumZGames user=postgres password=root');
if (!$conn) {
    die("Error de conexion: " . pg_last_error());
}

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

// Comprobar si el usuario ha iniciado sesión
$loggedIn = isset($_SESSION['username']);
$username = $loggedIn ? $_SESSION['username'] : null;

// Insertar una nueva FAQ
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['nueva_faq'])) {
    $pregunta = pg_escape_string($conn, $_POST['pregunta']);
    $respuesta = pg_escape_string($conn, $_POST['respuesta']);
    $fecha = date('Y-m-d H:i:s');

    $sql = "INSERT INTO faqs (pregunta, respuesta, fecha_creacion) VALUES ('$pregunta', '$respuesta', '$fecha')";
    $result = pg_query($conn, $sql);
    if ($result) {
        echo "<p>FAQ creada exitosamente!</p>";
    } else {
        echo "<p>Error: " . pg_last_error($conn) . "</p>";
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>ForumZGames - FAQ</title>
    <link rel="stylesheet" href="stylefaqs.css?v=<?php echo time(); ?>">
    <link href="https://fonts.googleapis.com/css2?family=Jersey+10&display=swap" rel="stylesheet">
    <link rel="icon" href="../.././imagenes/images/Logo_v1.png" type="image/x-icon">
    <script>
        // JavaScript para manejar los desplegables de las respuestas
        document.addEventListener('DOMContentLoaded', function() {
            const faqs = document.querySelectorAll('.faq');

            faqs.forEach(faq => {
                faq.addEventListener('click', function() {
                    // Alternar la clase "open" para mostrar/ocultar la respuesta
                    this.classList.toggle('open');
                });
            });
        });
    </script>
</head>
<body>
    <nav class="nav-bar">
        <ul class="nav-left">
            <li><a href="faqs.php">FAQs</a></li>
        </ul>

        <div class="logo">
            <img src="../.././imagenes/images/Forum_ZGames.jpeg" alt="Logo" />
        </div>

        <ul class="nav-right">
            <li><a href="../.././index.php">Inicio</a></li>
            <li><a href="../.././user/NoticiasUser/noticias.php">Noticias</a></li>
            <li><a href="../.././user/JuegoUser/juegos.php">Juegos</a></li>
            <li><a href="../.././user/ForoUser/foro.php">Foros</a></li>
            <?php if (isset($_SESSION['username'])): ?>
                <li><a href="../.././user/PerfilUser/perfil.php">Perfil (<?php echo htmlspecialchars($_SESSION['username']); ?>)</a></li>
            <?php else: ?>
                <li><a href="../.././login/login.php">Cuenta</a></li>
            <?php endif; ?>
        </ul>
    </nav>
    
    <div class="container">
        <h1>ForumZGames - Preguntas Frecuentes (FAQ)</h1>

        <!-- Mostrar todas las FAQs -->
        <?php
        $sql_faqs = "SELECT * FROM faqs ORDER BY fecha_creacion DESC";
        $resultado_faqs = pg_query($conn, $sql_faqs);

        if (pg_num_rows($resultado_faqs) > 0) {
            while ($faq = pg_fetch_assoc($resultado_faqs)) {
                echo "<div class='faq'>";
                echo "<h3>" . htmlspecialchars($faq['pregunta']) . "</h3>";
                echo "<p>" . htmlspecialchars($faq['respuesta']) . "</p>";
                echo "</div>";
            }
        } else {
            echo "<p>No hay preguntas frecuentes disponibles.</p>";
        }
        ?>

        <?php
        // Cerrar la conexion a la base de datos
        pg_close($conn);
        ?>
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
    </div>
</body>
</html>
