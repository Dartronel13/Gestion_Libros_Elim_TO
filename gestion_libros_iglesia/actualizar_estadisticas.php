<?php
// actualizar_estadisticas.php
require_once 'db.php';

header('Content-Type: application/json');

$estadisticas = [];

// Total de préstamos activos
$sql_activos = "SELECT COUNT(*) as total FROM prestamos WHERE devuelto = 0";
$result_activos = mysqli_query($link, $sql_activos);
$estadisticas['activos'] = mysqli_fetch_assoc($result_activos)['total'];

// Préstamos por vencer
$sql_por_vencer = "SELECT COUNT(*) as total FROM prestamos 
                   WHERE devuelto = 0 
                   AND fecha_devolucion <= DATE_ADD(CURDATE(), INTERVAL 3 DAY)
                   AND fecha_devolucion >= CURDATE()";
$result_por_vencer = mysqli_query($link, $sql_por_vencer);
$estadisticas['por_vencer'] = mysqli_fetch_assoc($result_por_vencer)['total'];

// Préstamos vencidos
$sql_vencidos = "SELECT COUNT(*) as total FROM prestamos 
                 WHERE devuelto = 0 
                 AND fecha_devolucion < CURDATE()";
$result_vencidos = mysqli_query($link, $sql_vencidos);
$estadisticas['vencidos'] = mysqli_fetch_assoc($result_vencidos)['total'];

echo json_encode($estadisticas);
?>