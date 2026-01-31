<?php
require_once 'db.php';

header('Content-Type: application/json');

// Leer datos JSON del cuerpo de la petición
$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['id'])) {
    echo json_encode(['success' => false, 'message' => 'No se proporcionó ID de préstamo']);
    exit;
}

$prestamo_id = intval($data['id']);

// Iniciar transacción
mysqli_begin_transaction($link);

try {
    // 1. Obtener información del préstamo
    $query_prestamo = "SELECT id_libro FROM prestamos WHERE id = ? AND devuelto = 0";
    $stmt = mysqli_prepare($link, $query_prestamo);
    mysqli_stmt_bind_param($stmt, 'i', $prestamo_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    if (mysqli_num_rows($result) === 0) {
        throw new Exception('El préstamo no existe o ya fue devuelto');
    }
    
    $prestamo = mysqli_fetch_assoc($result);
    $libro_id = $prestamo['id_libro'];
    
    // 2. Marcar préstamo como devuelto
    $update_prestamo = "UPDATE prestamos SET devuelto = 1 WHERE id = ?";
    $stmt1 = mysqli_prepare($link, $update_prestamo);
    mysqli_stmt_bind_param($stmt1, 'i', $prestamo_id);
    
    if (!mysqli_stmt_execute($stmt1)) {
        throw new Exception('Error al actualizar el préstamo');
    }
    
    // 3. Incrementar stock del libro
    $update_libro = "UPDATE libros SET stock = stock + 1 WHERE id = ?";
    $stmt2 = mysqli_prepare($link, $update_libro);
    mysqli_stmt_bind_param($stmt2, 'i', $libro_id);
    
    if (!mysqli_stmt_execute($stmt2)) {
        throw new Exception('Error al actualizar el stock del libro');
    }
    
    // 4. Confirmar transacción
    mysqli_commit($link);
    
    echo json_encode([
        'success' => true,
        'message' => 'Devolución registrada exitosamente'
    ]);
    
} catch (Exception $e) {
    mysqli_rollback($link);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>