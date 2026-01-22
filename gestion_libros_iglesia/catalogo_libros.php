<?php
// catalogo_libros.php
session_start();
require_once 'db.php';

// Configurar variables para layout
$titulo_pagina = '📚 Catálogo de Libros';
$icono_titulo = 'fas fa-book-open';

$mensaje_exito = '';
$mensaje_error = '';

// Obtener categorías para el formulario
$sql_categorias = "SELECT id, nombre FROM categorias ORDER BY nombre";
$result_categorias = mysqli_query($link, $sql_categorias);
$categorias = [];
while ($row = mysqli_fetch_assoc($result_categorias)) {
    $categorias[] = $row;
}

// Procesar agregar libro
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['agregar_libro'])) {
    $codigo_interno = trim($_POST['codigo_interno'] ?? '');
    $titulo = trim($_POST['titulo'] ?? '');
    $autor = trim($_POST['autor'] ?? '');
    $ano_publicacion = $_POST['ano_publicacion'] ?? null;
    $isbn = trim($_POST['isbn'] ?? '');
    $stock = intval($_POST['stock'] ?? 1);
    $categorias_seleccionadas = $_POST['categorias'] ?? [];
    
    // Validaciones básicas
    if (empty($codigo_interno) || empty($titulo) || empty($autor)) {
        $mensaje_error = "Código interno, título y autor son obligatorios.";
    } else {
        // Verificar si el código interno ya existe
        $sql_check = "SELECT id FROM libros WHERE codigo_interno = ?";
        $stmt_check = $db->query($sql_check, [$codigo_interno]);
        if ($stmt_check && mysqli_stmt_get_result($stmt_check)->num_rows > 0) {
            $mensaje_error = "El código interno '$codigo_interno' ya existe en el catálogo.";
        } else {
            // Insertar el libro
            $sql_insert = "INSERT INTO libros (codigo_interno, titulo, autor, año_publicacion, isbn, stock) 
                          VALUES (?, ?, ?, ?, ?, ?)";
            $stmt_insert = $db->query($sql_insert, [
                $codigo_interno, $titulo, $autor, $ano_publicacion, $isbn, $stock
            ]);
            
            if ($stmt_insert) {
                $id_libro = mysqli_insert_id($link);
                $mensaje_exito = "Libro '$titulo' agregado exitosamente al catálogo.";
                
                // Asignar categorías
                if (!empty($categorias_seleccionadas)) {
                    foreach ($categorias_seleccionadas as $id_categoria) {
                        $sql_cat = "INSERT INTO libro_categoria (id_libro, id_categoria) VALUES (?, ?)";
                        $db->query($sql_cat, [$id_libro, $id_categoria]);
                    }
                }
                
                // Limpiar formulario
                $_POST = [];
            } else {
                $mensaje_error = "Error al agregar el libro a la base de datos.";
            }
        }
    }
}

// Obtener todos los libros con sus categorías
$sql_libros = "SELECT l.*, GROUP_CONCAT(c.nombre SEPARATOR ', ') as categorias_nombres
               FROM libros l
               LEFT JOIN libro_categoria lc ON l.id = lc.id_libro
               LEFT JOIN categorias c ON lc.id_categoria = c.id
               GROUP BY l.id
               ORDER BY l.titulo ASC";
$result_libros = mysqli_query($link, $sql_libros);
$libros = [];
while ($row = mysqli_fetch_assoc($result_libros)) {
    $libros[] = $row;
}

// Estadísticas
$total_libros = count($libros);
$total_stock = array_sum(array_column($libros, 'stock'));

ob_start();
?>

<!-- ESTADÍSTICAS RÁPIDAS -->
<div class="row mb-4">
    <div class="col-md-6">
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
    
    <div class="col-md-6">
        <div class="card border-left-success shadow h-100 py-2">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                            Total en Stock</div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $total_stock; ?></div>
                    </div>
                    <div class="col-auto">
                        <i class="fas fa-cubes fa-2x text-gray-300"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- PESTAÑAS CATÁLOGO / AGREGAR -->
<ul class="nav nav-tabs mb-4" id="catalogoTab" role="tablist">
    <li class="nav-item" role="presentation">
        <button class="nav-link active" id="ver-tab" data-bs-toggle="tab" data-bs-target="#ver" type="button">
            <i class="fas fa-list me-1"></i> Ver Catálogo
        </button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link" id="agregar-tab" data-bs-toggle="tab" data-bs-target="#agregar" type="button">
            <i class="fas fa-plus me-1"></i> Agregar Libro
        </button>
    </li>
</ul>

