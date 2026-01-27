<?php
// Evitar doble inicio de sesión
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

date_default_timezone_set('Europe/Rome');

class Database {
    private $link;
    
    public function __construct() {
        $this->link = mysqli_init();
        
        $success = mysqli_real_connect(
            $this->link,
            'localhost',
            'root',
            'root',
            'gestion_libros_elim_torino',
            3306
        );
        
        if (!$success) {
            die("Error de conexión: " . mysqli_connect_error());
        }
        
        mysqli_set_charset($this->link, "utf8mb4");
    }
    
    public function getConnection() {
        return $this->link;
    }
    
    public function query($sql, $params = []) {
        $stmt = mysqli_prepare($this->link, $sql);
        
        if (!empty($params)) {
            $types = '';
            foreach ($params as $param) {
                if (is_int($param)) {
                    $types .= 'i';
                } elseif (is_float($param)) {
                    $types .= 'd';
                } elseif (is_string($param)) {
                    $types .= 's';
                } else {
                    $types .= 'b';
                }
            }
            mysqli_stmt_bind_param($stmt, $types, ...$params);
        }
        
        mysqli_stmt_execute($stmt);
        return $stmt;
    }
    
    // ===== NUEVAS FUNCIONES PARA LOGIN =====
    
    // Verificar login
    public function verificarLogin($usuario, $password) {
        $stmt = $this->query(
            "SELECT id, username, password_hash, nombre_completo 
             FROM usuarios 
             WHERE username = ? AND activo = 1",
            [$usuario]
        );
        
        $result = mysqli_stmt_get_result($stmt);
        
        if ($user_data = mysqli_fetch_assoc($result)) {
            if (password_verify($password, $user_data['password_hash'])) {
                // Actualizar último login
                $this->query(
                    "UPDATE usuarios SET ultimo_login = NOW() WHERE id = ?",
                    [$user_data['id']]
                );
                
                // Registrar log simple
                $this->registrarLog(
                    $user_data['id'],
                    'login',
                    'sistema',
                    "Inició sesión exitosamente"
                );
                
                return $user_data;
            }
        }
        
        // Log de intento fallido
        $this->registrarLog(
            null,
            'login_fallido',
            'sistema',
            "Intento fallido para usuario: $usuario"
        );
        
        return false;
    }
    
    // Registrar actividad en logs (versión simple)
    public function registrarLog($usuario_id, $accion, $modulo, $descripcion) {
        $this->query(
            "INSERT INTO logs_actividad (usuario_id, accion, modulo, descripcion) 
             VALUES (?, ?, ?, ?)",
            [$usuario_id, $accion, $modulo, $descripcion]
        );
    }
    
    // Función para registrar acciones específicas (opcional)
    public function registrarAccion($accion, $modulo, $descripcion) {
        $usuario_id = $_SESSION['usuario_id'] ?? null;
        $this->registrarLog($usuario_id, $accion, $modulo, $descripcion);
    }
}

// ===== FUNCIONES GLOBALES =====

function verificarAutenticacion() {
    if (!isset($_SESSION['autenticado']) || $_SESSION['autenticado'] !== true) {
        header('Location: login/login.php');
        exit;
    }
}

function obtenerUsuarioActual() {
    if (isset($_SESSION['nombre_completo'])) {
        return [
            'id' => $_SESSION['usuario_id'] ?? null,
            'nombre' => $_SESSION['nombre_completo'],
            'username' => $_SESSION['username'] ?? ''
        ];
    }
    return null;
}

// ===== INSTANCIA GLOBAL =====

$db = new Database();
$link = $db->getConnection();
?>