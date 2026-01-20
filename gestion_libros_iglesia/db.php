<?php
class Database {
    private $link;
    
    public function __construct() {
        $this->link = mysqli_init();
        
        $success = mysqli_real_connect(
            $this->link,
            'localhost',
            'root',
            'root',
            'gestion_libros_elim_torino',  // ← Cambiado a tu nueva BD
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
            $types = str_repeat('s', count($params));
            mysqli_stmt_bind_param($stmt, $types, ...$params);
        }
        
        mysqli_stmt_execute($stmt);
        return $stmt;
    }
}

// Uso global
$db = new Database();
$link = $db->getConnection();
?>
