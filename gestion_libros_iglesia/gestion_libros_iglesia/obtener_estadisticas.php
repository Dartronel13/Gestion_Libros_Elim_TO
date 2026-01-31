<?php
require_once 'db.php';

header('Content-Type: application/json');

$query = "SELECT 
            SUM(CASE WHEN devuelto = 0 THEN 1 ELSE 0 END) as total_activos,
            SUM(CASE WHEN devuelto = 0 AND fecha_devolucion BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 3 DAY) THEN 1 ELSE 0 END) as por_vencer,
            SUM(CASE WHEN devuelto = 0 AND fecha_devolucion < CURDATE() THEN 1 ELSE 0 END) as vencidos
          FROM prestamos";

$result = mysqli_query($link, $query);
$estadisticas = mysqli_fetch_assoc($result);

echo json_encode($estadisticas);
?>