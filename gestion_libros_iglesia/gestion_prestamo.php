<?php
// gestion_prestamo.php
session_start();
require_once 'db.php';

// Configurar variables para layout
$titulo_pagina = '📋 Gestión de Préstamos';
$icono_titulo = 'fas fa-tasks';

// Obtener estadísticas
$estadisticas = [];

// Total de préstamos activos
$sql_activos = "SELECT COUNT(*) as total FROM prestamos WHERE devuelto = 0";
$result_activos = mysqli_query($link, $sql_activos);
$estadisticas['activos'] = mysqli_fetch_assoc($result_activos)['total'];

// Préstamos por vencer (en los próximos 3 días)
$sql_por_vencer = "SELECT COUNT(*) as total FROM prestamos 
                   WHERE devuelto = 0 
                   AND fecha_devolucion <= DATE_ADD(CURDATE(), INTERVAL 3 DAY)
                   AND fecha_devolucion >= CURDATE()";
$result_por_vencer = mysqli_query($link, $sql_por_vencer);
$estadisticas['por_vencer'] = mysqli_fetch_assoc($result_por_vencer)['total'];

// Préstamos vencidos
$sql_vencidos = "SELECT COUNT(*) as total FROM prestamos 
                 WHERE devuelto = 0 
                 AND fecha_devolucion < CURDATE()";
$result_vencidos = mysqli_query($link, $sql_vencidos);
$estadisticas['vencidos'] = mysqli_fetch_assoc($result_vencidos)['total'];

// Total histórico
$sql_total = "SELECT COUNT(*) as total FROM prestamos";
$result_total = mysqli_query($link, $sql_total);
$estadisticas['total'] = mysqli_fetch_assoc($result_total)['total'];

// Determinar pestaña activa
$pestaña_activa = $_GET['tab'] ?? 'activos';
$busqueda = $_GET['busqueda'] ?? '';
$filtro_lector = $_GET['filtro_lector'] ?? '';
$filtro_fecha = $_GET['filtro_fecha'] ?? '';

// Construir consulta según pestaña
switch ($pestaña_activa) {
    case 'por_vencer':
        $where = "WHERE p.devuelto = 0 
                  AND p.fecha_devolucion <= DATE_ADD(CURDATE(), INTERVAL 3 DAY)
                  AND p.fecha_devolucion >= CURDATE()";
        break;
    case 'vencidos':
        $where = "WHERE p.devuelto = 0 AND p.fecha_devolucion < CURDATE()";
        break;
    case 'todos':
        $where = "";
        break;
    case 'activos':
    default:
        $where = "WHERE p.devuelto = 0";
        break;
}

// Agregar búsqueda si existe
if (!empty($busqueda)) {
    $where .= (empty($where) ? "WHERE " : " AND ");
    $where .= "(l.titulo LIKE '%$busqueda%' OR 
                l.codigo_interno LIKE '%$busqueda%' OR 
                l.isbn LIKE '%$busqueda%' OR 
                lec.nombre LIKE '%$busqueda%' OR 
                lec.apellido LIKE '%$busqueda%' OR 
                lec.email LIKE '%$busqueda%')";
}

// Agregar filtro por lector si existe
if (!empty($filtro_lector) && is_numeric($filtro_lector)) {
    $where .= (empty($where) ? "WHERE " : " AND ");
    $where .= "p.id_lector = $filtro_lector";
}

// Agregar filtro por fecha si existe
if (!empty($filtro_fecha)) {
    $where .= (empty($where) ? "WHERE " : " AND ");
    $where .= "DATE(p.fecha_prestamo) = '$filtro_fecha'";
}

