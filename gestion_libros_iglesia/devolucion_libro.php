<?php
// devolucion_libro.php - VERSIÓN MODIFICADA

// 1. VERIFICACIÓN DE ACCESO (AGREGAR ESTO AL INICIO)
require_once 'db.php';
verificarAutenticacion(); // ← ESTA LÍNEA ES NUEVA

// 2. REGISTRAR ACCESO A ESTA PÁGINA
$db->registrarAccion('acceso', 'devoluciones', "Accedió al módulo de devolución de libros");

// La agregue para que detecte que viene de gestion prestamo
if (isset($_GET['from']) && $_GET['from'] === 'gestion' && isset($_GET['codigo'])) {
    // 3. REGISTRAR REDIRECCIÓN DESDE GESTIÓN
    $db->registrarAccion(
        'redireccion_gestion', 
        'devoluciones', 
        "Redirigido desde gestión con código: " . htmlspecialchars($_GET['codigo'])
    );
    
    // Pre-llenar el campo de escáner
    echo '<script>
    document.addEventListener("DOMContentLoaded", function() {
        document.getElementById("codigo").value = "' . htmlspecialchars($_GET['codigo']) . '";
        // Opcional: disparar búsqueda automática
        setTimeout(function() {
            buscarPrestamo("' . htmlspecialchars($_GET['codigo']) . '");
        }, 500);
    });
    </script>';
}

// Configurar variables para layout
$titulo_pagina = '📖 Devolución de Libros';
$icono_titulo = 'fas fa-exchange-alt';

// 4. REGISTRAR CONSULTA DE PRÉSTAMOS ACTIVOS
$db->registrarAccion('consulta_activos', 'devoluciones', "Consultando préstamos activos para devolución");

// Obtener préstamos activos para mostrar
$query_activos = "SELECT p.*, l.titulo, l.codigo_interno, l.isbn,
                         lec.nombre, lec.apellido, lec.email, lec.telefono,
                         DATEDIFF(p.fecha_devolucion, CURDATE()) as dias_restantes
                  FROM prestamos p
                  JOIN libros l ON p.id_libro = l.id
                  LEFT JOIN lectores lec ON p.id_lector = lec.id
                  WHERE p.devuelto = 0
                  ORDER BY p.fecha_devolucion ASC
                  LIMIT 10";
$result_activos = mysqli_query($link, $query_activos);

// 5. REGISTRAR RESULTADO DE CONSULTA
if ($result_activos) {
    $num_activos = mysqli_num_rows($result_activos);
    $db->registrarAccion(
        'consulta_exitosa', 
        'devoluciones', 
        "Encontrados {$num_activos} préstamos activos para devolución"
    );
} else {
    $db->registrarAccion(
        'error_consulta', 
        'devoluciones', 
        "Error al consultar préstamos activos: " . mysqli_error($link)
    );
}

