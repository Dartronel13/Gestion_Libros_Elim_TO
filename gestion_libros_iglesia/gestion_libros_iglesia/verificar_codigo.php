<?php
// verificar_codigo.php
require_once 'db.php';

header('Content-Type: application/json');

$codigo = $_GET['codigo'] ?? '';

if (empty($codigo)) {
    echo json_encode(['existe' => false]);
    exit;
}

$sql = "SELECT id, titulo FROM libros 
        WHERE codigo_interno = ? AND activo = 1 
        LIMIT 1";
$stmt = $db->query($sql, [$codigo]);
$result = mysqli_stmt_get_result($stmt);

if (mysqli_num_rows($result) > 0) {
    $libro = mysqli_fetch_assoc($result);
    echo json_encode([
        'existe' => true,
        'titulo' => $libro['titulo'],
        'id' => $libro['id']
    ]);
} else {
    echo json_encode(['existe' => false]);
}
?>