// Obtener préstamos según filtros
$sql_prestamos = "SELECT p.*, 
                         l.titulo, l.codigo_interno, l.isbn, l.autor,
                         lec.nombre, lec.apellido, lec.email, lec.telefono,
                         lec.direccion, lec.codigo_fiscal,
                         DATEDIFF(p.fecha_devolucion, CURDATE()) as dias_restantes,
                         CASE 
                            WHEN p.devuelto = 1 THEN 'DEVUELTO'
                            WHEN p.fecha_devolucion < CURDATE() THEN 'VENCIDO'
                            WHEN DATEDIFF(p.fecha_devolucion, CURDATE()) <= 3 THEN 'POR VENCER'
                            ELSE 'ACTIVO'
                         END as estado_texto
                  FROM prestamos p
                  JOIN libros l ON p.id_libro = l.id
                  JOIN lectores lec ON p.id_lector = lec.id
                  $where
                  ORDER BY p.fecha_devolucion ASC
                  LIMIT 100";

$result_prestamos = mysqli_query($link, $sql_prestamos);
$prestamos = [];
if ($result_prestamos) {
    while ($row = mysqli_fetch_assoc($result_prestamos)) {
        $prestamos[] = $row;
    }
}

// Obtener lista de lectores para filtro
$sql_lectores = "SELECT id, nombre, apellido FROM lectores ORDER BY apellido, nombre";
$result_lectores = mysqli_query($link, $sql_lectores);
$lectores = [];
if ($result_lectores) {
    while ($row = mysqli_fetch_assoc($result_lectores)) {
        $lectores[] = $row;
    }
}

ob_start();
?>

<!-- DASHBOARD DE ESTADÍSTICAS -->
<div class="row mb-4">
    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card border-left-primary shadow h-100 py-2">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                            Préstamos Activos</div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800" id="contador-activos">
                            <?php echo $estadisticas['activos']; ?>
                        </div>
                    </div>
                    <div class="col-auto">
                        <i class="fas fa-book fa-2x text-gray-300"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card border-left-warning shadow h-100 py-2">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                            Por Vencer (3 días)</div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800" id="contador-por-vencer">
                            <?php echo $estadisticas['por_vencer']; ?>
                        </div>
                    </div>
                    <div class="col-auto">
                        <i class="fas fa-clock fa-2x text-gray-300"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card border-left-danger shadow h-100 py-2">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-danger text-uppercase mb-1">
                            Préstamos Vencidos</div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800" id="contador-vencidos">
                            <?php echo $estadisticas['vencidos']; ?>
                        </div>
                    </div>
                    <div class="col-auto">
                        <i class="fas fa-exclamation-triangle fa-2x text-gray-300"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card border-left-success shadow h-100 py-2">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                            Total Histórico</div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800">
                            <?php echo $estadisticas['total']; ?>
                        </div>
                    </div>
                    <div class="col-auto">
                        <i class="fas fa-history fa-2x text-gray-300"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- FILTROS DE BÚSQUEDA -->
