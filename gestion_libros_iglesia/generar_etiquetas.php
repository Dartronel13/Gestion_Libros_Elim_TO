<?php
// generar_etiquetas.php - VERSIÓN MODIFICADA

// 1. VERIFICACIÓN DE ACCESO (AGREGAR ESTO AL INICIO)
require_once 'db.php';
verificarAutenticacion(); // ← ESTA LÍNEA ES NUEVA

// 2. REGISTRAR ACCESO A ESTA PÁGINA
$db->registrarAccion('acceso', 'etiquetas', "Accedió al generador de etiquetas");

$titulo_pagina = '🏷️ Generar Etiquetas';
$icono_titulo = 'fas fa-barcode';

// 3. REGISTRAR CONSULTA DE LIBROS DISPONIBLES
$db->registrarAccion('consulta_libros', 'etiquetas', "Consultando libros disponibles para etiquetas");

// Obtener libros disponibles
$sql_libros = "SELECT id, codigo_interno, titulo, autor, stock 
               FROM libros WHERE activo = 1 AND stock > 0 
               ORDER BY titulo ASC";
$stmt_libros = $db->query($sql_libros);
$result_libros = $stmt_libros->get_result();
$libros = [];
while ($row = $result_libros->fetch_assoc()) {
    $libros[] = $row;
}

// 4. REGISTRAR RESULTADO DE CONSULTA
$num_libros = count($libros);
$db->registrarAccion(
    'resultado_consulta', 
    'etiquetas', 
    "Encontrados {$num_libros} libros disponibles para generar etiquetas"
);

// 5. PROCESAR GENERACIÓN DE ETIQUETAS SI SE SOLICITA
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['generar_etiquetas'])) {
    $libros_seleccionados = $_POST['libros'] ?? [];
    $tipo_etiqueta = $_POST['tipo_etiqueta'] ?? 'codigo_barras';
    $formato = $_POST['formato'] ?? 'pdf';
    $cantidad = intval($_POST['cantidad'] ?? 1);
    
    // 6. REGISTRAR INICIO DE GENERACIÓN
    $db->registrarAccion(
        'inicio_generacion', 
        'etiquetas', 
        "Iniciando generación de etiquetas - " .
        "Libros seleccionados: " . count($libros_seleccionados) . ", " .
        "Tipo: {$tipo_etiqueta}, Formato: {$formato}, Cantidad: {$cantidad}"
    );
    
    // Validar que se haya seleccionado al menos un libro
    if (empty($libros_seleccionados)) {
        // 7. REGISTRAR ERROR - SIN LIBROS SELECCIONADOS
        $db->registrarAccion(
            'error_seleccion', 
            'etiquetas', 
            "Error en generación - No se seleccionaron libros"
        );
        
        $_SESSION['error_etiquetas'] = "Debe seleccionar al menos un libro.";
        header('Location: generar_etiquetas.php');
        exit;
    }
    
    // Validar cantidad
    if ($cantidad < 1 || $cantidad > 100) {
        // 8. REGISTRAR ERROR - CANTIDAD INVÁLIDA
        $db->registrarAccion(
            'error_cantidad', 
            'etiquetas', 
            "Error en generación - Cantidad inválida: {$cantidad}"
        );
        
        $_SESSION['error_etiquetas'] = "La cantidad debe estar entre 1 y 100.";
        header('Location: generar_etiquetas.php');
        exit;
    }
    
    // Obtener información detallada de los libros seleccionados
    $ids_seleccionados = array_map('intval', $libros_seleccionados);
    $placeholders = str_repeat('?,', count($ids_seleccionados) - 1) . '?';
    
    $sql_detalle = "SELECT id, codigo_interno, titulo, autor FROM libros 
                    WHERE id IN ($placeholders) AND activo = 1";
    $stmt_detalle = $db->query($sql_detalle, $ids_seleccionados);
    $result_detalle = $stmt_detalle->get_result();
    
    $libros_etiquetas = [];
    while ($row = $result_detalle->fetch_assoc()) {
        $libros_etiquetas[] = $row;
    }
    
    // 9. REGISTRAR LIBROS OBTENIDOS PARA ETIQUETAS
    $nombres_libros = array_map(function($libro) {
        return "'{$libro['titulo']}'";
    }, $libros_etiquetas);
    
    $db->registrarAccion(
        'libros_para_etiquetas', 
        'etiquetas', 
        "Libros seleccionados para etiquetas: " . implode(', ', $nombres_libros)
    );
    
    // Preparar datos para la generación de etiquetas
    $datos_etiquetas = [
        'fecha_generacion' => date('Y-m-d H:i:s'),
        'usuario' => $_SESSION['nombre_completo'] ?? 'Desconocido',
        'total_libros' => count($libros_etiquetas),
        'total_etiquetas' => count($libros_etiquetas) * $cantidad,
        'tipo_etiqueta' => $tipo_etiqueta,
        'formato' => $formato,
        'libros' => $libros_etiquetas
    ];
    
    // Guardar en sesión para el proceso de generación
    $_SESSION['datos_etiquetas'] = $datos_etiquetas;
    
    // 10. REGISTRAR PREPARACIÓN EXITOSA
    $db->registrarAccion(
        'preparacion_exitosa', 
        'etiquetas', 
        "Datos preparados para generación - " .
        "Total etiquetas a generar: " . (count($libros_etiquetas) * $cantidad)
    );
    
    // Redirigir al generador real de etiquetas (PDF/Impresión)
    if ($formato === 'pdf') {
        header('Location: generar_pdf_etiquetas.php');
    } else {
        header('Location: imprimir_etiquetas.php');
    }
    exit;
}

