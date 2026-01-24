<?php
// exportar_libros.php
session_start();
require_once 'db.php';

// Configurar headers para descarga Excel
header('Content-Type: application/vnd.ms-excel');
header('Content-Disposition: attachment; filename="catalogo_libros_' . date('Y-m-d') . '.xls"');
header('Pragma: no-cache');
header('Expires: 0');

// Obtener todos los libros activos con filtros aplicados
$busqueda = trim($_GET['busqueda'] ?? '');
$categoria_filtro = $_GET['categoria'] ?? '';
$stock_filtro = $_GET['stock'] ?? '';

// Construir consulta con filtros (igual que en catálogo)
$condiciones = ["l.activo = 1"];
$parametros = [];
$tipos = "";

if (!empty($busqueda)) {
    $condiciones[] = "(l.titulo LIKE ? OR l.autor LIKE ? OR l.codigo_interno LIKE ? OR l.isbn LIKE ?)";
    $parametros = array_merge($parametros, 
        ["%$busqueda%", "%$busqueda%", "%$busqueda%", "%$busqueda%"]);
    $tipos .= "ssss";
}

if (!empty($categoria_filtro) && is_numeric($categoria_filtro)) {
    $condiciones[] = "lc.id_categoria = ?";
    $parametros[] = $categoria_filtro;
    $tipos .= "i";
}

if ($stock_filtro === 'disponible') {
    $condiciones[] = "l.stock > 0";
} elseif ($stock_filtro === 'agotado') {
    $condiciones[] = "l.stock = 0";
}

$where_clause = !empty($condiciones) ? "WHERE " . implode(" AND ", $condiciones) : "";

// Obtener libros
$sql_libros = "SELECT l.*, GROUP_CONCAT(c.nombre SEPARATOR ', ') as categorias_nombres
               FROM libros l
               LEFT JOIN libro_categoria lc ON l.id = lc.id_libro
               LEFT JOIN categorias c ON lc.id_categoria = c.id
               $where_clause
               GROUP BY l.id
               ORDER BY l.titulo ASC";

if (!empty($parametros)) {
    $stmt_libros = $db->query($sql_libros, $parametros);
    $result_libros = mysqli_stmt_get_result($stmt_libros);
} else {
    $result_libros = mysqli_query($link, $sql_libros);
}

// Crear contenido Excel
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        table { border-collapse: collapse; width: 100%; }
        th { background-color: #4CAF50; color: white; font-weight: bold; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        tr:nth-child(even) { background-color: #f2f2f2; }
        .stock-bajo { color: #ff9800; font-weight: bold; }
        .stock-agotado { color: #f44336; font-weight: bold; }
        .stock-normal { color: #4CAF50; }
    </style>
</head>
<body>
    <h2>Catálogo de Libros - <?php echo date('d/m/Y H:i'); ?></h2>
    
    <!-- Información del reporte -->
    <table style="border: none; margin-bottom: 20px;">
        <tr>
            <td style="border: none;"><strong>Generado:</strong> <?php echo date('d/m/Y H:i'); ?></td>
            <td style="border: none;"><strong>Total de Libros:</strong> <?php echo mysqli_num_rows($result_libros); ?></td>
        </tr>
        <?php if (!empty($busqueda)): ?>
        <tr>
            <td style="border: none;" colspan="2"><strong>Búsqueda:</strong> "<?php echo htmlspecialchars($busqueda); ?>"</td>
        </tr>
        <?php endif; ?>
    </table>
    
    <!-- Tabla principal -->
    <table>
        <thead>
            <tr>
                <th>#</th>
                <th>Código Interno</th>
                <th>Título</th>
                <th>Autor</th>
                <th>ISBN</th>
                <th>Año</th>
                <th>Stock</th>
                <th>Categorías</th>
                <th>Fecha Creación</th>
                <th>Estado</th>
            </tr>
        </thead>
        <tbody>
            <?php 
            $contador = 1;
            $total_stock = 0;
            while ($libro = mysqli_fetch_assoc($result_libros)): 
                $total_stock += $libro['stock'];
                
                // Determinar clase de stock
                $clase_stock = '';
                $estado = '';
                if ($libro['stock'] == 0) {
                    $clase_stock = 'stock-agotado';
                    $estado = 'AGOTADO';
                } elseif ($libro['stock'] < 3) {
                    $clase_stock = 'stock-bajo';
                    $estado = 'BAJO';
                } else {
                    $clase_stock = 'stock-normal';
                    $estado = 'DISPONIBLE';
                }
            ?>
            <tr>
                <td><?php echo $contador++; ?></td>
                <td><?php echo htmlspecialchars($libro['codigo_interno']); ?></td>
                <td><?php echo htmlspecialchars($libro['titulo']); ?></td>
                <td><?php echo htmlspecialchars($libro['autor']); ?></td>
                <td><?php echo htmlspecialchars($libro['isbn'] ?? '-'); ?></td>
                <td><?php echo htmlspecialchars($libro['año_publicacion'] ?? '-'); ?></td>
                <td class="<?php echo $clase_stock; ?>"><?php echo $libro['stock']; ?></td>
                <td><?php echo htmlspecialchars($libro['categorias_nombres'] ?? 'Sin categorías'); ?></td>
                <td><?php echo date('d/m/Y', strtotime($libro['fecha_creacion'])); ?></td>
                <td><?php echo $estado; ?></td>
            </tr>
            <?php endwhile; ?>
            
            <!-- Fila de totales -->
            <tr style="background-color: #e8f5e8; font-weight: bold;">
                <td colspan="6" style="text-align: right;">TOTALES:</td>
                <td><?php echo $total_stock; ?> copias</td>
                <td colspan="3"></td>
            </tr>
        </tbody>
    </table>
    
    <!-- Resumen estadístico -->
    <table style="border: none; margin-top: 30px;">
        <tr>
            <td style="border: none; padding: 10px; background-color: #f1f8e9;">
                <strong>Resumen Estadístico</strong><br>
                Total de Libros: <?php echo mysqli_num_rows($result_libros); ?><br>
                Total en Stock: <?php echo $total_stock; ?> copias<br>
                Fecha de Exportación: <?php echo date('d/m/Y H:i'); ?>
            </td>
        </tr>
    </table>
    
    <!-- Pie de página -->
    <div style="margin-top: 40px; text-align: center; color: #666; font-size: 12px;">
        Generado por Sistema de Gestión de Libros - <?php echo date('Y'); ?>
    </div>
</body>
</html>