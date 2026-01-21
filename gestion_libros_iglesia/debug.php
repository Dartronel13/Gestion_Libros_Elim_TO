<?php
// Activar todos los errores
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Mostrar encabezado para identificar
echo "<h3>🔧 MODO DEBUG ACTIVADO</h3>";
echo "<hr>";

// Incluir tu db.php
require_once 'db.php';

echo "<div style='background:#e9ecef; padding:10px; margin:10px 0;'>";
echo "<strong>✅ db.php cargado correctamente</strong><br>";
echo "Conectado a BD: " . mysqli_get_host_info($link);
echo "</div>";

// Ahora incluir tu devolucion_libro.php
echo "<hr><h4>Probando devolucion_libro.php:</h4>";

// Capturar cualquier error
ob_start();
try {
    include 'devolucion_libro.php';
} catch (Exception $e) {
    echo "<div style='background:#f8d7da; color:#721c24; padding:15px;'>";
    echo "<strong>❌ ERROR CAPTURADO:</strong><br>";
    echo $e->getMessage();
    echo "</div>";
}

$output = ob_get_clean();
echo $output;
?>