// 11. PROCESAR GENERACIÓN RÁPIDA POR CÓDIGO (si aplica)
if (isset($_GET['codigo']) && !empty($_GET['codigo'])) {
    $codigo = trim($_GET['codigo']);
    
    $db->registrarAccion(
        'generacion_rapida', 
        'etiquetas', 
        "Generación rápida solicitada para código: {$codigo}"
    );
    
    // Buscar libro por código
    $sql_buscar = "SELECT id FROM libros WHERE codigo_interno = ? AND activo = 1";
    $stmt_buscar = $db->query($sql_buscar, [$codigo]);
    $result_buscar = $stmt_buscar->get_result();
    
    if ($result_buscar->num_rows > 0) {
        $libro = $result_buscar->fetch_assoc();
        
        $db->registrarAccion(
            'libro_encontrado_rapido', 
            'etiquetas', 
            "Libro encontrado para código {$codigo} - ID: {$libro['id']}"
        );
        
        // Redirigir con el libro seleccionado
        echo '<script>
            document.addEventListener("DOMContentLoaded", function() {
                document.getElementById("libro_' . $libro['id'] . '").checked = true;
                document.getElementById("cantidad").value = 1;
            });
        </script>';
    } else {
        $db->registrarAccion(
            'libro_no_encontrado_rapido', 
            'etiquetas', 
            "Código no encontrado: {$codigo}"
        );
        
        $_SESSION['error_etiquetas'] = "Código '$codigo' no encontrado.";
    }
}

// 12. MOSTRAR MENSAJES DE SESIÓN (si existen)
if (isset($_SESSION['error_etiquetas'])) {
    $mensaje_error = $_SESSION['error_etiquetas'];
    unset($_SESSION['error_etiquetas']);
}

if (isset($_SESSION['exito_etiquetas'])) {
    $mensaje_exito = $_SESSION['exito_etiquetas'];
    unset($_SESSION['exito_etiquetas']);
}

ob_start();
?>

<!-- BREADCRUMB -->
<nav aria-label="breadcrumb" class="mb-4">
    <ol class="breadcrumb">
        <li class="breadcrumb-item">
            <a href="catalogo_libros.php">
                <i class="fas fa-book"></i> Catálogo
            </a>
        </li>
        <li class="breadcrumb-item active" aria-current="page">
            <i class="fas fa-barcode"></i> Etiquetas
        </li>
    </ol>
</nav>

