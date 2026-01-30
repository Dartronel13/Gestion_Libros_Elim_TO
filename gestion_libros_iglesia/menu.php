<?php
// menu.php - Página de Inicio/Dashboard usando layout.php
require_once 'db.php';
verificarAutenticacion();

// Configurar variables para el layout
$titulo_pagina = 'Dashboard - Sistema de Biblioteca';
$icono_titulo = 'fas fa-chart-line';

// Obtener estadísticas
$query_estadisticas = "
    SELECT 
        (SELECT COUNT(*) FROM libros) - 
        (SELECT COUNT(*) FROM prestamos WHERE devuelto = 0) as libros_disponibles,
        
        (SELECT COUNT(*) FROM prestamos WHERE devuelto = 0) as prestamos_activos,
        
        (SELECT COUNT(*) FROM prestamos 
         WHERE devuelto = 0 
         AND fecha_devolucion BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 3 DAY)) as prestamos_por_vencer,
        
        (SELECT COUNT(*) FROM prestamos 
         WHERE devuelto = 0 
         AND fecha_devolucion < CURDATE()) as prestamos_vencidos
";

$result = mysqli_query($link, $query_estadisticas);
$estadisticas = mysqli_fetch_assoc($result);

// Obtener préstamos activos recientes
$query_prestamos = "
    SELECT p.*, l.titulo, lec.nombre, lec.apellido,
           DATEDIFF(p.fecha_devolucion, CURDATE()) as dias_restantes
    FROM prestamos p
    LEFT JOIN libros l ON p.id_libro = l.id
    LEFT JOIN lectores lec ON p.id_lector = lec.id
    WHERE p.devuelto = 0
    ORDER BY p.fecha_prestamo DESC
    LIMIT 3
";

$result_prestamos = mysqli_query($link, $query_prestamos);
$num_prestamos = mysqli_num_rows($result_prestamos);

// CSS específico para el menú
$pageStyles = '
<link rel="stylesheet" href="css/menu-style.css">';

