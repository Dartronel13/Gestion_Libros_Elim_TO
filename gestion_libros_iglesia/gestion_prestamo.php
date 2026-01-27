<?php
// gestion_prestamo.php - VERSIÓN MODIFICADA CON LOGS

// 1. VERIFICACIÓN DE ACCESO (AGREGAR ESTO AL INICIO)
require_once 'db.php';
verificarAutenticacion(); // ← ESTA LÍNEA ES NUEVA

// 2. REGISTRAR ACCESO A ESTA PÁGINA
$db->registrarAccion('acceso', 'prestamos', "Accedió a gestión de préstamos");

// Configurar variables para layout
$titulo_pagina = '📋 Gestión de Préstamos';
$icono_titulo = 'fas fa-tasks';

// 3. REGISTRAR CONSULTA DE ESTADÍSTICAS
$db->registrarAccion('consulta', 'prestamos', "Consultando estadísticas de préstamos");

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

// 4. REGISTRAR ESTADÍSTICAS OBTENIDAS
$db->registrarAccion(
    'estadisticas_obtenidas', 
    'prestamos', 
    "Estadísticas obtenidas - Activos: {$estadisticas['activos']}, " .
    "Por vencer: {$estadisticas['por_vencer']}, " .
    "Vencidos: {$estadisticas['vencidos']}, " .
    "Total: {$estadisticas['total']}"
);

// Determinar pestaña activa
$pestaña_activa = $_GET['tab'] ?? 'activos';
$busqueda = $_GET['busqueda'] ?? '';
$filtro_lector = $_GET['filtro_lector'] ?? '';
$filtro_fecha = $_GET['filtro_fecha'] ?? '';

// 5. REGISTRAR FILTROS APLICADOS
$filtros_aplicados = [];
if (!empty($pestaña_activa) && $pestaña_activa != 'activos') {
    $filtros_aplicados[] = "Pestaña: {$pestaña_activa}";
}
if (!empty($busqueda)) {
    $filtros_aplicados[] = "Búsqueda: '{$busqueda}'";
}
if (!empty($filtro_lector)) {
    $filtros_aplicados[] = "Lector ID: {$filtro_lector}";
}
if (!empty($filtro_fecha)) {
    $filtros_aplicados[] = "Fecha: {$filtro_fecha}";
}

if (!empty($filtros_aplicados)) {
    $db->registrarAccion(
        'filtros_aplicados', 
        'prestamos', 
        "Filtros aplicados: " . implode(', ', $filtros_aplicados)
    );
}

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
    
    // 6. REGISTRAR CONSULTA EXITOSA
    $db->registrarAccion(
        'consulta_exitosa', 
        'prestamos', 
        "Consultados " . count($prestamos) . " préstamos con filtros aplicados"
    );
} else {
    // 7. REGISTRAR ERROR EN CONSULTA
    $db->registrarAccion(
        'error_consulta', 
        'prestamos', 
        "Error en consulta de préstamos: " . mysqli_error($link)
    );
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

// 8. AGREGAR LOGS PARA ACCIONES ESPECÍFICAS (si las tienes)
// Por ejemplo, si procesas devoluciones desde esta misma página:

if (isset($_GET['marcar_devuelto']) && is_numeric($_GET['marcar_devuelto'])) {
    $prestamo_id = $_GET['marcar_devuelto'];
    
    // 9. REGISTRAR INICIO DE DEVOLUCIÓN
    $db->registrarAccion(
        'inicio_devolucion', 
        'devoluciones', 
        "Iniciando devolución desde gestión - Préstamo ID: {$prestamo_id}"
    );
    
    // Obtener info del préstamo antes
    $sql_info = "SELECT p.id_libro, l.titulo, lec.nombre, lec.apellido 
                 FROM prestamos p
                 JOIN libros l ON p.id_libro = l.id
                 JOIN lectores lec ON p.id_lector = lec.id
                 WHERE p.id = ?";
    $stmt_info = $db->query($sql_info, [$prestamo_id]);
    $prestamo_info = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt_info));
    
    // Marcar como devuelto
    $sql_update = "UPDATE prestamos SET devuelto = 1, fecha_devolucion = CURDATE() WHERE id = ?";
    $stmt_update = $db->query($sql_update, [$prestamo_id]);
    
    if ($stmt_update) {
        // Actualizar stock
        $sql_stock = "UPDATE libros SET stock = stock + 1 WHERE id = ?";
        $db->query($sql_stock, [$prestamo_info['id_libro']]);
        
        // 10. REGISTRAR DEVOLUCIÓN EXITOSA
        $db->registrarAccion(
            'devolucion_exitosa', 
            'devoluciones', 
            "Devolución procesada - Préstamo ID: {$prestamo_id}, " .
            "Libro: '{$prestamo_info['titulo']}', " .
            "Lector: {$prestamo_info['nombre']} {$prestamo_info['apellido']}"
        );
        
        // 11. REGISTRAR STOCK RESTAURADO
        $db->registrarAccion(
            'stock_restaurado', 
            'inventario', 
            "Stock restaurado por devolución - Libro ID: {$prestamo_info['id_libro']}"
        );
        
        // Redirigir para evitar reenvío
        header("Location: gestion_prestamo.php?tab={$pestaña_activa}&exito=1");
        exit();
    } else {
        // 12. REGISTRAR ERROR EN DEVOLUCIÓN
        $db->registrarAccion(
            'error_devolucion', 
            'devoluciones', 
            "Error al marcar devolución - Préstamo ID: {$prestamo_id}"
        );
    }
}