<div class="tab-content" id="catalogoTabContent">
    <!-- PESTAÑA 1: VER CATÁLOGO -->
    <div class="tab-pane fade show active" id="ver" role="tabpanel">
        <?php if (empty($libros)): ?>
            <div class="alert alert-info">
                <i class="fas fa-info-circle me-2"></i>
                El catálogo está vacío. Agrega tu primer libro usando la pestaña "Agregar Libro".
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th width="10%">Código</th>
                            <th width="30%">Título</th>
                            <th width="20%">Autor</th>
                            <th width="10%">Año</th>
                            <th width="10%">Stock</th>
                            <th width="20%">Categorías</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($libros as $libro): 
                            $clase_stock = $libro['stock'] > 0 ? 'success' : ($libro['stock'] == 0 ? 'warning' : 'danger');
                        ?>
                        <tr>
                            <td>
                                <code><?php echo htmlspecialchars($libro['codigo_interno']); ?></code>
                            </td>
                            <td>
                                <strong><?php echo htmlspecialchars($libro['titulo']); ?></strong>
                                <?php if (!empty($libro['isbn'])): ?>
                                    <br><small class="text-muted">ISBN: <?php echo htmlspecialchars($libro['isbn']); ?></small>
                                <?php endif; ?>
                            </td>
                            <td><?php echo htmlspecialchars($libro['autor']); ?></td>
                            <td><?php echo htmlspecialchars($libro['año_publicacion'] ?? '-'); ?></td>
                            <td>
                                <span class="badge bg-<?php echo $clase_stock; ?>">
                                    <?php echo $libro['stock']; ?> disponible(s)
                                </span>
                            </td>
                            <td>
                                <?php if (!empty($libro['categorias_nombres'])): ?>
                                    <small><?php echo htmlspecialchars($libro['categorias_nombres']); ?></small>
                                <?php else: ?>
                                    <span class="text-muted">Sin categorías</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
            <div class="d-flex justify-content-between align-items-center mt-3">
                <div class="text-muted">
                    Mostrando <?php echo $total_libros; ?> libros en el catálogo
                </div>
                <div>
                    <button class="btn btn-outline-primary" onclick="window.print()">
                        <i class="fas fa-print me-1"></i> Imprimir Catálogo
                    </button>
                </div>
            </div>
        <?php endif; ?>
    </div>
    
    <!-- PESTAÑA 2: AGREGAR LIBRO -->
    <div class="tab-pane fade" id="agregar" role="tabpanel">
        <div class="card">
            <div class="card-header gradient-book">
                <h5 class="mb-0"><i class="fas fa-plus-circle me-2"></i>Agregar Nuevo Libro al Catálogo</h5>
            </div>
            <div class="card-body">
                <form method="POST" action="">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group mb-3">
                                <label for="codigo_interno" class="form-label">
                                    Código Interno <span class="text-danger">*</span>
                                </label>
                                <input type="text" class="form-control" id="codigo_interno" 
                                       name="codigo_interno" 
                                       value="<?php echo htmlspecialchars($_POST['codigo_interno'] ?? ''); ?>" 
                                       placeholder="Ej: BIB-001, LIB-2023-01" required>
                                <div class="form-text">Código único para identificar el libro en la biblioteca.</div>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="form-group mb-3">
                                <label for="isbn" class="form-label">ISBN</label>
                                <input type="text" class="form-control" id="isbn" 
                                       name="isbn" 
                                       value="<?php echo htmlspecialchars($_POST['isbn'] ?? ''); ?>" 
                                       placeholder="Ej: 978-1-59856-200-1">
                                <div class="form-text">Código ISBN (opcional).</div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-8">
                            <div class="form-group mb-3">
                                <label for="titulo" class="form-label">
                                    Título <span class="text-danger">*</span>
                                </label>
                                <input type="text" class="form-control" id="titulo" 
                                       name="titulo" 
                                       value="<?php echo htmlspecialchars($_POST['titulo'] ?? ''); ?>" 
                                       placeholder="Título completo del libro" required>
                            </div>
                        </div>
                        
                        <div class="col-md-4">
                            <div class="form-group mb-3">
                                <label for="ano_publicacion" class="form-label">Año de Publicación</label>
                                <input type="number" class="form-control" id="ano_publicacion" 
                                       name="ano_publicacion" 
                                       value="<?php echo htmlspecialchars($_POST['ano_publicacion'] ?? ''); ?>" 
                                       placeholder="Ej: 2023" min="1000" max="<?php echo date('Y'); ?>">
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-8">
                            <div class="form-group mb-3">
                                <label for="autor" class="form-label">
                                    Autor <span class="text-danger">*</span>
                                </label>
                                <input type="text" class="form-control" id="autor" 
                                       name="autor" 
                                       value="<?php echo htmlspecialchars($_POST['autor'] ?? ''); ?>" 
                                       placeholder="Nombre completo del autor" required>
                            </div>
                        </div>
                        
                        <div class="col-md-4">
                            <div class="form-group mb-3">
                                <label for="stock" class="form-label">Stock Inicial</label>
                                <input type="number" class="form-control" id="stock" 
                                       name="stock" 
                                       value="<?php echo htmlspecialchars($_POST['stock'] ?? '1'); ?>" 
                                       min="1" max="100" required>
                                <div class="form-text">Cantidad de copias disponibles.</div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-group mb-4">
                        <label class="form-label">Categorías</label>
                        <div class="row">
                            <?php foreach ($categorias as $categoria): ?>
                            <div class="col-md-4 mb-2">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" 
                                           name="categorias[]" 
                                           value="<?php echo $categoria['id']; ?>" 
                                           id="cat_<?php echo $categoria['id']; ?>"
                                           <?php echo (isset($_POST['categorias']) && in_array($categoria['id'], $_POST['categorias'])) ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="cat_<?php echo $categoria['id']; ?>">
                                        <?php echo htmlspecialchars($categoria['nombre']); ?>
                                    </label>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <div class="form-text">Seleccione las categorías que correspondan al libro.</div>
                    </div>
                    
                    <div class="d-grid gap-2">
                        <button type="submit" name="agregar_libro" class="btn btn-success btn-lg">
                            <i class="fas fa-save me-2"></i>Agregar Libro al Catálogo
                        </button>
                        
                        <button type="button" class="btn btn-outline-secondary" onclick="limpiarFormulario()">
                            <i class="fas fa-eraser me-2"></i>Limpiar Formulario
                        </button>
                    </div>
                </form>
            </div>
        </div>
        
        <!-- EJEMPLOS DE CÓDIGOS -->
        <div class="card mt-4">
            <div class="card-header">
                <h6 class="mb-0"><i class="fas fa-lightbulb me-2"></i>Sugerencias de Códigos</h6>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <h6>Formatos sugeridos:</h6>
                        <ul class="mb-0">
                            <li><code>LIB-001</code> - Libro #1</li>
                            <li><code>BIB-2023-015</code> - Biblioteca 2023, libro 15</li>
                            <li><code>TEOL-001</code> - Teológico #1</li>
                            <li><code>DEV-001</code> - Devocional #1</li>
                        </ul>
                    </div>
                    <div class="col-md-6">
                        <h6>Categorías disponibles:</h6>
                        <div class="d-flex flex-wrap gap-2">
                            <?php foreach ($categorias as $categoria): ?>
                                <span class="badge bg-info"><?php echo htmlspecialchars($categoria['nombre']); ?></span>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- MODAL PARA VER DETALLES COMPLETOS (OPCIONAL) -->
