<?php
// ajax_generar_vista_previa.php
require_once 'barcode_generator.php';

header('Content-Type: text/html; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $libro = [
        'codigo_interno' => $_POST['codigo'] ?? 'TEST001',
        'titulo' => $_POST['titulo'] ?? 'Libro de Prueba',
        'autor' => $_POST['autor'] ?? 'Autor Desconocido'
    ];
    
    $config = [
        'tamano' => $_POST['tamano'] ?? 'medium',
        'tipo_barcode' => $_POST['tipo_barcode'] ?? 'C128',
        'mostrar_titulo' => isset($_POST['mostrar_titulo']) && $_POST['mostrar_titulo'] === '1',
        'mostrar_autor' => isset($_POST['mostrar_autor']) && $_POST['mostrar_autor'] === '1',
        'mostrar_codigo' => isset($_POST['mostrar_codigo']) && $_POST['mostrar_codigo'] === '1',
        'color_borde' => '#cccccc',
        'alto_barcode' => 40,
        'ancho_barra' => 2
    ];
    
    $barcodeManager = new BarcodeManager();
    echo $barcodeManager->generarEtiquetaHTML($libro, $config);
}
?>