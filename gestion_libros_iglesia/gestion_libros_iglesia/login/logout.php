<?php
require_once __DIR__ . '/../db.php';

// Registrar logout
if (isset($_SESSION['usuario_id'])) {
    $db->registrarLog(
        $_SESSION['usuario_id'],
        'logout',
        'sistema',
        'Cierre de sesión'
    );
}

// Destruir sesión
session_destroy();

// Redirigir al login (que está en ESTA misma carpeta)
header('Location: login.php');  // ← Sin "../" porque logout.php está en /login/
exit;
?>