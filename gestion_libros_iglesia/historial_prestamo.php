<?php
require_once 'db.php';

// Configurar variables para layout
$titulo_pagina = '📜 Historial de Préstamos';
$icono_titulo = 'fas fa-history';

// Parámetros de filtro
$filtro_lector = $_GET['lector_id'] ?? '';
$filtro_estado = $_GET['estado'] ?? 'todos'; // todos, activos, devueltos, vencidos
$filtro_fecha_desde = $_GET['fecha_desde'] ?? '';
$filtro_fecha_hasta = $_GET['fecha_hasta'] ?? '';
$busqueda_texto = $_GET['q'] ?? '';

// Construir consulta con filtros
$where_conditions = [];
$params = [];
$types = '';

if (!empty($filtro_lector) && $filtro_lector !== 'todos') {
    $where_conditions[] = "p.id_lector = ?";
    $params[] = $filtro_lector;
    $types .= 'i';
}

if ($filtro_estado === 'activos') {
    $where_conditions[] = "p.devuelto = 0";
} elseif ($filtro_estado === 'devueltos') {
    $where_conditions[] = "p.devuelto = 1";
} elseif ($filtro_estado === 'vencidos') {
    $where_conditions[] = "p.devuelto = 0 AND p.fecha_devolucion < CURDATE()";
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

if (!empty($busqueda_texto)) {
    $where_conditions[] = "(l.titulo LIKE ? OR l.autor LIKE ? OR l.codigo_interno LIKE ? OR lec.nombre LIKE ? OR lec.apellido LIKE ?)";
    $busqueda_like = "%{$busqueda_texto}%";
    for ($i = 0; $i < 5; $i++) {
        $params[] = $busqueda_like;
        $types .= 's';
    }
}

$where_sql = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';

// Consulta principal
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

// Obtener total de registros para paginación
$query_count = "SELECT COUNT(*) as total FROM prestamos p
                JOIN libros l ON p.id_libro = l.id
                LEFT JOIN lectores lec ON p.id_lector = lec.id
                {$where_sql}";
$result_count = mysqli_query($link, $query_count);
$total_registros = mysqli_fetch_assoc($result_count)['total'];

// Aplicar límites para paginación
$por_pagina = 20;
$pagina_actual = $_GET['pagina'] ?? 1;
$offset = ($pagina_actual - 1) * $por_pagina;
$total_paginas = ceil($total_registros / $por_pagina);

$query .= " LIMIT ? OFFSET ?";
$params[] = $por_pagina;
$params[] = $offset;
$types .= 'ii';

// Ejecutar consulta con parámetros
$stmt = mysqli_prepare($link, $query);
if (!empty($params)) {
    mysqli_stmt_bind_param($stmt, $types, ...$params);
}
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

// Obtener lista de lectores para el filtro
$query_lectores = "SELECT id, nombre, apellido, email FROM lectores ORDER BY nombre, apellido";
$result_lectores = mysqli_query($link, $query_lectores);

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
                            <?php while($lector = mysqli_fetch_assoc($result_lectores)): ?>
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
                        <div class="d-flex justify-content-between mb-1">
                            <span>Página actual:</span>
                            <strong><?= $pagina_actual ?> de <?= $total_paginas ?></strong>
                        </div>
                        <?php if (!empty($filtro_lector) && $filtro_lector !== 'todos'): 
                            $lector_info = mysqli_fetch_assoc(mysqli_query($link, 
                                "SELECT nombre, apellido FROM lectores WHERE id = $filtro_lector"));
                        ?>
                        <div class="d-flex justify-content-between">
                            <span>Filtrado por:</span>
                            <strong><?= $lector_info['nombre'] . ' ' . $lector_info['apellido'] ?></strong>
                        </div>
                        <?php endif; ?>
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
                <?php if (mysqli_num_rows($result) > 0): ?>
                    <div class="table-responsive" id="tabla-historial">
                        <table class="table table-hover data-table">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Libro</th>
                                    <th>Persona</th>
                                    <th>Fechas</th>
                                    <th>Estado</th>
                                    <th>Código</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                $contador = $offset + 1;
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
                                        <div><strong>Préstamo:</strong> <?= $fecha_prestamo ?></div>
                                        <div><strong>Devolución:</strong> <?= $fecha_devolucion ?></div>
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
                                    <td>
                                        <?php if ($prestamo['estado_texto'] == 'ACTIVO' || $prestamo['estado_texto'] == 'VENCIDO'): ?>
                                            <a href="devolucion_libro.php?codigo=<?= $prestamo['isbn'] ?: $prestamo['codigo_interno'] ?>" 
                                               class="btn btn-sm btn-success" title="Registrar devolución">
                                                <i class="fas fa-check"></i>
                                            </a>
                                        <?php endif; ?>
                                        <button class="btn btn-sm btn-info" 
                                                onclick="verDetalles(<?= $prestamo['id'] ?>)" 
                                                title="Ver detalles">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                    
                    <!-- PAGINACIÓN -->
                    <?php if ($total_paginas > 1): ?>
                    <nav aria-label="Paginación" class="mt-4">
                        <ul class="pagination justify-content-center">
                            <?php if ($pagina_actual > 1): ?>
                                <li class="page-item">
                                    <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['pagina' => $pagina_actual - 1])) ?>">
                                        <i class="fas fa-chevron-left"></i>
                                    </a>
                                </li>
                            <?php endif; ?>
                            
                            <?php
                            $inicio = max(1, $pagina_actual - 2);
                            $fin = min($total_paginas, $pagina_actual + 2);
                            
                            for ($i = $inicio; $i <= $fin; $i++):
                            ?>
                                <li class="page-item <?= $i == $pagina_actual ? 'active' : '' ?>">
                                    <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['pagina' => $i])) ?>">
                                        <?= $i ?>
                                    </a>
                                </li>
                            <?php endfor; ?>
                            
                            <?php if ($pagina_actual < $total_paginas): ?>
                                <li class="page-item">
                                    <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['pagina' => $pagina_actual + 1])) ?>">
                                        <i class="fas fa-chevron-right"></i>
                                    </a>
                                </li>
                            <?php endif; ?>
                        </ul>
                    </nav>
                    <?php endif; ?>
                    
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

