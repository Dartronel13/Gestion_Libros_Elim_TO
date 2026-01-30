<?php
// exportar_libros.php - VERSIÓN PROFESIONAL CON LOGO

require_once 'db.php';
verificarAutenticacion();

// Cargar PhpSpreadsheet
require 'vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Color;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;
use PhpOffice\PhpSpreadsheet\Style\Font;
use PhpOffice\PhpSpreadsheet\Worksheet\PageSetup;

// Obtener parámetros de filtro
$busqueda = trim($_GET['busqueda'] ?? '');
$categoria_filtro = $_GET['categoria'] ?? '';
$stock_filtro = $_GET['stock'] ?? '';

// Registrar inicio de exportación
$db->registrarAccion(
    'inicio_exportacion', 
    'catalogo', 
    "Iniciando exportación de catálogo en formato profesional"
);

// Construir consulta con filtros
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

// Preparar y ejecutar consulta
if (!empty($parametros)) {
    $stmt_libros = $db->query($sql_libros, $parametros);
    $result_libros = mysqli_stmt_get_result($stmt_libros);
} else {
    $result_libros = mysqli_query($link, $sql_libros);
}

$num_libros = mysqli_num_rows($result_libros);

if ($num_libros === 0) {
    $db->registrarAccion(
        'exportacion_vacia', 
        'catalogo', 
        "Exportación cancelada - No hay libros que exportar"
    );
    header('Location: catalogo_libros.php?error_exportacion=1&mensaje=' . urlencode('No hay libros que exportar con los filtros aplicados.'));
    exit;
}

// ========== CREAR SPREADSHEET ==========
$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();
$sheet->setTitle('Catálogo de Libros');

// Configurar página para impresión
$sheet->getPageSetup()
    ->setOrientation(PageSetup::ORIENTATION_LANDSCAPE)
    ->setPaperSize(PageSetup::PAPERSIZE_A4)
    ->setFitToWidth(1)
    ->setFitToHeight(0);

// Margenes
$sheet->getPageMargins()
    ->setTop(0.5)
    ->setRight(0.5)
    ->setLeft(0.5)
    ->setBottom(0.5);

// ========== ESTILOS PERSONALIZADOS ==========
// Estilo para encabezado principal
$headerStyle = [
    'font' => [
        'bold' => true,
        'size' => 14,
        'color' => ['rgb' => 'FFFFFF']
    ],
    'fill' => [
        'fillType' => Fill::FILL_SOLID,
        'startColor' => ['rgb' => '2C3E50'] // Azul oscuro elegante
    ],
    'alignment' => [
        'horizontal' => Alignment::HORIZONTAL_CENTER,
        'vertical' => Alignment::VERTICAL_CENTER,
        'wrapText' => true
    ],
    'borders' => [
        'allBorders' => [
            'borderStyle' => Border::BORDER_MEDIUM,
            'color' => ['rgb' => '1A252F']
        ],
    ],
];

// Estilo para subtítulos
$subtitleStyle = [
    'font' => [
        'bold' => true,
        'size' => 11,
        'color' => ['rgb' => '34495E']
    ],
    'alignment' => [
        'horizontal' => Alignment::HORIZONTAL_LEFT,
    ]
];

// Estilo para encabezados de tabla
$tableHeaderStyle = [
    'font' => [
        'bold' => true,
        'color' => ['rgb' => '2C3E50'],
        'size' => 10,
    ],
    'fill' => [
        'fillType' => Fill::FILL_SOLID,
        'startColor' => ['rgb' => 'ECF0F1'] // Gris claro
    ],
    'alignment' => [
        'horizontal' => Alignment::HORIZONTAL_CENTER,
        'vertical' => Alignment::VERTICAL_CENTER,
    ],
    'borders' => [
        'allBorders' => [
            'borderStyle' => Border::BORDER_THIN,
            'color' => ['rgb' => 'BDC3C7']
        ],
    ],
];

// Estilo para datos de tabla
$tableDataStyle = [
    'font' => [
        'size' => 9,
    ],
    'alignment' => [
        'vertical' => Alignment::VERTICAL_CENTER,
        'wrapText' => true
    ],
    'borders' => [
        'allBorders' => [
            'borderStyle' => Border::BORDER_THIN,
            'color' => ['rgb' => 'ECF0F1']
        ],
    ],
];

