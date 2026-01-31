<?php
// agregar_prestamo.php - VERSIÓN MODIFICADA

// 1. VERIFICACIÓN DE ACCESO (AGREGAR ESTO AL INICIO)
require_once 'db.php';
verificarAutenticacion(); // ← ESTA LÍNEA ES NUEVA

// 2. REGISTRAR ACCESO A ESTA PÁGINA
$db->registrarAccion('acceso', 'prestamos', "Accedió al formulario de nuevo préstamo");

// Configurar variables para layout
$titulo_pagina = '📚 Nuevo Préstamo';
$icono_titulo = 'fas fa-plus-circle';

$error = '';
$libros_disponibles = [];
$lectores_registrados = [];

// Obtener libros disponibles (stock > 0)
$sql_libros = "SELECT id, codigo_interno, titulo, autor, stock FROM libros WHERE stock > 0 ORDER BY titulo";
$result_libros = mysqli_query($link, $sql_libros);
if ($result_libros) {
    while ($row = mysqli_fetch_assoc($result_libros)) {
        $libros_disponibles[] = $row;
    }
}

// Obtener lectores registrados
$sql_lectores = "SELECT id, nombre, apellido, email, telefono, codigo_fiscal FROM lectores ORDER BY apellido, nombre";
$result_lectores = mysqli_query($link, $sql_lectores);
if ($result_lectores) {
    while ($row = mysqli_fetch_assoc($result_lectores)) {
        $lectores_registrados[] = $row;
    }
}

// Determinar tipo de lector (usaremos AJAX, pero mantengamos compatibilidad)
$tipo_lector = $_POST['tipo_lector'] ?? 'existente';

// Procesar el formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // 3. REGISTRAR INTENTO DE CREAR PRÉSTAMO (AGREGAR ESTO)
    $db->registrarAccion('inicio_creacion', 'prestamos', "Inició proceso de creación de préstamo");
    
    // Validación simple
    if (empty($_POST['id_libro']) || empty($_POST['fecha_prestamo'])) {
        $error = "El libro y la fecha de préstamo son obligatorios.";
    } 
    // AQUÍ ESTÁ EL PROBLEMA PRINCIPAL - NO ESTÁS VALIDANDO BIEN EL LECTOR
    elseif (($_POST['tipo_lector'] ?? '') === 'existente' && empty($_POST['id_lector'])) {
        $error = "Debe seleccionar un lector registrado.";
    }
    elseif (($_POST['tipo_lector'] ?? '') === 'nuevo' && 
            (empty($_POST['nombre']) || empty($_POST['apellido']) || empty($_POST['email']))) {
        $error = "Para un nuevo lector, nombre, apellido y email son obligatorios.";
    }
    
    if (!$error) {
        // Preparar datos - AQUÍ ESTÁ EL ERROR
        $datos = [
            'id_libro' => $_POST['id_libro'],
            'tipo_lector' => $_POST['tipo_lector'] ?? 'existente',
            'fecha_prestamo' => $_POST['fecha_prestamo'],
            'fecha_devolucion_estimada' => date('Y-m-d', strtotime($_POST['fecha_prestamo'] . ' +30 days')),
            'nombre' => $_POST['nombre'] ?? '',
            'apellido' => $_POST['apellido'] ?? '',
            'email' => $_POST['email'] ?? '',
            'direccion' => $_POST['direccion'] ?? '',
            'telefono' => $_POST['telefono'] ?? '',
            'codigo_fiscal' => $_POST['codigo_fiscal'] ?? ''
        ];
        
        // CORRECCIÓN: Determinar id_lector correctamente
        if ($datos['tipo_lector'] === 'existente') {
            // Para lector existente
            $datos['id_lector'] = $_POST['id_lector'] ?? '';
            
            // Si es lector existente, obtener sus datos
            if (!empty($datos['id_lector']) && is_numeric($datos['id_lector'])) {
                $sql = "SELECT nombre, apellido, email, telefono, codigo_fiscal FROM lectores WHERE id = ?";
                $stmt = $db->query($sql, [$datos['id_lector']]);
                if ($stmt) {
                    $result = mysqli_stmt_get_result($stmt);
                    if ($row = mysqli_fetch_assoc($result)) {
                        $datos['nombre'] = $row['nombre'];
                        $datos['apellido'] = $row['apellido'];
                        $datos['email'] = $row['email'];
                        $datos['telefono'] = $row['telefono'] ?? '';
                        $datos['codigo_fiscal'] = $row['codigo_fiscal'] ?? '';
                    }
                }
            }
        } else {
            // Para nuevo lector
            $datos['id_lector'] = 'nuevo'; // Esto indica que es un lector nuevo
        }
        
        // 4. REGISTRAR VALIDACIÓN EXITOSA (AGREGAR ESTO)
        $db->registrarAccion(
            'validacion_exitosa', 
            'prestamos', 
            "Datos de préstamo validados - Libro ID: {$datos['id_libro']}, " .
            "Tipo lector: {$datos['tipo_lector']}"
        );
        
        // Guardar en sesión
        $_SESSION['datos_prestamo'] = $datos;
        
        // 5. REGISTRAR REDIRECCIÓN A CONFIRMACIÓN (AGREGAR ESTO)
        $db->registrarAccion('redireccion', 'prestamos', "Redirigiendo a confirmar_prestamo.php con datos validados");
        
        // Redirigir
        header('Location: confirmar_prestamo.php');
        exit();
    } else {
        // 6. REGISTRAR ERROR DE VALIDACIÓN (AGREGAR ESTO)
        $db->registrarAccion(
            'validacion_fallida', 
            'prestamos', 
            "Error en validación: " . $error
        );
    }
}

