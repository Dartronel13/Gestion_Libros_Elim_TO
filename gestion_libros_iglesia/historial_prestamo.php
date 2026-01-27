<?php
// historial_prestamos.php

// 1. VERIFICACIÓN DE ACCESO (AGREGAR ESTO AL INICIO)
require_once 'db.php';
verificarAutenticacion(); // ← ESTA LÍNEA ES NUEVA

// 2. REGISTRAR ACCESO A ESTA PÁGINA
$db->registrarAccion('acceso', 'historial', "Accedió al historial de préstamos");

// Configurar variables para layout
$titulo_pagina = '📜 Historial de Préstamos';
$icono_titulo = 'fas fa-history';

// Parámetros de filtro
$filtro_lector = $_GET['lector_id'] ?? '';
$filtro_estado = $_GET['estado'] ?? 'todos'; // todos, activos, devueltos, vencidos
$filtro_fecha_desde = $_GET['fecha_desde'] ?? '';
$filtro_fecha_hasta = $_GET['fecha_hasta'] ?? '';
$busqueda_texto = $_GET['q'] ?? '';

// 3. REGISTRAR FILTROS APLICADOS
$filtros_aplicados = [];
if (!empty($filtro_lector) && $filtro_lector !== 'todos') {
    $filtros_aplicados[] = "Lector ID: {$filtro_lector}";
}
if ($filtro_estado !== 'todos') {
    $filtros_aplicados[] = "Estado: {$filtro_estado}";
}
if (!empty($filtro_fecha_desde)) {
    $filtros_aplicados[] = "Desde: {$filtro_fecha_desde}";
}
if (!empty($filtro_fecha_hasta)) {
    $filtros_aplicados[] = "Hasta: {$filtro_fecha_hasta}";
}
if (!empty($busqueda_texto)) {
    $filtros_aplicados[] = "Búsqueda: '{$busqueda_texto}'";
}

if (!empty($filtros_aplicados)) {
    $db->registrarAccion(
        'filtros_historial', 
        'historial', 
        "Filtros aplicados al historial: " . implode(', ', $filtros_aplicados)
    );
}

// Construir consulta con filtros
$where_conditions = [];
$params = [];
$types = '';

// CORRECCIÓN: Solo agregar filtro si NO es "todos" y no está vacío
if (!empty($filtro_lector) && $filtro_lector !== 'todos') {
    $where_conditions[] = "p.id_lector = ?";
    $params[] = $filtro_lector;
    $types .= 'i';
}

if ($filtro_estado !== 'todos') {
    if ($filtro_estado === 'activos') {
        $where_conditions[] = "p.devuelto = 0";
    } elseif ($filtro_estado === 'devueltos') {
        $where_conditions[] = "p.devuelto = 1";
    } elseif ($filtro_estado === 'vencidos') {
        $where_conditions[] = "p.devuelto = 0 AND p.fecha_devolucion < CURDATE()";
    }
}

if (!empty($filtro_fecha_desde)) {
    $where_conditions[] = "p.fecha_prestamo >= ?";
    $params[] = $filtro_fecha_desde;
    $types .= 's';
}

if (!empty($filtro_fecha_hasta)) {
    $where_conditions[] = "p.fecha_prestamo <= ?";
    $params[] = $filtro_fecha_hasta;
    $types .= 's';
}

// CORRECCIÓN: Búsqueda de texto - parámetros correctos
if (!empty($busqueda_texto)) {
    $where_conditions[] = "(l.titulo LIKE ? OR l.autor LIKE ? OR l.codigo_interno LIKE ? OR lec.nombre LIKE ? OR lec.apellido LIKE ?)";
    $busqueda_like = "%{$busqueda_texto}%";
    $params[] = $busqueda_like;
    $params[] = $busqueda_like;
    $params[] = $busqueda_like;
    $params[] = $busqueda_like;
    $params[] = $busqueda_like;
    $types .= 'sssss'; // CORREGIDO: 5 's' para 5 parámetros
}

$where_sql = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';

