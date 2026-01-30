<?php
// ERROR REPORTING
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

// VERIFICAR SESIÓN
session_start();
if (!isset($_SESSION['usuario_id'])) {
    header('Location: login.php');
    exit;
}

// generar_etiquetas.php - VERSIÓN PULIDA

// 1. INCLUSIÓN DE ARCHIVOS
require_once 'db.php';
require_once 'barcode_generator.php';

// 2. INICIALIZAR
$barcodeManager = new BarcodeManager($db);
$db->registrarAccion('acceso', 'etiquetas', "Accedió al generador de etiquetas");

// 3. OBTENER LIBROS
$sql_libros = "SELECT id, codigo_interno, titulo, autor, stock, isbn 
               FROM libros 
               WHERE activo = 1 
               ORDER BY titulo ASC";
$stmt_libros = $db->query($sql_libros);
$result_libros = $stmt_libros->get_result();
$libros = [];
while ($row = $result_libros->fetch_assoc()) {
    $libros[] = $row;
}

// 4. MENSAJES DE SESIÓN
if (isset($_SESSION['error_etiquetas'])) {
    $mensaje_error = $_SESSION['error_etiquetas'];
    unset($_SESSION['error_etiquetas']);
}

if (isset($_SESSION['exito_etiquetas'])) {
    $mensaje_exito = $_SESSION['exito_etiquetas'];
    unset($_SESSION['exito_etiquetas']);
}

// 5. TIPOS DE CÓDIGO
$tipos_barcode = [
    'C128' => [
        'nombre' => 'CODE 128',
        'descripcion' => 'Estándar industrial, muy compacto',
        'recomendado' => true
    ],
    'C39' => [
        'nombre' => 'CODE 39',
        'descripcion' => 'Solo mayúsculas y números',
        'recomendado' => false
    ]
];
$titulo_pagina = 'Generar Etiquetas';
$icono_titulo = 'fas fa-tags'; 


// CSS específico para el menú
$pageStyles = '
<link rel="stylesheet" href="css\generar_etiquetas-style.css">';

