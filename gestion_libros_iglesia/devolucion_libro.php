<?php
require_once 'db.php';

// La agregue para que detecte que viene de gestion prestamo
if (isset($_GET['from']) && $_GET['from'] === 'gestion' && isset($_GET['codigo'])) {
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
                        <button class="btn btn-primary" type="button" id="btn-simular">
                            <i class="fas fa-camera"></i>
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
        
        <!-- Búsqueda Manual -->
        <div class="card mt-4">
            <div class="card-header">
                <h6 class="mb-0"><i class="fas fa-search me-2"></i>Búsqueda Manual</h6>
            </div>
            <div class="card-body">
                <div class="input-group">
                    <input type="text" class="form-control" id="busqueda-manual" 
                           placeholder="Buscar por título, persona, código...">
                    <button class="btn btn-outline-primary" type="button" id="btn-buscar">
                        <i class="fas fa-search"></i>
                    </button>
                </div>
                <div id="resultados-busqueda" class="mt-3"></div>
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
    // DETECCIÓN AUTOMÁTICA DE CÓDIGO ESCANEADO
    // ============================================
    let tiempoEscaneo = null;
    let codigoAcumulado = '';
    
    codigoInput.addEventListener('keydown', function(e) {
        // Si es Enter (como si el escáner enviara Enter al final)
        if (e.key === 'Enter') {
            e.preventDefault();
            const codigo = this.value.trim();
            if (codigo.length >= 8) { // Código válido
                buscarPrestamo(codigo);
            }
            this.value = '';
            return;
        }
        
        // Detectar escaneo rápido (escáneres suelen ser rápidos)
        clearTimeout(tiempoEscaneo);
        tiempoEscaneo = setTimeout(() => {
            const codigo = this.value.trim();
            if (codigo.length >= 8 && !/\s/.test(codigo)) {
                // Suena como un código de barras (sin espacios, >8 chars)
                buscarPrestamo(codigo);
                this.value = '';
            }
        }, 100);
    });
    
    // También por input para detectar pegado o escritura manual
    codigoInput.addEventListener('input', function() {
        const codigo = this.value.trim();
        
        // Si parece un código completo (sin espacios, longitud típica)
        if ((codigo.length === 13 || codigo.length === 10 || codigo.length >= 8) && 
            !/\s/.test(codigo) && 
            !this.value.includes(' ')) {
            
            // Pequeña pausa para asegurar que terminó de escanear
            clearTimeout(tiempoEscaneo);
            tiempoEscaneo = setTimeout(() => {
                buscarPrestamo(codigo);
                this.value = '';
            }, 300);
        }
    });
    
    // ============================================
    // FUNCIÓN PARA BUSCAR PRÉSTAMO
    // ============================================
    function buscarPrestamo(codigo) {
        if (!codigo || codigo.length < 3) return;
        
        // Mostrar estado de búsqueda
        mostrarEstadoEscaneo(true, `Buscando préstamo para: <strong>${codigo}</strong>`);
        
        // Simular sonido de escáner (opcional)
        playBeepSound();
        
        // Hacer petición AJAX
        fetch(`buscar_prestamo.php?codigo=${encodeURIComponent(codigo)}`)
            .then(response => response.json())
            .then(data => {
                mostrarEstadoEscaneo(false);
                
                if (data.success) {
                    // Mostrar modal con los datos
                    prestamoActual = data.prestamo;
                    mostrarModalDevolucion(data.prestamo);
                } else {
                    // Mostrar modal de error
                    mostrarModalError(codigo, data.message);
                }
            })
            .catch(error => {
                mostrarEstadoEscaneo(false);
                mostrarModalError(codigo, 'Error de conexión con el servidor');
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
        
        // Determinar estado
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
        
        // Construir HTML del modal
        const modalHTML = `
            <div class="row">
                <!-- Información del Libro -->
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
                
                <!-- Información del Préstamo -->
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
            
            <!-- Información de la Persona -->
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
            
            <!-- Resumen -->
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
        
        // Hacer petición para registrar devolución
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
                // Mostrar mensaje de éxito
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
                
                // Ocultar botones del footer
                document.querySelector('.modal-footer').style.display = 'none';
                
                // Actualizar lista de préstamos después de 2 segundos
                setTimeout(() => {
                    modalDevolucion.hide();
                    cargarPrestamosActivos();
                    cargarEstadisticas();
                    prestamoActual = null;
                    
                    // Restaurar botones
                    document.querySelector('.modal-footer').style.display = 'flex';
                    btn.disabled = false;
                    btn.innerHTML = '<i class="fas fa-check me-1"></i> Confirmar Devolución';
                    
                    // Reproducir sonido de éxito
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
                
                // Agregar eventos a los botones de usar código
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
        // Crear sonido de escáner simple
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
        // Sonido de éxito
        try {
            const audioContext = new (window.AudioContext || window.webkitAudioContext)();
            const oscillator = audioContext.createOscillator();
            const gainNode = audioContext.createGain();
            
            oscillator.connect(gainNode);
            gainNode.connect(audioContext.destination);
            
            oscillator.frequency.setValueAtTime(523.25, audioContext.currentTime); // Do
            oscillator.frequency.setValueAtTime(659.25, audioContext.currentTime + 0.1); // Mi
            oscillator.frequency.setValueAtTime(783.99, audioContext.currentTime + 0.2); // Sol
            
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
    // SIMULADOR DE ESCÁNER (para pruebas)
    // ============================================
    document.getElementById('btn-simular').addEventListener('click', function() {
        const codigosEjemplo = [
            '978-1-59856-200-1',
            'BIB-001',
            'LIB-2023-015',
            '978-0-8423-3780-7',
            'COM-001'
        ];
        
        const codigoAleatorio = codigosEjemplo[Math.floor(Math.random() * codigosEjemplo.length)];
        codigoInput.value = codigoAleatorio;
        
        // Mostrar notificación
        const alerta = document.createElement('div');
        alerta.className = 'alert alert-info alert-dismissible fade show mt-2';
        alerta.innerHTML = `
            <i class="fas fa-camera me-2"></i>
            <strong>Escaneo simulado:</strong> ${codigoAleatorio}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        `;
        document.querySelector('.card-body').insertBefore(alerta, codigoInput.parentNode.nextSibling);
        
        // Buscar automáticamente después de 0.5 segundos
        setTimeout(() => {
            buscarPrestamo(codigoAleatorio);
            codigoInput.value = '';
        }, 500);
    });
    
    // Búsqueda manual
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
    
    // Auto-focus en el campo de código
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