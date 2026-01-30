<?php
// imprimir_etiquetas.php - VERSIÓN CORREGIDA Y ALINEADA
session_start();

// VERIFICAR QUE HAY DATOS EN SESIÓN
if (!isset($_SESSION['etiquetas_para_imprimir'])) {
    $_SESSION['error_etiquetas'] = "No hay etiquetas para imprimir. Genere etiquetas primero.";
    header('Location: generar_etiquetas.php');
    exit;
}

$etiquetas = $_SESSION['etiquetas_para_imprimir'];
$libros = $etiquetas['libros'];
$config = $etiquetas['config'];

// Estilos según tamaño - AJUSTADOS PARA IMPRESIÓN EXACTA
$estilosTamano = [
    'small' => 'width: 63mm; height: 33mm;', // Un poco más grande para bordes
    'medium' => 'width: 73mm; height: 43mm;',
    'large' => 'width: 103mm; height: 63mm;'
];

$tamanoEstilo = $estilosTamano[$config['tamano']] ?? $estilosTamano['medium'];
$titulo_pagina = '';
$icono_titulo = '';

// CSS COMPACTO Y CORREGIDO
$pageStyles = '
<style>
/* ===== RESET GENERAL ===== */
* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}

/* ===== ESTILOS PANTALLA (VISTA PREVIA) ===== */
body.screen-preview {
    padding: 20px;
    background: #f0f2f5;
    font-family: "Segoe UI", Arial, sans-serif;
    min-height: 100vh;
}

/* Contenedor principal vista previa */
.preview-container {
    max-width: 1200px;
    margin: 0 auto;
}

/* Encabezado informativo */
.print-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 20px;
    border-radius: 10px;
    margin-bottom: 20px;
    box-shadow: 0 4px 6px rgba(0,0,0,0.1);
}

.print-header h2 {
    margin-bottom: 15px;
    display: flex;
    align-items: center;
    gap: 10px;
}

.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 15px;
    margin-top: 15px;
}

.stat-box {
    background: rgba(255,255,255,0.1);
    padding: 12px;
    border-radius: 8px;
    backdrop-filter: blur(10px);
}

/* Panel de instrucciones */
.instructions-panel {
    background: #e3f2fd;
    border-left: 4px solid #2196f3;
    padding: 20px;
    border-radius: 8px;
    margin-bottom: 25px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.05);
}

/* Botones */
.controls-container {
    background: white;
    padding: 20px;
    border-radius: 10px;
    margin-bottom: 25px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.05);
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.btn {
    padding: 12px 24px;
    border: none;
    border-radius: 6px;
    font-weight: 600;
    font-size: 14px;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    transition: all 0.3s ease;
    text-decoration: none;
}

.btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
}

.btn-back {
    background: #6c757d;
    color: white;
}

