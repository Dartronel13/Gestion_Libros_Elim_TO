<?php
// imprimir_etiquetas.php - VERSIÓN PULIDA Y SIMPLIFICADA
session_start();

// VERIFICAR QUE HAY DATOS EN SESIÓN
if (!isset($_SESSION['etiquetas_para_imprimir'])) {
    // Si no hay datos, redirigir con mensaje
    $_SESSION['error_etiquetas'] = "No hay etiquetas para imprimir. Genere etiquetas primero.";
    header('Location: generar_etiquetas.php');
    exit;
}

$etiquetas = $_SESSION['etiquetas_para_imprimir'];
$libros = $etiquetas['libros'];
$config = $etiquetas['config'];

// Calcular disposición para impresión
$etiquetasPorFila = 3;
$totalEtiquetas = count($libros);

// Determinar estilos según tamaño
$estilosTamano = [
    'small' => 'width: 60mm; height: 30mm;',
    'medium' => 'width: 70mm; height: 40mm;',
    'large' => 'width: 100mm; height: 60mm;'
];

$tamanoEstilo = $estilosTamano[$config['tamano']] ?? $estilosTamano['medium'];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Imprimir Etiquetas - Sistema Biblioteca</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        @media print {
            body {
                margin: 0;
                padding: 0;
            }
            .no-print {
                display: none !important;
            }
            .page-break {
                page-break-after: always;
            }
            .etiqueta-container {
                break-inside: avoid;
            }
        }
        
        body {
            padding: 20px;
            background-color: #f5f5f5;
        }
        
        .etiquetas-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 10px;
            margin-bottom: 20px;
        }
        
        .etiqueta {
            border: 1px solid #ccc;
            background: white;
            padding: 10px;
            text-align: center;
            box-sizing: border-box;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            <?php echo $tamanoEstilo; ?>
        }
        
        .barcode-img {
            max-width: 100%;
            height: auto;
            margin: 5px 0;
        }
        
        .codigo-text {
            font-family: 'Courier New', monospace;
            font-weight: bold;
            font-size: 12px;
            margin-bottom: 5px;
        }
        
        .titulo-text {
            font-size: 10px;
            font-weight: bold;
            margin-top: 5px;
            max-width: 100%;
            word-wrap: break-word;
        }
        
        .autor-text {
            font-size: 9px;
            color: #666;
            margin-top: 2px;
        }
        
        .fecha-text {
            font-size: 8px;
            color: #999;
            margin-top: 3px;
        }
        
        .header-info {
            background-color: #f8f9fa;
            border-left: 4px solid #007bff;
            padding: 15px;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <!-- ENCABEZADO NO IMPRIMIBLE -->
    <div class="container-fluid no-print">
        <div class="header-info">
            <h4><i class="fas fa-print"></i> Vista de Impresión</h4>
            <div class="row">
                <div class="col-md-4">
                    <strong><i class="fas fa-calendar"></i> Fecha:</strong> 
                    <?php echo date('d/m/Y H:i', strtotime($etiquetas['fecha'])); ?>
                </div>
                <div class="col-md-4">
                    <strong><i class="fas fa-user"></i> Usuario:</strong> 
                    <?php echo htmlspecialchars($etiquetas['usuario']); ?>
                </div>
                <div class="col-md-4">
                    <strong><i class="fas fa-barcode"></i> Total etiquetas:</strong> 
                    <?php echo count($libros); ?>
                </div>
            </div>
        </div>
        
        <div class="alert alert-info">
            <i class="fas fa-info-circle"></i> 
            <strong>Instrucciones de impresión:</strong>
            <ol class="mb-0">
                <li>Use papel adhesivo A4</li>
                <li>Configure márgenes mínimos (0.5cm o menos)</li>
                <li>Haga clic en "Imprimir" o presione Ctrl+P</li>
            </ol>
        </div>
        
        <!-- BOTONES SIMPLIFICADOS -->
        <div class="d-flex justify-content-between mb-4">
            <a href="generar_etiquetas.php" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Volver
            </a>
            <button onclick="window.print()" class="btn btn-success">
                <i class="fas fa-print"></i> Imprimir
            </button>
        </div>
    </div>
    
    <!-- CONTENIDO PARA IMPRIMIR -->
    <div class="container-fluid">
        <div class="etiquetas-grid">
            <?php 
            require_once 'barcode_generator.php';
            $barcodeManager = new BarcodeManager();
            
            foreach ($libros as $index => $libro): 
                // Generar código de barras
                $barcodeBase64 = $barcodeManager->generarBase64(
                    $libro['codigo_interno'],
                    $config['tipo_barcode'],
                    $config['alto_barcode'],
                    $config['ancho_barra']
                );
                
                if (empty($barcodeBase64)) {
                    continue;
                }
            ?>
            <div class="etiqueta-container">
                <div class="etiqueta">
                    <?php if ($config['mostrar_codigo']): ?>
                    <div class="codigo-text"><?php echo htmlspecialchars($libro['codigo_interno']); ?></div>
                    <?php endif; ?>
                    
                    <img src="<?php echo $barcodeBase64; ?>" 
                         alt="<?php echo htmlspecialchars($libro['codigo_interno']); ?>" 
                         class="barcode-img">
                    
                    <?php if ($config['mostrar_titulo'] && !empty($libro['titulo'])): 
                        $tituloCorto = strlen($libro['titulo']) > 30 ? 
                            substr($libro['titulo'], 0, 27) . '...' : 
                            $libro['titulo'];
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
            </div>
            
            <?php 
                // Insertar salto de página cada 21 etiquetas (para 3 columnas = 7 filas)
                if (($index + 1) % 21 === 0 && ($index + 1) < $totalEtiquetas):
            ?>
            <div class="page-break"></div>
            <?php endif; ?>
            
            <?php endforeach; ?>
        </div>
    </div>
    
    <script>
    // Configurar para impresión
    document.addEventListener('DOMContentLoaded', function() {
        // Agregar mensaje antes de imprimir
        window.onbeforeprint = function() {
            console.log('Preparando para imprimir etiquetas...');
        };
    });
    </script>
</body>
</html>
<?php
// Opcional: limpiar la sesión después de imprimir
// unset($_SESSION['etiquetas_para_imprimir']);
?>