// 6. PROCESAR DEVOLUCIÓN SI SE RECIBE POR POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Identificar si es búsqueda o confirmación de devolución
    if (isset($_POST['buscar_codigo'])) {
        $codigo = trim($_POST['codigo'] ?? '');
        
        // 7. REGISTRAR BÚSQUEDA DE CÓDIGO
        $db->registrarAccion(
            'busqueda_codigo', 
            'devoluciones', 
            "Buscando préstamo por código: '{$codigo}'"
        );
        
        // ... tu código de búsqueda ...
        
    } elseif (isset($_POST['confirmar_devolucion'])) {
        $prestamo_id = $_POST['prestamo_id'] ?? 0;
        $observaciones = trim($_POST['observaciones'] ?? '');
        $condicion_libro = $_POST['condicion_libro'] ?? 'bueno';
        
        // 8. REGISTRAR INICIO DE DEVOLUCIÓN
        $db->registrarAccion(
            'inicio_devolucion', 
            'devoluciones', 
            "Iniciando devolución - Préstamo ID: {$prestamo_id}, " .
            "Condición: {$condicion_libro}, " .
            "Observaciones: " . substr($observaciones, 0, 100)
        );
        
        // Obtener información del préstamo antes de procesar
        $sql_info = "SELECT p.*, l.titulo, l.id as libro_id, l.stock, 
                            lec.nombre, lec.apellido, lec.email
                     FROM prestamos p
                     JOIN libros l ON p.id_libro = l.id
                     LEFT JOIN lectores lec ON p.id_lector = lec.id
                     WHERE p.id = ? AND p.devuelto = 0";
        
        $stmt_info = $db->query($sql_info, [$prestamo_id]);
        $prestamo_info = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt_info));
        
        if (!$prestamo_info) {
            // 9. REGISTRAR ERROR - PRÉSTAMO NO ENCONTRADO
            $db->registrarAccion(
                'error_no_encontrado', 
                'devoluciones', 
                "Préstamo no encontrado o ya devuelto - ID: {$prestamo_id}"
            );
            
            $error = "Préstamo no encontrado o ya devuelto";
            
        } else {
            // Iniciar transacción para asegurar consistencia
            mysqli_begin_transaction($link);
            
            try {
                // 1. Marcar préstamo como devuelto
                $sql_update = "UPDATE prestamos 
                               SET devuelto = 1, 
                                   fecha_devolucion_real = CURDATE(),
                                   observaciones_devolucion = ?
                               WHERE id = ?";
                $stmt_update = $db->query($sql_update, [$observaciones, $prestamo_id]);
                
                if (!$stmt_update) {
                    throw new Exception("Error al actualizar préstamo");
                }
                
                // 2. Actualizar stock del libro
                $sql_stock = "UPDATE libros SET stock = stock + 1 WHERE id = ?";
                $stmt_stock = $db->query($sql_stock, [$prestamo_info['libro_id']]);
                
                if (!$stmt_stock) {
                    throw new Exception("Error al actualizar stock");
                }
                
                // 3. Registrar condición del libro (si tienes tabla para esto)
                if ($condicion_libro !== 'bueno') {
                    $sql_condicion = "INSERT INTO libro_condiciones 
                                     (libro_id, prestamo_id, condicion, observaciones, fecha)
                                     VALUES (?, ?, ?, ?, CURDATE())";
                    $db->query($sql_condicion, [
                        $prestamo_info['libro_id'],
                        $prestamo_id,
                        $condicion_libro,
                        $observaciones
                    ]);
                }
                
                // Confirmar transacción
                mysqli_commit($link);
                
                // 10. REGISTRAR DEVOLUCIÓN EXITOSA
                $db->registrarAccion(
                    'devolucion_exitosa', 
                    'devoluciones', 
                    "Devolución completada - Préstamo ID: {$prestamo_id}, " .
                    "Libro: '{$prestamo_info['titulo']}' (ID: {$prestamo_info['libro_id']}), " .
                    "Lector: {$prestamo_info['nombre']} {$prestamo_info['apellido']}, " .
                    "Stock actualizado de {$prestamo_info['stock']} a " . ($prestamo_info['stock'] + 1)
                );
                
                // 11. REGISTRAR STOCK RESTAURADO
                $db->registrarAccion(
                    'stock_restaurado', 
                    'inventario', 
                    "Stock restaurado por devolución - " .
                    "Libro ID: {$prestamo_info['libro_id']}, " .
                    "Nuevo stock: " . ($prestamo_info['stock'] + 1)
                );
                
                // Preparar datos para mostrar confirmación
                $devolucion_exitosa = true;
                $datos_devolucion = [
                    'prestamo_id' => $prestamo_id,
                    'libro_titulo' => $prestamo_info['titulo'],
                    'lector_nombre' => $prestamo_info['nombre'] . ' ' . $prestamo_info['apellido'],
                    'fecha_prestamo' => $prestamo_info['fecha_prestamo'],
                    'fecha_devolucion_estimada' => $prestamo_info['fecha_devolucion'],
                    'condicion' => $condicion_libro,
                    'observaciones' => $observaciones,
                    'nuevo_stock' => $prestamo_info['stock'] + 1
                ];
                
                // Generar número de recibo de devolución
                $numero_recibo = 'DEV-' . str_pad($prestamo_id, 6, '0', STR_PAD_LEFT) . '-' . date('Ymd');
                
                $db->registrarAccion(
                    'recibo_devolucion', 
                    'devoluciones', 
                    "Recibo de devolución generado: {$numero_recibo}"
                );
                
            } catch (Exception $e) {
                // Revertir transacción en caso de error
                mysqli_rollback($link);
                
                // 12. REGISTRAR ERROR EN DEVOLUCIÓN
                $db->registrarAccion(
                    'error_devolucion', 
                    'devoluciones', 
                    "Error en devolución - Préstamo ID: {$prestamo_id}, " .
                    "Mensaje: " . $e->getMessage()
                );
                
                $error = "Error al procesar la devolución: " . $e->getMessage();
            }
        }
    }
}

ob_start();
?>