// Consulta principal SIN límites para DataTables
$query = "SELECT p.*, l.titulo, l.autor, l.codigo_interno, l.isbn,
                 lec.nombre, lec.apellido, lec.email,
                 DATEDIFF(p.fecha_devolucion, CURDATE()) as dias_restantes,
                 CASE 
                     WHEN p.devuelto = 1 THEN 'DEVUELTO'
                     WHEN p.fecha_devolucion < CURDATE() THEN 'VENCIDO'
                     ELSE 'ACTIVO'
                 END as estado_texto,
                 CASE 
                     WHEN p.devuelto = 1 THEN 'success'
                     WHEN p.fecha_devolucion < CURDATE() THEN 'danger'
                     ELSE 'warning'
                 END as estado_color
          FROM prestamos p
          JOIN libros l ON p.id_libro = l.id
          LEFT JOIN lectores lec ON p.id_lector = lec.id
          {$where_sql}
          ORDER BY p.fecha_prestamo DESC, p.id DESC";

// 4. REGISTRAR CONSULTA DE HISTORIAL
$db->registrarAccion(
    'consulta_historial', 
    'historial', 
    "Consultando historial con " . count($where_conditions) . " filtros, " . count($params) . " parámetros"
);

// Obtener total de registros
$query_count = "SELECT COUNT(*) as total FROM prestamos p
                JOIN libros l ON p.id_libro = l.id
                LEFT JOIN lectores lec ON p.id_lector = lec.id
                {$where_sql}";
                
// Para la consulta COUNT
$result_count = mysqli_prepare($link, $query_count);
if (!empty($params) && !empty($where_conditions)) {
    mysqli_stmt_bind_param($result_count, $types, ...$params);
}
mysqli_stmt_execute($result_count);
mysqli_stmt_bind_result($result_count, $total_registros);
mysqli_stmt_fetch($result_count);
mysqli_stmt_close($result_count);

// 5. REGISTRAR TOTAL OBTENIDO
$db->registrarAccion(
    'total_obtenido', 
    'historial', 
    "Total de registros en historial: {$total_registros}"
);

// Ejecutar consulta principal SIN límites (DataTables manejará la paginación)
$stmt = mysqli_prepare($link, $query);
if (!empty($params) && !empty($where_conditions)) {
    mysqli_stmt_bind_param($stmt, $types, ...$params);
}
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

// Verificar si hubo error
if (!$result) {
    // 6. REGISTRAR ERROR EN CONSULTA
    $db->registrarAccion(
        'error_consulta', 
        'historial', 
        "Error en consulta de historial: " . mysqli_error($link)
    );
} else {
    $registros_obtenidos = mysqli_num_rows($result);
    
    // 7. REGISTRAR CONSULTA EXITOSA
    $db->registrarAccion(
        'consulta_exitosa', 
        'historial', 
        "Historial consultado exitosamente - {$registros_obtenidos} registros encontrados"
    );
}

// Obtener lista de lectores para el filtro
$query_lectores = "SELECT id, nombre, apellido, email FROM lectores ORDER BY nombre, apellido";
$result_lectores = mysqli_query($link, $query_lectores);

// 8. AGREGAR LOGS PARA ACCIONES ESPECÍFICAS (si las tienes en esta página)
// Por ejemplo, si exportas el historial
if (isset($_GET['exportar']) && $_GET['exportar'] == 'csv') {
    
    $db->registrarAccion(
        'exportacion_inicio', 
        'historial', 
        "Iniciando exportación CSV del historial"
    );
    
    // ... tu código de exportación ...
    
    $db->registrarAccion(
        'exportacion_exitosa', 
        'historial', 
        "Exportación CSV completada - {$total_registros} registros exportados"
    );
}

// 9. Si procesas devoluciones desde el historial también
if (isset($_GET['marcar_devuelto']) && is_numeric($_GET['marcar_devuelto'])) {
    $prestamo_id = $_GET['marcar_devuelto'];
    
    $db->registrarAccion(
        'devolucion_desde_historial', 
        'historial', 
        "Marcando devolución desde historial - Préstamo ID: {$prestamo_id}"
    );
    
    // ... tu código para marcar como devuelto ...
    
    $db->registrarAccion(
        'devolucion_completada', 
        'historial', 
        "Devolución marcada desde historial - Préstamo ID: {$prestamo_id}"
    );
}