// Estilo para resaltar (stock bajo)
$highlightStyle = [
    'fill' => [
        'fillType' => Fill::FILL_SOLID,
        'startColor' => ['rgb' => 'FFF3CD'] // Amarillo suave
    ],
    'font' => [
        'bold' => true,
        'color' => ['rgb' => '856404']
    ]
];

// Estilo para agotado
$warningStyle = [
    'fill' => [
        'fillType' => Fill::FILL_SOLID,
        'startColor' => ['rgb' => 'F8D7DA'] // Rojo suave
    ],
    'font' => [
        'bold' => true,
        'color' => ['rgb' => '721C24']
    ]
];

// Estilo para disponible
$successStyle = [
    'fill' => [
        'fillType' => Fill::FILL_SOLID,
        'startColor' => ['rgb' => 'D4EDDA'] // Verde suave
    ],
    'font' => [
        'bold' => true,
        'color' => ['rgb' => '155724']
    ]
];

// ========== ENCABEZADO CON LOGO ==========
$row = 1;

// Logo (si existe)
$logoPath = 'images/logo.png';
if (file_exists($logoPath)) {
    $drawing = new Drawing();
    $drawing->setName('Logo');
    $drawing->setDescription('Logo');
    $drawing->setPath($logoPath);
    $drawing->setHeight(60);
    $drawing->setCoordinates('A1');
    $drawing->setWorksheet($sheet);
    
    // Título al lado del logo
    $sheet->mergeCells('C1:H1');
    $sheet->setCellValue('C1', 'CATÁLOGO DE LIBROS');
    $sheet->getStyle('C1')->applyFromArray([
        'font' => [
            'bold' => true,
            'size' => 20,
            'color' => ['rgb' => '2C3E50']
        ],
        'alignment' => [
            'horizontal' => Alignment::HORIZONTAL_CENTER,
            'vertical' => Alignment::VERTICAL_CENTER
        ]
    ]);
    
    $sheet->mergeCells('C2:H2');
    $sheet->setCellValue('C2', 'Sistema de Gestión Bibliotecaria');
    $sheet->getStyle('C2')->applyFromArray([
        'font' => [
            'size' => 12,
            'color' => ['rgb' => '7F8C8D']
        ],
        'alignment' => [
            'horizontal' => Alignment::HORIZONTAL_CENTER
        ]
    ]);
    
    // Información de exportación
    $sheet->setCellValue('I1', 'Reporte No:');
    $sheet->setCellValue('J1', 'CAT-' . date('Ymd-His'));
    $sheet->getStyle('I1:J1')->applyFromArray($subtitleStyle);
    
    $sheet->setCellValue('I2', 'Fecha:');
    $sheet->setCellValue('J2', date('d/m/Y H:i:s'));
    $sheet->getStyle('I2:J2')->applyFromArray($subtitleStyle);
    
    $row = 4; // Saltar filas para el logo
} else {
    // Sin logo
    $sheet->mergeCells('A1:J1');
    $sheet->setCellValue('A1', 'CATÁLOGO DE LIBROS');
    $sheet->getStyle('A1')->applyFromArray([
        'font' => [
            'bold' => true,
            'size' => 20,
            'color' => ['rgb' => '2C3E50']
        ],
        'alignment' => [
            'horizontal' => Alignment::HORIZONTAL_CENTER
        ]
    ]);
    
    $sheet->mergeCells('A2:J2');
    $sheet->setCellValue('A2', 'Sistema de Gestión Bibliotecaria');
    $sheet->getStyle('A2')->applyFromArray([
        'font' => [
            'size' => 14,
            'color' => ['rgb' => '7F8C8D']
        ],
        'alignment' => [
            'horizontal' => Alignment::HORIZONTAL_CENTER
        ]
    ]);
    
    $row = 4;
}

// ========== INFORMACIÓN DEL REPORTE ==========
$infoRow = $row;
$sheet->mergeCells('A' . $infoRow . ':J' . $infoRow);
$sheet->setCellValue('A' . $infoRow, 'INFORMACIÓN DEL REPORTE');
$sheet->getStyle('A' . $infoRow)->applyFromArray($headerStyle);
$sheet->getRowDimension($infoRow)->setRowHeight(25);

$row++;