<div class="row">
    <!-- COLUMNA IZQUIERDA: Escáner -->
    <div class="col-md-5 mb-4">
        <div class="card">
            <div class="card-header gradient-primary">
                <h5 class="mb-0"><i class="fas fa-barcode me-2"></i>Escanear Libro</h5>
            </div>
            <div class="card-body">
                <!-- Formulario para escaneo -->
                <div class="mb-4">
                    <label for="codigo" class="form-label">
                        <i class="fas fa-qrcode me-1"></i>Escanea el código ISBN o Interno
                    </label>
                    <div class="input-group input-group-lg">
                        <input type="text" 
                               class="form-control" 
                               id="codigo" 
                               name="codigo" 
                               placeholder="Pase el código por el escáner..."
                               autofocus
                               autocomplete="off">
                        <button class="btn btn-warning" type="button" id="btn-manual">
                            <i class="fas fa-keyboard"></i> Manual
                        </button>
                    </div>
                    <div class="form-text mt-2">
                        <i class="fas fa-info-circle text-primary"></i>
                        El sistema buscará automáticamente al detectar un código válido
                    </div>
                </div>
                
                <!-- Indicador de estado -->
                <div id="escanner-status" class="alert alert-info d-none">
                    <div class="d-flex align-items-center">
                        <div class="spinner-border spinner-border-sm me-2" role="status"></div>
                        <span id="status-text">Buscando préstamo...</span>
                    </div>
                </div>
                
                <!-- Estadísticas rápidas -->
                <div class="card mt-4">
                    <div class="card-body p-3">
                        <div class="row text-center">
                            <div class="col-4">
                                <div class="h4 mb-0" id="total-activos">0</div>
                                <small class="text-muted">Activos</small>
                            </div>
                            <div class="col-4">
                                <div class="h4 mb-0 text-warning" id="por-vencer">0</div>
                                <small class="text-muted">Por vencer</small>
                            </div>
                            <div class="col-4">
                                <div class="h4 mb-0 text-danger" id="vencidos">0</div>
                                <small class="text-muted">Vencidos</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Instrucciones -->
        <div class="card mt-4">
            <div class="card-header gradient-book">
                <h6 class="mb-0"><i class="fas fa-info-circle me-2"></i>Instrucciones Rápidas</h6>
            </div>
            <div class="card-body">
                <ol class="mb-0 small">
                    <li class="mb-1">Coloque el libro frente al escáner</li>
                    <li class="mb-1">Espere el sonido de confirmación</li>
                    <li class="mb-1">Revise los datos en el pop-up</li>
                    <li class="mb-1">Confirme la devolución</li>
                    <li>El libro volverá al stock automáticamente</li>
                </ol>
            </div>
        </div>
    </div>
    
    <!-- COLUMNA DERECHA: Préstamos Activos -->
    <div class="col-md-7 mb-4">
        <div class="card">
            <div class="card-header gradient-warning">
                <h5 class="mb-0">
                    <i class="fas fa-clock me-2"></i>Préstamos Activos
                    <span class="badge bg-light text-dark ms-2" id="contador-activos">0</span>
                </h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0" id="tabla-activos">
                        <thead>
                            <tr>
                                <th width="40%">Libro</th>
                                <th width="30%">Persona</th>
                                <th width="20%">Devolución</th>
                                <th width="10%" class="text-center">Acción</th>
                            </tr>
                        </thead>
                        <tbody id="cuerpo-tabla">
                            <!-- Se llenará con JavaScript -->
                        </tbody>
                    </table>
                </div>
                
                <!-- Estado vacío -->
                <div id="estado-vacio" class="text-center py-5 d-none">
                    <i class="fas fa-check-circle fa-3x text-success mb-3"></i>
                    <h5>¡Todos los libros están devueltos!</h5>
                    <p class="text-muted">No hay préstamos activos en este momento.</p>
                </div>
            </div>
</div>

<!-- MODAL PARA INGRESO MANUAL -->
<div class="modal fade" id="modalManual" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header gradient-warning text-white">
                <h5 class="modal-title">
                    <i class="fas fa-keyboard me-2"></i>
                    Ingresar Código Manualmente
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label for="codigo-manual" class="form-label">
                        <i class="fas fa-barcode me-1"></i> ISBN o Código Interno
                    </label>
                    <input type="text" 
                           class="form-control form-control-lg" 
                           id="codigo-manual" 
                           placeholder="Ej: 978-1-59856-200-1 o BIB-001"
                           autofocus>
                    <div class="form-text">
                        Ingresa el código ISBN (13 dígitos) o el código interno del libro
                    </div>
                </div>
                
                <div class="alert alert-info">
                    <i class="fas fa-info-circle me-2"></i>
                    <strong>Tip:</strong> También puedes pegar el código copiado desde otra pantalla
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="fas fa-times me-1"></i> Cancelar
                </button>
                <button type="button" class="btn btn-primary" id="btn-buscar-manual">
                    <i class="fas fa-search me-1"></i> Buscar Préstamo
                </button>
            </div>
        </div>
    </div>
