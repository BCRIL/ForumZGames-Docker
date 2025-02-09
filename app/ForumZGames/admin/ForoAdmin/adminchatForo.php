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

$base_path_imagenes = '../../imagenes/foros/';
$base_path_imagenes_autor = '../../imagenes/uploads/';

if (!$result_admin || pg_num_rows($result_admin) === 0) {
    echo "Error al obtener los datos del administrador.";
    exit();
}

$admin_data = pg_fetch_assoc($result_admin);
$admin_fullname = $admin_data['admin_fullname'];
$admin_url_foto_perfil = $admin_data['admin_url_foto_perfil'];


$id_foro = (int)$_GET['id_foro'];

// Consulta para obtener la información del foro y el creador
$query_foro = "
    SELECT f.titulo, f.descripcion, f.fecha, u.username, u.url_foto_perfil, f.imagen 
    FROM foro f 
    JOIN usuario u ON f.id_usuario = u.username
    WHERE f.id_foro = $1
";
$result_foro = pg_query_params($conn, $query_foro, array($id_foro));

if (!$result_foro || pg_num_rows($result_foro) !== 1) {
    echo "Foro no encontrado.";
    exit();
}

$foro = pg_fetch_assoc($result_foro);

// Consulta para obtener los mensajes del foro seleccionado
$query_mensajes = "SELECT m.id_mensaje, m.texto, m.fecha, u.username, u.url_foto_perfil 
                   FROM mensaje m 
                   JOIN usuario u ON m.id_usuario = u.username
                   WHERE m.id_foro = $1 AND m.mostrar = true
                   ORDER BY m.fecha ASC";
$result_mensajes = pg_query_params($conn, $query_mensajes, array($id_foro));

if (!$result_mensajes) {
    echo "Error en la consulta de mensajes: " . pg_last_error($conn);
    exit();
}

$mensajes = pg_fetch_all($result_mensajes);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($foro['titulo']); ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Jersey+10&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="styleadminchatForo.css?v=<?php echo time(); ?>">

    <script>
        function confirmReport(messageId, description) {
            // Mostrar mensaje de confirmación
            if (confirm("¿Está seguro de denunciar este mensaje?")) {
                // Crear solicitud AJAX para enviar el reporte
                var xhr = new XMLHttpRequest();
                xhr.open("POST", "reportMensaje.php", true);
                xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");

                xhr.onreadystatechange = function() {
                    if (xhr.readyState === 4 && xhr.status === 200) {
                        var response = JSON.parse(xhr.responseText);
                        if (response.status === "success") {
                            alert("Denuncia enviada con éxito.");
                            // Recargar o redirigir al chat después de denunciar
                            window.location.href = window.location.href; // Recargar la misma página
                        } else {
                            alert("Error al enviar la denuncia: " + response.message);
                        }
                    }
                };

                // Datos para enviar
                var params = "message_id=" + encodeURIComponent(messageId) +
                             "&description=" + encodeURIComponent(description);
                xhr.send(params);
            }
            return false; // Prevenir el envío del formulario de forma normal
        }
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

    <!-- Foro description section with creator info -->
    <div class="background-container" style="background-image: url('<?php echo htmlspecialchars($base_path_imagenes . $foro['imagen']); ?>'); background-size: cover;">
        <div class="forum-description-container">
            <div class="forum-description-header">
                <img src="<?php echo htmlspecialchars($base_path_imagenes_autor . $foro['url_foto_perfil']); ?>" alt="Profile Picture" class="forum-creator-avatar">
                <div class="forum-creator-info">
                    <span class="forum-creator-name"><?php echo htmlspecialchars($foro['username']); ?></span>
                    <span class="forum-creation-date"><?php echo htmlspecialchars(date('F j, Y H:i', strtotime($foro['fecha']))); ?></span>
                </div>
            </div>
            <h1><?php echo htmlspecialchars($foro['titulo']); ?></h1>
            <div class="foro-description">
                <p><?php echo htmlspecialchars($foro['descripcion']); ?></p>
            </div>
        </div>
    </div>

    <div class="messages-container">
        <?php if ($mensajes): ?>
            <?php foreach ($mensajes as $mensaje): ?>
                <?php 
                $isCurrentUser = $mensaje['username'] === ($_SESSION['username'] ?? '');
                $messageClass = $isCurrentUser ? 'message-right' : 'message-left';
                ?>
                <div class="message <?php echo $messageClass; ?>">
                    <div class="message-header">
                        <img class="user-avatar" src="<?php echo htmlspecialchars($base_path_imagenes_autor . $mensaje['url_foto_perfil'] ?? 'images/default-avatar.png'); ?>" alt="Foto de perfil">
                        <div class="user-info">
                            <span class="user-name"><?php echo htmlspecialchars($mensaje['username']); ?></span>
                            <span class="message-date"><?php echo htmlspecialchars(date('F j, Y H:i', strtotime($mensaje['fecha']))); ?></span>
                        </div>                       
                    </div>
                    <div class="content">
                        <p><?php echo htmlspecialchars($mensaje['texto']); ?></p>
                    </div>
                </div>
            <?php endforeach; ?>

        <?php else: ?>
            <p>No se encontraron mensajes en este foro.</p>
        <?php endif; ?>
    </div>

<?php pg_close($conn); ?>
</body>
</html>