// Iniciar buffer para contenido
ob_start();
?>

<div class="row">
    <!-- FILTROS -->
    <div class="col-md-3 mb-4">
        <div class="card">
            <div class="card-header gradient-primary">
                <h6 class="mb-0"><i class="fas fa-filter me-2"></i>Filtros</h6>
            </div>
            <div class="card-body">
                <form method="GET" id="form-filtros">
                    <!-- Filtro por lector -->
                    <div class="mb-3">
                        <label for="lector_id" class="form-label">Filtrar por persona</label>
                        <select class="form-select" id="lector_id" name="lector_id">
                            <option value="todos">Todas las personas</option>
                            <?php 
                            // Resetear puntero del resultado para usarlo nuevamente
                            mysqli_data_seek($result_lectores, 0);
                            while($lector = mysqli_fetch_assoc($result_lectores)): 
                            ?>
                                <option value="<?= $lector['id'] ?>" 
                                    <?= $filtro_lector == $lector['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($lector['nombre'] . ' ' . $lector['apellido']) ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    
                    <!-- Filtro por estado -->
                    <div class="mb-3">
                        <label for="estado" class="form-label">Estado</label>
                        <select class="form-select" id="estado" name="estado">
                            <option value="todos" <?= $filtro_estado == 'todos' ? 'selected' : '' ?>>Todos los estados</option>
                            <option value="activos" <?= $filtro_estado == 'activos' ? 'selected' : '' ?>>Solo activos</option>
                            <option value="devueltos" <?= $filtro_estado == 'devueltos' ? 'selected' : '' ?>>Solo devueltos</option>
                            <option value="vencidos" <?= $filtro_estado == 'vencidos' ? 'selected' : '' ?>>Solo vencidos</option>
                        </select>
                    </div>
                    
                    <!-- Fechas -->
                    <div class="mb-3">
                        <label class="form-label">Rango de fechas</label>
                        <div class="row g-2">
                            <div class="col-12">
                                <input type="date" class="form-control form-control-sm" 
                                       name="fecha_desde" value="<?= $filtro_fecha_desde ?>"
                                       placeholder="Desde">
                            </div>
                            <div class="col-12">
                                <input type="date" class="form-control form-control-sm" 
                                       name="fecha_hasta" value="<?= $filtro_fecha_hasta ?>"
                                       placeholder="Hasta">
                            </div>
                        </div>
                    </div>
                    
                    <!-- Búsqueda -->
                    <div class="mb-3">
                        <label for="q" class="form-label">Búsqueda de texto</label>
                        <input type="text" class="form-control" id="q" name="q" 
                               value="<?= htmlspecialchars($busqueda_texto) ?>"
                               placeholder="Libro, persona, código...">
                    </div>
                    
                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-search me-2"></i>Aplicar Filtros
                        </button>
                        <a href="historial_prestamo.php" class="btn btn-outline-secondary">
                            <i class="fas fa-redo me-2"></i>Limpiar Filtros
                        </a>
                    </div>
                </form>
                
                <!-- Resumen -->
                <div class="mt-4 pt-3 border-top">
                    <h6 class="text-muted mb-2">Resumen</h6>
                    <div class="small">
                        <div class="d-flex justify-content-between mb-1">
                            <span>Total registros:</span>
                            <strong><?= number_format($total_registros) ?></strong>
                        </div>
                        <?php if (!empty($filtro_lector) && $filtro_lector !== 'todos'): 
                            // Resetear la conexión para nueva consulta
                            $lector_info_query = mysqli_query($link, 
                                "SELECT nombre, apellido FROM lectores WHERE id = " . intval($filtro_lector));
                            if ($lector_info_query && mysqli_num_rows($lector_info_query) > 0) {
                                $lector_info = mysqli_fetch_assoc($lector_info_query);
                        ?>
                        <div class="d-flex justify-content-between">
                            <span>Filtrado por:</span>
                            <strong><?= htmlspecialchars($lector_info['nombre'] . ' ' . $lector_info['apellido']) ?></strong>
                        </div>
                        <?php 
                            }
                        endif; 
                        ?>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Exportación -->
        <div class="card mt-4">
            <div class="card-header gradient-success">
                <h6 class="mb-0"><i class="fas fa-download me-2"></i>Exportar</h6>
            </div>
            <div class="card-body">
                <div class="d-grid gap-2">
                    <button class="btn btn-outline-success" onclick="exportarExcel()">
                        <i class="fas fa-file-excel me-2"></i>Excel
                    </button>
                    <button class="btn btn-outline-danger" onclick="imprimirTabla()">
                        <i class="fas fa-print me-2"></i>Imprimir
                    </button>
                    <button class="btn btn-outline-info" onclick="generarReporte()">
                        <i class="fas fa-chart-bar me-2"></i>Reporte
                    </button>
                </div>
            </div>
        </div>
    </div>
    
    <!-- LISTA DE PRÉSTAMOS -->
    <div class="col-md-9">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center gradient-book">
                <h5 class="mb-0">
                    <i class="fas fa-list me-2"></i>Historial de Préstamos
                    <span class="badge bg-light text-dark ms-2"><?= $total_registros ?></span>
                </h5>
                <div>
                    <span class="me-2">
                        <span class="badge bg-success">Devuelto</span>
                        <span class="badge bg-warning">Activo</span>
                        <span class="badge bg-danger">Vencido</span>
                    </span>
                </div>
            </div>
            <div class="card-body">
                <?php if ($total_registros > 0): ?>
                    <div class="table-responsive" id="tabla-historial">
                        <table class="table table-hover" id="tabla-prestamos">
                            <thead>
                                <tr>
                                    <th width="5%">#</th>
                                    <th width="25%">Libro</th>
                                    <th width="20%">Persona</th>
                                    <th width="15%">Fechas</th>
                                    <th width="10%">Estado</th>
                                    <th width="15%">Código</th>
                                    <th width="10%" class="text-center">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                $contador = 1;
                                while($prestamo = mysqli_fetch_assoc($result)): 
                                    $fecha_prestamo = date('d/m/Y', strtotime($prestamo['fecha_prestamo']));
                                    $fecha_devolucion = date('d/m/Y', strtotime($prestamo['fecha_devolucion']));
                                ?>
                                <tr>
                                    <td><?= $contador++ ?></td>
                                    <td>
                                        <div class="fw-bold"><?= htmlspecialchars($prestamo['titulo']) ?></div>
                                        <small class="text-muted"><?= htmlspecialchars($prestamo['autor']) ?></small>
                                    </td>
                                    <td>
                                        <div><?= htmlspecialchars($prestamo['nombre'] . ' ' . $prestamo['apellido']) ?></div>
                                        <small class="text-muted"><?= $prestamo['email'] ?></small>
                                    </td>
                                    <td>
                                        <div><small><strong>Préstamo:</strong> <?= $fecha_prestamo ?></small></div>
                                        <div><small><strong>Devolución:</strong> <?= $fecha_devolucion ?></small></div>
                                        <?php if ($prestamo['estado_texto'] == 'ACTIVO'): ?>
                                            <small class="text-<?= $prestamo['dias_restantes'] <= 3 ? 'warning' : 'success' ?>">
                                                <?= $prestamo['dias_restantes'] ?> días restantes
                                            </small>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="badge bg-<?= $prestamo['estado_color'] ?>">
                                            <?= $prestamo['estado_texto'] ?>
                                        </span>
                                    </td>
                                    <td>
                                        <code class="small"><?= $prestamo['codigo_interno'] ?></code>
                                        <?php if ($prestamo['isbn']): ?>
                                            <br><small class="text-muted">ISBN: <?= $prestamo['isbn'] ?></small>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center">
                                        <?php if ($prestamo['estado_texto'] == 'ACTIVO' || $prestamo['estado_texto'] == 'VENCIDO'): ?>
                                            <a href="devolucion_libro.php?codigo=<?= urlencode($prestamo['isbn'] ?: $prestamo['codigo_interno']) ?>" 
                                               class="btn btn-sm btn-success mb-1" title="Registrar devolución">
                                                <i class="fas fa-check"></i>
                                            </a>
                                        <?php endif; ?>
                                        <button class="btn btn-sm btn-info ver-detalles" 
                                                data-id="<?= $prestamo['id'] ?>" 
                                                title="Ver detalles">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                    
                <?php else: ?>
                    <div class="text-center py-5">
                        <i class="fas fa-history fa-4x text-muted mb-3"></i>
                        <h4>No se encontraron préstamos</h4>
                        <p class="text-muted mb-4">
                            <?php if (!empty($filtro_lector) || !empty($busqueda_texto) || !empty($filtro_fecha_desde)): ?>
                                Intente con otros filtros de búsqueda
                            <?php else: ?>
                                No hay préstamos registrados en el sistema
                            <?php endif; ?>
                        </p>
                        <a href="agregar_prestamo.php" class="btn btn-primary">
                            <i class="fas fa-plus me-2"></i>Registrar Primer Préstamo
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Estadísticas -->
        <div class="row mt-4">
            <div class="col-md-4">
                <div class="card bg-primary text-white">
                    <div class="card-body text-center">
                        <h2 class="mb-0">
                            <?php 
                            $activos = mysqli_fetch_assoc(mysqli_query($link, 
                                "SELECT COUNT(*) as total FROM prestamos WHERE devuelto = 0"));
                            echo $activos['total'];
                            ?>
                        </h2>
                        <small>Préstamos Activos</small>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card bg-success text-white">
                    <div class="card-body text-center">
                        <h2 class="mb-0">
                            <?php 
                            $devueltos = mysqli_fetch_assoc(mysqli_query($link, 
                                "SELECT COUNT(*) as total FROM prestamos WHERE devuelto = 1"));
                            echo $devueltos['total'];
                            ?>
                        </h2>
                        <small>Total Devueltos</small>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card bg-danger text-white">
                    <div class="card-body text-center">
                        <h2 class="mb-0">
                            <?php 
                            $vencidos = mysqli_fetch_assoc(mysqli_query($link, 
                                "SELECT COUNT(*) as total FROM prestamos 
                                 WHERE devuelto = 0 AND fecha_devolucion < CURDATE()"));
                            echo $vencidos['total'];
                            ?>
                        </h2>
                        <small>Vencidos Actualmente</small>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- MODAL PARA DETALLES -->
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
            </div>
        </div>
    </div>
</div>

<!-- Scripts específicos -->
<script>
$(document).ready(function() {
    // Inicializar DataTables
    $('#tabla-prestamos').DataTable({
        language: {
            // Usar CDN de DataTables para español
            url: 'https://cdn.datatables.net/plug-ins/1.13.4/i18n/es-ES.json'
        },
        pageLength: 20,
        lengthMenu: [[10, 20, 50, 100, -1], [10, 20, 50, 100, "Todos"]],
        order: [[0, 'asc']],
        responsive: true,
        dom: '<"row"<"col-md-6"l><"col-md-6"f>>rt<"row"<"col-md-12 text-center"p><"col-md-12 text-end"B>>',
        columnDefs: [
            { orderable: false, targets: [6] }
        ],
        info: false,
        buttons: [
            {
                extend: 'copy',
                text: '<i class="fas fa-copy"></i> Copiar',
                className: 'btn btn-sm btn-outline-secondary',
                title: 'Historial de Préstamos'
            },
            {
                extend: 'excel',
                text: '<i class="fas fa-file-excel"></i> Excel',
                className: 'btn btn-sm btn-outline-success',
                title: 'Historial de Préstamos'
            },
            {
                extend: 'pdf',
                text: '<i class="fas fa-file-pdf"></i> PDF',
                className: 'btn btn-sm btn-outline-danger',
                title: 'Historial de Préstamos'
            },
            {
                extend: 'print',
                text: '<i class="fas fa-print"></i> Imprimir',
                className: 'btn btn-sm btn-outline-info',
                title: 'Historial de Préstamos'
            }
        ]
    });
    
    
    // Auto-enviar formulario de filtros al cambiar algunos selects
    $('#lector_id').change(function() {
        if ($(this).val() !== 'todos') {
            $('#form-filtros').submit();
        }
    });
    
    $('#estado').change(function() {
        if ($(this).val() !== 'todos') {
            $('#form-filtros').submit();
        }
    });
    
    // Ver detalles del préstamo
    $(document).on('click', '.ver-detalles', function() {
        const prestamoId = $(this).data('id');
        const modalDetalles = new bootstrap.Modal(document.getElementById('modalDetalles'));
        
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
        
        // Cargar detalles reales via AJAX
        $.ajax({
            url: 'obtener_detalles_prestamo.php',
            method: 'GET',
            data: { 
                id: prestamoId,
                _t: new Date().getTime()
            },
            dataType: 'html',
            success: function(response) {
                $('#modalDetallesBody').html(response);
            },
            error: function(xhr, status, error) {
                console.error('Error al cargar detalles:', error);
                $('#modalDetallesBody').html(`
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        Error al cargar los detalles del préstamo.<br>
                        <small>${error}</small>
                    </div>
                `);
            }
        });
    });
});

// Funciones de exportación
function exportarExcel() {
    alert('Función de exportación a Excel - En desarrollo');
    // En un sistema real: window.location = 'exportar_excel.php?' + new URLSearchParams(window.location.search);
}

function imprimirTabla() {
    // Crear una versión limpia de la tabla sin DataTables
    const tablaOriginal = document.getElementById('tabla-prestamos');
    const tablaClon = tablaOriginal.cloneNode(true);
    
    // Remover funcionalidades de DataTables
    $(tablaClon).find('td, th').css({
        'border': '1px solid #000',
        'padding': '5px'
    });
    
    // Crear ventana de impresión
    const printWindow = window.open('', '_blank');
    const titulo = 'Historial de Préstamos - ' + new Date().toLocaleDateString();
    
    printWindow.document.write(`
        <html>
            <head>
                <title>${titulo}</title>
                <style>
                    body { 
                        font-family: Arial, sans-serif; 
                        margin: 20px;
                        font-size: 12px;
                    }
                    h4 { 
                        text-align: center; 
                        margin-bottom: 20px;
                        color: #333;
                    }
                    table {
                        width: 100%;
                        border-collapse: collapse;
                        margin-bottom: 20px;
                    }
                    th {
                        background-color: #f2f2f2;
                        border: 1px solid #000;
                        padding: 8px;
                        text-align: left;
                        font-weight: bold;
                    }
                    td {
                        border: 1px solid #000;
                        padding: 6px;
                        vertical-align: top;
                    }
                    .no-print {
                        display: none !important;
                    }
                    .badge {
                        display: inline-block;
                        padding: 3px 8px;
                        border-radius: 3px;
                        font-size: 11px;
                        font-weight: bold;
                    }
                    .bg-success { background-color: #28a745 !important; color: white; }
                    .bg-warning { background-color: #ffc107 !important; color: black; }
                    .bg-danger { background-color: #dc3545 !important; color: white; }
                    .text-center { text-align: center; }
                    .text-muted { color: #6c757d !important; }
                    .fw-bold { font-weight: bold !important; }
                </style>
            </head>
            <body>
                <h4>${titulo}</h4>
                ${tablaClon.outerHTML}
                <div class="text-center no-print mt-4">
                    <button onclick="window.print()" class="btn btn-primary">Imprimir</button>
                    <button onclick="window.close()" class="btn btn-secondary">Cerrar</button>
                </div>
                <script>
                    // Añadir estilos para impresión
                    const style = document.createElement('style');
                    style.textContent = '@media print { .no-print { display: none !important; } }';
                    document.head.appendChild(style);
                <\/script>
            </body>
        </html>
    `);
    printWindow.document.close();
}

function generarReporte() {
    window.location.href = 'reporte_prestamos.php?' + new URLSearchParams(window.location.search);
}
</script>
<?php
$contenido = ob_get_clean();
include 'layout.php';
?>