<div class="row">
    <div class="col-lg-4">
        <!-- PANEL DE CONFIGURACIÓN -->
        <div class="card">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0">
                    <i class="fas fa-cog me-2"></i>
                    Configurar Etiquetas
                </h5>
            </div>
            <div class="card-body">
                <form id="formEtiquetas" onsubmit="generarEtiquetas(); return false;">
                    <!-- TIPO DE ETIQUETA -->
                    <div class="mb-3">
                        <label class="form-label">Tipo de Etiqueta</label>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="tipo_etiqueta" 
                                   id="simple" value="simple" checked>
                            <label class="form-check-label" for="simple">
                                Simple (Código + Título)
                            </label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="tipo_etiqueta" 
                                   id="completa" value="completa">
                            <label class="form-check-label" for="completa">
                                Completa (Todos los datos)
                            </label>
                        </div>
                    </div>
                    
                    <!-- TAMAÑO -->
                    <div class="mb-3">
                        <label for="tamano" class="form-label">Tamaño de Etiqueta</label>
                        <select class="form-select" id="tamano">
                            <option value="small">Pequeña (50x30 mm)</option>
                            <option value="medium" selected>Mediana (70x40 mm)</option>
                            <option value="large">Grande (100x60 mm)</option>
                        </select>
                    </div>
                    
                    <!-- CÓDIGO DE BARRAS -->
                    <div class="mb-3">
                        <label class="form-label">Incluir Código de Barras</label>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="incluir_barcode" checked>
                            <label class="form-check-label" for="incluir_barcode">
                                Sí, generar código de barras
                            </label>
                        </div>
                    </div>
                    
                    <!-- TIPO DE CÓDIGO -->
                    <div class="mb-3">
                        <label for="tipo_barcode" class="form-label">Tipo de Código</label>
                        <select class="form-select" id="tipo_barcode">
                            <option value="code128">CODE 128 (Recomendado)</option>
                            <option value="code39">CODE 39</option>
                            <option value="qr">QR Code</option>
                            <option value="simple">Simple</option>
                        </select>
                    </div>
                    
                    <!-- CANTIDAD POR LIBRO -->
                    <div class="mb-4">
                        <label for="cantidad" class="form-label">Cantidad por libro</label>
                        <input type="number" class="form-control" id="cantidad" 
                               value="1" min="1" max="10">
                        <div class="form-text">
                            Número de etiquetas iguales por cada libro seleccionado.
                        </div>
                    </div>
                    
                    <hr>
                    
                    <!-- BOTONES -->
                    <div class="d-grid gap-2">
                        <button type="button" class="btn btn-primary" onclick="seleccionarTodos()">
                            <i class="fas fa-check-square me-1"></i> Seleccionar Todos
                        </button>
                        <button type="button" class="btn btn-secondary" onclick="deseleccionarTodos()">
                            <i class="fas fa-square me-1"></i> Deseleccionar Todos
                        </button>
                        <button type="submit" class="btn btn-success">
                            <i class="fas fa-print me-1"></i> Generar Etiquetas
                        </button>
                    </div>
                </form>
            </div>
        </div>
        
        <!-- AYUDA -->
        <div class="card mt-4">
            <div class="card-header bg-light">
                <h6 class="mb-0">
                    <i class="fas fa-question-circle me-2"></i>
                    Instrucciones
                </h6>
            </div>
            <div class="card-body small">
                <ol>
                    <li>Seleccione los libros del panel derecho</li>
                    <li>Configure el tipo y tamaño de etiqueta</li>
                    <li>Haga clic en "Generar Etiquetas"</li>
                    <li>Imprima desde el navegador (Ctrl+P)</li>
                </ol>
                <div class="alert alert-info mt-2">
                    <i class="fas fa-lightbulb me-2"></i>
                    <strong>Tip:</strong> Use papel adhesivo para etiquetas de 70x40 mm.
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-lg-8">
        <!-- LISTA DE LIBROS -->
        <div class="card">
            <div class="card-header bg-success text-white d-flex justify-content-between align-items-center">
                <h5 class="mb-0">
                    <i class="fas fa-book me-2"></i>
                    Seleccionar Libros para Etiquetas
                </h5>
                <span class="badge bg-light text-dark" id="contadorSeleccionados">0 seleccionados</span>
            </div>
            <div class="card-body">
                <?php if (empty($libros)): ?>
                    <div class="alert alert-warning text-center py-4">
                        <i class="fas fa-exclamation-triangle fa-2x mb-3"></i>
                        <h5>No hay libros disponibles</h5>
                        <p class="mb-0">
                            Todos los libros están agotados o no hay libros en el catálogo.
                        </p>
                    </div>
                <?php else: ?>
                    <!-- BARRA DE BÚSQUEDA -->
                    <div class="input-group mb-3">
                        <input type="text" class="form-control" id="buscarLibros" 
                               placeholder="Buscar libros por título o código...">
                        <button class="btn btn-outline-secondary" type="button" onclick="buscarLibros()">
                            <i class="fas fa-search"></i>
                        </button>
                    </div>
                    
                    <!-- LISTA DE LIBROS -->
                    <div class="table-responsive" style="max-height: 500px; overflow-y: auto;">
                        <table class="table table-hover" id="tablaLibros">
                            <thead>
                                <tr>
                                    <th width="5%">
                                        <input type="checkbox" id="checkTodos" onclick="toggleTodos()">
                                    </th>
                                    <th width="15%">Código</th>
                                    <th width="45%">Título</th>
                                    <th width="25%">Autor</th>
                                    <th width="10%">Stock</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($libros as $libro): ?>
                                <tr class="libro-fila">
                                    <td>
                                        <input type="checkbox" class="libro-check" 
                                               value="<?php echo $libro['id']; ?>"
                                               data-codigo="<?php echo htmlspecialchars($libro['codigo_interno']); ?>"
                                               data-titulo="<?php echo htmlspecialchars($libro['titulo']); ?>"
                                               data-autor="<?php echo htmlspecialchars($libro['autor']); ?>">
                                    </td>
                                    <td>
                                        <code><?php echo htmlspecialchars($libro['codigo_interno']); ?></code>
                                    </td>
                                    <td><?php echo htmlspecialchars($libro['titulo']); ?></td>
                                    <td><?php echo htmlspecialchars($libro['autor']); ?></td>
                                    <td>
                                        <span class="badge bg-success">
                                            <?php echo $libro['stock']; ?>
                                        </span>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    
                    <div class="mt-3 text-end">
                        <small class="text-muted">
                            Mostrando <?php echo count($libros); ?> libros disponibles
                        </small>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- VISTA PREVIA -->
        <div class="card mt-4">
            <div class="card-header bg-info text-white">
                <h5 class="mb-0">
                    <i class="fas fa-eye me-2"></i>
                    Vista Previa de Etiqueta
                </h5>
            </div>
            <div class="card-body text-center">
                <div id="vistaPrevia" class="border p-3 d-inline-block">
                    <div style="width: 200px; height: 100px; background: #f8f9fa; 
                                border: 1px dashed #ccc; display: flex; 
                                align-items: center; justify-content: center;">
                        <div class="text-center">
                            <i class="fas fa-barcode fa-2x text-muted"></i>
                            <div class="small mt-2">Vista previa</div>
                        </div>
                    </div>
                </div>
                <div class="mt-3">
                    <button class="btn btn-sm btn-outline-primary" onclick="actualizarVistaPrevia()">
                        <i class="fas fa-sync me-1"></i> Actualizar Vista Previa
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- MODAL DE IMPRESIÓN -->
<div class="modal fade" id="modalImpresion" tabindex="-1" style="--bs-modal-width: 800px;">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title">
                    <i class="fas fa-print me-2"></i>
                    Etiquetas para Imprimir
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div id="contenidoImpresion" style="padding: 20px;">
                    <!-- Aquí se cargarán las etiquetas -->
                </div>
                
                <div class="alert alert-info mt-3">
                    <i class="fas fa-info-circle me-2"></i>
                    <strong>Instrucciones de impresión:</strong>
                    <ol class="mb-0 mt-2">
                        <li>Haga clic en "Imprimir Etiquetas"</li>
                        <li>En la ventana de impresión, configure:
                            <ul>
                                <li>Orientación: Horizontal</li>
                                <li>Márgenes: Mínimos o Ninguno</li>
                                <li>Escala: 100%</li>
                            </ul>
                        </li>
                        <li>Use papel adhesivo para etiquetas</li>
                    </ol>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                <button type="button" class="btn btn-success" onclick="window.print()">
                    <i class="fas fa-print me-1"></i> Imprimir Etiquetas
                </button>
            </div>
        </div>
    </div>