// Si hay error, mostrar mensaje
if ($error) {
    $mensaje_error = $error;
}

ob_start();
?>

<!-- PARA QUE APAREZCA Y DESAPAREZCA EL MENU DE NUEVO LECTOR -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

<!-- SISTEMA MEJORADO CON JQUERY/AJAX COMO DEVOLUCIONES -->
<div class="row">
    <!-- COLUMNA IZQUIERDA: Escáner y Libro -->
    <div class="col-md-5 mb-4">
        <!-- Escáner de Libro -->
        <div class="card mb-4">
            <div class="card-header gradient-primary">
                <h5 class="mb-0"><i class="fas fa-barcode me-2"></i>Escanear Libro</h5>
            </div>
            <div class="card-body">
                <div class="form-group mb-3">
                    <label for="codigo_scanner" class="form-label">
                        <i class="fas fa-qrcode me-1"></i>Escanear código ISBN o Interno
                    </label>
                    <div class="input-group">
                        <input type="text" 
                               class="form-control" 
                               id="codigo_scanner" 
                               placeholder="Pase el código por el escáner..."
                               autofocus>
                        <button class="btn btn-primary" type="button" id="btn-buscar-codigo">
                            <i class="fas fa-search"></i>
                        </button>
                    </div>
                    <div class="form-text mt-2">
                        <i class="fas fa-info-circle text-primary"></i>
                        El sistema buscará automáticamente al detectar un código válido
                    </div>
                </div>
                
                <div id="scanner-status" class="alert alert-info d-none">
                    <div class="d-flex align-items-center">
                        <div class="spinner-border spinner-border-sm me-2" role="status"></div>
                        <span id="status-text">Buscando libro...</span>
                    </div>
                </div>
                
                <!-- Estadísticas rápidas -->
                <div class="card mt-4">
                    <div class="card-body p-3">
                        <div class="row text-center">
                            <div class="col-6">
                                <div class="h5 mb-0" id="total-libros"><?php echo count($libros_disponibles); ?></div>
                                <small class="text-muted">Libros Disponibles</small>
                            </div>
                            <div class="col-6">
                                <div class="h5 mb-0"><?php echo count($lectores_registrados); ?></div>
                                <small class="text-muted">Lectores Registrados</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Información del Libro Seleccionado -->
        <div class="card">
            <div class="card-header gradient-book">
                <h5 class="mb-0"><i class="fas fa-book me-2"></i>Libro Seleccionado</h5>
            </div>
            <div class="card-body">
                <form method="POST" action="" id="formPrestamo">
                    <div class="form-group mb-3">
                        <label for="id_libro" class="form-label">Libro a Prestar <span class="text-danger">*</span></label>
                        <select name="id_libro" id="id_libro" class="form-select" required>
                            <option value="">-- Seleccione un libro --</option>
                            <?php foreach ($libros_disponibles as $libro): ?>
                                <option value="<?php echo $libro['id']; ?>"
                                        data-codigo="<?php echo htmlspecialchars($libro['codigo_interno']); ?>"
                                        data-titulo="<?php echo htmlspecialchars($libro['titulo']); ?>"
                                        data-autor="<?php echo htmlspecialchars($libro['autor']); ?>">
                                    <?php echo htmlspecialchars($libro['titulo'] . ' - ' . $libro['autor'] . ' (Stock: ' . $libro['stock'] . ')'); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div id="info-libro" class="alert alert-info d-none">
                        <!-- Información del libro se cargará aquí -->
                    </div>
            </div>
        </div>
    </div>
    
    <!-- COLUMNA DERECHA: Lector y Fechas -->
    <div class="col-md-7 mb-4">
        <!-- Datos del Lector -->
        <div class="card mb-4">
            <div class="card-header gradient-primary">
                <h5 class="mb-0"><i class="fas fa-user me-2"></i>Datos del Lector</h5>
            </div>
            <div class="card-body">
                <!-- Sistema de Toggle Mejorado -->
                <div class="form-group mb-4">
                    <label class="form-label d-block mb-3">Tipo de Lector <span class="text-danger">*</span></label>
                    <div class="btn-group w-100" role="group" id="tipo-lector-group">
                        <input type="radio" class="btn-check" name="tipo_lector" id="lector_existente" value="existente" autocomplete="off" checked>
                        <label class="btn btn-outline-primary" for="lector_existente">
                            <i class="fas fa-user-check me-2"></i>Lector Registrado
                        </label>
                        
                        <input type="radio" class="btn-check" name="tipo_lector" id="lector_nuevo" value="nuevo" autocomplete="off">
                        <label class="btn btn-outline-primary" for="lector_nuevo">
                            <i class="fas fa-user-plus me-2"></i>Nuevo Lector
                        </label>
                    </div>
                </div>
                
                <!-- Para lectores existentes -->
                <div id="grupo-lector-existente">
                    <div class="form-group mb-3">
                        <label for="id_lector" class="form-label">Seleccionar Lector Registrado <span class="text-danger">*</span></label>
                        <select name="id_lector" id="id_lector" class="form-select">
                            <option value="">-- Seleccione un lector --</option>
                            <?php foreach ($lectores_registrados as $lector): ?>
                                <option value="<?php echo $lector['id']; ?>"
                                        data-nombre="<?php echo htmlspecialchars($lector['nombre']); ?>"
                                        data-apellido="<?php echo htmlspecialchars($lector['apellido']); ?>"
                                        data-email="<?php echo htmlspecialchars($lector['email']); ?>"
                                        data-telefono="<?php echo htmlspecialchars($lector['telefono'] ?? ''); ?>"
                                        data-codigo-fiscal="<?php echo htmlspecialchars($lector['codigo_fiscal'] ?? ''); ?>">
                                    <?php echo htmlspecialchars($lector['apellido'] . ', ' . $lector['nombre'] . ' (' . $lector['email'] . ')'); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div id="info-lector-existente" class="alert alert-info d-none">
                        <!-- Información del lector se cargará aquí -->
                    </div>
                </div>
                
                <!-- Para nuevos lectores -->
                <div id="grupo-nuevo-lector" style="display: none;">
                    <div class="alert alert-info mb-3">
                        <i class="fas fa-info-circle me-2"></i>
                        Complete los datos del nuevo lector. Los campos marcados con <span class="text-danger">*</span> son obligatorios.
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group mb-3">
                                <label for="nombre" class="form-label">Nombre <span class="text-danger">*</span></label>
                                <input type="text" name="nombre" id="nombre" class="form-control" placeholder="Ingrese el nombre">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group mb-3">
                                <label for="apellido" class="form-label">Apellido <span class="text-danger">*</span></label>
                                <input type="text" name="apellido" id="apellido" class="form-control" placeholder="Ingrese el apellido">
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group mb-3">
                                <label for="email" class="form-label">Email <span class="text-danger">*</span></label>
                                <input type="email" name="email" id="email" class="form-control" placeholder="ejemplo@email.com">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group mb-3">
                                <label for="telefono" class="form-label">Teléfono</label>
                                <input type="tel" name="telefono" id="telefono" class="form-control" placeholder="Número de teléfono">
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-8">
                            <div class="form-group mb-3">
                                <label for="direccion" class="form-label">Dirección</label>
                                <input type="text" name="direccion" id="direccion" class="form-control" placeholder="Dirección completa">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group mb-3">
                                <label for="codigo_fiscal" class="form-label">Código Fiscal</label>
                                <input type="text" name="codigo_fiscal" id="codigo_fiscal" class="form-control" placeholder="Código fiscal">
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Fechas del Préstamo -->
        <div class="card mb-4">
            <div class="card-header gradient-warning">
                <h5 class="mb-0"><i class="fas fa-calendar-alt me-2"></i>Fechas del Préstamo</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group mb-3">
                            <label for="fecha_prestamo" class="form-label">Fecha de Préstamo <span class="text-danger">*</span></label>
                            <input type="date" name="fecha_prestamo" id="fecha_prestamo" 
                                   class="form-control" 
                                   value="<?php echo date('Y-m-d'); ?>" 
                                   min="<?php echo date('Y-m-d'); ?>" 
                                   required>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group mb-3">
                            <label for="fecha_devolucion_estimada" class="form-label">Fecha de Devolución</label>
                            <input type="date" id="fecha_devolucion_estimada" 
                                   class="form-control" 
                                   readonly 
                                   value="<?php echo date('Y-m-d', strtotime('+30 days')); ?>">
                        </div>
                    </div>
                </div>
                
                <div class="alert alert-warning">
                    <i class="fas fa-info-circle me-2"></i>
                    El préstamo tendrá una duración de <strong>30 días</strong>.
                </div>
            </div>
        </div>
        
        <!-- Botón de Confirmación -->
        <div class="card">
            <div class="card-header gradient-success">
                <h5 class="mb-0"><i class="fas fa-check-circle me-2"></i>Confirmar Préstamo</h5>
            </div>
            <div class="card-body">
                <div class="d-grid gap-2">
                    <button type="submit" class="btn btn-success btn-lg">
                        <i class="fas fa-file-alt me-2"></i>Confirmar Préstamo
                    </button>
                    
                    <button type="button" class="btn btn-outline-secondary" onclick="window.location.href='menu.php'">
                        <i class="fas fa-times me-2"></i>Cancelar
                    </button>
                </div>
            </div>
        </div>
        </form> <!-- Cierre del formulario -->
    </div>