<!-- Scripts específicos -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Inicializar DataTables
    if ($('.data-table').length) {
        $('.data-table').DataTable({
            language: {
                url: '//cdn.datatables.net/plug-ins/1.13.4/i18n/es-ES.json'
            },
            pageLength: 25,
            order: [[0, 'desc']],
            dom: '<"row"<"col-md-6"l><"col-md-6"f>>rt<"row"<"col-md-6"i><"col-md-6"p>>',
            responsive: true
        });
    }
    
    // Auto-enviar formulario de filtros al cambiar algunos selects
    document.getElementById('lector_id').addEventListener('change', function() {
        if (this.value !== 'todos') {
            document.getElementById('form-filtros').submit();
        }
    });
    
    document.getElementById('estado').addEventListener('change', function() {
        if (this.value !== 'todos') {
            document.getElementById('form-filtros').submit();
        }
    });
});

// Función para ver detalles
function verDetalles(prestamoId) {
    // En un sistema real, esto haría una petición AJAX
    const modalContent = `
        <div class="modal fade" id="modalDetalles" tabindex="-1">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header gradient-primary text-white">
                        <h5 class="modal-title">Detalles del Préstamo</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="text-center">
                            <div class="spinner-border text-primary"></div>
                            <p class="mt-2">Cargando detalles...</p>
                        </div>
                        <div id="detalles-contenido"></div>
                    </div>
                </div>
            </div>
        </div>
    `;
    
    // Agregar modal al DOM si no existe
    if (!document.getElementById('modalDetalles')) {
        document.body.insertAdjacentHTML('beforeend', modalContent);
    }
    
    const modal = new bootstrap.Modal(document.getElementById('modalDetalles'));
    modal.show();
    
    // Simular carga de detalles
    setTimeout(() => {
        document.getElementById('detalles-contenido').innerHTML = `
            <div class="row">
                <div class="col-md-6">
                    <h6><i class="fas fa-book me-2"></i>Información del Libro</h6>
                    <ul class="list-unstyled">
                        <li><strong>ID Préstamo:</strong> ${prestamoId}</li>
                        <li><strong>Título:</strong> Libro ejemplo</li>
                        <li><strong>Autor:</strong> Autor ejemplo</li>
                        <li><strong>ISBN:</strong> 978-3-16-148410-0</li>
                    </ul>
                </div>
                <div class="col-md-6">
                    <h6><i class="fas fa-user me-2"></i>Información de la Persona</h6>
                    <ul class="list-unstyled">
                        <li><strong>Nombre:</strong> Juan Pérez</li>
                        <li><strong>Email:</strong> juan@email.com</li>
                        <li><strong>Teléfono:</strong> (123) 456-7890</li>
                    </ul>
                </div>
            </div>
            <hr>
            <div class="row">
                <div class="col-md-6">
                    <h6><i class="fas fa-calendar me-2"></i>Fechas</h6>
                    <ul class="list-unstyled">
                        <li><strong>Préstamo:</strong> ${new Date().toLocaleDateString()}</li>
                        <li><strong>Devolución:</strong> ${new Date(Date.now() + 15*24*60*60*1000).toLocaleDateString()}</li>
                        <li><strong>Días transcurridos:</strong> 5</li>
                    </ul>
                </div>
                <div class="col-md-6">
                    <h6><i class="fas fa-info-circle me-2"></i>Estado</h6>
                    <p><span class="badge bg-success">DEVUELTO</span></p>
                    <p><small class="text-muted">Última actualización: Hoy</small></p>
                </div>
            </div>
        `;
    }, 800);
}

// Funciones de exportación
function exportarExcel() {
    alert('Función de exportación a Excel - En desarrollo');
    // En un sistema real: window.location = 'exportar_excel.php?' + new URLSearchParams(window.location.search);
}

function imprimirTabla() {
    const printWindow = window.open('', '_blank');
    const titulo = 'Historial de Préstamos - ' + new Date().toLocaleDateString();
    const contenido = document.getElementById('tabla-historial').outerHTML;
    
    printWindow.document.write(`
        <html>
            <head>
                <title>${titulo}</title>
                <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
                <style>
                    body { padding: 20px; }
                    @media print {
                        .no-print { display: none !important; }
                        table { font-size: 12px; }
                    }
                </style>
            </head>
            <body>
                <h4>${titulo}</h4>
                ${contenido}
                <div class="text-center no-print mt-4">
                    <button onclick="window.print()" class="btn btn-primary">Imprimir</button>
                    <button onclick="window.close()" class="btn btn-secondary">Cerrar</button>
                </div>
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