</div>

<!-- ESTILOS PARA ETIQUETAS -->
<style>
.etiqueta-container {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 10px;
    margin-bottom: 20px;
}

.etiqueta {
    border: 1px solid #ddd;
    padding: 10px;
    text-align: center;
    page-break-inside: avoid;
    background: white;
}

.etiqueta-small { width: 50mm; height: 30mm; }
.etiqueta-medium { width: 70mm; height: 40mm; }
.etiqueta-large { width: 100mm; height: 60mm; }

.codigo-barras {
    margin: 5px 0;
    max-width: 100%;
    height: auto;
}

.texto-codigo {
    font-family: monospace;
    font-size: 10px;
    margin-top: 5px;
}

.titulo-etiqueta {
    font-size: 11px;
    font-weight: bold;
    margin: 5px 0;
    overflow: hidden;
    text-overflow: ellipsis;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
}

.autor-etiqueta {
    font-size: 9px;
    color: #666;
}

@media print {
    .no-print { display: none !important; }
    body { margin: 0; padding: 0; }
    .etiqueta-container {
        gap: 0;
        margin: 0;
    }
    .etiqueta {
        border: none;
        padding: 2mm;
    }
}
</style>

<script>
// Variables globales
let librosSeleccionados = [];

// Actualizar contador
function actualizarContador() {
    const checks = document.querySelectorAll('.libro-check:checked');
    document.getElementById('contadorSeleccionados').textContent = 
        checks.length + ' seleccionados';
    
    // Guardar en array
    librosSeleccionados = Array.from(checks).map(check => ({
        id: check.value,
        codigo: check.dataset.codigo,
        titulo: check.dataset.titulo,
        autor: check.dataset.autor
    }));
    
    actualizarVistaPrevia();
}

