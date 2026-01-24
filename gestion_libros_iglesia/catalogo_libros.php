<?php
// catalogo_libros.php
session_start();
require_once 'db.php';

// Configurar variables para layout
$titulo_pagina = '📚 Catálogo de Libros';
$icono_titulo = 'fas fa-book-open';

$mensaje_exito = '';
$mensaje_error = '';

// Procesar eliminación de libro
if (isset($_GET['eliminar']) && is_numeric($_GET['eliminar'])) {
    $id_eliminar = intval($_GET['eliminar']);
    
    // Verificar si el libro tiene préstamos activos
    $sql_check_prestamos = "SELECT COUNT(*) as total FROM prestamos WHERE id_libro = ? AND devuelto = 0";
    $stmt_check = $db->query($sql_check_prestamos, [$id_eliminar]);
    $result_check = mysqli_stmt_get_result($stmt_check);
    $row_check = mysqli_fetch_assoc($result_check);
    
    if ($row_check['total'] > 0) {
        $mensaje_error = "No se puede eliminar el libro porque tiene préstamos activos.";
    } else {
        // Eliminación lógica (actualizar activo = 0)
        $sql_eliminar = "UPDATE libros SET activo = 0 WHERE id = ?";
        $stmt_eliminar = $db->query($sql_eliminar, [$id_eliminar]);
        
        if ($stmt_eliminar && mysqli_stmt_affected_rows($stmt_eliminar) > 0) {
            $mensaje_exito = "Libro eliminado correctamente (archivado).";
        } else {
            $mensaje_error = "Error al eliminar el libro.";
        }
    }
}

// Obtener categorías para filtros
$sql_categorias = "SELECT id, nombre FROM categorias ORDER BY nombre";
$result_categorias = mysqli_query($link, $sql_categorias);
$categorias = [];
while ($row = mysqli_fetch_assoc($result_categorias)) {
    $categorias[] = $row;
}

// Parámetros de búsqueda y filtros
$busqueda = trim($_GET['busqueda'] ?? '');
$categoria_filtro = $_GET['categoria'] ?? '';
$stock_filtro = $_GET['stock'] ?? '';
$pagina = max(1, intval($_GET['pagina'] ?? 1));
$por_pagina = 20; // Libros por página

// Construir consulta base con filtros
$condiciones = ["l.activo = 1"];
$parametros = [];
$tipos = "";

if (!empty($busqueda)) {
    $condiciones[] = "(l.titulo LIKE ? OR l.autor LIKE ? OR l.codigo_interno LIKE ? OR l.isbn LIKE ?)";
    $parametros = array_merge($parametros, 
        ["%$busqueda%", "%$busqueda%", "%$busqueda%", "%$busqueda%"]);
    $tipos .= "ssss";
}

if (!empty($categoria_filtro) && is_numeric($categoria_filtro)) {
    $condiciones[] = "lc.id_categoria = ?";
    $parametros[] = $categoria_filtro;
    $tipos .= "i";
}

if ($stock_filtro === 'disponible') {
    $condiciones[] = "l.stock > 0";
} elseif ($stock_filtro === 'agotado') {
    $condiciones[] = "l.stock = 0";
}

$where_clause = !empty($condiciones) ? "WHERE " . implode(" AND ", $condiciones) : "";

// Contar total de libros para paginación
$sql_count = "SELECT COUNT(DISTINCT l.id) as total 
              FROM libros l
              LEFT JOIN libro_categoria lc ON l.id = lc.id_libro
              $where_clause";
              
if (!empty($parametros)) {
    $stmt_count = $db->query($sql_count, $parametros);
    $result_count = mysqli_stmt_get_result($stmt_count);
} else {
    $result_count = mysqli_query($link, $sql_count);
}
$row_count = mysqli_fetch_assoc($result_count);
$total_libros = $row_count['total'];
$total_paginas = ceil($total_libros / $por_pagina);
$offset = ($pagina - 1) * $por_pagina;

// Obtener libros con filtros y paginación
$sql_libros = "SELECT l.*, GROUP_CONCAT(c.nombre SEPARATOR ', ') as categorias_nombres,
               GROUP_CONCAT(c.id SEPARATOR ',') as categorias_ids
               FROM libros l
               LEFT JOIN libro_categoria lc ON l.id = lc.id_libro
               LEFT JOIN categorias c ON lc.id_categoria = c.id
               $where_clause
               GROUP BY l.id
               ORDER BY l.titulo ASC
               LIMIT ? OFFSET ?";
               