<div class="modal fade" id="modalDetallesLibro" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header gradient-book text-white">
                <h5 class="modal-title">
                    <i class="fas fa-book me-2"></i>
                    Detalles del Libro
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="modalDetallesLibroBody">
                <!-- Se cargará con AJAX si decides implementarlo -->
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    // Auto-generar código basado en título (opcional)
    $('#titulo').on('blur', function() {
        if ($('#codigo_interno').val() === '') {
            const titulo = $(this).val().trim();
            if (titulo.length > 0) {
                // Crear un código simple basado en las primeras letras
                const palabras = titulo.split(' ');
                let codigo = '';
                if (palabras.length >= 2) {
                    codigo = palabras[0].substring(0, 3).toUpperCase() + '-' + 
                            palabras[1].substring(0, 3).toUpperCase();
                } else {
                    codigo = titulo.substring(0, 6).toUpperCase().replace(/\s/g, '');
                }
                $('#codigo_interno').val(codigo + '-001');
            }
        }
    });
    
    // Formatear ISBN automáticamente
    $('#isbn').on('blur', function() {
        let isbn = $(this).val().trim();
        isbn = isbn.replace(/[^0-9X]/gi, '');
        if (isbn.length === 10 || isbn.length === 13) {
            // Formatear con guiones
            if (isbn.length === 10) {
                isbn = isbn.substring(0, 1) + '-' + 
                       isbn.substring(1, 4) + '-' + 
                       isbn.substring(4, 9) + '-' + 
                       isbn.substring(9);
            } else if (isbn.length === 13) {
                isbn = isbn.substring(0, 3) + '-' + 
                       isbn.substring(3, 4) + '-' + 
                       isbn.substring(4, 9) + '-' + 
                       isbn.substring(9, 12) + '-' + 
                       isbn.substring(12);
            }
            $(this).val(isbn);
        }
    });
    
    // Validar año de publicación
    $('#ano_publicacion').on('blur', function() {
        const year = parseInt($(this).val());
        const currentYear = new Date().getFullYear();
        if (year && (year < 1000 || year > currentYear)) {
            alert(`El año debe estar entre 1000 y ${currentYear}`);
            $(this).val('');
        }
    });
    
    // Limpiar formulario
    window.limpiarFormulario = function() {
        if (confirm('¿Está seguro de limpiar todo el formulario?')) {
            $('#agregar form')[0].reset();
        }
    };
    
    // Si hay mensaje de éxito, mostrar pestaña de catálogo
    <?php if (!empty($mensaje_exito)): ?>
    setTimeout(function() {
        $('#ver-tab').tab('show');
    }, 100);
    <?php endif; ?>
    
    // Si hay mensaje de error, mantener en pestaña de agregar
    <?php if (!empty($mensaje_error)): ?>
    $('#agregar-tab').tab('show');
    <?php endif; ?>
    
    // Auto-enfoque en primer campo según pestaña
    $('button[data-bs-toggle="tab"]').on('shown.bs.tab', function(e) {
        const target = $(e.target).attr('data-bs-target');
        if (target === '#agregar') {
            setTimeout(function() {
                $('#codigo_interno').focus();
            }, 300);
        }
    });
});
</script>

<?php
$contenido = ob_get_clean();
include 'layout.php';
?>