// Seleccionar/deseleccionar todos
function toggleTodos() {
    const checkTodos = document.getElementById('checkTodos');
    const checks = document.querySelectorAll('.libro-check');
    
    checks.forEach(check => {
        check.checked = checkTodos.checked;
    });
    
    actualizarContador();
}

function seleccionarTodos() {
    document.querySelectorAll('.libro-check').forEach(check => {
        check.checked = true;
    });
    document.getElementById('checkTodos').checked = true;
    actualizarContador();
}

function deseleccionarTodos() {
    document.querySelectorAll('.libro-check').forEach(check => {
        check.checked = false;
    });
    document.getElementById('checkTodos').checked = false;
    actualizarContador();
}

// Búsqueda de libros
function buscarLibros() {
    const texto = document.getElementById('buscarLibros').value.toLowerCase();
    const filas = document.querySelectorAll('.libro-fila');
    
    filas.forEach(fila => {
        const textoFila = fila.textContent.toLowerCase();
        fila.style.display = textoFila.includes(texto) ? '' : 'none';
    });
}

// Actualizar vista previa
function actualizarVistaPrevia() {
    if (librosSeleccionados.length === 0) return;
    
    const libro = librosSeleccionados[0];
    const tipo = document.querySelector('input[name="tipo_etiqueta"]:checked').value;
    const tamano = document.getElementById('tamano').value;
    const incluirBarcode = document.getElementById('incluir_barcode').checked;
    
    let contenido = '';
    
    if (tipo === 'simple') {
        contenido = `
            <div class="etiqueta etiqueta-${tamano}">
                ${incluirBarcode ? 
                    `<div class="codigo-barras">
                        <img src="barcode_simple.php?code=${encodeURIComponent(libro.codigo)}&type=simple" 
                             alt="${libro.codigo}" style="max-width: 100%; height: 30px;">
                    </div>` : ''
                }
                <div class="texto-codigo">${libro.codigo}</div>
                <div class="titulo-etiqueta">${libro.titulo.substring(0, 50)}</div>
            </div>
        `;
    } else {
        contenido = `
            <div class="etiqueta etiqueta-${tamano}">
                <div class="texto-codigo"><strong>${libro.codigo}</strong></div>
                ${incluirBarcode ? 
                    `<div class="codigo-barras">
                        <img src="barcode_simple.php?code=${encodeURIComponent(libro.codigo)}&type=simple" 
                             alt="${libro.codigo}" style="max-width: 100%; height: 40px;">
                    </div>` : ''
                }
                <div class="titulo-etiqueta">${libro.titulo}</div>
                <div class="autor-etiqueta">${libro.autor}</div>
                <div style="font-size: 8px; color: #999; margin-top: 5px;">
                    Sistema de Biblioteca
                </div>
            </div>
        `;
    }
    
    document.getElementById('vistaPrevia').innerHTML = contenido;
}