// JavaScript específico para el menú
$pageScripts = '
<script>
// Función para búsqueda rápida
function realizarBusqueda(termino) {
    if (termino.length < 2) {
        document.getElementById(\'resultados-busqueda\').style.display = \'none\';
        return;
    }
    
    const resultadosDiv = document.getElementById(\'resultados-busqueda\');
    
    resultadosDiv.innerHTML = `
        <div class="alert alert-info">
            <i class="fas fa-search me-2"></i>
            Buscaría: "${termino}" en la base de datos
            <div class="mt-2">
                <a href="catalogo_libros.php?q=${encodeURIComponent(termino)}" class="btn btn-sm btn-primary me-2">
                    <i class="fas fa-book me-1"></i>Buscar en catálogo
                </a>
                <a href="historial_prestamo.php?q=${encodeURIComponent(termino)}" class="btn btn-sm btn-secondary">
                    <i class="fas fa-history me-1"></i>Buscar en historial
                </a>
            </div>
        </div>
    `;
    resultadosDiv.style.display = \'block\';
}

// Configurar búsqueda
let timeoutBusqueda;
const inputBusqueda = document.getElementById(\'busqueda-rapida\');

if(inputBusqueda) {
    inputBusqueda.addEventListener(\'input\', function() {
        clearTimeout(timeoutBusqueda);
        timeoutBusqueda = setTimeout(() => {
            realizarBusqueda(this.value);
        }, 300);
    });
}

// Cerrar resultados al hacer clic fuera
document.addEventListener(\'click\', function(e) {
    const resultadosDiv = document.getElementById(\'resultados-busqueda\');
    if (inputBusqueda && resultadosDiv) {
        if (!inputBusqueda.contains(e.target) && 
            !resultadosDiv.contains(e.target)) {
            resultadosDiv.style.display = \'none\';
        }
    }
});
</script>';

// Preparar el contenido dinámico
ob_start(); // Iniciar buffer de salida
?>
<!-- ESTADÍSTICAS -->
<div class="row mb-5">
    <!-- LIBROS DISPONIBLES -->
    <div class="col-md-3 mb-4">
        <div class="stats-card" style="background: linear-gradient(135deg, var(--book-color), #1abc9c);">
            <div class="stats-icon">
                <i class="fas fa-book"></i>
            </div>
            <div class="stats-number"><?= $estadisticas['libros_disponibles'] ?? 0 ?></div>
            <div class="stats-label">Libros Disponibles</div>
        </div>
    </div>
    
    <!-- PRÉSTAMOS ACTIVOS -->
    <div class="col-md-3 mb-4">
        <div class="stats-card" style="background: linear-gradient(135deg, var(--secondary-color), #2980b9);">
            <div class="stats-icon">
                <i class="fas fa-exchange-alt"></i>
            </div>
            <div class="stats-number"><?= $estadisticas['prestamos_activos'] ?? 0 ?></div>
            <div class="stats-label">Préstamos Activos</div>
        </div>
    </div>
    
    <!-- PRÉSTAMOS POR VENCER -->
    <div class="col-md-3 mb-4">
        <div class="stats-card" style="background: linear-gradient(135deg, var(--warning-color), #d68910);">
            <div class="stats-icon">
                <i class="fas fa-clock"></i>
            </div>
            <div class="stats-number"><?= $estadisticas['prestamos_por_vencer'] ?? 0 ?></div>
            <div class="stats-label">Prestamos por Vencer (≤3 días)</div>
        </div>
    </div>
    
    <!-- PRÉSTAMOS VENCIDOS -->
    <div class="col-md-3 mb-4">
        <div class="stats-card" style="background: linear-gradient(135deg, var(--danger-color), #c0392b);">
            <div class="stats-icon">
                <i class="fas fa-exclamation-triangle"></i>
            </div>
            <div class="stats-number"><?= $estadisticas['prestamos_vencidos'] ?? 0 ?></div>
            <div class="stats-label">Préstamos Vencidos</div>
        </div>
    </div>
</div>

<!-- LAS 5 OPCIONES PRINCIPALES -->
<div class="row mb-5" id="opciones">
    <div class="col-12 mb-4">
        <h2 class="mb-4 border-bottom pb-3">
            <i class="fas fa-th-large text-primary me-2"></i>Opciones del Sistema
        </h2>
    </div>
    
    <!-- AGREGAR PRÉSTAMO -->
    <div class="col-md-4 mb-4">
        <div class="option-card">
            <div class="card-header" style="background: linear-gradient(135deg, #3498db, #2980b9);">
                <h5 class="mb-0">
                    <i class="fas fa-plus-circle me-2"></i>Agregar Préstamo
                </h5>
            </div>
            <div class="card-body">
                <p class="card-text">Registrar un nuevo préstamo de libro a una persona.</p>
                <a href="agregar_prestamo.php" class="btn btn-primary btn-option">
                    <i class="fas fa-plus me-2"></i>Ir a Agregar Préstamo
                </a>
            </div>
        </div>
    </div>
    
    <!-- GESTIONAR PRÉSTAMOS -->
    <div class="col-md-4 mb-4">
        <div class="option-card">
            <div class="card-header" style="background: linear-gradient(135deg, #27ae60, #229954);">
                <h5 class="mb-0">
                    <i class="fas fa-tasks me-2"></i>Gestionar Préstamos
                </h5>
            </div>
            <div class="card-body">
                <p class="card-text">Administrar todos los préstamos activos actualmente.</p>
                <a href="gestion_prestamo.php" class="btn btn-success btn-option">
                    <i class="fas fa-cog me-2"></i>Ir a Gestionar
                </a>
            </div>
        </div>
    </div>
    
    <!-- DEVOLUCIÓN -->
    <div class="col-md-4 mb-4">
        <div class="option-card">
            <div class="card-header" style="background: linear-gradient(135deg, #f39c12, #d68910);">
                <h5 class="mb-0">
                    <i class="fas fa-exchange-alt me-2"></i>Devolución
                </h5>
            </div>
            <div class="card-body">
                <p class="card-text">Registrar la devolución de un libro prestado.</p>
                <a href="devolucion_libro.php" class="btn btn-warning btn-option">
                    <i class="fas fa-check me-2"></i>Ir a Devolución
                </a>
            </div>
        </div>
    </div>
    
    <!-- HISTORIAL -->
    <div class="col-md-4 mb-4">
        <div class="option-card">
            <div class="card-header" style="background: linear-gradient(135deg, #8e44ad, #9b59b6);">
                <h5 class="mb-0">
                    <i class="fas fa-history me-2"></i>Historial
                </h5>
            </div>
            <div class="card-body">
                <p class="card-text">Consultar historial completo de préstamos.</p>
                <a href="historial_prestamo.php" class="btn btn-option text-white" style="background: #8e44ad;">
                    <i class="fas fa-search me-2"></i>Ir a Historial
                </a>
            </div>
        </div>
    </div>
    
    <!-- CATÁLOGO -->
    <div class="col-md-4 mb-4">
        <div class="option-card">
            <div class="card-header" style="background: linear-gradient(135deg, #16a085, #1abc9c);">
                <h5 class="mb-0">
                    <i class="fas fa-book-open me-2"></i>Catálogo
                </h5>
            </div>
            <div class="card-body">
                <p class="card-text">Explorar catálogo de libros disponibles.</p>
                <a href="catalogo_libros.php" class="btn btn-info btn-option text-white">
                    <i class="fas fa-book me-2"></i>Ir a Catálogo
                </a>
            </div>
        </div>
    </div>
</div>

<!-- PRÉSTAMOS ACTIVOS RECIENTES -->
<div class="row mb-4">
    <div class="col-12">
        <div class="card option-card">
            <div class="card-header d-flex justify-content-between align-items-center" 
                 style="background: linear-gradient(135deg, #2c3e50, #4a6491);">
                <h5 class="mb-0 text-white">
                    <i class="fas fa-clock me-2"></i>Préstamos Activos Recientes
                </h5>
                <span class="badge bg-light text-dark">
                    <?= $estadisticas['prestamos_activos'] ?? 0 ?> activos
                </span>
            </div>
            <div class="card-body">
                <?php if ($num_prestamos > 0): ?>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Libro</th>
                                <th>Persona</th>
                                <th>Préstamo</th>
                                <th>Devolución</th>
                                <th>Estado</th>
                                <th>Acción</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while($prestamo = mysqli_fetch_assoc($result_prestamos)): 
                                $dias_restantes = $prestamo['dias_restantes'];
                                
                                if ($dias_restantes < 0) {
                                    $estado_clase = 'badge bg-danger';
                                    $estado_texto = 'VENCIDO';
                                } elseif ($dias_restantes <= 3) {
                                    $estado_clase = 'badge bg-warning text-dark';
                                    $estado_texto = 'POR VENCER';
                                } else {
                                    $estado_clase = 'badge bg-success';
                                    $estado_texto = 'ACTIVO';
                                }
                            ?>
                            <tr>
                                <td><strong><?= htmlspecialchars($prestamo['titulo'] ?? 'Sin título') ?></strong></td>
                                <td><?= htmlspecialchars(($prestamo['nombre'] ?? '') . ' ' . ($prestamo['apellido'] ?? '')) ?></td>
                                <td><?= date('d/m/Y', strtotime($prestamo['fecha_prestamo'])) ?></td>
                                <td><?= date('d/m/Y', strtotime($prestamo['fecha_devolucion'])) ?></td>
                                <td>
                                    <span class="<?= $estado_clase ?>">
                                        <?= $estado_texto ?>
                                        <?php if ($dias_restantes >= 0): ?>
                                            <small>(<?= abs($dias_restantes) ?> días)</small>
                                        <?php endif; ?>
                                    </span>
                                </td>
                                <td>
                                    <a href="devolucion_libro.php?id=<?= $prestamo['id'] ?>" 
                                       class="btn btn-sm btn-success">
                                        <i class="fas fa-check me-1"></i>Devolver
                                    </a>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
                <?php else: ?>
                <div class="text-center py-4">
                    <div class="text-muted">
                        <i class="fas fa-book fa-3x mb-3"></i>
                        <p class="mb-0">No hay préstamos activos en este momento</p>
                    </div>
                </div>
                <?php endif; ?>
            </div>
            <div class="card-footer text-center">
                <a href="gestion_prestamo.php" class="btn btn-primary">
                    <i class="fas fa-list me-2"></i>Ver Todos los Préstamos Activos
                </a>
            </div>
        </div>
    </div>
</div>

<?php
$contenido = ob_get_clean(); // Obtener el contenido del buffer

// Ahora necesitamos modificar el layout.php para aceptar estilos y scripts de página
// Agrega estas líneas en el layout.php después de cargar el CSS principal:

// En el layout.php, después de <link rel="stylesheet" href="css/style.css">
// Debería haber algo como:
// <?php if (!empty($pageStyles)) echo $pageStyles; 
// <?php if (!empty($pageScripts)) echo $pageScripts;

// Como necesitas modificar el layout, te muestro una alternativa temporal:
// Incluye directamente con variables

$GLOBALS['pageStyles'] = $pageStyles;
$GLOBALS['pageScripts'] = $pageScripts;
// Incluir el layout
include 'layout.php';
?>