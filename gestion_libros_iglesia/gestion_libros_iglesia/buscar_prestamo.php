<?php
require_once 'db.php';

header('Content-Type: application/json');

if (!isset($_GET['codigo'])) {
    echo json_encode(['success' => false, 'message' => 'No se proporcionó código']);
    exit;
}

$codigo = trim($_GET['codigo']);

// Buscar préstamo activo por ISBN o código interno
$query = "SELECT p.*, l.titulo, l.autor, l.codigo_interno, l.isbn, l.año_publicacion,
                 lec.nombre, lec.apellido, lec.email, lec.telefono, lec.direccion,
                 DATEDIFF(p.fecha_devolucion, CURDATE()) as dias_restantes
          FROM prestamos p
          JOIN libros l ON p.id_libro = l.id
          LEFT JOIN lectores lec ON p.id_lector = lec.id
          WHERE p.devuelto = 0 
            AND (l.isbn = ? OR l.codigo_interno = ?)
          LIMIT 1";

$stmt = mysqli_prepare($link, $query);
mysqli_stmt_bind_param($stmt, 'ss', $codigo, $codigo);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if ($prestamo = mysqli_fetch_assoc($result)) {
    echo json_encode([
        'success' => true,
        'prestamo' => $prestamo
    ]);
} else {
    // Verificar si el libro existe pero no está prestado
    $query_libro = "SELECT * FROM libros WHERE isbn = ? OR codigo_interno = ? LIMIT 1";
    $stmt_libro = mysqli_prepare($link, $query_libro);
    mysqli_stmt_bind_param($stmt_libro, 'ss', $codigo, $codigo);
    mysqli_stmt_execute($stmt_libro);
    $result_libro = mysqli_stmt_get_result($stmt_libro);
    
    if (mysqli_num_rows($result_libro) > 0) {
        echo json_encode([
            'success' => false,
            'message' => 'El libro existe pero no está registrado como prestado actualmente.'
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'No se encontró ningún libro con ese código en el sistema.'
        ]);
    }
}
?>