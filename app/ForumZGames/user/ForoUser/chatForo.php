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


// Verificar si se ha recibido el ID del foro por GET
if (!isset($_GET['id_foro'])) {
    echo "ID de foro no proporcionado.";
    exit();
}

$base_path_imagenes = '../../imagenes/foros/';
$base_path_imagenes_autor = '../../imagenes/uploads/';

// Comprobar si el usuario ha iniciado sesión
$loggedIn = isset($_SESSION['username']);
$username = $loggedIn ? $_SESSION['username'] : null;

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
$query_mensajes = "SELECT m.id_mensaje, m.texto, m.fecha, m.mostrar, u.username, u.url_foto_perfil
                   FROM mensaje m 
                   JOIN usuario u ON m.id_usuario = u.username
                   WHERE m.id_foro = $1
                   ORDER BY m.fecha ASC";
$result_mensajes = pg_query_params($conn, $query_mensajes, array($id_foro));

if (!$result_mensajes) {
    echo "Error en la consulta de mensajes: " . pg_last_error($conn);
    exit();
}

$mensajes = pg_fetch_all($result_mensajes);

// Lógica para eliminar un mensaje
if ($loggedIn && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_message_id'])) {
    $message_id = $_POST['delete_message_id'];
    
    // Primero eliminar denuncias asociadas al mensaje
    $query_delete_denuncias = "
        DELETE FROM denuncia
        WHERE id_mensaje = $1
    ";

    $result_denuncias = pg_query_params($conn, $query_delete_denuncias, array($message_id));
    
    if ($result_denuncias) {
        // Luego eliminar el mensaje si el usuario es el autor
        $query_delete_message = "
            DELETE FROM mensaje
            WHERE id_mensaje = $1 AND id_usuario = $2
        ";

        $result_message = pg_query_params($conn, $query_delete_message, array($message_id, $_SESSION['username']));

        if ($result_message) {
            // Redirigir al foro después de la eliminación exitosa
            header("Location: chatForo.php?id_foro=" . $id_foro);
            exit();
        } else {
            //echo "Error al eliminar el mensaje.";
        }
    } else {
        //echo "Error al eliminar las denuncias asociadas.";
    }

    // Redirigir al foro incluso si hay errores
    header("Location: chatForo.php?id_foro=" . $id_foro);
    exit();
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($foro['titulo']); ?></title>
    <link rel="stylesheet" href="styleChatForo.css?v=<?php echo time(); ?>">
    <link href="https://fonts.googleapis.com/css2?family=Jersey+10&display=swap" rel="stylesheet">
    <link rel="icon" href="../.././imagenes/images/Logo_v1.png" type="image/x-icon">
    <script>
        function openDeleteModal(messageId) {
            document.getElementById('deleteMessageId').value = messageId;
            document.getElementById('deleteModal').style.display = 'block';
        }

        function closeDeleteModal() {
            document.getElementById('deleteModal').style.display = 'none';
        }
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
            $isCurrentUser = $loggedIn && ($mensaje['username'] === $username);
            $messageClass = $isCurrentUser ? 'message-right' : 'message-left';
            ?>
            <div class="message <?php echo $messageClass; ?>">
                <div class="message-header">
                    <img class="user-avatar" src="<?php echo htmlspecialchars($base_path_imagenes_autor . $mensaje['url_foto_perfil'] ?? 'images/default-avatar.png'); ?>" alt="Foto de perfil">
                    <div class="user-info">
                        <span class="user-name"><?php echo htmlspecialchars($mensaje['username']); ?></span>
                        <span class="message-date"><?php echo htmlspecialchars(date('F j, Y H:i', strtotime($mensaje['fecha']))); ?></span>
                    </div>
                    <?php if ($isCurrentUser && $mensaje['mostrar'] === 't'): // El usuario solo puede eliminar si el mensaje está activo ?>
                        <!-- Botón para eliminar el mensaje -->
                        <button type="button" class="delete-button" onclick="openDeleteModal(<?php echo $mensaje['id_mensaje']; ?>)">Eliminar</button>
                    <?php elseif ($mensaje['mostrar'] === 't'): ?>
                        <form id="reportForm-<?php echo $mensaje['id_mensaje']; ?>" onsubmit="return confirmReport(<?php echo $mensaje['id_mensaje']; ?>, '<?php echo htmlspecialchars($mensaje['texto']); ?>');" style="display:inline;">
                            <button type="button" class="report-button" onclick="confirmReport(<?php echo $mensaje['id_mensaje']; ?>, '<?php echo htmlspecialchars($mensaje['texto']); ?>')">Denunciar</button>
                        </form>
                    <?php endif; ?>
                </div>
                <div class="content">
                    <?php if ($mensaje['mostrar'] === 't'): ?>
                        <p><?php echo htmlspecialchars($mensaje['texto']); ?></p>
                    <?php else: ?>
                        <p><i>Este mensaje ha sido eliminado por un administrador</i></p>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
    <?php else: ?>
        <p>No se encontraron mensajes en este foro.</p>
    <?php endif; ?>
</div>
    
    <!-- Input Message Container -->
    <div class="message-input-container">
        <form id="messageForm" action="procesarMensaje.php" method="POST">
            <input type="hidden" name="id_foro" value="<?php echo htmlspecialchars($id_foro); ?>">
            <input type="hidden" name="id_usuario" value="<?php echo htmlspecialchars($_SESSION['username']); ?>">
            <input type="text" name="texto" placeholder="Escribe un Mensaje" required>
            <button type="submit" class="send-button">+</button>
        </form>
    </div>
    
    <!-- Modal de confirmación de eliminación -->
    <div id="deleteModal" class="modal" style="display:none;">
        <div class="modal-content">
            <span class="close" onclick="closeDeleteModal()">&times;</span>
            <h2>Confirmación de eliminación</h2>
            <p>¿Estás seguro de que quieres eliminar este mensaje?</p>
            <form method="POST" action="">
                <input type="hidden" id="deleteMessageId" name="delete_message_id">
                <button type="submit" class="delete-confirm-button">Eliminar</button>
                <button type="button" class="cancel-button" onclick="closeDeleteModal()">Cancelar</button>
            </form>
        </div>
    </div>

    <?php pg_close($conn); ?>
</body>
</html>