</div>

<!-- MODAL PARA RESULTADOS DE ESCANEO -->
<div class="modal fade" id="modalScanner" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header gradient-book text-white">
                <h5 class="modal-title">
                    <i class="fas fa-barcode me-2"></i>
                    Resultado del Escaneo
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="modalScannerBody">
                <!-- Contenido dinámico -->
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="fas fa-times me-1"></i> Cerrar
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Scripts usando jQuery como en devoluciones -->
<script>
$(document).ready(function() {
    console.log('✅ jQuery cargado correctamente');
    
    // ============================================
    // TOGGLE ENTRE LECTOR EXISTENTE Y NUEVO
    // ============================================
    function toggleDatosLector() {
        const esNuevo = $('#lector_nuevo').is(':checked');
        
        if (esNuevo) {
            $('#grupo-lector-existente').hide();
            $('#grupo-nuevo-lector').show();
            
            // Hacer campos obligatorios
            $('#nombre, #apellido, #email').prop('required', true);
        } else {
            $('#grupo-lector-existente').show();
            $('#grupo-nuevo-lector').hide();
            
            // Quitar requerido de campos nuevos
            $('#nombre, #apellido, #email').prop('required', false);
        }
    }
    
    // Eventos para los radio buttons
    $('input[name="tipo_lector"]').change(function() {
        toggleDatosLector();
    });
    
    // Inicializar
    toggleDatosLector();
    
    // ============================================
    // INFO DEL LIBRO SELECCIONADO
    // ============================================
    $('#id_libro').change(function() {
        if ($(this).val()) {
            const selected = $(this).find('option:selected');
            const titulo = selected.data('titulo');
            const autor = selected.data('autor');
            const codigo = selected.data('codigo');
            
            $('#info-libro').html(`
                <div class="d-flex align-items-center">
                    <i class="fas fa-check-circle me-3"></i>
                    <div>
                        <strong>Libro seleccionado:</strong><br>
                        <strong>Título:</strong> ${titulo}<br>
                        <strong>Autor:</strong> ${autor}<br>
                        <strong>Código interno:</strong> ${codigo}
                    </div>
                </div>
            `).removeClass('d-none');
            
            // También poner el código en el escáner para referencia
            $('#codigo_scanner').val(codigo);
        } else {
            $('#info-libro').addClass('d-none');
        }
    });
    
    // ============================================
    // INFO DEL LECTOR SELECCIONADO
    // ============================================
    $('#id_lector').change(function() {
        if ($(this).val()) {
            const selected = $(this).find('option:selected');
            const nombre = selected.data('nombre');
            const apellido = selected.data('apellido');
            const email = selected.data('email');
            const telefono = selected.data('telefono') || 'No registrado';
            const codigoFiscal = selected.data('codigo-fiscal') || 'No registrado';
            
            $('#info-lector-existente').html(`
                <div class="d-flex align-items-center">
                    <i class="fas fa-user-check me-3"></i>
                    <div>
                        <strong>Lector seleccionado:</strong><br>
                        <strong>Nombre:</strong> ${nombre} ${apellido}<br>
                        <strong>Email:</strong> ${email}<br>
                        <strong>Teléfono:</strong> ${telefono}<br>
                        <strong>Código Fiscal:</strong> ${codigoFiscal}
                    </div>
                </div>
            `).removeClass('d-none');
            
            // También llenar los campos ocultos para nuevo lector
            // (por si cambian a "nuevo lector" después)
            $('#nombre').val(nombre);
            $('#apellido').val(apellido);
            $('#email').val(email);
            $('#telefono').val(selected.data('telefono') || '');
            $('#codigo_fiscal').val(selected.data('codigo-fiscal') || '');
        } else {
            $('#info-lector-existente').addClass('d-none');
        }
    });
    
    // ============================================
    // ESCANEO DE LIBROS (IGUAL QUE DEVOLUCIONES)
    // ============================================
    let scannerTimer;
    
    $('#codigo_scanner').on('input', function() {
        clearTimeout(scannerTimer);
        
        scannerTimer = setTimeout(function() {
            const codigo = $('#codigo_scanner').val().trim();
            if (codigo.length >= 3) {
                buscarLibroPorCodigo(codigo);
            }
        }, 300);
    });
    
    $('#btn-buscar-codigo').click(function() {
        const codigo = $('#codigo_scanner').val().trim();
        if (codigo.length >= 3) {
            buscarLibroPorCodigo(codigo);
        } else {
            showToast('Por favor ingrese un código válido (mínimo 3 caracteres)', 'warning');
        }
    });
    
    function buscarLibroPorCodigo(codigo) {
        // Mostrar estado de búsqueda
        $('#scanner-status').removeClass('d-none');
        $('#status-text').text(`Buscando: ${codigo}`);
        
        // Hacer petición AJAX
        $.ajax({
            url: 'buscar_libro_ajax.php',
            method: 'GET',
            data: { codigo: codigo },
            dataType: 'json',
            success: function(response) {
                $('#scanner-status').addClass('d-none');
                
                if (response.success) {
                    // Encontró el libro
                    const libro = response.libro;
                    
                    // Mostrar en modal
                    $('#modalScannerBody').html(`
                        <div class="alert alert-success">
                            <div class="d-flex align-items-center">
                                <i class="fas fa-check-circle fa-2x me-3"></i>
                                <div>
                                    <h5 class="mb-1">Libro encontrado</h5>
                                    <p class="mb-0">Código: <strong>${codigo}</strong></p>
                                </div>
                            </div>
                        </div>
                        
                        <div class="card mb-3">
                            <div class="card-body">
                                <h6 class="card-title">${libro.titulo}</h6>
                                <p class="card-text mb-1"><strong>Autor:</strong> ${libro.autor}</p>
                                <p class="card-text mb-1"><strong>Código interno:</strong> ${libro.codigo_interno}</p>
                                <p class="card-text mb-0">
                                    <span class="badge ${libro.stock > 0 ? 'bg-success' : 'bg-danger'}">
                                        ${libro.stock} disponible(s)
                                    </span>
                                </p>
                            </div>
                        </div>
                        
                        <div class="text-center">
                            <button class="btn btn-primary" onclick="seleccionarLibro(${libro.id})">
                                <i class="fas fa-check me-2"></i>Seleccionar este libro
                            </button>
                        </div>
                    `);
                    
                    $('#modalScanner').modal('show');
                    playBeepSound(true);
                    
                } else {
                    // No encontró el libro
                    $('#modalScannerBody').html(`
                        <div class="alert alert-danger">
                            <div class="d-flex align-items-center">
                                <i class="fas fa-exclamation-circle fa-2x me-3"></i>
                                <div>
                                    <h5 class="mb-1">Libro no encontrado</h5>
                                    <p class="mb-0">Código: <strong>${codigo}</strong></p>
                                </div>
                            </div>
                        </div>
                        
                        <div class="alert alert-warning">
                            <i class="fas fa-lightbulb me-2"></i>
                            <strong>Posibles causas:</strong>
                            <ul class="mb-0 mt-2">
                                <li>El código es incorrecto</li>
                                <li>El libro no está en el sistema</li>
                                <li>El libro no tiene stock disponible</li>
                                <li>Verifique el código e intente nuevamente</li>
                            </ul>
                        </div>
                    `);
                    
                    $('#modalScanner').modal('show');
                    playBeepSound(false);
                }
                
                // Limpiar campo de escáner
                $('#codigo_scanner').val('');
            },
            error: function() {
                $('#scanner-status').addClass('d-none');
                showToast('Error de conexión con el servidor', 'danger');
            }
        });
    }
    
    // ============================================
    // FUNCIONES AUXILIARES
    // ============================================
    
    // Función para seleccionar libro desde el modal
    window.seleccionarLibro = function(idLibro) {
        $('#id_libro').val(idLibro).trigger('change');
        $('#modalScanner').modal('hide');
        showToast('Libro seleccionado correctamente', 'success');
    };
    
    // Actualizar fecha de devolución
    $('#fecha_prestamo').change(function() {
        if ($(this).val()) {
            const fechaPrestamo = new Date($(this).val());
            fechaPrestamo.setDate(fechaPrestamo.getDate() + 30);
            const fechaDevolucion = fechaPrestamo.toISOString().split('T')[0];
            $('#fecha_devolucion_estimada').val(fechaDevolucion);
        }
    });
    
    // Sonido de escáner
    function playBeepSound(success = true) {
        try {
            const audioContext = new (window.AudioContext || window.webkitAudioContext)();
            const oscillator = audioContext.createOscillator();
            const gainNode = audioContext.createGain();
            
            oscillator.connect(gainNode);
            gainNode.connect(audioContext.destination);
            
            if (success) {
                oscillator.frequency.setValueAtTime(800, audioContext.currentTime);
                oscillator.frequency.setValueAtTime(1200, audioContext.currentTime + 0.1);
            } else {
                oscillator.frequency.setValueAtTime(400, audioContext.currentTime);
                oscillator.frequency.setValueAtTime(300, audioContext.currentTime + 0.1);
            }
            
            oscillator.type = 'sine';
            gainNode.gain.setValueAtTime(0.3, audioContext.currentTime);
            gainNode.gain.exponentialRampToValueAtTime(0.01, audioContext.currentTime + 0.2);
            
            oscillator.start(audioContext.currentTime);
            oscillator.stop(audioContext.currentTime + 0.2);
        } catch (e) {
            console.log('Audio no disponible');
        }
    }
    
    // Mostrar toast (notificación)
    function showToast(message, type = 'info') {
        // Crear toast dinámico
        const toastId = 'toast-' + Date.now();
        const toastHtml = `
            <div id="${toastId}" class="toast align-items-center text-white bg-${type}" role="alert">
                <div class="d-flex">
                    <div class="toast-body">
                        ${message}
                    </div>
                    <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
                </div>
            </div>
        `;
        
        // Agregar al contenedor
        if (!$('#toast-container').length) {
            $('body').append('<div id="toast-container" class="position-fixed bottom-0 end-0 p-3" style="z-index: 1055"></div>');
        }
        
        $('#toast-container').append(toastHtml);
        
        // Mostrar toast
        const toastElement = $('#' + toastId);
        const toast = new bootstrap.Toast(toastElement[0], { delay: 3000 });
        toast.show();
        
        // Eliminar después de ocultar
        toastElement.on('hidden.bs.toast', function() {
            $(this).remove();
        });
    }
    
    // Simulador de escáner (para pruebas)
    $('#btn-simular').click(function() {
        const codigosEjemplo = [
            '978-1-59856-200-1',
            'BIB-001',
            'LIB-2023-015',
            '978-0-8423-3780-7',
            'COM-001'
        ];
        
        const codigoAleatorio = codigosEjemplo[Math.floor(Math.random() * codigosEjemplo.length)];
        $('#codigo_scanner').val(codigoAleatorio);
        
        // Simular input
        $('#codigo_scanner').trigger('input');
        
        showToast(`🔍 Escaneo simulado: <strong>${codigoAleatorio}</strong>`, 'info');
    });
    
    // Validación del formulario
    $('#formPrestamo').submit(function(e) {
        // Validar libro
        if (!$('#id_libro').val()) {
            e.preventDefault();
            showToast('Debe seleccionar un libro', 'warning');
            $('#id_libro').focus();
            return false;
        }
        
        // Validar lector
        const esNuevoLector = $('#lector_nuevo').is(':checked');
        
        if (!esNuevoLector) {
            // Lector existente
            if (!$('#id_lector').val()) {
                e.preventDefault();
                showToast('Debe seleccionar un lector registrado', 'warning');
                $('#id_lector').focus();
                return false;
            }
        } else {
            // Nuevo lector
            const nombre = $('#nombre').val().trim();
            const apellido = $('#apellido').val().trim();
            const email = $('#email').val().trim();
            
            if (!nombre || !apellido || !email) {
                e.preventDefault();
                showToast('Para nuevo lector, nombre, apellido y email son obligatorios', 'warning');
                return false;
            }
        }
        
        // Validar fecha
        if (!$('#fecha_prestamo').val()) {
            e.preventDefault();
            showToast('Debe seleccionar una fecha de préstamo', 'warning');
            $('#fecha_prestamo').focus();
            return false;
        }
        
        return true;
    });
    
    // Poner foco en el escáner
    $('#codigo_scanner').focus();
});
</script>

<?php
$contenido = ob_get_clean();
include 'layout.php';
?>