$parametros_paginados = array_merge($parametros, [$por_pagina, $offset]);
$tipos_paginados = $tipos . "ii";

$stmt_libros = $db->query($sql_libros, $parametros_paginados);
$result_libros = mysqli_stmt_get_result($stmt_libros);
$libros = [];
while ($row = mysqli_fetch_assoc($result_libros)) {
    $libros[] = $row;
}

// Estadísticas
$sql_stats = "SELECT 
               SUM(stock) as total_stock,
               COUNT(CASE WHEN stock > 0 THEN 1 END) as libros_disponibles,
               COUNT(CASE WHEN stock = 0 THEN 1 END) as libros_agotados
               FROM libros WHERE activo = 1";
$result_stats = mysqli_query($link, $sql_stats);
$stats = mysqli_fetch_assoc($result_stats);

ob_start();
?>

<!-- ESTADÍSTICAS RÁPIDAS -->
<div class="row mb-4">
    <div class="col-md-4">
        <div class="card border-left-primary shadow h-100 py-2">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                            Total de Libros</div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $total_libros; ?></div>
                    </div>
                    <div class="col-auto">
                        <i class="fas fa-book fa-2x text-gray-300"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-4">
        <div class="card border-left-success shadow h-100 py-2">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                            Disponibles</div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $stats['libros_disponibles']; ?></div>
                    </div>
                    <div class="col-auto">
                        <i class="fas fa-check-circle fa-2x text-gray-300"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-4">
        <div class="card border-left-warning shadow h-100 py-2">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                            Agotados</div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $stats['libros_agotados']; ?></div>
                    </div>
                    <div class="col-auto">
                        <i class="fas fa-exclamation-circle fa-2x text-gray-300"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- BARRA DE BÚSQUEDA Y FILTROS -->