<div class="card mb-4">
    <div class="card-header gradient-primary">
        <h5 class="mb-0"><i class="fas fa-filter me-2"></i>Filtros de Búsqueda</h5>
    </div>
    <div class="card-body">
        <form method="GET" action="" class="row g-3">
            <div class="col-md-4">
                <label for="busqueda" class="form-label">Buscar</label>
                <div class="input-group">
                    <input type="text" class="form-control" id="busqueda" name="busqueda" 
                           value="<?php echo htmlspecialchars($busqueda); ?>" 
                           placeholder="Libro, código, lector...">
                    <button class="btn btn-outline-primary" type="button" id="btn-limpiar-busqueda">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            </div>
            
            <div class="col-md-3">
                <label for="filtro_lector" class="form-label">Filtrar por Lector</label>
                <select class="form-select" id="filtro_lector" name="filtro_lector">
                    <option value="">Todos los lectores</option>
                    <?php foreach ($lectores as $lector): ?>
                        <option value="<?php echo $lector['id']; ?>"
                            <?php echo ($filtro_lector == $lector['id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($lector['apellido'] . ', ' . $lector['nombre']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="col-md-3">
                <label for="filtro_fecha" class="form-label">Filtrar por Fecha Préstamo</label>
                <input type="date" class="form-control" id="filtro_fecha" name="filtro_fecha" 
                       value="<?php echo htmlspecialchars($filtro_fecha); ?>">
            </div>
            
            <div class="col-md-2 d-flex align-items-end">
                <div class="d-grid gap-2 w-100">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-search me-1"></i> Filtrar
                    </button>
                </div>
            </div>
            
            <input type="hidden" name="tab" value="<?php echo $pestaña_activa; ?>">
        </form>
    </div>
</div>

<!-- PESTAÑAS DE ESTADO -->
<div class="card mb-4">
    <div class="card-header p-0">
        <ul class="nav nav-tabs" id="tabsPrestamos" role="tablist">
            <li class="nav-item" role="presentation">
                <a class="nav-link <?php echo $pestaña_activa == 'activos' ? 'active' : ''; ?>" 
                   href="?tab=activos&busqueda=<?php echo urlencode($busqueda); ?>&filtro_lector=<?php echo $filtro_lector; ?>&filtro_fecha=<?php echo $filtro_fecha; ?>">
                    <i class="fas fa-book me-1"></i> Activos
                    <span class="badge bg-primary ms-1"><?php echo $estadisticas['activos']; ?></span>
                </a>
            </li>
            <li class="nav-item" role="presentation">
                <a class="nav-link <?php echo $pestaña_activa == 'por_vencer' ? 'active' : ''; ?>" 
                   href="?tab=por_vencer&busqueda=<?php echo urlencode($busqueda); ?>&filtro_lector=<?php echo $filtro_lector; ?>&filtro_fecha=<?php echo $filtro_fecha; ?>">
                    <i class="fas fa-clock me-1"></i> Por Vencer
                    <span class="badge bg-warning ms-1"><?php echo $estadisticas['por_vencer']; ?></span>
                </a>
            </li>
            <li class="nav-item" role="presentation">
                <a class="nav-link <?php echo $pestaña_activa == 'vencidos' ? 'active' : ''; ?>" 
                   href="?tab=vencidos&busqueda=<?php echo urlencode($busqueda); ?>&filtro_lector=<?php echo $filtro_lector; ?>&filtro_fecha=<?php echo $filtro_fecha; ?>">
                    <i class="fas fa-exclamation-triangle me-1" ></i> Vencidos
                    <span class="badge bg-danger ms-1"><?php echo $estadisticas['vencidos']; ?></span>
                </a>
            </li>
            <li class="nav-item" role="presentation">
                <a class="nav-link <?php echo $pestaña_activa == 'todos' ? 'active' : ''; ?>" 
                   href="?tab=todos&busqueda=<?php echo urlencode($busqueda); ?>&filtro_lector=<?php echo $filtro_lector; ?>&filtro_fecha=<?php echo $filtro_fecha; ?>">
                    <i class="fas fa-list me-1"></i> Todos
                    <span class="badge bg-secondary ms-1"><?php echo $estadisticas['total']; ?></span>
                </a>
            </li>
        </ul>
    </div>
    
    <div class="card-body">
        <?php if (empty($prestamos)): ?>
            <div class="text-center py-5">
                <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                <h4 class="text-muted">No hay préstamos encontrados</h4>
                <p class="text-muted">No se encontraron préstamos con los filtros aplicados.</p>
                <a href="gestion_prestamo.php" class="btn btn-primary">
                    <i class="fas fa-redo me-1"></i> Ver todos los préstamos
                </a>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover" id="tablaPrestamos">
                    <thead>
                        <tr>
                            <th width="5%">ID</th>
                            <th width="25%">Libro</th>
                            <th width="20%">Lector</th>
                            <th width="10%">Préstamo</th>
                            <th width="10%">Devolución</th>
                            <th width="10%">Estado</th>
                            <th width="20%" class="text-center">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($prestamos as $prestamo): 
                            $estado_clase = '';
                            $estado_icono = '';
                            switch ($prestamo['estado_texto']) {
                                case 'ACTIVO':
                                    $estado_clase = 'success';
                                    $estado_icono = 'check-circle';
                                    break;
                                case 'POR VENCER':
                                    $estado_clase = 'warning';
                                    $estado_icono = 'clock';
                                    break;
                                case 'VENCIDO':
                                    $estado_clase = 'danger';
                                    $estado_icono = 'exclamation-triangle';
                                    break;
                                case 'DEVUELTO':
                                    $estado_clase = 'secondary';
                                    $estado_icono = 'check';
                                    break;
                            }
                        ?>
                        <tr>
                            <td>
                                <strong>#<?php echo str_pad($prestamo['id'], 5, '0', STR_PAD_LEFT); ?></strong>
                            </td>
                            <td>
                                <div class="fw-bold"><?php echo htmlspecialchars($prestamo['titulo']); ?></div>
                                <small class="text-muted">
                                    <?php echo htmlspecialchars($prestamo['autor']); ?>
                                    <?php if (!empty($prestamo['codigo_interno'])): ?>
                                        <br><code><?php echo htmlspecialchars($prestamo['codigo_interno']); ?></code>
                                    <?php endif; ?>
                                </small>
                            </td>
                            <td>
                                <div class="fw-bold"><?php echo htmlspecialchars($prestamo['nombre'] . ' ' . $prestamo['apellido']); ?></div>
                                <small class="text-muted">
                                    <?php echo htmlspecialchars($prestamo['email']); ?>
                                    <?php if (!empty($prestamo['telefono'])): ?>
                                        <br><?php echo htmlspecialchars($prestamo['telefono']); ?>
                                    <?php endif; ?>
                                </small>
                            </td>
                            <td>
                                <?php echo date('d/m/Y', strtotime($prestamo['fecha_prestamo'])); ?>
                            </td>
                            <td>
                                <?php echo date('d/m/Y', strtotime($prestamo['fecha_devolucion'])); ?>
                                <?php if ($prestamo['dias_restantes'] !== null): ?>
                                    <br>
                                    <small class="text-<?php echo $prestamo['dias_restantes'] < 0 ? 'danger' : ($prestamo['dias_restantes'] <= 3 ? 'warning' : 'success'); ?>">
                                        <?php echo abs($prestamo['dias_restantes']); ?> días
                                        <?php echo $prestamo['dias_restantes'] < 0 ? 'de retraso' : 'restantes'; ?>
                                    </small>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="badge bg-<?php echo $estado_clase; ?>">
                                    <i class="fas fa-<?php echo $estado_icono; ?> me-1"></i>
                                    <?php echo $prestamo['estado_texto']; ?>
                                </span>
                            </td>
                            <td class="text-center">
                                <button class="btn btn-sm btn-info ver-detalles" 
                                        data-id="<?php echo $prestamo['id']; ?>"
                                        title="Ver detalles">
                                    <i class="fas fa-eye"></i>
                                </button>
                                
                                <?php if ($prestamo['devuelto'] == 0): ?>
                               <button class="btn btn-sm btn-success marcar-devuelto" 
                                    data-id="<?php echo $prestamo['id']; ?>"
                                    data-codigo="<?php echo htmlspecialchars($prestamo['codigo_interno']); ?>"
                                    title="Marcar como devuelto">
                                    <i class="fas fa-check"></i>
                                </button>
                                <button class="btn btn-sm btn-warning enviar-recordatorio" 
                                        data-id="<?php echo $prestamo['id']; ?>"
                                        data-email="<?php echo htmlspecialchars($prestamo['email']); ?>"
                                        title="Enviar recordatorio">
                                    <i class="fas fa-envelope"></i>
                                </button>
                                <?php endif; ?>
                                
                                <a href="imprimir_comprobante.php?id=<?php echo $prestamo['id']; ?>" 
                                   class="btn btn-sm btn-secondary"
                                   title="Imprimir comprobante"
                                   target="_blank">
                                    <i class="fas fa-print"></i>
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
            <div class="d-flex justify-content-between align-items-center mt-3">
                <div class="text-muted">
                    Mostrando <?php echo count($prestamos); ?> préstamos
                </div>
                <div>
                    <button class="btn btn-outline-primary" id="btn-exportar">
                        <i class="fas fa-file-export me-1"></i> Exportar
                    </button>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- MODAL PARA VER DETALLES -->
<div class="modal fade" id="modalDetalles" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header gradient-primary text-white">
                <h5 class="modal-title">
                    <i class="fas fa-info-circle me-2"></i>
                    Detalles del Préstamo
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="modalDetallesBody">
                <!-- Se cargará con AJAX -->
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="fas fa-times me-1"></i> Cerrar
                </button>
                <button type="button" class="btn btn-primary" id="btn-imprimir-detalles">
                    <i class="fas fa-print me-1"></i> Imprimir
                </button>
            </div>
        </div>
    </div>
</div>


<!-- MODAL PARA ENVIAR RECORDATORIO -->
<div class="modal fade" id="modalRecordatorio" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header gradient-warning text-white">
                <h5 class="modal-title">
                    <i class="fas fa-envelope me-2"></i>
                    Enviar Recordatorio
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="modalRecordatorioBody">
                <!-- Se cargará con AJAX -->
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="fas fa-times me-1"></i> Cancelar
                </button>
                <button type="button" class="btn btn-warning" id="btn-enviar-recordatorio">
                    <i class="fas fa-paper-plane me-1"></i> Enviar Recordatorio
                </button>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    // Variables globales
    let prestamoActual = null;
    const modalDetalles = new bootstrap.Modal(document.getElementById('modalDetalles'));
    const modalRecordatorio = new bootstrap.Modal(document.getElementById('modalRecordatorio'));
    
    // ============================================
    // VER DETALLES DEL PRÉSTAMO
    // ============================================
    $(document).on('click', '.ver-detalles', function() {
        const prestamoId = $(this).data('id');
        prestamoActual = prestamoId;
        
        // Mostrar loading
        $('#modalDetallesBody').html(`
            <div class="text-center py-4">
                <div class="spinner-border text-primary mb-3" role="status">
                    <span class="visually-hidden">Cargando...</span>
                </div>
                <h5>Cargando detalles...</h5>
            </div>
        `);
        
        modalDetalles.show();
        
        // Cargar detalles via AJAX
        $.ajax({
            url: 'obtener_detalles_prestamo.php',
            method: 'GET',
            data: { id: prestamoId },
            dataType: 'html',
            success: function(response) {
                $('#modalDetallesBody').html(response);
            },
            error: function() {
                $('#modalDetallesBody').html(`
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        Error al cargar los detalles del préstamo.
                    </div>
                `);
            }
        });
    });
    
    // ============================================
    // MARCAR COMO DEVUELTO - USANDO DEVOLUCIONES.PHP
    // ============================================
$(document).on('click', '.marcar-devuelto', function() {
        const prestamoId = $(this).data('id');
        const libroCodigo = $(this).data('codigo');
    
        // Confirmar redirección
        if (confirm('¿Marcar este préstamo como devuelto?\n\nSerá redirigido a la página de devoluciones para confirmar.')) {
        // Redirigir a devoluciones.php con el código
        window.location.href = `devolucion_libro.php?codigo=${encodeURIComponent(libroCodigo)}&from=gestion`;
        }
});
    
    // ============================================
    // ENVIAR RECORDATORIO
    // ============================================
    $(document).on('click', '.enviar-recordatorio', function() {
        const prestamoId = $(this).data('id');
        const email = $(this).data('email');
        prestamoActual = prestamoId;
        
        // Mostrar formulario para recordatorio
        $('#modalRecordatorioBody').html(`
            <div class="mb-3">
                <label for="asunto-recordatorio" class="form-label">Asunto</label>
                <input type="text" class="form-control" id="asunto-recordatorio" 
                       value="Recordatorio de Devolución - Biblioteca Elim Torino">
            </div>
            <div class="mb-3">
                <label for="mensaje-recordatorio" class="form-label">Mensaje</label>
                <textarea class="form-control" id="mensaje-recordatorio" rows="4">
Estimado/a,

Le recordamos que tiene un libro pendiente de devolución en la Biblioteca Elim Torino.

Fecha límite de devolución: [FECHA_DEVOLUCION]

Por favor, acérquese a la biblioteca para realizar la devolución.

Atentamente,
Biblioteca Elim Torino
                </textarea>
            </div>
            <div class="alert alert-info">
                <i class="fas fa-info-circle me-2"></i>
                El recordatorio se enviará a: <strong>${email}</strong>
            </div>
        `);
        
        modalRecordatorio.show();
    });
    
    // Enviar recordatorio
    $('#btn-enviar-recordatorio').click(function() {
        const btn = $(this);
        const asunto = $('#asunto-recordatorio').val();
        const mensaje = $('#mensaje-recordatorio').val();
        
        btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-1"></i> Enviando...');
        
        $.ajax({
            url: 'enviar_recordatorio.php',
            method: 'POST',
            data: { 
                id: prestamoActual,
                asunto: asunto,
                mensaje: mensaje
            },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    $('#modalRecordatorioBody').html(`
                        <div class="text-center py-4">
                            <i class="fas fa-check-circle fa-3x text-success mb-3"></i>
                            <h4 class="text-success">¡Recordatorio Enviado!</h4>
                            <p>El recordatorio ha sido enviado correctamente.</p>
                        </div>
                    `);
                    
                    // Ocultar botones
                    $('.modal-footer').hide();
                    
                    // Cerrar después de 2 segundos
                    setTimeout(function() {
                        modalRecordatorio.hide();
                    }, 2000);
                } else {
                    $('#modalRecordatorioBody').html(`
                        <div class="alert alert-danger">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            Error: ${response.message || 'No se pudo enviar el recordatorio'}
                        </div>
                    `);
                    btn.prop('disabled', false).html('<i class="fas fa-paper-plane me-1"></i> Enviar Recordatorio');
                }
            },
            error: function() {
                $('#modalRecordatorioBody').html(`
                    <div class="alert alert-danger">
                        <i class="fas fa-times-circle me-2"></i>
                        Error de conexión con el servidor.
                    </div>
                `);
                btn.prop('disabled', false).html('<i class="fas fa-paper-plane me-1"></i> Enviar Recordatorio');
            }
        });
    });
    
    // ============================================
    // FUNCIONES AUXILIARES
    // ============================================
    
    // Limpiar búsqueda
    $('#btn-limpiar-busqueda').click(function() {
        $('#busqueda').val('');
        window.location.href = 'gestion_prestamo.php?tab=<?php echo $pestaña_activa; ?>';
    });
    
    // Exportar datos
    $('#btn-exportar').click(function() {
        const tab = '<?php echo $pestaña_activa; ?>';
        const busqueda = '<?php echo urlencode($busqueda); ?>';
        
        window.open(`exportar_prestamos.php?tab=${tab}&busqueda=${busqueda}`, '_blank');
    });
    
    // Imprimir desde modal
    $('#btn-imprimir-detalles').click(function() {
        window.print();
    });
    
    // Auto-refrescar cada 60 segundos
    setInterval(function() {
        // Solo actualizar si no hay modales abiertos
        if (!$('.modal.show').length) {
            $.ajax({
                url: 'actualizar_estadisticas.php',
                method: 'GET',
                dataType: 'json',
                success: function(data) {
                    $('#contador-activos').text(data.activos || 0);
                    $('#contador-por-vencer').text(data.por_vencer || 0);
                    $('#contador-vencidos').text(data.vencidos || 0);
                }
            });
        }
    }, 60000);
    
    // Inicializar DataTables si hay muchos registros
    if ($('#tablaPrestamos tbody tr').length > 10) {
        $('#tablaPrestamos').DataTable({
            language: {
                url: '//cdn.datatables.net/plug-ins/1.13.4/i18n/es-ES.json'
            },
            pageLength: 25,
            order: [[0, 'desc']],
            responsive: true
        });
    }
});
</script>

<?php
$contenido = ob_get_clean();
include 'layout.php';
?>