<?php
require_once __DIR__ . '/../db.php';

// Si ya está autenticado, redirigir a la raíz
if (isset($_SESSION['autenticado']) && $_SESSION['autenticado'] === true) {
    header('Location: ../index.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $usuario = trim($_POST['usuario'] ?? '');
    $password = $_POST['contrasena'] ?? '';
    
    if (empty($usuario) || empty($password)) {
        $error = "Por favor ingresa usuario y contraseña";
    } else {
        $usuario_data = $db->verificarLogin($usuario, $password);
        
        if ($usuario_data) {
            $_SESSION['autenticado'] = true;
            $_SESSION['usuario_id'] = $usuario_data['id'];
            $_SESSION['username'] = $usuario_data['username'];
            $_SESSION['nombre_completo'] = $usuario_data['nombre_completo'];
            
            header('Location: ../index.php');
            exit;
        } else {
            $error = "Usuario o contraseña incorrectos";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../css/login-style.css">
    <link rel="icon" type="image/png" href="../images/logo.png">
    <title>Login - Biblioteca Elim Torino</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <div class="login-container">
        <div class="logo">
            <?php
            $logo_path = '../images/logo.png';
            $absolute_path = __DIR__ . '/../images/logo.png';
            
            if (file_exists($absolute_path)):
            ?>
                <img src="<?php echo $logo_path; ?>" 
                     alt="Logo Iglesia Elim Torino" 
                     class="login-logo">
            <?php else: ?>
                <i class="fas fa-book"></i>
            <?php endif; ?>
            <h1>Biblioteca Elim Torino</h1>
            <p>Sistema de Gestión Bibliotecaria</p>
        </div>
        
        <h2>Acceso al Sistema</h2>
        
        <?php if (!empty($error)): ?>
            <div class="error">
                <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>
        
        <form method="POST" action="">
            <!-- CAMPO USUARIO -->
            <div class="form-group">
                <label for="usuario">Usuario</label>
                <div class="input-container">
                    <i class="fas fa-user input-icon-left"></i>
                    <input type="text" 
                           id="usuario" 
                           name="usuario" 
                           class="form-input" 
                           required 
                           placeholder="Ingresa tu usuario"
                           autocomplete="username"
                           autofocus>
                </div>
            </div>
            
            <!-- CAMPO CONTRASEÑA CON OJO -->
            <div class="form-group">
                <label for="contrasena">Contraseña</label>
                <div class="input-container">
                    <i class="fas fa-lock input-icon-left"></i>
                    <input type="password" 
                           id="contrasena" 
                           name="contrasena" 
                           class="form-input" 
                           required 
                           placeholder="Ingresa tu contraseña"
                           autocomplete="current-password">
                    <button type="button" 
                            class="password-toggle" 
                            id="togglePassword"
                            aria-label="Mostrar contraseña">
                        <i class="fas fa-eye"></i>
                    </button>
                </div>
            </div>
            
            <button type="submit" class="btn-login">
                <i class="fas fa-sign-in-alt"></i> Iniciar Sesión
            </button>
        </form>
        
        <div class="footer">
            <i class="fas fa-info-circle"></i> Acceso restringido al personal autorizado
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const usuarioInput = document.getElementById('usuario');
            const contrasenaInput = document.getElementById('contrasena');
            const togglePassword = document.getElementById('togglePassword');
            const eyeIcon = togglePassword.querySelector('i');
            
            // Función para mostrar/ocultar contraseña
            function togglePasswordVisibility() {
                const type = contrasenaInput.getAttribute('type') === 'password' ? 'text' : 'password';
                contrasenaInput.setAttribute('type', type);
                
                // Cambiar icono
                if (type === 'text') {
                    eyeIcon.classList.remove('fa-eye');
                    eyeIcon.classList.add('fa-eye-slash');
                    togglePassword.setAttribute('aria-label', 'Ocultar contraseña');
                    togglePassword.setAttribute('title', 'Ocultar contraseña');
                } else {
                    eyeIcon.classList.remove('fa-eye-slash');
                    eyeIcon.classList.add('fa-eye');
                    togglePassword.setAttribute('aria-label', 'Mostrar contraseña');
                    togglePassword.setAttribute('title', 'Mostrar contraseña');
                }
            }
            
            // Evento click en el botón del ojo
            togglePassword.addEventListener('click', togglePasswordVisibility);
            
            // También funcionar con teclado
            togglePassword.addEventListener('keydown', function(e) {
                if (e.key === 'Enter' || e.key === ' ') {
                    e.preventDefault();
                    togglePasswordVisibility();
                }
            });
            
            // Si hay error, mantener el usuario escrito
            <?php if (isset($_POST['usuario'])): ?>
                usuarioInput.value = <?php echo json_encode($_POST['usuario']); ?>;
                // Enfocar en contraseña si ya hay usuario
                contrasenaInput.focus();
            <?php endif; ?>
            
            // Prevenir envío doble del formulario
            const form = document.querySelector('form');
            form.addEventListener('submit', function() {
                const submitBtn = form.querySelector('button[type="submit"]');
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Verificando...';
            });
            
            // Enfoque automático
            if (!usuarioInput.value.trim()) {
                usuarioInput.focus();
            }
            
            // Mejorar accesibilidad
            togglePassword.setAttribute('role', 'button');
            togglePassword.setAttribute('tabindex', '0');
        });
    </script>
</body>
</html>