ob_start();
?>


    <!-- LOADING OVERLAY - CON TRANSICIÓN -->
    <div class="loading-overlay" id="loadingOverlay" style="display: none; visibility: hidden; opacity: 0; transition: opacity 0.3s ease;">
        <div class="text-center bg-white p-4 rounded shadow">
            <div class="spinner-border text-primary mb-3" role="status">
                <span class="visually-hidden">Cargando...</span>
            </div>
            <h5>Generando etiquetas...</h5>
            <p class="text-muted mb-0">Por favor espere</p>
        </div>
    </div>

    <div class="container-fluid">
        <!-- BREADCRUMB -->
        <nav aria-label="breadcrumb" class="mb-4">
            <ol class="breadcrumb">
                <li class="breadcrumb-item">
                    <a href="catalogo_libros.php"><i class="fas fa-book"></i> Catálogo</a>
                </li>
                <li class="breadcrumb-item active"><i class="fas fa-barcode"></i> Etiquetas</li>
            </ol>
        </nav>

        <!-- MENSAJES -->
        <?php if (isset($mensaje_error)): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="fas fa-exclamation-triangle me-2"></i>
            <?php echo htmlspecialchars($mensaje_error); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>

        <?php if (isset($mensaje_exito)): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fas fa-check-circle me-2"></i>
            <?php echo htmlspecialchars($mensaje_exito); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>

        <div class="row">
            <div class="col-lg-4">
                <!-- PANEL DE CONFIGURACIÓN -->
                <div class="card shadow-sm">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0"><i class="fas fa-cog me-2"></i>Configurar Etiquetas</h5>
                    </div>
                    <div class="card-body">
                        <!-- FORMULARIO SIMPLIFICADO -->
                        <div id="configForm">
                            <!-- TAMAÑO -->
                            <div class="mb-3">
                                <label for="tamano" class="form-label">
                                    <i class="fas fa-expand-alt me-1"></i>Tamaño de Etiqueta
                                </label>
                                <select class="form-select" id="tamano">
                                    <option value="small">Pequeña (60x30 mm)</option>
                                    <option value="medium" selected>Mediana (70x40 mm)</option>
                                    <option value="large">Grande (100x60 mm)</option>
                                </select>
                            </div>
                            
                            <!-- TIPO DE CÓDIGO -->
                            <div class="mb-3">
                                <label for="tipo_barcode" class="form-label">
                                    <i class="fas fa-qrcode me-1"></i>Tipo de Código
                                </label>
                                <select class="form-select" id="tipo_barcode">
                                    <?php foreach ($tipos_barcode as $tipo => $info): ?>
                                    <option value="<?php echo $tipo; ?>" <?php echo $info['recomendado'] ? 'selected' : ''; ?>>
                                        <?php echo $info['nombre']; ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                                <small class="text-muted" id="descripcion-tipo">
                                    <?php echo $tipos_barcode['C128']['descripcion']; ?>
                                </small>
                            </div>
                            
                            <!-- INFORMACIÓN A MOSTRAR -->
                            <div class="mb-3">
                                <label class="form-label"><i class="fas fa-eye me-1"></i>Información a Mostrar</label>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="mostrar_codigo">
                                    <label class="form-check-label" for="mostrar_codigo">Código interno</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="mostrar_titulo">
                                    <label class="form-check-label" for="mostrar_titulo">Título</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="mostrar_autor">
                                    <label class="form-check-label" for="mostrar_autor">Autor</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="mostrar_fecha">
                                    <label class="form-check-label" for="mostrar_fecha">Fecha</label>
                                </div>
                            </div>
                            
                            <!-- CANTIDAD -->
                            <div class="mb-4">
                                <label for="cantidad" class="form-label">
                                    <i class="fas fa-copy me-1"></i>Cantidad por libro
                                </label>
                                <input type="number" class="form-control" id="cantidad" value="1" min="1" max="50">
                            </div>
                            
                            <hr>
                            
                            <!-- BOTONES SIMPLIFICADOS -->
                            <div class="d-grid gap-2">
                                <button type="button" class="btn btn-outline-primary" onclick="seleccionarTodos()">
                                    <i class="fas fa-check-square me-1"></i> Seleccionar Todos
                                </button>
                                <button type="button" class="btn btn-outline-secondary" onclick="deseleccionarTodos()">
                                    <i class="fas fa-square me-1"></i> Deseleccionar Todos
                                </button>
                                <!-- BOTÓN PRINCIPAL -->
                                <button type="button" id="btnGenerarAjax" class="btn btn-success btn-lg">
                                    <i class="fas fa-print me-1"></i> Generar Etiquetas
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- VISTA PREVIA -->
                <div class="card shadow-sm mt-4">
                    <div class="card-header bg-info text-white">
                        <h6 class="mb-0"><i class="fas fa-eye me-2"></i>Vista Previa</h6>
                    </div>
                    <div class="card-body text-center p-3">
                        <div id="vistaPrevia" class="border rounded p-2 bg-white">
                            <div class="text-center py-4">
                                <i class="fas fa-barcode fa-3x text-muted mb-3"></i>
                                <p class="mb-0 text-muted">Seleccione un libro para ver vista previa</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-8">
                <!-- LISTA DE LIBROS -->
                <div class="card shadow-sm">
                    <div class="card-header bg-success text-white d-flex justify-content-between align-items-center">
                        <div>
                            <h5 class="mb-0"><i class="fas fa-book me-2"></i>Seleccionar Libros</h5>
                            <small class="opacity-75">Haga clic en la fila para seleccionar</small>
                        </div>
                        <div class="text-end">
                            <span class="badge bg-warning text-dark fs-6" id="contadorSeleccionados">0</span>
                            <small class="d-block opacity-75">seleccionados</small>
                        </div>
                    </div>
                    <div class="card-body">
                        <?php if (empty($libros)): ?>
                        <div class="alert alert-warning text-center py-5">
                            <i class="fas fa-book-open fa-3x mb-3 text-warning"></i>
                            <h5>No hay libros disponibles</h5>
                            <p class="mb-0">Agregue libros al catálogo primero.</p>
                        </div>
                        <?php else: ?>
                        <!-- BARRA DE BÚSQUEDA -->
                        <div class="input-group mb-3">
                            <span class="input-group-text"><i class="fas fa-search"></i></span>
                            <input type="text" class="form-control" id="buscarLibros" placeholder="Buscar...">
                            <button class="btn btn-outline-secondary" type="button" onclick="limpiarBusqueda()">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                        
                        <!-- TABLA DE LIBROS -->
                        <div class="table-responsive" style="max-height: 500px; overflow-y: auto;">
                            <table class="table table-hover table-sm" id="tablaLibros">
                                <thead class="sticky-top bg-light">
                                    <tr>
                                        <th width="5%"><input type="checkbox" id="checkTodos" onclick="toggleTodos()"></th>
                                        <th width="15%">Código</th>
                                        <th width="40%">Título</th>
                                        <th width="25%">Autor</th>
                                        <th width="15%" class="text-center">Stock</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($libros as $libro): 
                                        $tieneStock = $libro['stock'] > 0;
                                    ?>
                                    <tr class="libro-fila" onclick="toggleSeleccionFila(this)">
                                        <td onclick="event.stopPropagation();">
                                            <input type="checkbox" class="libro-check" 
                                                   value="<?php echo $libro['id']; ?>"
                                                   data-codigo="<?php echo htmlspecialchars($libro['codigo_interno']); ?>"
                                                   data-titulo="<?php echo htmlspecialchars($libro['titulo']); ?>"
                                                   data-autor="<?php echo htmlspecialchars($libro['autor']); ?>"
                                                   onchange="updateSelectionCount()">
                                        </td>
                                        <td>
                                            <code class="<?php echo $tieneStock ? 'text-success' : 'text-danger'; ?>">
                                                <?php echo htmlspecialchars($libro['codigo_interno']); ?>
                                            </code>
                                            <?php if (!empty($libro['isbn'])): ?>
                                            <br><small class="text-muted">ISBN: <?php echo htmlspecialchars($libro['isbn']); ?></small>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php echo htmlspecialchars($libro['titulo']); ?>
                                            <?php if (!$tieneStock): ?>
                                            <span class="badge bg-warning text-dark ms-1">Sin stock</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo htmlspecialchars($libro['autor']); ?></td>
                                        <td class="text-center">
                                            <span class="badge <?php echo $tieneStock ? 'bg-success' : 'bg-danger'; ?> stock-badge">
                                                <?php echo $libro['stock']; ?>
                                            </span>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        
                        <div class="mt-3 d-flex justify-content-between align-items-center">
                            <small class="text-muted">Total: <?php echo count($libros); ?> libros</small>
                            <small class="text-muted">Seleccionados: <span id="contadorTexto">0</span></small>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- JAVASCRIPT PULIDO -->
    <script>
    // ==============================================
    // FUNCIONES PARA EL LOADING OVERLAY
    // ==============================================

    function mostrarLoading() {
        const loadingOverlay = document.getElementById('loadingOverlay');
        if (loadingOverlay) {
            loadingOverlay.style.display = 'flex';
            loadingOverlay.style.visibility = 'visible';
            loadingOverlay.style.opacity = '1';
            // Asegurar que esté encima de todo
            loadingOverlay.style.zIndex = '9999';
        }
    }

    function ocultarLoading() {
        const loadingOverlay = document.getElementById('loadingOverlay');
        if (loadingOverlay) {
            loadingOverlay.style.display = 'none';
            loadingOverlay.style.visibility = 'hidden';
            loadingOverlay.style.opacity = '0';
        }
    }

    // Asegurar que el overlay se oculte si la página se recarga
    window.addEventListener('beforeunload', function() {
        ocultarLoading();
    });

    // Ocultar overlay si hay un error de JavaScript
    window.addEventListener('error', function() {
        ocultarLoading();
    });





    // ==============================================
    // VARIABLES Y FUNCIONES BÁSICAS
    // ==============================================
    
    function updateSelectionCount() {
        const checks = document.querySelectorAll('.libro-check:checked');
        const total = checks.length;
        document.getElementById('contadorSeleccionados').textContent = total;
        document.getElementById('contadorTexto').textContent = total;
        
        const badge = document.getElementById('contadorSeleccionados');
        badge.className = `badge ${total > 0 ? 'bg-warning text-dark' : 'bg-secondary'} fs-6`;
        
        // Actualizar vista previa si hay seleccionados
        if (total > 0) {
            actualizarVistaPrevia();
        }
    }
    
    function seleccionarTodos() {
        document.querySelectorAll('.libro-check').forEach(check => {
            check.checked = true;
            check.closest('tr').classList.add('table-active');
        });
        document.getElementById('checkTodos').checked = true;
        updateSelectionCount();
    }
    
    function deseleccionarTodos() {
        document.querySelectorAll('.libro-check').forEach(check => {
            check.checked = false;
            check.closest('tr').classList.remove('table-active');
        });
        document.getElementById('checkTodos').checked = false;
        updateSelectionCount();
    }
    
    function toggleTodos() {
        const checkTodos = document.getElementById('checkTodos');
        const checks = document.querySelectorAll('.libro-check');
        checks.forEach(check => {
            check.checked = checkTodos.checked;
            check.closest('tr').classList.toggle('table-active', check.checked);
        });
        updateSelectionCount();
    }
    
    function toggleSeleccionFila(fila) {
        const check = fila.querySelector('.libro-check');
        if (check) {
            check.checked = !check.checked;
            fila.classList.toggle('table-active', check.checked);
            updateSelectionCount();
        }
    }
    
    function buscarLibros() {
        const texto = document.getElementById('buscarLibros').value.toLowerCase();
        document.querySelectorAll('.libro-fila').forEach(fila => {
            const textoFila = fila.textContent.toLowerCase();
            fila.style.display = textoFila.includes(texto) ? '' : 'none';
        });
    }
    
    function limpiarBusqueda() {
        document.getElementById('buscarLibros').value = '';
        document.querySelectorAll('.libro-fila').forEach(fila => {
            fila.style.display = '';
        });
    }
    
    async function actualizarVistaPrevia() {
        const checks = document.querySelectorAll('.libro-check:checked');
        if (checks.length === 0) {
            document.getElementById('vistaPrevia').innerHTML = `
                <div class="text-center py-4">
                    <i class="fas fa-barcode fa-3x text-muted mb-3"></i>
                    <p class="mb-0 text-muted">Seleccione un libro para ver vista previa</p>
                </div>
            `;
            return;
        }
        
        const primerCheck = checks[0];
        const config = {
            tamano: document.getElementById('tamano').value,
            tipo_barcode: document.getElementById('tipo_barcode').value,
            mostrar_titulo: document.getElementById('mostrar_titulo').checked ? '1' : '0',
            mostrar_autor: document.getElementById('mostrar_autor').checked ? '1' : '0',
            mostrar_codigo: document.getElementById('mostrar_codigo').checked ? '1' : '0',
            mostrar_fecha: document.getElementById('mostrar_fecha').checked ? '1' : '0'
        };
        
        try {
            const response = await fetch('ajax_generar_vista_previa.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: new URLSearchParams({
                    codigo: primerCheck.dataset.codigo,
                    titulo: primerCheck.dataset.titulo,
                    autor: primerCheck.dataset.autor,
                    ...config
                })
            });
            
            if (response.ok) {
                const html = await response.text();
                document.getElementById('vistaPrevia').innerHTML = html;
            }
        } catch (error) {
            console.error('Error en vista previa:', error);
        }
    }
    
    // ==============================================
    // FUNCIÓN PRINCIPAL CON AJAX - MODIFICADA
    // ==============================================

    async function generarEtiquetasConAjax() {
        console.log('🔄 Iniciando generación de etiquetas...');
        
        // 1. Obtener libros seleccionados
        const checkboxes = document.querySelectorAll('.libro-check:checked');
        const librosIds = Array.from(checkboxes).map(cb => cb.value);
        
        if (librosIds.length === 0) {
            alert('❌ Por favor seleccione al menos un libro.');
            return false;
        }
        
        // 2. Obtener configuración
        const config = {
            tamano: document.getElementById('tamano').value,
            tipo_barcode: document.getElementById('tipo_barcode').value,
            mostrar_titulo: document.getElementById('mostrar_titulo').checked ? 1 : 0,
            mostrar_autor: document.getElementById('mostrar_autor').checked ? 1 : 0,
            mostrar_codigo: document.getElementById('mostrar_codigo').checked ? 1 : 0,
            mostrar_fecha: document.getElementById('mostrar_fecha').checked ? 1 : 0,
            cantidad: document.getElementById('cantidad').value
        };
        
        // 3. Validar cantidad
        const cantidad = parseInt(config.cantidad);
        if (isNaN(cantidad) || cantidad < 1 || cantidad > 50) {
            alert('❌ La cantidad debe ser un número entre 1 y 50.');
            return false;
        }
        
        // 4. Confirmación
        const totalEtiquetas = librosIds.length * cantidad;
        const confirmar = confirm(
            '¿Generar etiquetas para imprimir?\n\n' +
            `📚 Libros seleccionados: ${librosIds.length}\n` +
            `📄 Cantidad por libro: ${cantidad}\n` +
            `🖨️ Total etiquetas: ${totalEtiquetas}\n\n` +
            '¿Continuar?'
        );
        
        if (!confirmar) {
            return false;
        }
        
        // 5. Mostrar loading SOLO DESPUÉS DE CONFIRMAR
        mostrarLoading();
        const btnGenerar = document.getElementById('btnGenerarAjax');
        
        btnGenerar.disabled = true;
        btnGenerar.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i> Procesando...';
        
        try {
            // 6. Enviar datos por AJAX
            const response = await fetch('procesar_etiquetas_ajax.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams({
                    libros: JSON.stringify(librosIds),
                    config: JSON.stringify(config),
                    generar_etiquetas: '1'
                })
            });
            
            const data = await response.json();
            
            // 7. Procesar respuesta
            if (data.success) {
                // Éxito - Cambiar mensaje brevemente
                btnGenerar.innerHTML = '<i class="fas fa-check me-1"></i> ¡Listo!';
                
                // Pequeña pausa para mostrar el mensaje de éxito (100ms)
                setTimeout(() => {
                    // Redirigir a la página de impresión
                    window.location.href = 'imprimir_etiquetas.php';
                }, 100);
                
            } else {
                // Error - Ocultar loading y restaurar botón
                ocultarLoading();
                alert('❌ Error: ' + (data.message || 'Error al generar etiquetas'));
                btnGenerar.disabled = false;
                btnGenerar.innerHTML = '<i class="fas fa-print me-1"></i> Generar Etiquetas';
            }
            
        } catch (error) {
            console.error('Error en la solicitud:', error);
            ocultarLoading();
            alert('❌ Error de conexión. Intente nuevamente.');
            btnGenerar.disabled = false;
            btnGenerar.innerHTML = '<i class="fas fa-print me-1"></i> Generar Etiquetas';
        }
        
        return false;
    }
    
    // ==============================================
    // INICIALIZACIÓN
    // ==============================================
    
    document.addEventListener('DOMContentLoaded', function() {
        // Evento para el botón principal
        const btnGenerar = document.getElementById('btnGenerarAjax');
        if (btnGenerar) {
            btnGenerar.addEventListener('click', generarEtiquetasConAjax);
        }
        
        // Evento para búsqueda
        const inputBusqueda = document.getElementById('buscarLibros');
        if (inputBusqueda) {
            inputBusqueda.addEventListener('input', buscarLibros);
        }
        
        // Eventos para checkboxes
        document.querySelectorAll('.libro-check').forEach(check => {
            check.addEventListener('change', function() {
                this.closest('tr').classList.toggle('table-active', this.checked);
                updateSelectionCount();
            });
        });
        
        // Inicializar contador
        updateSelectionCount();
        
        // Evento para cambio de tipo de código
        document.getElementById('tipo_barcode').addEventListener('change', function() {
            const tipo = this.value;
            const descripciones = {
                'C128': 'Estándar industrial, muy compacto',
                'C39': 'Solo mayúsculas y números'
            };
            document.getElementById('descripcion-tipo').textContent = descripciones[tipo] || '';
        });
    });
    </script>
<?php
$contenido = ob_get_clean(); 
$GLOBALS['pageStyles'] = $pageStyles;
$GLOBALS['pageScripts'] = $pageScripts;
// Incluir el layout
include 'layout.php';
?>