// Filas de información
$sheet->setCellValue('A' . $row, 'Total de libros:');
$sheet->setCellValue('B' . $row, $num_libros);
$sheet->getStyle('A' . $row)->applyFromArray($subtitleStyle);
$sheet->getStyle('B' . $row)->getFont()->setBold(true);

$sheet->setCellValue('D' . $row, 'Generado por:');
$sheet->setCellValue('E' . $row, $_SESSION['nombre_usuario'] ?? 'Sistema');
$sheet->getStyle('D' . $row)->applyFromArray($subtitleStyle);

$sheet->setCellValue('G' . $row, 'ID Reporte:');
$sheet->setCellValue('H' . $row, 'CAT-' . date('Ymd-His'));
$sheet->getStyle('G' . $row)->applyFromArray($subtitleStyle);

$row++;

if (!empty($busqueda)) {
    $sheet->setCellValue('A' . $row, 'Búsqueda aplicada:');
    $sheet->setCellValue('B' . $row, '"' . $busqueda . '"');
    $sheet->getStyle('A' . $row)->applyFromArray($subtitleStyle);
    $row++;
}

if (!empty($categoria_filtro) && is_numeric($categoria_filtro)) {
    $sql_cat = "SELECT nombre FROM categorias WHERE id = ?";
    $stmt_cat = $db->query($sql_cat, [$categoria_filtro]);
    $result_cat = mysqli_stmt_get_result($stmt_cat);
    $categoria = mysqli_fetch_assoc($result_cat);
    
    $sheet->setCellValue('A' . $row, 'Categoría:');
    $sheet->setCellValue('B' . $row, $categoria['nombre'] ?? 'Desconocida');
    $sheet->getStyle('A' . $row)->applyFromArray($subtitleStyle);
    $row++;
}

if (!empty($stock_filtro)) {
    $sheet->setCellValue('A' . $row, 'Filtro de stock:');
    $sheet->setCellValue('B' . $row, ($stock_filtro == 'disponible') ? 'Solo disponibles' : 'Solo agotados');
    $sheet->getStyle('A' . $row)->applyFromArray($subtitleStyle);
    $row++;
}

$row++; // Espacio

// ========== ENCABEZADOS DE TABLA ==========
$headerRow = $row;

// Definir columnas
$columns = [
    'No.' => 6,
    'CÓDIGO' => 15,
    'TÍTULO' => 40,
    'AUTOR' => 25,
    'ISBN' => 18,
    'AÑO' => 8,
    'STOCK' => 10,
    'CATEGORÍAS' => 30,
    'ESTADO' => 12,
    'OBSERVACIONES' => 25
];

$col = 'A';
foreach ($columns as $title => $width) {
    $sheet->setCellValue($col . $headerRow, $title);
    $sheet->getColumnDimension($col)->setWidth($width);
    $col++;
}

// Aplicar estilo a encabezados
$sheet->getStyle('A' . $headerRow . ':J' . $headerRow)->applyFromArray($tableHeaderStyle);
$sheet->getRowDimension($headerRow)->setRowHeight(25);

// ========== DATOS DE LOS LIBROS ==========
$dataRow = $headerRow + 1;
$contador = 1;
$total_stock = 0;
$disponibles = 0;
$agotados = 0;
$bajo_stock = 0;

