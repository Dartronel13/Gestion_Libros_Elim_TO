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
    <title>Login - Biblioteca Elim Torino</title>
    <style>
        * { box-sizing: border-box; }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f0f2f5;
            height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            margin: 0;
        }
        .login-container {
            background: white;
            padding: 2.5rem;
            border-radius: 12px;
            box-shadow: 0 8px 30px rgba(0,0,0,0.12);
            width: 100%;
            max-width: 420px;
            border: 1px solid #e1e5e9;
        }
        .logo {
            text-align: center;
            margin-bottom: 1.5rem;
            color: #2d3748;
        }
        .logo i {
            font-size: 2.5rem;
            color: #4f46e5;
            margin-bottom: 0.5rem;
            display: block;
        }
        .logo h1 {
            margin: 0;
            font-size: 1.5rem;
            font-weight: 600;
            color: #2d3748;
        }
        .logo p {
            margin: 0.25rem 0 0;
            color: #718096;
            font-size: 0.9rem;
        }
        h2 {
            color: #2d3748;
            text-align: center;
            margin: 0 0 1.5rem 0;
            font-size: 1.25rem;
            font-weight: 500;
        }
        .form-group {
            margin-bottom: 1.25rem;
        }
        label {
            display: block;
            margin-bottom: 0.5rem;
            color: #4a5568;
            font-weight: 500;
            font-size: 0.9rem;
        }
        
        /* CONTENEDOR DE INPUT CON ÍCONOS */
        .input-container {
            position: relative;
        }
        
        /* ÍCONO IZQUIERDO (usuario y candado) */
        .input-icon-left {
            position: absolute;
            left: 14px;
            top: 50%;
            transform: translateY(-50%);
            color: #a0aec0;
            z-index: 2;
            pointer-events: none;
        }
        
        /* BOTÓN DERECHO (ojo) */
        .password-toggle {
            position: absolute;
            right: 14px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: #718096;
            cursor: pointer;
            padding: 4px;
            z-index: 3;
            width: 24px;
            height: 24px;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: color 0.2s;
        }
        
        .password-toggle:hover {
            color: #4a5568;
        }
        
        .password-toggle:focus {
            outline: none;
            color: #4f46e5;
        }
        
        /* INPUTS */
        .form-input {
            width: 100%;
            padding: 0.75rem 1rem;
            padding-left: 42px; /* Espacio para ícono izquierdo */
            padding-right: 42px; /* Espacio para ícono derecho */
            border: 1px solid #cbd5e0;
            border-radius: 8px;
            font-size: 1rem;
            transition: all 0.2s;
            background: #f7fafc;
            height: 48px;
        }
        
        /* Input de usuario solo necesita padding izquierdo */
        #usuario {
            padding-right: 1rem;
        }
        
        /* Input de contraseña necesita ambos paddings */
        #contrasena {
            padding-left: 42px;
            padding-right: 42px;
        }
        
        .form-input:focus {
            outline: none;
            border-color: #4f46e5;
            background: white;
            box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.1);
        }
        
        .btn-login {
            width: 100%;
            padding: 0.875rem;
            background: #4f46e5;
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.2s;
            margin-top: 0.5rem;
        }
        .btn-login:hover {
            background: #4338ca;
        }
        .btn-login:active {
            transform: translateY(1px);
        }
        .error {
            color: #c53030;
            background: #fed7d7;
            padding: 0.75rem 1rem;
            border-radius: 8px;
            margin-bottom: 1.5rem;
            text-align: center;
            font-size: 0.9rem;
            border: 1px solid #fc8181;
        }
        .footer {
            text-align: center;
            margin-top: 1.5rem;
            color: #718096;
            font-size: 0.85rem;
            padding-top: 1rem;
            border-top: 1px solid #e2e8f0;
        }
    </style>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <div class="login-container">
        <div class="logo">
            <i class="fas fa-book"></i>
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