.btn-print {
    background: linear-gradient(135deg, #4CAF50 0%, #2E7D32 100%);
    color: white;
}

.btn-preview {
    background: linear-gradient(135deg, #2196F3 0%, #0D47A1 100%);
    color: white;
}

/* Contenedor etiquetas en pantalla */
.etiquetas-screen-container {
    background: white;
    padding: 30px;
    border-radius: 12px;
    box-shadow: 0 4px 20px rgba(0,0,0,0.08);
    margin-bottom: 30px;
}

/* Grid de etiquetas - AJUSTADO PARA ALINEACIÓN PERFECTA */
.etiquetas-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 15px;
    width: 100%;
}

/* Etiqueta individual */
.etiqueta {
    background: white;
    border: 1px solid #e0e0e0;
    border-radius: 4px;
    padding: 15px;
    text-align: center;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    transition: all 0.3s ease;
    overflow: hidden;
    position: relative;
}

.etiqueta:hover {
    border-color: #4CAF50;
    box-shadow: 0 4px 12px rgba(76, 175, 80, 0.2);
    transform: translateY(-3px);
}

/* Elementos dentro de la etiqueta */
.codigo-text {
    font-family: "Courier New", monospace;
    font-weight: bold;
    font-size: 14px;
    color: #333;
    margin-bottom: 8px;
    letter-spacing: 1px;
}

.barcode-img {
    max-width: 90%;
    height: 40px;
    margin: 8px 0;
    image-rendering: crisp-edges;
    object-fit: contain;
}

.titulo-text {
    font-size: 11px;
    font-weight: 600;
    color: #222;
    margin-top: 6px;
    line-height: 1.3;
    max-width: 95%;
    word-wrap: break-word;
}

.autor-text {
    font-size: 10px;
    color: #666;
    margin-top: 4px;
    font-style: italic;
}

.fecha-text {
    font-size: 9px;
    color: #999;
    margin-top: 6px;
    font-family: "Courier New", monospace;
}

/* Indicador de etiqueta (solo pantalla) */
.etiqueta::before {
    content: "✓ LISTA";
    position: absolute;
    top: 5px;
    right: 5px;
    background: #4CAF50;
    color: white;
    font-size: 8px;
    padding: 2px 6px;
    border-radius: 3px;
    opacity: 0;
    transition: opacity 0.3s;
}

.etiqueta:hover::before {
    opacity: 1;
}

/* ===== ESTILOS IMPRESIÓN (SOLO ETIQUETAS) ===== */
@media print {
    /* OCULTAR TODO EXCEPTO ETIQUETAS */
    body * {
        visibility: hidden;
    }
    
    /* MOSTRAR SOLO EL CONTENEDOR DE ETIQUETAS */
    .etiquetas-print-container,
    .etiquetas-print-container * {
        visibility: visible;
    }
    
    /* POSICIONAR SOLO PARA IMPRESIÓN */
    .etiquetas-print-container {
        position: absolute;
        left: 0;
        top: 0;
        width: 100%;
        padding: 0;
        margin: 0;
    }
    
    /* Grid para impresión - ALINEACIÓN EXACTA */
    .print-grid {
        display: grid !important;
        grid-template-columns: repeat(3, 1fr) !important;
        gap: 0mm !important;
        width: 100% !important;
        height: 100% !important;
        page-break-inside: avoid !important;
    }
    
    /* Etiqueta para impresión - SIN BORDES VISIBLES */
    .print-etiqueta {
        width: 63mm !important;
        height: 33mm !important;
        margin: 0 !important;
        padding: 2mm !important;
        border: none !important;
        background: white !important;
        display: flex !important;
        flex-direction: column !important;
        align-items: center !important;
        justify-content: center !important;
        page-break-inside: avoid !important;
        break-inside: avoid !important;
        -webkit-print-color-adjust: exact !important;
        print-color-adjust: exact !important;
        box-shadow: none !important;
        outline: 1px solid transparent !important; /* Para corte */
    }
    
    /* Ajustar tamaños según configuración */
    .print-etiqueta.medium {
        width: 73mm !important;
        height: 43mm !important;
    }
    
    .print-etiqueta.large {
        width: 103mm !important;
        height: 63mm !important;
    }
    
    /* Elementos de etiqueta en impresión */
    .print-codigo {
        font-family: "Courier New", monospace !important;
        font-weight: bold !important;
        font-size: 9pt !important;
        margin-bottom: 1mm !important;
    }
    
    .print-barcode {
        max-width: 85% !important;
        height: 15mm !important;
        margin: 1mm 0 !important;
        image-rendering: crisp-edges !important;
    }
    
    .print-titulo {
        font-size: 7pt !important;
        font-weight: bold !important;
        margin-top: 1mm !important;
        max-width: 95% !important;
        line-height: 1.1 !important;
    }
    
    .print-autor {
        font-size: 6pt !important;
        color: #666 !important;
        margin-top: 0.5mm !important;
    }
    
    .print-fecha {
        font-size: 5pt !important;
        color: #999 !important;
        margin-top: 1mm !important;
    }
    
    /* Configuración de página */
    @page {
        margin: 5mm !important;
        size: A4 portrait !important;
        padding: 0 !important;
        
        /* Eliminar encabezados del navegador */
        prince-shrink-to-fit: none;
        
        /* Chrome/Edge/Firefox */
        margin-top: 5mm !important;
        margin-bottom: 5mm !important;
        margin-left: 5mm !important;
        margin-right: 5mm !important;
    }
    
    /* Eliminar márgenes del body */
    body {
        margin: 0 !important;
        padding: 0 !important;
        background: white !important;
        width: 100% !important;
        height: 100% !important;
    }
    
    /* Salto de página controlado */
    .page-break {
        page-break-after: always !important;
        break-after: page !important;
    }
}

/* ===== RESPONSIVE ===== */
@media (max-width: 992px) {
    .etiquetas-grid {
        grid-template-columns: repeat(2, 1fr);
    }
}

@media (max-width: 768px) {
    .controls-container {
        flex-direction: column;
        gap: 15px;
    }
    
    .btn {
        width: 100%;
        justify-content: center;
    }
    
    .stats-grid {
        grid-template-columns: 1fr;
    }
}

@media (max-width: 576px) {
    .etiquetas-grid {
        grid-template-columns: 1fr;
    }
    
    body.screen-preview {
        padding: 10px;
    }
}
</style>';

ob_start();
?>

<!-- ===== VISTA PREVIA EN PANTALLA ===== -->
<div class="screen-preview">
    <div class="preview-container">
        
        <!-- ENCABEZADO -->
        <div class="print-header no-print">
            <h2><i class="fas fa-print"></i> Impresión de Etiquetas</h2>
            <div class="stats-grid">
                <div class="stat-box">
                    <i class="fas fa-calendar"></i> 
                    <strong>Fecha:</strong> <?php echo date('d/m/Y H:i', strtotime($etiquetas['fecha'])); ?>
                </div>
                <div class="stat-box">
                    <i class="fas fa-user"></i> 
                    <strong>Usuario:</strong> <?php echo htmlspecialchars($etiquetas['usuario']); ?>
                </div>
                <div class="stat-box">
                    <i class="fas fa-barcode"></i> 
                    <strong>Total Etiquetas:</strong> <?php echo count($libros); ?>
                </div>
                <div class="stat-box">
                    <i class="fas fa-ruler"></i> 
                    <strong>Tamaño:</strong> <?php echo ucfirst($config['tamano']); ?>
                </div>
            </div>
        </div>
        
        <!-- INSTRUCCIONES -->
        <div class="instructions-panel no-print">
            <h4><i class="fas fa-info-circle"></i> Instrucciones para Imprimir:</h4>
            <div class="row mt-3">
                <div class="col-md-6">
                    <ul>
                        <li>Use papel adhesivo A4</li>
                        <li>Configure márgenes en 5mm o menos</li>
                        <li>Imprimir a escala 100%</li>
                    </ul>
                </div>
                <div class="col-md-6">
                    <ul>
                        <li>Desactive "encabezados y pies de página"</li>
                        <li>Verifique la alineación con una hoja normal primero</li>
                        <li>Presione Ctrl+P para acceso rápido</li>
                    </ul>
                </div>
            </div>
        </div>
        
        <!-- CONTROLES -->
        <div class="controls-container no-print">
            <a href="generar_etiquetas.php" class="btn btn-back">
                <i class="fas fa-arrow-left"></i> Volver a Generar
            </a>
            <div class="d-flex gap-3">
                <button onclick="imprimirEtiquetasLimpias()" class="btn btn-print">
                    <i class="fas fa-print"></i> Imprimir Etiquetas
                </button>
            </div>
        </div>
        
        <!-- VISTA PREVIA DE ETIQUETAS -->
        <div class="etiquetas-screen-container no-print">
            <h4 class="mb-3"><i class="fas fa-tags"></i> Vista Previa de Etiquetas</h4>
            <div class="etiquetas-grid">
                <?php 
                if (file_exists('barcode_generator.php')) {
                    require_once 'barcode_generator.php';
                    $barcodeManager = new BarcodeManager();
                    
                    foreach (array_slice($libros, 0, 12) as $index => $libro): // Mostrar solo 12 en preview
                        $barcodeBase64 = $barcodeManager->generarBase64(
                            $libro['codigo_interno'],
                            $config['tipo_barcode'],
                            $config['alto_barcode'],
                            $config['ancho_barra']
                        );
                        
                        if (empty($barcodeBase64)) continue;
                ?>
                <div class="etiqueta" style="<?php echo $tamanoEstilo; ?>">
                    <?php if ($config['mostrar_codigo']): ?>
                    <div class="codigo-text"><?php echo htmlspecialchars($libro['codigo_interno']); ?></div>
                    <?php endif; ?>
                    
                    <img src="<?php echo $barcodeBase64; ?>" 
                         alt="<?php echo htmlspecialchars($libro['codigo_interno']); ?>" 
                         class="barcode-img">
                    
                    <?php if ($config['mostrar_titulo'] && !empty($libro['titulo'])): 
                        $tituloCorto = strlen($libro['titulo']) > 25 ? substr($libro['titulo'], 0, 22) . '...' : $libro['titulo'];
                    ?>
                    <div class="titulo-text"><?php echo htmlspecialchars($tituloCorto); ?></div>
                    <?php endif; ?>
                    
                    <?php if ($config['mostrar_autor'] && !empty($libro['autor'])): ?>
                    <div class="autor-text"><?php echo htmlspecialchars($libro['autor']); ?></div>
                    <?php endif; ?>
                    
                    <?php if ($config['mostrar_fecha']): ?>
                    <div class="fecha-text"><?php echo date('d/m/Y'); ?></div>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
                <?php } ?>
            </div>
            <?php if (count($libros) > 12): ?>
            <div class="text-center mt-3">
                <span class="badge bg-secondary">Mostrando 12 de <?php echo count($libros); ?> etiquetas</span>
            </div>
            <?php endif; ?>
        </div>
        
    </div>
</div>

<!-- ===== CONTENIDO PARA IMPRESIÓN (OCULTO EN PANTALLA) ===== -->
<div class="etiquetas-print-container" style="display: none;">
    <div class="print-grid">
        <?php 
        if (file_exists('barcode_generator.php')) {
            require_once 'barcode_generator.php';
            $barcodeManager = new BarcodeManager();
            
            foreach ($libros as $index => $libro):
                $barcodeBase64 = $barcodeManager->generarBase64(
                    $libro['codigo_interno'],
                    $config['tipo_barcode'],
                    $config['alto_barcode'],
                    $config['ancho_barra']
                );
                
                if (empty($barcodeBase64)) continue;
                
                // Determinar clase de tamaño
                $tamanoClass = $config['tamano'] ?? 'medium';
        ?>
        <div class="print-etiqueta <?php echo $tamanoClass; ?>">
            <?php if ($config['mostrar_codigo']): ?>
            <div class="print-codigo"><?php echo htmlspecialchars($libro['codigo_interno']); ?></div>
            <?php endif; ?>
            
            <img src="<?php echo $barcodeBase64; ?>" 
                 alt="<?php echo htmlspecialchars($libro['codigo_interno']); ?>" 
                 class="print-barcode">
            
            <?php if ($config['mostrar_titulo'] && !empty($libro['titulo'])): 
                $tituloCorto = strlen($libro['titulo']) > 30 ? substr($libro['titulo'], 0, 27) . '...' : $libro['titulo'];
            ?>
            <div class="print-titulo"><?php echo htmlspecialchars($tituloCorto); ?></div>
            <?php endif; ?>
            
            <?php if ($config['mostrar_autor'] && !empty($libro['autor'])): ?>
            <div class="print-autor"><?php echo htmlspecialchars($libro['autor']); ?></div>
            <?php endif; ?>
            
            <?php if ($config['mostrar_fecha']): ?>
            <div class="print-fecha"><?php echo date('d/m/Y'); ?></div>
            <?php endif; ?>
        </div>
        
        <?php 
                // Salto de página cada 21 etiquetas (3x7)
                if (($index + 1) % 21 === 0 && ($index + 1) < count($libros)):
        ?>
        <div class="page-break"></div>
        <?php 
                endif;
            endforeach;
        }
        ?>
    </div>
</div>

<!-- ===== SCRIPTS ===== -->
<script>
// Función para imprimir solo etiquetas (MODO LIMPIO)
function imprimirEtiquetasLimpias() {
    // Guardar estado actual
    const originalBodyClass = document.body.className;
    const originalDisplay = document.querySelector('.etiquetas-print-container').style.display;
    
    // Mostrar contenedor de impresión
    document.querySelector('.etiquetas-print-container').style.display = 'block';
    document.body.className = '';
    
    // Configurar eventos de impresión
    window.onbeforeprint = function() {
        // Configurar título limpio
        document.title = 'Etiquetas_' + new Date().toISOString().slice(0,10);
        
        // Eliminar cualquier encabezado del navegador
        const style = document.createElement('style');
        style.innerHTML = `
            @page { 
                margin: 5mm !important; 
                size: A4 portrait !important;
            }
            body { 
                margin: 0 !important; 
                padding: 0 !important; 
            }
        `;
        document.head.appendChild(style);
    };
    
    window.onafterprint = function() {
        // Restaurar estado
        document.querySelector('.etiquetas-print-container').style.display = originalDisplay;
        document.body.className = originalBodyClass;
        document.title = 'Imprimir Etiquetas';
        
        // Notificación
        alert('Impresión completada. Revise las etiquetas.');
    };
    
    // Ejecutar impresión
    window.print();
}

// Función para vista previa de impresión
function abrirVistaPreviaImpresion() {
    // Crear ventana de vista previa
    const previewWindow = window.open('', 'VistaPreviaEtiquetas', 
        'width=900,height=700,scrollbars=yes,resizable=yes');
    
    // Obtener contenido de impresión
    const printContent = document.querySelector('.etiquetas-print-container').innerHTML;
    
    // Crear página de vista previa
    previewWindow.document.write(`
        <!DOCTYPE html>
        <html>
        <head>
            <title>Vista Previa de Impresión</title>
            <style>
                body {
                    margin: 20px;
                    background: #f5f5f5;
                    font-family: Arial, sans-serif;
                }
                .preview-header {
                    background: #2196F3;
                    color: white;
                    padding: 15px;
                    border-radius: 8px;
                    margin-bottom: 20px;
                    text-align: center;
                }
                .print-preview-grid {
                    display: grid;
                    grid-template-columns: repeat(3, 1fr);
                    gap: 2mm;
                    background: white;
                    padding: 20px;
                    border-radius: 8px;
                    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
                }
                .preview-etiqueta {
                    width: ${$config['tamano'] === 'small' ? '63mm' : ($config['tamano'] === 'medium' ? '73mm' : '103mm')};
                    height: ${$config['tamano'] === 'small' ? '33mm' : ($config['tamano'] === 'medium' ? '43mm' : '63mm')};
                    border: 1px dashed #ccc;
                    padding: 2mm;
                    display: flex;
                    flex-direction: column;
                    align-items: center;
                    justify-content: center;
                }
                .controls {
                    text-align: center;
                    margin-top: 20px;
                }
                .btn-print {
                    padding: 12px 30px;
                    background: #4CAF50;
                    color: white;
                    border: none;
                    border-radius: 6px;
                    font-size: 16px;
                    cursor: pointer;
                }
            </style>
        </head>
        <body>
            <div class="preview-header">
                <h2>Vista Previa de Impresión</h2>
                <p>Esta es la vista exacta que se imprimirá</p>
            </div>
            <div class="print-preview-grid">
                ${printContent}
            </div>
            <div class="controls">
                <button onclick="window.print()" class="btn-print">
                    <i class="fas fa-print"></i> Imprimir desde Vista Previa
                </button>
                <button onclick="window.close()" style="margin-left:10px;padding:12px 30px;background:#666;color:white;border:none;border-radius:6px;cursor:pointer;">
                    Cerrar
                </button>
            </div>
        </body>
        </html>
    `);
    
    previewWindow.document.close();
}

// Atajo de teclado Ctrl+P
document.addEventListener('keydown', function(e) {
    if ((e.ctrlKey || e.metaKey) && e.key === 'p') {
        e.preventDefault();
        imprimirEtiquetasLimpias();
    }
});

// Inicializar
document.addEventListener('DOMContentLoaded', function() {
    console.log('Vista de impresión cargada. Etiquetas: <?php echo count($libros); ?>');
});
</script>

<?php
$contenido = ob_get_clean(); 
$GLOBALS['pageStyles'] = $pageStyles;
include 'layout.php';
?>