// También para renovar préstamos
if (isset($_GET['renovar']) && is_numeric($_GET['renovar'])) {
    $prestamo_id = $_GET['renovar'];
    
    // 13. REGISTRAR INICIO DE RENOVACIÓN
    $db->registrarAccion(
        'inicio_renovacion', 
        'prestamos', 
        "Iniciando renovación - Préstamo ID: {$prestamo_id}"
    );
    
}

ob_start();
?>

<style>
/* Estilos para pestañas con texto negro */
.nav-tabs .nav-link {
    color: #000000 !important;
    font-weight: 500;
    border-bottom: 1px solid transparent;
}

.nav-tabs .nav-link:hover {
    color: #000000 !important;
    background-color: rgba(0, 0, 0, 0.05);
    border-color: #dee2e6 #dee2e6 transparent;
}

.nav-tabs .nav-link.active {
    color: #000000 !important;
    background-color: #ffffff;
    border-color: #dee2e6 #dee2e6 #ffffff;
    font-weight: 600;
    border-bottom: 3px solid #007bff;
}

/* Estilos para los badges dentro de las pestañas */
.nav-tabs .nav-link .badge {
    color: #ffffff;
}
</style>

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
                <a class="nav-link text-dark <?php echo $pestaña_activa == 'activos' ? 'active' : ''; ?>" 
                   href="?tab=activos&busqueda=<?php echo urlencode($busqueda); ?>&filtro_lector=<?php echo $filtro_lector; ?>&filtro_fecha=<?php echo $filtro_fecha; ?>">
                    <i class="fas fa-book me-1"></i> Activos
                    <span class="badge bg-primary ms-1"><?php echo $estadisticas['activos']; ?></span>
                </a>
            </li>
            <li class="nav-item" role="presentation">
                <a class="nav-link text-dark <?php echo $pestaña_activa == 'por_vencer' ? 'active' : ''; ?>" 
                   href="?tab=por_vencer&busqueda=<?php echo urlencode($busqueda); ?>&filtro_lector=<?php echo $filtro_lector; ?>&filtro_fecha=<?php echo $filtro_fecha; ?>">
                    <i class="fas fa-clock me-1"></i> Por Vencer
                    <span class="badge bg-warning ms-1"><?php echo $estadisticas['por_vencer']; ?></span>
                </a>
            </li>
            <li class="nav-item" role="presentation">
                <a class="nav-link text-dark <?php echo $pestaña_activa == 'vencidos' ? 'active' : ''; ?>" 
                   href="?tab=vencidos&busqueda=<?php echo urlencode($busqueda); ?>&filtro_lector=<?php echo $filtro_lector; ?>&filtro_fecha=<?php echo $filtro_fecha; ?>">
                    <i class="fas fa-exclamation-triangle me-1"></i> Vencidos
                    <span class="badge bg-danger ms-1"><?php echo $estadisticas['vencidos']; ?></span>
                </a>
            </li>
            <li class="nav-item" role="presentation">
                <a class="nav-link text-dark <?php echo $pestaña_activa == 'todos' ? 'active' : ''; ?>" 
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
                                <?php endif; ?>
                                
                                <!-- COMENTE EL BOTON DE IMPRIMIR DE LA TABLA PARA HABILITARLO LUEGO SI LLEGASE A SERVIR
                                 <button class="btn btn-sm btn-secondary imprimir-recibo" 
                                        data-id="<?php echo $prestamo['id']; ?>"
                                        title="Imprimir recibo">
                                    <i class="fas fa-print"></i>
                                </button> -->
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
                <!-- Botón de exportar removido según solicitud -->
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
                <button type="button" class="btn btn-primary" id="btn-imprimir-modal">
                    <i class="fas fa-print me-1"></i> Imprimir
                </button>
            </div>
        </div>
    </div>