while ($libro = mysqli_fetch_assoc($result_libros)) {
    $total_stock += $libro['stock'];
    
    // Determinar estado y estilo
    $estado = '';
    $estiloFila = $tableDataStyle;
    
    if ($libro['stock'] == 0) {
        $estado = 'AGOTADO';
        $estiloFila = array_merge($tableDataStyle, $warningStyle);
        $agotados++;
    } elseif ($libro['stock'] < 3) {
        $estado = 'BAJO STOCK';
        $estiloFila = array_merge($tableDataStyle, $highlightStyle);
        $bajo_stock++;
        $disponibles++;
    } else {
        $estado = 'DISPONIBLE';
        $estiloFila = array_merge($tableDataStyle, $successStyle);
        $disponibles++;
    }
    
    // Llenar datos
    $sheet->setCellValue('A' . $dataRow, $contador);
    $sheet->setCellValue('B' . $dataRow, $libro['codigo_interno']);
    $sheet->setCellValue('C' . $dataRow, $libro['titulo']);
    $sheet->setCellValue('D' . $dataRow, $libro['autor']);
    $sheet->setCellValue('E' . $dataRow, $libro['isbn'] ?: '-');
    $sheet->setCellValue('F' . $dataRow, $libro['año_publicacion'] ?: '-');
    $sheet->setCellValue('G' . $dataRow, $libro['stock']);
    $sheet->setCellValue('H' . $dataRow, $libro['categorias_nombres'] ?: 'Sin categorías');
    $sheet->setCellValue('I' . $dataRow, $estado);
    $sheet->setCellValue('J' . $dataRow, ''); // Observaciones (vacío para que el usuario complete)
    
    // Aplicar estilo a toda la fila
    $sheet->getStyle('A' . $dataRow . ':J' . $dataRow)->applyFromArray($estiloFila);
    
    // Centrar columnas específicas
    $sheet->getStyle('A' . $dataRow . ':A' . $dataRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
    $sheet->getStyle('F' . $dataRow . ':G' . $dataRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
    $sheet->getStyle('I' . $dataRow . ':I' . $dataRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
    
    $contador++;
    $dataRow++;
    
    // Alternar colores para mejor lectura (filas alternas)
    if ($contador % 2 == 0) {
        $sheet->getStyle('A' . $dataRow . ':J' . $dataRow)->getFill()->setFillType(Fill::FILL_SOLID);
        $sheet->getStyle('A' . $dataRow . ':J' . $dataRow)->getFill()->getStartColor()->setARGB('F8F9FA');
    }
}

$lastDataRow = $dataRow - 1;

// ========== TOTALES ==========
$totalsRow = $dataRow + 1;
$sheet->mergeCells('A' . $totalsRow . ':F' . $totalsRow);
$sheet->setCellValue('A' . $totalsRow, 'TOTALES:');
$sheet->getStyle('A' . $totalsRow)->applyFromArray([
    'font' => ['bold' => true, 'size' => 11],
    'alignment' => ['horizontal' => Alignment::HORIZONTAL_RIGHT]
]);

$sheet->setCellValue('G' . $totalsRow, '=SUM(G' . ($headerRow + 1) . ':G' . $lastDataRow . ')');
$sheet->getStyle('G' . $totalsRow)->applyFromArray([
    'font' => ['bold' => true, 'size' => 11],
    'fill' => [
        'fillType' => Fill::FILL_SOLID,
        'startColor' => ['rgb' => '2C3E50']
    ],
    'font' => [
        'bold' => true,
        'color' => ['rgb' => 'FFFFFF']
    ],
    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER]
]);

$sheet->setCellValue('H' . $totalsRow, $num_libros . ' libros');
$sheet->getStyle('H' . $totalsRow)->applyFromArray([
    'font' => ['bold' => true],
    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER]
]);

$sheet->mergeCells('I' . $totalsRow . ':J' . $totalsRow);
$sheet->setCellValue('I' . $totalsRow, 'Total general');
$sheet->getStyle('I' . $totalsRow)->applyFromArray([
    'font' => ['bold' => true],
    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER]
]);

// ========== RESUMEN ESTADÍSTICO ==========
$summaryRow = $totalsRow + 3;

// Encabezado del resumen
$sheet->mergeCells('A' . $summaryRow . ':J' . $summaryRow);
$sheet->setCellValue('A' . $summaryRow, 'RESUMEN ESTADÍSTICO');
$sheet->getStyle('A' . $summaryRow)->applyFromArray($headerStyle);
$sheet->getRowDimension($summaryRow)->setRowHeight(25);

$summaryRow++;

// Crear tabla de resumen
$summaryData = [
    ['📊 ESTADÍSTICAS', 'CANTIDAD', 'PORCENTAJE'],
    ['Libros disponibles', $disponibles, ($num_libros > 0 ? round(($disponibles / $num_libros) * 100, 1) : 0) . '%'],
    ['- Con stock normal', $disponibles - $bajo_stock, ($num_libros > 0 ? round((($disponibles - $bajo_stock) / $num_libros) * 100, 1) : 0) . '%'],
    ['- Con stock bajo', $bajo_stock, ($num_libros > 0 ? round(($bajo_stock / $num_libros) * 100, 1) : 0) . '%'],
    ['Libros agotados', $agotados, ($num_libros > 0 ? round(($agotados / $num_libros) * 100, 1) : 0) . '%'],
    ['Total copias en stock', $total_stock . ' copias', ''],
    ['Promedio stock/libro', ($num_libros > 0 ? number_format($total_stock / $num_libros, 1) : 0) . ' copias', '']
];