</div>

<!-- MODAL DE CONFIRMACIÓN DE DEVOLUCIÓN -->
<div class="modal fade" id="modalDevolucion" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header gradient-success text-white">
                <h5 class="modal-title">
                    <i class="fas fa-check-circle me-2"></i>
                    Confirmar Devolución
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="modal-body">
                <!-- Se llenará dinámicamente -->
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="fas fa-times me-1"></i> Cancelar
                </button>
                <button type="button" class="btn btn-success" id="btn-confirmar-devolucion">
                    <i class="fas fa-check me-1"></i> Confirmar Devolución
                </button>
            </div>
        </div>
    </div>
</div>

<!-- MODAL DE ERROR -->
<div class="modal fade" id="modalError" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header gradient-danger text-white">
                <h5 class="modal-title">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    No se encontró el préstamo
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="modal-error-body">
                <!-- Se llenará dinámicamente -->
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-primary" data-bs-dismiss="modal">
                    <i class="fas fa-redo me-1"></i> Intentar con otro código
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Scripts AJAX y Funcionalidad -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    const codigoInput = document.getElementById('codigo');
    const modalDevolucion = new bootstrap.Modal(document.getElementById('modalDevolucion'));
    const modalError = new bootstrap.Modal(document.getElementById('modalError'));
    let prestamoActual = null;
    
    // Cargar préstamos activos al iniciar
    cargarPrestamosActivos();
    cargarEstadisticas();
    
    // ============================================
    // DETECCIÓN MEJORADA PARA ESCÁNER DE CÓDIGOS
    // ============================================
    let escaneando = false;
    let codigoBuffer = '';
    let ultimoTiempoTecla = 0;
    const TIEMPO_ENTRE_TECLAS = 50;
    
    codigoInput.addEventListener('keydown', function(e) {
        const tiempoActual = new Date().getTime();
        
        // Ignorar teclas especiales excepto Enter
        if (e.key.length > 1 && e.key !== 'Enter') {
            return;
        }
        
        // Si es Enter y hay contenido
        if (e.key === 'Enter') {
            e.preventDefault();
            
            const codigo = this.value.trim();
            if (codigo.length >= 3) {
                buscarPrestamo(codigo);
                this.value = '';
                codigoBuffer = '';
            }
            return;
        }
        
        // Para escáneres que no envían Enter
        if (tiempoActual - ultimoTiempoTecla > TIEMPO_ENTRE_TECLAS) {
            codigoBuffer = '';
        }
        
        codigoBuffer += e.key;
        ultimoTiempoTecla = tiempoActual;
        
        // Detectar si es un código completo
        if (codigoBuffer.length >= 8 && !/\s/.test(codigoBuffer)) {
            clearTimeout(window.tiempoEscaneo);
            window.tiempoEscaneo = setTimeout(() => {
                if (codigoBuffer.length >= 8) {
                    buscarPrestamo(codigoBuffer);
                    this.value = '';
                    codigoBuffer = '';
                }
            }, 150);
        }
    });
    
    codigoInput.addEventListener('input', function(e) {
        const codigo = this.value.trim();
        
        if (codigo.length >= 8 && !/\s/.test(codigo) && !codigo.includes(' ')) {
            clearTimeout(window.tiempoInput);
            window.tiempoInput = setTimeout(() => {
                buscarPrestamo(codigo);
                this.value = '';
            }, 200);
        }
    });
    
    codigoInput.addEventListener('keyup', function(e) {
        if (e.key === 'Enter') {
            const codigo = this.value.trim();
            if (codigo.length >= 3) {
                buscarPrestamo(codigo);
                this.value = '';
            }
        }
    });
    
    // ============================================
    // BOTÓN PARA INGRESO MANUAL - VERSIÓN CORREGIDA
    // ============================================
    
    // 1. Abrir modal al hacer clic en el botón manual
    document.getElementById('btn-manual').addEventListener('click', function() {
        const modalElement = document.getElementById('modalManual');
        const modalManual = new bootstrap.Modal(modalElement);
        modalManual.show();
        
        setTimeout(() => {
            document.getElementById('codigo-manual').focus();
        }, 500);
    });
    
    // 2. Buscar cuando se haga clic en el botón del modal
    document.getElementById('btn-buscar-manual').addEventListener('click', function() {
        procesarBusquedaManual();
    });
    
    // 3. También buscar al presionar Enter en el campo del modal
    document.getElementById('codigo-manual').addEventListener('keypress', function(e) {
        if (e.key === 'Enter') {
            procesarBusquedaManual();
        }
    });
    
    // Función corregida para procesar búsqueda manual
    function procesarBusquedaManual() {
        const inputManual = document.getElementById('codigo-manual');
        const codigo = inputManual.value.trim();
        
        if (!codigo || codigo.length < 3) {
            mostrarErrorEnModal('El código debe tener al menos 3 caracteres');
            return;
        }
        
        // Cerrar modal primero
        const modalElement = document.getElementById('modalManual');
        const modalInstance = bootstrap.Modal.getInstance(modalElement);
        if (modalInstance) {
            modalInstance.hide();
        }
        
        // Limpiar campo
        inputManual.value = '';
        
        // EJECUTAR BÚSQUEDA DIRECTAMENTE
        buscarPrestamo(codigo);
    }
    
    // Función auxiliar para mostrar errores en el modal
    function mostrarErrorEnModal(mensaje) {
        // Remover alertas anteriores
        const alertasAnteriores = document.querySelectorAll('#modalManual .alert-danger');
        alertasAnteriores.forEach(alerta => alerta.remove());
        
        // Crear nueva alerta
        const errorDiv = document.createElement('div');
        errorDiv.className = 'alert alert-danger alert-dismissible fade show mt-3';
        errorDiv.innerHTML = `
            <i class="fas fa-exclamation-circle me-2"></i>
            <strong>Error:</strong> ${mensaje}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        `;
        
        // Insertar después del campo
        const campoCodigo = document.getElementById('codigo-manual').parentNode;
        campoCodigo.parentNode.insertBefore(errorDiv, campoCodigo.nextSibling);
        
        // Auto-remover después de 5 segundos
        setTimeout(() => {
            if (errorDiv.parentNode) {
                errorDiv.remove();
            }
        }, 5000);
    }
    
    // ============================================
    // FUNCIÓN PARA BUSCAR PRÉSTAMO - VERSIÓN SEGURA
    // ============================================
    function buscarPrestamo(codigo) {
        if (!codigo || codigo.length < 3) {
            console.log('Código muy corto:', codigo);
            return;
        }
        
        console.log('Buscando préstamo para código:', codigo);
        
        codigo = codigo.trim();
        codigo = codigo.replace(/[^a-zA-Z0-9\-]/g, '');
        
        // Mostrar estado SOLO si los elementos existen
        try {
            if (document.getElementById('escanner-status') && document.getElementById('status-text')) {
                mostrarEstadoEscaneo(true, `Buscando préstamo: <strong>${codigo}</strong>`);
            }
        } catch (e) {
            console.log('No se pudo mostrar estado de escaneo:', e);
        }
        
        // Reproducir sonido
        playBeepSound();
        
        // Hacer petición AJAX
        const controller = new AbortController();
        const timeoutId = setTimeout(() => controller.abort(), 10000);
        
        fetch(`buscar_prestamo.php?codigo=${encodeURIComponent(codigo)}`, {
            signal: controller.signal
        })
        .then(response => {
            clearTimeout(timeoutId);
            if (!response.ok) {
                throw new Error('Error en la respuesta del servidor');
            }
            return response.json();
        })
        .then(data => {
            // Ocultar estado SOLO si los elementos existen
            try {
                if (document.getElementById('escanner-status')) {
                    mostrarEstadoEscaneo(false);
                }
            } catch (e) {
                console.log('No se pudo ocultar estado de escaneo:', e);
            }
            
            if (data.success && data.prestamo) {
                prestamoActual = data.prestamo;
                mostrarModalDevolucion(data.prestamo);
            } else {
                mostrarModalError(codigo, data.message || 'No se encontró préstamo activo');
            }
        })
        .catch(error => {
            clearTimeout(timeoutId);
            
            // Ocultar estado SOLO si los elementos existen
            try {
                if (document.getElementById('escanner-status')) {
                    mostrarEstadoEscaneo(false);
                }
            } catch (e) {
                console.log('No se pudo ocultar estado de escaneo:', e);
            }
            
            if (error.name === 'AbortError') {
                mostrarModalError(codigo, 'Tiempo de espera agotado');
            } else {
                mostrarModalError(codigo, 'Error de conexión con el servidor');
            }
            console.error('Error:', error);
        });
    }
    
    // ============================================
    // FUNCIÓN PARA MOSTRAR MODAL DE DEVOLUCIÓN
    // ============================================
    function mostrarModalDevolucion(prestamo) {
        const fechaPrestamo = new Date(prestamo.fecha_prestamo).toLocaleDateString('es-ES');
        const fechaDevolucion = new Date(prestamo.fecha_devolucion).toLocaleDateString('es-ES');
        const hoy = new Date().toLocaleDateString('es-ES');
        const diasTranscurridos = Math.floor((new Date() - new Date(prestamo.fecha_prestamo)) / (1000 * 60 * 60 * 24));
        
        let estadoHTML = '';
        let estadoClase = '';
        if (prestamo.devuelto) {
            estadoHTML = '<span class="badge bg-success">DEVUELTO</span>';
            estadoClase = 'success';
        } else if (new Date(prestamo.fecha_devolucion) < new Date()) {
            estadoHTML = `<span class="badge bg-danger">VENCIDO (${prestamo.dias_restantes * -1} días)</span>`;
            estadoClase = 'danger';
        } else if (prestamo.dias_restantes <= 3) {
            estadoHTML = `<span class="badge bg-warning">POR VENCER (${prestamo.dias_restantes} días)</span>`;
            estadoClase = 'warning';
        } else {
            estadoHTML = `<span class="badge bg-primary">ACTIVO (${prestamo.dias_restantes} días restantes)</span>`;
            estadoClase = 'primary';
        }
        
        const modalHTML = `
            <div class="row">
                <div class="col-md-6">
                    <div class="card border-${estadoClase} mb-3">
                        <div class="card-header bg-${estadoClase} text-white">
                            <h6 class="mb-0"><i class="fas fa-book me-2"></i>Información del Libro</h6>
                        </div>
                        <div class="card-body">
                            <h5 class="card-title">${prestamo.titulo}</h5>
                            <p class="card-text">
                                <strong>Autor:</strong> ${prestamo.autor || 'No especificado'}<br>
                                <strong>Código:</strong> <code>${prestamo.codigo_interno}</code><br>
                                ${prestamo.isbn ? `<strong>ISBN:</strong> <code>${prestamo.isbn}</code><br>` : ''}
                                <strong>Año:</strong> ${prestamo.año_publicacion || '--'}
                            </p>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-6">
                    <div class="card border-${estadoClase} mb-3">
                        <div class="card-header bg-${estadoClase} text-white">
                            <h6 class="mb-0"><i class="fas fa-calendar-alt me-2"></i>Detalles del Préstamo</h6>
                        </div>
                        <div class="card-body">
                            <p class="card-text">
                                <strong>Estado:</strong> ${estadoHTML}<br>
                                <strong>Préstamo:</strong> ${fechaPrestamo}<br>
                                <strong>Devolución:</strong> ${fechaDevolucion}<br>
                                <strong>Días transcurridos:</strong> ${diasTranscurridos} días<br>
                                <strong>ID Préstamo:</strong> #${prestamo.id}
                            </p>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="row mt-2">
                <div class="col-12">
                    <div class="card border-info">
                        <div class="card-header bg-info text-white">
                            <h6 class="mb-0"><i class="fas fa-user me-2"></i>Información de la Persona</h6>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-4">
                                    <strong>Nombre:</strong><br>
                                    ${prestamo.nombre} ${prestamo.apellido}
                                </div>
                                <div class="col-md-4">
                                    <strong>Contacto:</strong><br>
                                    ${prestamo.email || 'Sin email'}<br>
                                    ${prestamo.telefono || 'Sin teléfono'}
                                </div>
                                <div class="col-md-4">
                                    <strong>Dirección:</strong><br>
                                    ${prestamo.direccion || 'Sin dirección'}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="alert alert-${estadoClase} mt-3">
                <div class="d-flex align-items-center">
                    <i class="fas fa-info-circle fa-2x me-3"></i>
                    <div>
                        <strong>Resumen:</strong> ${prestamo.nombre} tiene prestado 
                        "${prestamo.titulo}" desde el ${fechaPrestamo}. 
                        ${prestamo.devuelto ? 'Ya fue devuelto.' : 
                          new Date(prestamo.fecha_devolucion) < new Date() ? 
                          '¡ESTÁ VENCIDO!' : 
                          'Vence el ' + fechaDevolucion + '.'}
                    </div>
                </div>
            </div>
        `;
        
        document.getElementById('modal-body').innerHTML = modalHTML;
        modalDevolucion.show();
    }
    
    // ============================================
    // FUNCIÓN PARA MOSTRAR MODAL DE ERROR
    // ============================================
    function mostrarModalError(codigo, mensaje) {
        const modalHTML = `
            <div class="text-center py-3">
                <i class="fas fa-times-circle fa-4x text-danger mb-3"></i>
                <h4 class="text-danger">No se encontró préstamo activo</h4>
                <p class="lead">Código: <code>${codigo}</code></p>
                <div class="alert alert-warning">
                    <i class="fas fa-lightbulb me-2"></i>
                    ${mensaje || 'Posibles causas:'}
                    <ul class="mt-2 mb-0">
                        <li>El libro ya fue devuelto anteriormente</li>
                        <li>El código es incorrecto</li>
                        <li>El libro no está registrado como prestado</li>
                        <li>No existe un libro con ese código en el sistema</li>
                    </ul>
                </div>
                <p class="text-muted">Verifique el código e intente nuevamente.</p>
            </div>
        `;
        
        document.getElementById('modal-error-body').innerHTML = modalHTML;
        modalError.show();
    }
    
    // ============================================
    // CONFIRMAR DEVOLUCIÓN
    // ============================================
    document.getElementById('btn-confirmar-devolucion').addEventListener('click', function() {
        if (!prestamoActual) return;
        
        const btn = this;
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i> Procesando...';
        
        fetch('procesar_devolucion.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ id: prestamoActual.id })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                document.getElementById('modal-body').innerHTML = `
                    <div class="text-center py-5">
                        <i class="fas fa-check-circle fa-4x text-success mb-3"></i>
                        <h3 class="text-success">¡Devolución Exitosa!</h3>
                        <div class="alert alert-success mt-3">
                            <i class="fas fa-book me-2"></i>
                            <strong>${prestamoActual.titulo}</strong><br>
                            ha sido devuelto por <strong>${prestamoActual.nombre} ${prestamoActual.apellido}</strong>
                        </div>
                        <p class="text-muted">El stock del libro ha sido actualizado automáticamente.</p>
                    </div>
                `;
                
                document.querySelector('.modal-footer').style.display = 'none';
                
                setTimeout(() => {
                    modalDevolucion.hide();
                    cargarPrestamosActivos();
                    cargarEstadisticas();
                    prestamoActual = null;
                    
                    document.querySelector('.modal-footer').style.display = 'flex';
                    btn.disabled = false;
                    btn.innerHTML = '<i class="fas fa-check me-1"></i> Confirmar Devolución';
                    
                    playSuccessSound();
                }, 2000);
            } else {
                alert('Error: ' + (data.message || 'No se pudo procesar la devolución'));
                btn.disabled = false;
                btn.innerHTML = '<i class="fas fa-check me-1"></i> Confirmar Devolución';
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error de conexión');
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-check me-1"></i> Confirmar Devolución';
        });
    });
    
    // ============================================
    // FUNCIONES AUXILIARES
    // ============================================
    function mostrarEstadoEscaneo(mostrar, texto = '') {
        const statusDiv = document.getElementById('escanner-status');
        const statusText = document.getElementById('status-text');
        
        // Verificar que los elementos existen
        if (!statusDiv || !statusText) {
            return;
        }
        
        if (mostrar) {
            statusDiv.classList.remove('d-none');
            statusText.innerHTML = texto;
        } else {
            statusDiv.classList.add('d-none');
        }
    }
    
    function cargarPrestamosActivos() {
        fetch('obtener_prestamos_activos.php')
            .then(response => response.json())
            .then(data => {
                const cuerpo = document.getElementById('cuerpo-tabla');
                const estadoVacio = document.getElementById('estado-vacio');
                const contador = document.getElementById('contador-activos');
                
                if (data.length === 0) {
                    cuerpo.innerHTML = '';
                    estadoVacio.classList.remove('d-none');
                    contador.textContent = '0';
                    return;
                }
                
                estadoVacio.classList.add('d-none');
                contador.textContent = data.length;
                
                let html = '';
                data.forEach(prestamo => {
                    const fechaDev = new Date(prestamo.fecha_devolucion).toLocaleDateString('es-ES');
                    const diasRestantes = prestamo.dias_restantes;
                    
                    let estadoBadge = '';
                    if (diasRestantes < 0) {
                        estadoBadge = `<span class="badge bg-danger">Vencido</span>`;
                    } else if (diasRestantes <= 3) {
                        estadoBadge = `<span class="badge bg-warning">${diasRestantes}d</span>`;
                    } else {
                        estadoBadge = `<span class="badge bg-success">${diasRestantes}d</span>`;
                    }
                    
                    html += `
                        <tr>
                            <td>
                                <div class="fw-bold">${prestamo.titulo}</div>
                                <small class="text-muted">${prestamo.codigo_interno}</small>
                            </td>
                            <td>${prestamo.nombre} ${prestamo.apellido}</td>
                            <td>
                                ${fechaDev}<br>
                                ${estadoBadge}
                            </td>
                            <td class="text-center">
                                <button class="btn btn-sm btn-outline-primary usar-codigo" 
                                        data-codigo="${prestamo.isbn || prestamo.codigo_interno}"
                                        title="Usar este código">
                                    <i class="fas fa-arrow-right"></i>
                                </button>
                            </td>
                        </tr>
                    `;
                });
                
                cuerpo.innerHTML = html;
                
                document.querySelectorAll('.usar-codigo').forEach(btn => {
                    btn.addEventListener('click', function() {
                        const codigo = this.getAttribute('data-codigo');
                        codigoInput.value = codigo;
                        buscarPrestamo(codigo);
                    });
                });
            })
            .catch(error => console.error('Error cargando préstamos:', error));
    }
    
    function cargarEstadisticas() {
        fetch('obtener_estadisticas.php')
            .then(response => response.json())
            .then(data => {
                document.getElementById('total-activos').textContent = data.total_activos || 0;
                document.getElementById('por-vencer').textContent = data.por_vencer || 0;
                document.getElementById('vencidos').textContent = data.vencidos || 0;
            })
            .catch(error => console.error('Error cargando estadísticas:', error));
    }
    
    function playBeepSound() {
        try {
            const audioContext = new (window.AudioContext || window.webkitAudioContext)();
            const oscillator = audioContext.createOscillator();
            const gainNode = audioContext.createGain();
            
            oscillator.connect(gainNode);
            gainNode.connect(audioContext.destination);
            
            oscillator.frequency.value = 1000;
            oscillator.type = 'sine';
            
            gainNode.gain.setValueAtTime(0.3, audioContext.currentTime);
            gainNode.gain.exponentialRampToValueAtTime(0.01, audioContext.currentTime + 0.1);
            
            oscillator.start(audioContext.currentTime);
            oscillator.stop(audioContext.currentTime + 0.1);
        } catch (e) {
            console.log('No se pudo reproducir sonido');
        }
    }
    
    function playSuccessSound() {
        try {
            const audioContext = new (window.AudioContext || window.webkitAudioContext)();
            const oscillator = audioContext.createOscillator();
            const gainNode = audioContext.createGain();
            
            oscillator.connect(gainNode);
            gainNode.connect(audioContext.destination);
            
            oscillator.frequency.setValueAtTime(523.25, audioContext.currentTime);
            oscillator.frequency.setValueAtTime(659.25, audioContext.currentTime + 0.1);
            oscillator.frequency.setValueAtTime(783.99, audioContext.currentTime + 0.2);
            
            oscillator.type = 'sine';
            
            gainNode.gain.setValueAtTime(0.3, audioContext.currentTime);
            gainNode.gain.exponentialRampToValueAtTime(0.01, audioContext.currentTime + 0.3);
            
            oscillator.start(audioContext.currentTime);
            oscillator.stop(audioContext.currentTime + 0.3);
        } catch (e) {
            console.log('No se pudo reproducir sonido de éxito');
        }
    }
    
    // ============================================
    // BÚSQUEDA EN LA TABLA
    // ============================================
    document.getElementById('btn-buscar').addEventListener('click', function() {
        const texto = document.getElementById('busqueda-manual').value.trim();
        if (texto.length >= 2) {
            buscarPrestamo(texto);
        }
    });
    
    document.getElementById('busqueda-manual').addEventListener('keypress', function(e) {
        if (e.key === 'Enter') {
            document.getElementById('btn-buscar').click();
        }
    });
    
    // ============================================
    // ATRAJOS DE TECLADO
    // ============================================
    document.addEventListener('keydown', function(e) {
        // Alt + M para abrir modal manual
        if (e.altKey && e.keyCode === 77) {
            e.preventDefault();
            document.getElementById('btn-manual').click();
        }
        
        // Esc para cerrar modal si está abierto
        if (e.key === 'Escape' && document.getElementById('modalManual').classList.contains('show')) {
            const modalManual = bootstrap.Modal.getInstance(document.getElementById('modalManual'));
            modalManual.hide();
            document.getElementById('codigo').focus();
        }
    });
    
    // Cuando se cierra el modal manual, enfocar el campo principal
    document.getElementById('modalManual').addEventListener('hidden.bs.modal', function() {
        setTimeout(() => {
            document.getElementById('codigo').focus();
        }, 100);
    });
    
    // ============================================
    // INICIALIZACIÓN
    // ============================================
    codigoInput.focus();
    
    // Actualizar cada 30 segundos
    setInterval(() => {
        cargarPrestamosActivos();
        cargarEstadisticas();
    }, 30000);
});
</script>
<?php
$contenido = ob_get_clean();
include 'layout.php';
?>