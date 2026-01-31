<?php
// buscar_libro_ajax.php
require_once 'db.php';

$codigo = $_GET['codigo'] ?? '';

// Limpiar el código (puede venir con espacios, guiones, etc.)
$codigo = trim($codigo);
$codigo = str_replace(['-', ' ', '_'], '', $codigo);

// Buscar por ISBN o código interno
$sql = "SELECT id, codigo_interno, titulo, autor, stock 
        FROM libros 
        WHERE REPLACE(REPLACE(REPLACE(isbn, '-', ''), ' ', ''), '_', '') = ? 
           OR REPLACE(REPLACE(REPLACE(codigo_interno, '-', ''), ' ', ''), '_', '') = ?
        LIMIT 1";

$stmt = $db->query($sql, [$codigo, $codigo]);
if ($stmt) {
    $result = mysqli_stmt_get_result($stmt);
    $libro = mysqli_fetch_assoc($result);
    
    if ($libro) {
        echo json_encode([
            'success' => true,
            'libro' => $libro
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Libro no encontrado'
        ]);
    }
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Error en la consulta'
    ]);
}
?>