$startSummaryRow = $summaryRow;
foreach ($summaryData as $index => $rowData) {
    $sheet->setCellValue('A' . $summaryRow, $rowData[0]);
    $sheet->setCellValue('B' . $summaryRow, $rowData[1]);
    $sheet->setCellValue('C' . $summaryRow, $rowData[2]);
    
    if ($index == 0) {
        // Encabezado de la tabla de resumen
        $sheet->getStyle('A' . $summaryRow . ':C' . $summaryRow)->applyFromArray($tableHeaderStyle);
    } else {
        // Datos del resumen
        $style = $tableDataStyle;
        if ($rowData[0] == 'Libros disponibles') {
            $style = array_merge($style, $successStyle);
        } elseif ($rowData[0] == 'Libros agotados') {
            $style = array_merge($style, $warningStyle);
        } elseif (strpos($rowData[0], 'stock bajo') !== false) {
            $style = array_merge($style, $highlightStyle);
        }
        
        $sheet->getStyle('A' . $summaryRow . ':C' . $summaryRow)->applyFromArray($style);
        $sheet->getStyle('B' . $summaryRow . ':C' . $summaryRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
    }
    
    $summaryRow++;
}

$endSummaryRow = $summaryRow - 1;
$sheet->getStyle('A' . $startSummaryRow . ':C' . $endSummaryRow)->applyFromArray([
    'borders' => [
        'allBorders' => [
            'borderStyle' => Border::BORDER_THIN,
            'color' => ['rgb' => 'BDC3C7']
        ],
    ],
]);

// Ajustar ancho de columnas del resumen
$sheet->getColumnDimension('A')->setWidth(30);
$sheet->getColumnDimension('B')->setWidth(15);
$sheet->getColumnDimension('C')->setWidth(15);

// ========== FIRMAS Y VALIDACIÓN ==========
$signatureRow = $summaryRow + 3;

$sheet->mergeCells('A' . $signatureRow . ':C' . $signatureRow);
$sheet->setCellValue('A' . $signatureRow, '___________________________________');
$sheet->getStyle('A' . $signatureRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

$sheet->mergeCells('A' . ($signatureRow + 1) . ':C' . ($signatureRow + 1));
$sheet->setCellValue('A' . ($signatureRow + 1), 'Responsable de Biblioteca');
$sheet->getStyle('A' . ($signatureRow + 1))->applyFromArray($subtitleStyle);
$sheet->getStyle('A' . ($signatureRow + 1))->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

$sheet->mergeCells('H' . $signatureRow . ':J' . $signatureRow);
$sheet->setCellValue('H' . $signatureRow, '___________________________________');
$sheet->getStyle('H' . $signatureRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

$sheet->mergeCells('H' . ($signatureRow + 1) . ':J' . ($signatureRow + 1));
$sheet->setCellValue('H' . ($signatureRow + 1), 'Fecha de validación');
$sheet->getStyle('H' . ($signatureRow + 1))->applyFromArray($subtitleStyle);
$sheet->getStyle('H' . ($signatureRow + 1))->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

// ========== PIE DE PÁGINA ==========
$sheet->getHeaderFooter()
    ->setOddFooter('&L&P / &N páginas &RGenerado el: &D &T');

// ========== CONGELAR PANELES (HEADERS VISIBLES AL SCROLL) ==========
$sheet->freezePane('A' . ($headerRow + 1));

// ========== GUARDAR Y DESCARGAR ==========
// Configurar headers
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment; filename="Catalogo_Libros_' . date('Y-m-d') . '.xlsx"');
header('Cache-Control: max-age=0');
header('Pragma: public');

// Crear writer
$writer = new Xlsx($spreadsheet);
$writer->save('php://output');

// Registrar exportación exitosa
$db->registrarAccion(
    'exportacion_exitosa', 
    'catalogo', 
    "Exportación profesional completada - {$num_libros} libros exportados"
);

// Cerrar recursos
if (isset($stmt_libros)) mysqli_stmt_close($stmt_libros);
if (isset($stmt_cat)) mysqli_stmt_close($stmt_cat);
if (isset($result_libros)) mysqli_free_result($result_libros);

exit;