<?php
require_once 'db.php';

header('Content-Type: application/json');

$query = "SELECT p.*, l.titulo, l.codigo_interno, l.isbn,
                 lec.nombre, lec.apellido,
                 DATEDIFF(p.fecha_devolucion, CURDATE()) as dias_restantes
          FROM prestamos p
          JOIN libros l ON p.id_libro = l.id
          LEFT JOIN lectores lec ON p.id_lector = lec.id
          WHERE p.devuelto = 0
          ORDER BY p.fecha_devolucion ASC
          LIMIT 20";

$result = mysqli_query($link, $query);
$prestamos = [];

while ($row = mysqli_fetch_assoc($result)) {
    $prestamos[] = $row;
}

echo json_encode($prestamos);
?>