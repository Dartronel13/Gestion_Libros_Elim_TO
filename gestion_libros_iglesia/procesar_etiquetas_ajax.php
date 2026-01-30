<?php
// procesar_etiquetas_ajax.php - PROCESA PETICIONES AJAX
session_start();
require_once 'db.php';
require_once 'barcode_generator.php';

header('Content-Type: application/json');

// Verificar sesión
if (!isset($_SESSION['usuario_id'])) {
    echo json_encode(['success' => false, 'message' => 'No autorizado']);
    exit;
}

// Verificar que es POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
    exit;
}

// Verificar que se está generando etiquetas
if (!isset($_POST['generar_etiquetas'])) {
    echo json_encode(['success' => false, 'message' => 'Solicitud inválida']);
    exit;
}

// Obtener datos
$libros_json = $_POST['libros'] ?? '[]';
$config_json = $_POST['config'] ?? '{}';

$libros_ids = json_decode($libros_json, true);
$config = json_decode($config_json, true);

// Validar datos básicos
if (empty($libros_ids) || !is_array($libros_ids)) {
    echo json_encode(['success' => false, 'message' => 'No hay libros seleccionados']);
    exit;
}

$cantidad = intval($config['cantidad'] ?? 1);
if ($cantidad < 1 || $cantidad > 50) {
    echo json_encode(['success' => false, 'message' => 'Cantidad inválida']);
    exit;
}

try {
    // Conectar a la base de datos
    $db = new Database();
    
    // Obtener información de los libros
    $ids = array_map('intval', $libros_ids);
    $placeholders = str_repeat('?,', count($ids) - 1) . '?';
    
    $sql = "SELECT id, codigo_interno, titulo, autor FROM libros 
            WHERE id IN ($placeholders) AND activo = 1";
    $stmt = $db->query($sql, $ids);
    $result = $stmt->get_result();
    
    $libros_etiquetas = [];
    while ($row = $result->fetch_assoc()) {
        // Duplicar según cantidad
        for ($i = 0; $i < $cantidad; $i++) {
            $libros_etiquetas[] = $row;
        }
    }
    
    if (empty($libros_etiquetas)) {
        echo json_encode(['success' => false, 'message' => 'No se encontraron los libros seleccionados']);
        exit;
    }
    
    // Configuración completa (INCLUYENDO color_borde)
    $config_completa = [
        'tamano' => $config['tamano'] ?? 'medium',
        'tipo_barcode' => $config['tipo_barcode'] ?? 'C128',
        'color_borde' => $config['color_borde'] ?? '#cccccc',
        'mostrar_titulo' => !empty($config['mostrar_titulo']),
        'mostrar_autor' => !empty($config['mostrar_autor']),
        'mostrar_codigo' => !empty($config['mostrar_codigo']),
        'mostrar_fecha' => !empty($config['mostrar_fecha']),
        'alto_barcode' => 40,
        'ancho_barra' => 2
    ];
    
    // Guardar en sesión
    $_SESSION['etiquetas_para_imprimir'] = [
        'libros' => $libros_etiquetas,
        'config' => $config_completa,
        'fecha' => date('Y-m-d H:i:s'),
        'usuario' => $_SESSION['nombre_completo'] ?? 'Usuario'
    ];
    
    // Registrar en logs
    $db->registrarAccion(
        'generacion_etiquetas_ajax', 
        'etiquetas', 
        "Generadas " . count($libros_etiquetas) . " etiquetas para " . 
        count($libros_ids) . " libros via AJAX"
    );
    
    // Respuesta exitosa
    echo json_encode([
        'success' => true,
        'message' => 'Etiquetas generadas correctamente',
        'count_etiquetas' => count($libros_etiquetas),
        'count_libros' => count($libros_ids),
        'redirect_url' => 'imprimir_etiquetas.php'
    ]);
    
} catch (Exception $e) {
    error_log("Error en procesar_etiquetas_ajax.php: " . $e->getMessage());
    echo json_encode([
        'success' => false, 
        'message' => 'Error del servidor: ' . $e->getMessage()
    ]);
}
?>