// Generar etiquetas
function generarEtiquetas() {
    if (librosSeleccionados.length === 0) {
        alert('Por favor seleccione al menos un libro.');
        return;
    }
    
    const tipo = document.querySelector('input[name="tipo_etiqueta"]:checked').value;
    const tamano = document.getElementById('tamano').value;
    const incluirBarcode = document.getElementById('incluir_barcode').checked;
    const tipoBarcode = document.getElementById('tipo_barcode').value;
    const cantidad = parseInt(document.getElementById('cantidad').value);
    
    let contenido = '<div class="etiqueta-container">';
    
    // Generar etiquetas para cada libro seleccionado
    librosSeleccionados.forEach(libro => {
        for (let i = 0; i < cantidad; i++) {
            let etiqueta = '';
            
            if (tipo === 'simple') {
                etiqueta = `
                    <div class="etiqueta etiqueta-${tamano}">
                        ${incluirBarcode ? 
                            `<div class="codigo-barras">
                                <img src="barcode_simple.php?code=${encodeURIComponent(libro.codigo)}&type=${tipoBarcode}" 
                                     alt="${libro.codigo}" style="max-width: 100%; height: 30px;">
                            </div>` : ''
                        }
                        <div class="texto-codigo"><strong>${libro.codigo}</strong></div>
                        <div class="titulo-etiqueta">${libro.titulo.substring(0, 40)}</div>
                    </div>
                `;
            } else {
                etiqueta = `
                    <div class="etiqueta etiqueta-${tamano}">
                        <div class="texto-codigo" style="font-size: 12px; font-weight: bold;">
                            ${libro.codigo}
                        </div>
                        ${incluirBarcode ? 
                            `<div class="codigo-barras">
                                <img src="barcode_simple.php?code=${encodeURIComponent(libro.codigo)}&type=${tipoBarcode}" 
                                     alt="${libro.codigo}" style="max-width: 100%; height: 40px;">
                            </div>` : ''
                        }
                        <div class="titulo-etiqueta">${libro.titulo}</div>
                        <div class="autor-etiqueta">${libro.autor}</div>
                        <div style="font-size: 8px; color: #999; margin-top: 3px;">
                            Biblioteca - ${new Date().getFullYear()}
                        </div>
                    </div>
                `;
            }
            
            contenido += etiqueta;
        }
    });
    
    contenido += '</div>';
    
    // Agregar información del lote
    contenido += `
        <div class="no-print alert alert-light border mt-4">
            <div class="row">
                <div class="col-md-6">
                    <strong>Información del lote:</strong><br>
                    Libros: ${librosSeleccionados.length}<br>
                    Etiquetas totales: ${librosSeleccionados.length * cantidad}<br>
                    Tamaño: ${tamano}
                </div>
                <div class="col-md-6">
                    <strong>Configuración:</strong><br>
                    Tipo: ${tipo}<br>
                    Código de barras: ${incluirBarcode ? 'Sí (' + tipoBarcode + ')' : 'No'}<br>
                    Generado: ${new Date().toLocaleString()}
                </div>
            </div>
        </div>
    `;
    
    // Mostrar en modal
    document.getElementById('contenidoImpresion').innerHTML = contenido;
    const modal = new bootstrap.Modal(document.getElementById('modalImpresion'));
    modal.show();
}

// Event listeners
document.addEventListener('DOMContentLoaded', function() {
    // Actualizar contador cuando se marcan checkboxes
    document.querySelectorAll('.libro-check').forEach(check => {
        check.addEventListener('change', actualizarContador);
    });
    
    // Buscar al escribir
    document.getElementById('buscarLibros').addEventListener('input', buscarLibros);
    
    // Actualizar vista previa al cambiar config
    document.querySelectorAll('input[name="tipo_etiqueta"]').forEach(radio => {
        radio.addEventListener('change', actualizarVistaPrevia);
    });
    
    document.getElementById('tamano').addEventListener('change', actualizarVistaPrevia);
    document.getElementById('incluir_barcode').addEventListener('change', actualizarVistaPrevia);
    document.getElementById('tipo_barcode').addEventListener('change', actualizarVistaPrevia);
    document.getElementById('cantidad').addEventListener('change', actualizarVistaPrevia);
    
    // Inicializar vista previa
    if (document.querySelector('.libro-check')) {
        document.querySelector('.libro-check').checked = true;
        actualizarContador();
    }
});

// Función para generar etiqueta individual desde catálogo
function generarEtiquetaIndividual(codigo, titulo, autor) {
    librosSeleccionados = [{
        id: 0,
        codigo: codigo,
        titulo: titulo,
        autor: autor
    }];
    
    // Configurar para etiqueta completa
    document.getElementById('completa').checked = true;
    document.getElementById('incluir_barcode').checked = true;
    
    generarEtiquetas();
}
</script>

<?php
$contenido = ob_get_clean();
include 'layout.php';
?>