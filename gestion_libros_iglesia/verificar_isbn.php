<?php
// verificar_isbn.php
require_once 'db.php';

header('Content-Type: application/json');

$isbn = $_GET['isbn'] ?? '';

if (empty($isbn)) {
    echo json_encode(['existe' => false]);
    exit;
}

// Limpiar guiones del ISBN para búsqueda
$isbn_limpio = str_replace('-', '', $isbn);

$sql = "SELECT id, titulo, codigo_interno FROM libros 
        WHERE REPLACE(isbn, '-', '') = ? AND activo = 1 
        LIMIT 1";
$stmt = $db->query($sql, [$isbn_limpio]);
$result = mysqli_stmt_get_result($stmt);

if (mysqli_num_rows($result) > 0) {
    $libro = mysqli_fetch_assoc($result);
    echo json_encode([
        'existe' => true,
        'titulo' => $libro['titulo'],
        'codigo' => $libro['codigo_interno'],
        'id' => $libro['id']
    ]);
} else {
    echo json_encode(['existe' => false]);
}
?>