</div>

<!-- MODAL PARA IMPRIMIR RECIBO -->
<div class="modal fade" id="modalImprimirRecibo" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header gradient-info text-white">
                <h5 class="modal-title">
                    <i class="fas fa-print me-2"></i>
                    Vista Previa del Recibo
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="modalImprimirReciboBody">
                <!-- Se cargará con AJAX -->
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="fas fa-times me-1"></i> Cerrar
                </button>
                <button type="button" class="btn btn-primary" id="btn-confirmar-imprimir">
                    <i class="fas fa-print me-1"></i> Imprimir Recibo
                </button>
            </div>
        </div>
    </div>
</div>


<script>
// Espera a que jQuery se cargue
function waitForjQuery() {
    if (typeof window.jQuery === 'undefined') {
        console.log('Esperando jQuery...');
        setTimeout(waitForjQuery, 100);
    } else {
        console.log('jQuery cargado, inicializando...');
        $(document).ready(function() {
            let prestamoActual = null;
            const modalDetalles = new bootstrap.Modal(document.getElementById('modalDetalles'));
            const modalImprimirRecibo = new bootstrap.Modal(document.getElementById('modalImprimirRecibo'));

            // ============================================
            // VER DETALLES DEL PRÉSTAMO
            // ============================================
            $(document).on('click', '.ver-detalles', function(e) {
                e.preventDefault();
                const prestamoId = $(this).data('id');
                prestamoActual = prestamoId;
                
                console.log('Ver detalles del préstamo ID:', prestamoId);
                
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
            
            // ============================================
            // MARCAR COMO DEVUELTO - Redirección directa
            // ============================================
            $(document).on('click', '.marcar-devuelto', function(e) {
                e.preventDefault();
                const prestamoId = $(this).data('id');
                const libroCodigo = $(this).data('codigo');
                
                console.log('Marcar como devuelto - ID:', prestamoId, 'Código:', libroCodigo);
                
                // Confirmar redirección
                if (confirm('¿Marcar este préstamo como devuelto?\n\nSerá redirigido a la página de devoluciones para confirmar.')) {
                    // Redirigir a devoluciones.php con el código
                    window.location.href = `devolucion_libro.php?codigo=${encodeURIComponent(libroCodigo)}&from=gestion`;
                }
            });
            
            // ============================================
            // IMPRIMIR RECIBO - Nueva funcionalidad
            // ============================================
            $(document).on('click', '.imprimir-recibo', function(e) {
                e.preventDefault();
                const prestamoId = $(this).data('id');
                prestamoActual = prestamoId;
                
                console.log('Imprimir recibo del préstamo ID:', prestamoId);
                
                // Mostrar loading
                $('#modalImprimirReciboBody').html(`
                    <div class="text-center py-4">
                        <div class="spinner-border text-primary mb-3" role="status">
                            <span class="visually-hidden">Cargando...</span>
                        </div>
                        <h5>Cargando recibo...</h5>
                    </div>
                `);
                
                modalImprimirRecibo.show();
                
                // Cargar recibo via AJAX
                $.ajax({
                    url: 'imprimir_recibo_prestamo.php',
                    method: 'GET',
                    data: { 
                        id: prestamoId,
                        _t: new Date().getTime()
                    },
                    dataType: 'html',
                    success: function(response) {
                        $('#modalImprimirReciboBody').html(response);
                    },
                    error: function(xhr, status, error) {
                        console.error('Error al cargar recibo:', error);
                        $('#modalImprimirReciboBody').html(`
                            <div class="alert alert-danger">
                                <i class="fas fa-exclamation-triangle me-2"></i>
                                Error al cargar el recibo del préstamo.<br>
                                <small>${error}</small>
                            </div>
                        `);
                    }
                });
            });
            
            // ============================================
            // CONFIRMAR IMPRESIÓN DEL RECIBO
            // ============================================
            $('#btn-confirmar-imprimir').click(function() {
                // Crear ventana para impresión
                const contenido = $('#modalImprimirReciboBody').html();
                const ventanaImpresion = window.open('', '_blank');
                
                ventanaImpresion.document.write(`
                    <!DOCTYPE html>
                    <html>
                    <head>
                        <title>Recibo de Préstamo</title>
                        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
                        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
                        <style>
                            @media print {
                                body { margin: 0; padding: 0; }
                                .no-print { display: none !important; }
                                .print-only { display: block !important; }
                                .container { max-width: 100% !important; }
                                .card { border: 1px solid #000 !important; }
                                .text-center { text-align: center !important; }
                                .mt-3 { margin-top: 1rem !important; }
                                .mb-3 { margin-bottom: 1rem !important; }
                            }
                            body { font-family: Arial, sans-serif; }
                            .recibo-header { 
                                border-bottom: 2px solid #000; 
                                padding-bottom: 10px;
                                margin-bottom: 20px;
                            }
                            .recibo-footer { 
                                border-top: 2px solid #000; 
                                padding-top: 10px;
                                margin-top: 20px;
                                font-size: 12px;
                            }
                            .firma { 
                                margin-top: 50px;
                                border-top: 1px solid #000;
                                width: 200px;
                                padding-top: 10px;
                            }
                        </style>
                    </head>
                    <body>
                        <div class="container mt-3">
                            ${contenido}
                        </div>
                        <div class="text-center no-print mt-3">
                            <button onclick="window.print()" class="btn btn-primary">
                                <i class="fas fa-print me-1"></i> Imprimir
                            </button>
                            <button onclick="window.close()" class="btn btn-secondary ms-2">
                                <i class="fas fa-times me-1"></i> Cerrar
                            </button>
                        </div>
                    </body>
                    </html>
                `);
                
                ventanaImpresion.document.close();
                
                // Cerrar modal después de abrir ventana de impresión
                setTimeout(() => {
                    modalImprimirRecibo.hide();
                }, 500);
            });
            
            // ============================================
            // FUNCIONES AUXILIARES
            // ============================================
            
            // Limpiar búsqueda
            $('#btn-limpiar-busqueda').click(function() {
                $('#busqueda').val('');
                window.location.href = 'gestion_prestamo.php?tab=<?php echo $pestaña_activa; ?>';
            });
            
            // Imprimir desde modal de detalles
            $('#btn-imprimir-modal').click(function() {
                window.print();
            });
            
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
        }); // Cierra $(document).ready()
    } // Cierra else
} // Cierra waitForjQuery()

// Iniciar
waitForjQuery();
</script>
<?php
$contenido = ob_get_clean();
include 'layout.php';
?>