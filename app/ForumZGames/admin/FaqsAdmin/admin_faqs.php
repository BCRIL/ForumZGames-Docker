<?php
session_start();
// Conexion a la base de datos
$conn = pg_connect('host=db dbname=ForumZGames user=postgres password=root');
if (!$conn) {
    die("Error de conexion: " . pg_last_error());
}

// Comprobar si el usuario ha iniciado sesión
if (!isset($_SESSION['admin_username'])) {
    // Redirigir a la página de inicio de sesión si no hay sesión activa
    header("Location: ../.././login/login.php");
    exit();
}

// Si ha iniciado sesión, obtener el nombre de usuario
$username = $_SESSION['admin_username'];
$query_admin = "SELECT admin_fullname, admin_url_foto_perfil FROM administrador WHERE admin_username = $1";
$result_admin = pg_query_params($conn, $query_admin, array($username));

if (!$result_admin || pg_num_rows($result_admin) === 0) {
    echo "Error al obtener los datos del administrador.";
    exit();
}

$base_path_imagenes_autor = '../../imagenes/uploads/';

$admin_data = pg_fetch_assoc($result_admin);
$admin_fullname = $admin_data['admin_fullname'];
$admin_url_foto_perfil = $admin_data['admin_url_foto_perfil'];

// Insertar una nueva FAQ
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['nueva_faq'])) {
    $pregunta = pg_escape_string($conn, $_POST['pregunta']);
    $respuesta = pg_escape_string($conn, $_POST['respuesta']);
    $fecha = date('Y-m-d H:i:s');

    $sql = "INSERT INTO faqs (pregunta, respuesta, fecha_creacion) VALUES ('$pregunta', '$respuesta', '$fecha')";
    $result = pg_query($conn, $sql);
}

// Eliminar una FAQ
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['confirmar_eliminar_faq'])) {
    $id_faq = pg_escape_string($conn, $_POST['id_faq']);

    $sql = "DELETE FROM faqs WHERE id = '$id_faq'";
    $result = pg_query($conn, $sql);
}

// Obtener todas las FAQs
$sql_faqs = "SELECT * FROM faqs ORDER BY fecha_creacion DESC";
$resultado_faqs = pg_query($conn, $sql_faqs);

// Cerrar la conexión a la base de datos
pg_close($conn);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>ForumZGames - Admin FAQ</title>
    <link rel="stylesheet" href="styleadmin_faqs.css?v=<?php echo time(); ?>">
    <link href="https://fonts.googleapis.com/css2?family=Jersey+10&display=swap" rel="stylesheet">
    <link rel="icon" href="../.././imagenes/images/Logo_v1.png" type="image/x-icon">
    <script>
        // JavaScript para manejar los desplegables de las respuestas
        document.addEventListener('DOMContentLoaded', function() {
            const faqs = document.querySelectorAll('.faq h3');

            faqs.forEach(faq => {
                faq.addEventListener('click', function() {
                    const respuesta = this.nextElementSibling;
                    if (respuesta.style.display === 'none' || respuesta.style.display === '') {
                        respuesta.style.display = 'block';
                    } else {
                        respuesta.style.display = 'none';
                    }
                });
            });

            // Abrir y cerrar modal para añadir FAQ
            const addButton = document.getElementById('add-faq-button');
            const modal = document.getElementById('faq-modal');
            const closeButton = document.getElementById('close-modal');

            addButton.addEventListener('click', function() {
                modal.style.display = 'block';
            });

            closeButton.addEventListener('click', function() {
                modal.style.display = 'none';
            });

            window.addEventListener('click', function(event) {
                if (event.target === modal) {
                    modal.style.display = 'none';
                }
            });

            // Abrir y cerrar modal para confirmar eliminación
            const deleteButtons = document.querySelectorAll('.delete-faq-button');
            const deleteModal = document.getElementById('delete-faq-modal');
            const closeDeleteButton = document.getElementById('close-delete-modal');
            const confirmDeleteButton = document.getElementById('confirm-delete-button');
            let faqToDelete = null;

            deleteButtons.forEach(button => {
                button.addEventListener('click', function() {
                    faqToDelete = this.getAttribute('data-faq-id');
                    deleteModal.style.display = 'block';
                });
            });

            closeDeleteButton.addEventListener('click', function() {
                deleteModal.style.display = 'none';
                faqToDelete = null;
            });

            window.addEventListener('click', function(event) {
                if (event.target === deleteModal) {
                    deleteModal.style.display = 'none';
                    faqToDelete = null;
                }
            });

            confirmDeleteButton.addEventListener('click', function() {
                if (faqToDelete) {
                    document.getElementById('delete-faq-form').elements['id_faq'].value = faqToDelete;
                    document.getElementById('delete-faq-form').submit();
                }
            });
        });
    </script>
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
            <li><a href="../.././admin/NoticiasAdmin/admin_noticias.php">Noticias</a></li>
            <hr class="separator-inf">
            <li><a href="../.././admin/NotificacionesAdmin/admin_notificaciones.php">Denuncias</a></li>
        </ul>
        <div class="logo">
            <img src="../.././imagenes/images/Forum_ZGames.jpeg" alt="Logo" />
        </div>
    </nav>
    <div class="no-sidebar">
        <div class="container">
            <h1>ForumZGames - Preguntas Frecuentes (FAQ)</h1>
            
            <!-- Botón para añadir nueva FAQ -->
            <button id="add-faq-button">Añadir Pregunta FAQ</button>

            <!-- Modal para añadir nueva FAQ -->
            <div id="faq-modal" class="modal">
                <div class="modal-content">
                    <span id="close-modal" class="close">&times;</span>
                    <h2>Añadir una nueva FAQ</h2>
                    <form method="post">
                        <input type="hidden" name="nueva_faq" value="1">
                        <label>Pregunta:</label><br>
                        <input type="text" name="pregunta" required><br><br>
                        <label>Respuesta:</label><br>
                        <textarea name="respuesta" required></textarea><br><br>
                        <button type="submit">Añadir FAQ</button>
                    </form>
                </div>
            </div>
            
            <!-- Modal para confirmar eliminación de FAQ -->
            <div id="delete-faq-modal" class="modal">
                <div class="modal-content">
                    <span id="close-delete-modal" class="close">&times;</span>
                    <h2>Confirmar Eliminación</h2>
                    <p>¿Estás seguro de que deseas eliminar esta FAQ?</p>
                    <button id="confirm-delete-button">Eliminar</button>
                </div>
            </div>
            
            <hr>

            <!-- Mostrar todas las FAQs -->
            <h2>Preguntas Frecuentes</h2>
            <?php
            if ($resultado_faqs && pg_num_rows($resultado_faqs) > 0) {
                while ($faq = pg_fetch_assoc($resultado_faqs)) {
                    echo "<div class='faq'>";
                    echo "<h3>" . htmlspecialchars($faq['pregunta']) . "</h3>";
                    echo "<p>" . htmlspecialchars($faq['respuesta']) . "</p>";
                    echo "<button class='delete-faq-button' data-faq-id='" . htmlspecialchars($faq['id']) . "'>Eliminar FAQ</button>";
                    echo "</div>";
                }
            } else {
                echo "<p>No hay preguntas frecuentes disponibles.</p>";
            }
            ?>

            <!-- Formulario oculto para eliminar FAQ -->
            <form id="delete-faq-form" method="post" style="display: none;">
                <input type="hidden" name="confirmar_eliminar_faq" value="1">
                <input type="hidden" name="id_faq" value="">
            </form>
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
    </div>
</body>
</html>
