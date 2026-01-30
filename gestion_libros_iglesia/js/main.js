// js/main.js - Archivo principal de JavaScript para Sistema de Biblioteca

console.log('📚 Biblioteca Elim TO - Sistema inicializado');

// ============================================
// FUNCIONES BÁSICAS Y ÚTILES
// ============================================

// Inicializar cuando el DOM esté listo
document.addEventListener('DOMContentLoaded', function() {
    console.log('✅ DOM cargado completamente');
    
    // 1. Tooltips de Bootstrap
    inicializarTooltips();
    
    // 2. Alertas auto-cierre
    configurarAlertasAutoCierre();
    
    // 3. Confirmaciones antes de borrar
    configurarConfirmaciones();
    
    // 4. Formatear números automáticamente
    configurarFormatosNumeros();
    
    // 5. Auto-focus en formularios
    configurarAutoFocus();
    
    // 6. Manejo de DataTables básico
    configurarDataTablesBasico();
});

// ============================================
// FUNCIONES ESPECÍFICAS
// ============================================

function inicializarTooltips() {
    // Tooltips de Bootstrap
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    var tooltipList = tooltipTriggerList.map(function(tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
    
    if (tooltipList.length > 0) {
        console.log(`ℹ️ ${tooltipList.length} tooltips inicializados`);
    }
}

function configurarAlertasAutoCierre() {
    // Cerrar alertas automáticamente después de 5 segundos
    setTimeout(function() {
        document.querySelectorAll('.alert:not(.no-auto-close)').forEach(function(alert) {
            const bsAlert = new bootstrap.Alert(alert);
            bsAlert.close();
        });
    }, 5000);
}

function configurarConfirmaciones() {
    // Confirmación antes de acciones de borrado/eliminación
    document.querySelectorAll('.btn-eliminar, .btn-danger[data-confirm]').forEach(function(boton) {
        boton.addEventListener('click', function(e) {
            const mensaje = this.getAttribute('data-confirm') || 
                           '¿Está seguro de que desea realizar esta acción?';
            
            if (!confirm(mensaje)) {
                e.preventDefault();
                e.stopPropagation();
            }
        });
    });
}

function configurarFormatosNumeros() {
    // Formatear números con separadores de miles
    document.querySelectorAll('input[data-format-number]').forEach(function(input) {
        input.addEventListener('blur', function() {
            let valor = this.value.replace(/\D/g, '');
            if (valor) {
                this.value = parseInt(valor).toLocaleString('es-ES');
            }
        });
        
        input.addEventListener('focus', function() {
            this.value = this.value.replace(/\./g, '');
        });
    });
}

function configurarAutoFocus() {
    // Poner focus en el primer campo de formularios
    document.querySelectorAll('form').forEach(function(form) {
        const primerInput = form.querySelector('input:not([type="hidden"]):not([disabled]), select:not([disabled]), textarea:not([disabled])');
        if (primerInput && !primerInput.value) {
            setTimeout(() => primerInput.focus(), 100);
        }
    });
}

function configurarDataTablesBasico() {
    // Configuración básica para DataTables si existen
    if (typeof $.fn.DataTable === 'function') {
        $('.datatable-basico').DataTable({
            language: {
                url: 'https://cdn.datatables.net/plug-ins/1.13.4/i18n/es-ES.json'
            },
            pageLength: 25,
            responsive: true,
            dom: '<"row"<"col-md-6"l><"col-md-6"f>>rt<"row"<"col-md-12 text-center"p>>'
        });
    }
}

// ============================================
// FUNCIONES DE UTILIDAD PARA TODO EL SISTEMA
// ============================================

// Función para formatear fechas
function formatearFecha(fecha, formato = 'dd/mm/yyyy') {
    if (!fecha) return '';
    
    try {
        const date = new Date(fecha);
        if (isNaN(date.getTime())) return fecha;
        
        const dia = String(date.getDate()).padStart(2, '0');
        const mes = String(date.getMonth() + 1).padStart(2, '0');
        const anio = date.getFullYear();
        
        if (formato === 'dd/mm/yyyy') {
            return `${dia}/${mes}/${anio}`;
        } else if (formato === 'yyyy-mm-dd') {
            return `${anio}-${mes}-${dia}`;
        }
        
        return fecha;
    } catch (e) {
        return fecha;
    }
}

// Función para copiar texto al portapapeles
function copiarAlPortapapeles(texto) {
    navigator.clipboard.writeText(texto)
        .then(() => {
            alert('✅ Texto copiado al portapapeles');
        })
        .catch(err => {
            console.error('Error al copiar: ', err);
            alert('❌ Error al copiar el texto');
        });
}

// Función para calcular días restantes
function calcularDiasRestantes(fechaDevolucion) {
    const hoy = new Date();
    const devolucion = new Date(fechaDevolucion);
    const diferencia = devolucion.getTime() - hoy.getTime();
    return Math.ceil(diferencia / (1000 * 3600 * 24));
}

// Función para mostrar estado de préstamo
function obtenerEstadoPrestamo(fechaDevolucion, devuelto) {
    if (devuelto) return { texto: 'DEVUELTO', clase: 'success' };
    
    const dias = calcularDiasRestantes(fechaDevolucion);
    
    if (dias < 0) {
        return { texto: 'VENCIDO', clase: 'danger' };
    } else if (dias <= 3) {
        return { texto: 'POR VENCER', clase: 'warning' };
    } else {
        return { texto: 'ACTIVO', clase: 'info' };
    }
}

// ============================================
// MANEJO DE IMPRESIÓN
// ============================================

function imprimirSeccion(elementoId) {
    const elemento = document.getElementById(elementoId);
    if (!elemento) {
        alert('❌ No se encontró el elemento a imprimir');
        return;
    }
    
    const ventanaImpresion = window.open('', '_blank');
    ventanaImpresion.document.write(`
        <html>
            <head>
                <title>Imprimir - Biblioteca Elim TO</title>
                <style>
                    body { font-family: Arial, sans-serif; margin: 20px; }
                    @media print {
                        .no-print { display: none !important; }
                        body { margin: 0; padding: 0; }
                    }
                    h4 { text-align: center; margin-bottom: 20px; }
                    table { width: 100%; border-collapse: collapse; }
                    th, td { border: 1px solid #000; padding: 8px; }
                    th { background-color: #f2f2f2; }
                </style>
            </head>
            <body>
                ${elemento.outerHTML}
                <div class="no-print" style="margin-top: 20px; text-align: center;">
                    <button onclick="window.print()" style="padding: 10px 20px; margin: 5px;">🖨️ Imprimir</button>
                    <button onclick="window.close()" style="padding: 10px 20px; margin: 5px;">❌ Cerrar</button>
                </div>
            </body>
        </html>
    `);
    ventanaImpresion.document.close();
}

// ============================================
// FUNCIONES PARA FORMULARIOS
// ============================================

// Validar que una fecha no sea futura
function validarFechaNoFutura(inputId) {
    const input = document.getElementById(inputId);
    if (!input) return true;
    
    const hoy = new Date().toISOString().split('T')[0];
    const fechaSeleccionada = input.value;
    
    if (fechaSeleccionada > hoy) {
        alert('⚠️ La fecha no puede ser futura');
        input.value = hoy;
        return false;
    }
    
    return true;
}

// Validar que la fecha de devolución sea posterior al préstamo
function validarRangoFechas(fechaInicioId, fechaFinId) {
    const inicio = document.getElementById(fechaInicioId);
    const fin = document.getElementById(fechaFinId);
    
    if (!inicio || !fin) return true;
    
    if (inicio.value && fin.value && fin.value < inicio.value) {
        alert('⚠️ La fecha de devolución debe ser posterior a la fecha de préstamo');
        fin.value = '';
        fin.focus();
        return false;
    }
    
    return true;
}

// ============================================
// EXPORTAR A CSV SIMPLE
// ============================================

function exportarTablaCSV(tablaId, nombreArchivo = 'datos') {
    const tabla = document.getElementById(tablaId);
    if (!tabla) {
        alert('❌ No se encontró la tabla');
        return;
    }
    
    let csv = [];
    const filas = tabla.querySelectorAll('tr');
    
    filas.forEach(fila => {
        const celdas = [];
        fila.querySelectorAll('th, td').forEach(celda => {
            // Excluir botones de acción
            if (!celda.querySelector('button, .btn, .acciones')) {
                let texto = celda.innerText.replace(/(\r\n|\n|\r)/gm, ' ').replace(/,/g, ';');
                celdas.push(`"${texto}"`);
            }
        });
        if (celdas.length > 0) {
            csv.push(celdas.join(','));
        }
    });
    
    const csvContent = csv.join('\n');
    const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
    const url = URL.createObjectURL(blob);
    const link = document.createElement('a');
    
    link.href = url;
    link.download = `${nombreArchivo}_${new Date().toISOString().slice(0,10)}.csv`;
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
    
    alert('✅ Exportación completada');
}

// ============================================
// FUNCIONES DE NOTIFICACIÓN SIMPLES
// ============================================

function mostrarNotificacion(mensaje, tipo = 'info') {
    // Crear notificación simple
    const notificacion = document.createElement('div');
    notificacion.className = `alert alert-${tipo} alert-dismissible fade show position-fixed`;
    notificacion.style.cssText = `
        top: 20px;
        right: 20px;
        z-index: 9999;
        min-width: 300px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
    `;
    
    notificacion.innerHTML = `
        <strong>${tipo === 'success' ? '✅' : tipo === 'error' ? '❌' : 'ℹ️'}</strong>
        ${mensaje}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;
    
    document.body.appendChild(notificacion);
    
    // Auto-eliminar después de 4 segundos
    setTimeout(() => {
        if (notificacion.parentNode) {
            notificacion.remove();
        }
    }, 4000);
}

// ============================================
// INICIALIZACIÓN ADICIONAL
// ============================================

// Función para cargar datos vía AJAX (si es necesario)
function cargarDatosAjax(url, callback) {
    fetch(url)
        .then(response => {
            if (!response.ok) throw new Error('Error en la respuesta');
            return response.json();
        })
        .then(data => callback(data))
        .catch(error => {
            console.error('Error en AJAX:', error);
            mostrarNotificacion('❌ Error al cargar los datos', 'error');
        });
}

// Función para detectar si es dispositivo móvil
function esDispositivoMovil() {
    return /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent);
}

// Configuración específica para móviles
if (esDispositivoMovil()) {
    document.documentElement.classList.add('es-movil');
}

// ============================================
// FIN DEL ARCHIVO
// ============================================

console.log('✨ main.js cargado correctamente');