<div class="card mb-4">
    <div class="card-header bg-light">
        <div class="row">
            <div class="col-md-8">
                <form method="GET" action="" class="row g-2">
                    <div class="col-md-5">
                        <div class="input-group">
                            <input type="text" class="form-control" name="busqueda" 
                                   value="<?php echo htmlspecialchars($busqueda); ?>" 
                                   placeholder="Buscar por título, autor, código o ISBN">
                            <button class="btn btn-outline-primary" type="submit">
                                <i class="fas fa-search"></i>
                            </button>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <select name="categoria" class="form-select">
                            <option value="">Todas las categorías</option>
                            <?php foreach ($categorias as $cat): ?>
                                <option value="<?php echo $cat['id']; ?>" 
                                    <?php echo ($categoria_filtro == $cat['id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($cat['nombre']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <select name="stock" class="form-select">
                            <option value="">Todo el stock</option>
                            <option value="disponible" <?php echo ($stock_filtro == 'disponible') ? 'selected' : ''; ?>>Disponible</option>
                            <option value="agotado" <?php echo ($stock_filtro == 'agotado') ? 'selected' : ''; ?>>Agotado</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="fas fa-filter me-1"></i> Filtrar
                        </button>
                    </div>
                </form>
            </div>
            <div class="col-md-4 text-end">
                <a href="agregar_libro.php" class="btn btn-success me-2">
                    <i class="fas fa-plus me-1"></i> Nuevo Libro
                </a>
                <a href="exportar_libros.php?<?php echo http_build_query($_GET); ?>" 
                   class="btn btn-outline-secondary">
                    <i class="fas fa-download me-1"></i> Exportar
                </a>
            </div>
        </div>
    </div>
</div>

<!-- TABLA DE LIBROS -->
<div class="card">
    <div class="card-header gradient-book text-white d-flex justify-content-between align-items-center">
        <h5 class="mb-0">
            <i class="fas fa-list me-2"></i>
            Catálogo de Libros
            <?php if (!empty($busqueda)): ?>
                <small class="ms-2">(Resultados para: "<?php echo htmlspecialchars($busqueda); ?>")</small>
            <?php endif; ?>
        </h5>
        <div class="text-light">
            Mostrando <?php echo count($libros); ?> de <?php echo $total_libros; ?> libros
        </div>
    </div>
    
    <div class="card-body">
        <?php if (empty($libros)): ?>
            <div class="alert alert-info text-center py-4">
                <i class="fas fa-info-circle fa-2x mb-3"></i>
                <h5>No se encontraron libros</h5>
                <p class="mb-0">
                    <?php if (!empty($busqueda) || !empty($categoria_filtro) || !empty($stock_filtro)): ?>
                        Intenta con otros criterios de búsqueda o 
                        <a href="catalogo_libros.php" class="alert-link">ver todos los libros</a>.
                    <?php else: ?>
                        El catálogo está vacío. 
                        <a href="agregar_libro.php" class="alert-link">Agrega tu primer libro</a>.
                    <?php endif; ?>
                </p>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th width="10%">Código</th>
                            <th width="25%">Título</th>
                            <th width="15%">Autor</th>
                            <th width="10%">Año</th>
                            <th width="10%">Stock</th>
                            <th width="20%">Categorías</th>
                            <th width="10%">Acciones</th>
                                                    <td>
    <div class="btn-group btn-group-sm" role="group">
        <a href="editar_libro.php?id=<?php echo $libro['id']; ?>" 
           class="btn btn-outline-primary" title="Editar">
            <i class="fas fa-edit"></i>
        </a>
        <button type="button" 
                class="btn btn-outline-info" 
                title="Generar Etiqueta"
                onclick="generarEtiquetaIndividual('<?php echo htmlspecialchars(addslashes($libro['codigo_interno'])); ?>', 
                                                   '<?php echo htmlspecialchars(addslashes($libro['titulo'])); ?>',
                                                   '<?php echo htmlspecialchars(addslashes($libro['autor'])); ?>')">
            <i class="fas fa-barcode"></i>
        </button>
        <button type="button" 
                class="btn btn-outline-danger" 
                title="Eliminar"
                onclick="confirmarEliminacion(<?php echo $libro['id']; ?>, '<?php echo htmlspecialchars(addslashes($libro['titulo'])); ?>')">
            <i class="fas fa-trash"></i>
        </button>
    </div>
</td>
                            
                        </tr>

                    </thead>
                    <tbody>
                        <?php foreach ($libros as $libro): 
                            $clase_stock = $libro['stock'] > 0 ? 'success' : 'warning';
                        ?>
                        <tr>
                            <td>
                                <div class="d-flex align-items-center">
                                    <code class="me-2"><?php echo htmlspecialchars($libro['codigo_interno']); ?></code>
                                    <?php if (!empty($libro['isbn'])): ?>
                                        <i class="fas fa-barcode text-muted" title="ISBN: <?php echo htmlspecialchars($libro['isbn']); ?>"></i>
                                    <?php endif; ?>
                                </div>
                            </td>
                            <td>
                                <strong class="d-block"><?php echo htmlspecialchars($libro['titulo']); ?></strong>
                                <?php if (!empty($libro['isbn'])): ?>
                                    <small class="text-muted d-block">ISBN: <?php echo htmlspecialchars($libro['isbn']); ?></small>
                                <?php endif; ?>
                            </td>
                            <td><?php echo htmlspecialchars($libro['autor']); ?></td>
                            <td><?php echo htmlspecialchars($libro['año_publicacion'] ?? '-'); ?></td>
                            <td>
                                <span class="badge bg-<?php echo $clase_stock; ?>">
                                    <?php echo $libro['stock']; ?> copia(s)
                                </span>
                            </td>
                            <td>
                                <?php if (!empty($libro['categorias_nombres'])): ?>
                                    <small><?php echo htmlspecialchars($libro['categorias_nombres']); ?></small>
                                <?php else: ?>
                                    <span class="text-muted">Sin categorías</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="btn-group btn-group-sm" role="group">
                                    <a href="editar_libro.php?id=<?php echo $libro['id']; ?>" 
                                       class="btn btn-outline-primary" title="Editar">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <button type="button" 
                                            class="btn btn-outline-danger" 
                                            title="Eliminar"
                                            onclick="confirmarEliminacion(<?php echo $libro['id']; ?>, '<?php echo htmlspecialchars(addslashes($libro['titulo'])); ?>')">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
            <!-- PAGINACIÓN -->
            <?php if ($total_paginas > 1): ?>
            <nav aria-label="Paginación">
                <ul class="pagination justify-content-center">
                    <li class="page-item <?php echo ($pagina <= 1) ? 'disabled' : ''; ?>">
                        <a class="page-link" 
                           href="?<?php echo http_build_query(array_merge($_GET, ['pagina' => $pagina - 1])); ?>">
                            <i class="fas fa-chevron-left"></i>
                        </a>
                    </li>
                    
                    <?php for ($i = 1; $i <= $total_paginas; $i++): ?>
                        <?php if ($i == 1 || $i == $total_paginas || abs($i - $pagina) <= 2): ?>
                            <li class="page-item <?php echo ($i == $pagina) ? 'active' : ''; ?>">
                                <a class="page-link" 
                                   href="?<?php echo http_build_query(array_merge($_GET, ['pagina' => $i])); ?>">
                                    <?php echo $i; ?>
                                </a>
                            </li>
                        <?php elseif (abs($i - $pagina) == 3): ?>
                            <li class="page-item disabled">
                                <span class="page-link">...</span>
                            </li>
                        <?php endif; ?>
                    <?php endfor; ?>
                    
                    <li class="page-item <?php echo ($pagina >= $total_paginas) ? 'disabled' : ''; ?>">
                        <a class="page-link" 
                           href="?<?php echo http_build_query(array_merge($_GET, ['pagina' => $pagina + 1])); ?>">
                            <i class="fas fa-chevron-right"></i>
                        </a>
                    </li>
                </ul>
            </nav>
            <?php endif; ?>
            
            <div class="d-flex justify-content-between align-items-center mt-3">
                <div class="text-muted">
                    Página <?php echo $pagina; ?> de <?php echo $total_paginas; ?> 
                    • Total en stock: <?php echo $stats['total_stock']; ?> copias
                </div>
                <div>
                    <a href="javascript:window.print()" class="btn btn-outline-primary me-2">
                        <i class="fas fa-print me-1"></i> Imprimir
                    </a>
                    <a href="agregar_libro.php" class="btn btn-success">
                        <i class="fas fa-plus me-1"></i> Agregar Libro
                    </a>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- MODAL DE CONFIRMACIÓN DE ELIMINACIÓN -->
<div class="modal fade" id="modalConfirmarEliminacion" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-warning text-white">
                <h5 class="modal-title">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    Confirmar Eliminación
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>¿Está seguro de eliminar el libro <strong id="tituloLibroEliminar"></strong>?</p>
                <div class="alert alert-warning">
                    <i class="fas fa-info-circle me-2"></i>
                    Esta acción no se puede deshacer. El libro será archivado y no aparecerá en el catálogo.
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <a href="#" id="btnConfirmarEliminar" class="btn btn-danger">
                    <i class="fas fa-trash me-1"></i> Sí, Eliminar
                </a>
            </div>
        </div>
    </div>
</div>

<script>
function confirmarEliminacion(id, titulo) {
    document.getElementById('tituloLibroEliminar').textContent = titulo;
    document.getElementById('btnConfirmarEliminar').href = '?eliminar=' + id + '&' + new URLSearchParams(window.location.search).toString();
    
    const modal = new bootstrap.Modal(document.getElementById('modalConfirmarEliminacion'));
    modal.show();
}

// Detección de código de barras para búsqueda rápida
document.addEventListener('DOMContentLoaded', function() {
    const inputBusqueda = document.querySelector('input[name="busqueda"]');
    let timerEscaneo = null;
    let codigoAcumulado = '';
    
    if (inputBusqueda) {
        inputBusqueda.addEventListener('keydown', function(e) {
            // Si presiona Enter, buscar normalmente
            if (e.key === 'Enter') {
                return; // Dejar que el formulario se envíe
            }
            
            // Si es un carácter de código de barras (no espacio)
            if (e.key.length === 1 && e.key !== ' ') {
                codigoAcumulado += e.key;
                
                clearTimeout(timerEscaneo);
                timerEscaneo = setTimeout(function() {
                    // Si el código acumulado parece un código de barras
                    if (codigoAcumulado.length >= 8 && /^[0-9X]+$/i.test(codigoAcumulado)) {
                        // Buscar automáticamente
                        window.location.href = '?busqueda=' + encodeURIComponent(codigoAcumulado);
                    }
                    codigoAcumulado = '';
                }, 100);
            }
        });
        
        // También detectar pegado
        inputBusqueda.addEventListener('input', function(e) {
            const valor = this.value.trim();
            if ((valor.length === 13 || valor.length === 10 || valor.length === 8) && 
                /^[0-9X]+$/i.test(valor)) {
                setTimeout(() => {
                    window.location.href = '?busqueda=' + encodeURIComponent(valor);
                }, 300);
            }
        });
        
        // Poner foco en búsqueda al cargar
        inputBusqueda.focus();
        inputBusqueda.select();
    }
});
</script>

<?php
$contenido = ob_get_clean();
include 'layout.php';
?>