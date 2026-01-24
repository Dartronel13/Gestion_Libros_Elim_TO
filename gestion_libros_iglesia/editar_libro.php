<?php
// editar_libro.php
session_start();
require_once 'db.php';

// Configurar variables para layout
$titulo_pagina = '✏️ Editar Libro';
$icono_titulo = 'fas fa-edit';

$mensaje_exito = '';
$mensaje_error = '';
$errores = [];

// Verificar que se haya proporcionado ID
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: catalogo_libros.php');
    exit;
}

$id_libro = intval($_GET['id']);

// Obtener categorías (usando el mismo método consistente)
$sql_categorias = "SELECT id, nombre FROM categorias ORDER BY nombre";
$stmt_categorias = $db->query($sql_categorias);
$result_categorias = $stmt_categorias->get_result();
$categorias = [];
while ($row = $result_categorias->fetch_assoc()) {
    $categorias[] = $row;
}

// Obtener datos del libro actual
$sql_libro = "SELECT * FROM libros WHERE id = ? AND activo = 1";
$stmt_libro = $db->query($sql_libro, [$id_libro]);
$result_libro = $stmt_libro->get_result();
$libro = $result_libro->fetch_assoc();

if (!$libro) {
    header('Location: catalogo_libros.php');
    exit;
}

// Obtener categorías actuales del libro
$sql_cats_libro = "SELECT id_categoria FROM libro_categoria WHERE id_libro = ?";
$stmt_cats = $db->query($sql_cats_libro, [$id_libro]);
$result_cats = $stmt_cats->get_result();
$categorias_actuales = [];
while ($row = $result_cats->fetch_assoc()) {
    $categorias_actuales[] = $row['id_categoria'];
}

// Procesar actualización
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['actualizar_libro'])) {
    $codigo_interno = trim($_POST['codigo_interno'] ?? '');
    $titulo = trim($_POST['titulo'] ?? '');
    $autor = trim($_POST['autor'] ?? '');
    $ano_publicacion = !empty($_POST['ano_publicacion']) ? intval($_POST['ano_publicacion']) : null;
    $isbn = trim($_POST['isbn'] ?? '');
    $stock = intval($_POST['stock'] ?? 1);
    $categorias_seleccionadas = $_POST['categorias'] ?? [];
    
    // VALIDACIONES
    // 1. Campos obligatorios
    if (empty($codigo_interno)) {
        $errores[] = "El código interno es obligatorio.";
    }
    if (empty($titulo)) {
        $errores[] = "El título es obligatorio.";
    }
    if (empty($autor)) {
        $errores[] = "El autor es obligatorio.";
    }
    
    // 2. Validar código interno único (excluyendo el libro actual)
    if (!empty($codigo_interno) && $codigo_interno != $libro['codigo_interno']) {
        $sql_check_codigo = "SELECT id FROM libros WHERE codigo_interno = ? AND id != ? AND activo = 1";
        $stmt_check = $db->query($sql_check_codigo, [$codigo_interno, $id_libro]);
        $result_check = $stmt_check->get_result();
        if ($result_check->num_rows > 0) {
            $errores[] = "El código interno '$codigo_interno' ya existe en el catálogo.";
        }
        $result_check->free();
    }
    
    // 3. Validar ISBN único (excluyendo el libro actual)
    if (!empty($isbn) && $isbn != $libro['isbn']) {
        $sql_check_isbn = "SELECT id, titulo FROM libros WHERE isbn = ? AND id != ? AND activo = 1";
        $stmt_check_isbn = $db->query($sql_check_isbn, [$isbn, $id_libro]);
        $result_check_isbn = $stmt_check_isbn->get_result();
        if ($result_check_isbn->num_rows > 0) {
            $libro_existente = $result_check_isbn->fetch_assoc();
            $errores[] = "El ISBN '$isbn' ya está registrado para el libro: " . 
                        htmlspecialchars($libro_existente['titulo']) . ".";
        }
        $result_check_isbn->free();
    }
    
    // 4. Validar año de publicación
    if (!empty($ano_publicacion)) {
        $ano_actual = date('Y');
        if ($ano_publicacion < 1000 || $ano_publicacion > $ano_actual) {
            $errores[] = "El año de publicación debe estar entre 1000 y $ano_actual.";
        }
    }
    
    // 5. Validar stock
    if ($stock < 0 || $stock > 1000) {
        $errores[] = "El stock debe estar entre 0 y 1000.";
    }
    
    // Si no hay errores, actualizar
    if (empty($errores)) {
        try {
            // Iniciar transacción
            $link->begin_transaction();
            
            // Actualizar libro
            $sql_update = "UPDATE libros SET 
                          codigo_interno = ?, 
                          titulo = ?, 
                          autor = ?, 
                          año_publicacion = ?, 
                          isbn = ?, 
                          stock = ? 
                          WHERE id = ? AND activo = 1";
            
            $stmt_update = $db->query($sql_update, [
                $codigo_interno, 
                $titulo, 
                $autor, 
                $ano_publicacion, 
                $isbn, 
                $stock, 
                $id_libro
            ]);
            
            if (!$stmt_update || $stmt_update->affected_rows === 0) {
                throw new Exception("Error al actualizar el libro.");
            }
            
            // Eliminar categorías actuales
            $sql_delete_cats = "DELETE FROM libro_categoria WHERE id_libro = ?";
            $db->query($sql_delete_cats, [$id_libro]);
            
            // Asignar nuevas categorías
            if (!empty($categorias_seleccionadas)) {
                foreach ($categorias_seleccionadas as $id_categoria) {
                    if (is_numeric($id_categoria)) {
                        $sql_cat = "INSERT INTO libro_categoria (id_libro, id_categoria) VALUES (?, ?)";
                        $db->query($sql_cat, [$id_libro, $id_categoria]);
                    }
                }
            }
            
            // Confirmar transacción
            $link->commit();
            
            $mensaje_exito = "Libro '$titulo' actualizado exitosamente.";
            
            // Actualizar datos del libro en memoria
            $libro['codigo_interno'] = $codigo_interno;
            $libro['titulo'] = $titulo;
            $libro['autor'] = $autor;
            $libro['año_publicacion'] = $ano_publicacion;
            $libro['isbn'] = $isbn;
            $libro['stock'] = $stock;
            
            // Actualizar categorías actuales
            $categorias_actuales = $categorias_seleccionadas;
            
        } catch (Exception $e) {
            $link->rollback();
            $mensaje_error = "Error al actualizar el libro: " . $e->getMessage();
        }
    } else {
        $mensaje_error = implode("<br>", $errores);
    }
}

// Obtener estadísticas de préstamos para este libro
$sql_prestamos = "SELECT 
                 COUNT(*) as total_prestamos,
                 SUM(CASE WHEN devuelto = 0 THEN 1 ELSE 0 END) as prestamos_activos
                 FROM prestamos WHERE id_libro = ?";
$stmt_prestamos = $db->query($sql_prestamos, [$id_libro]);
$result_prestamos = $stmt_prestamos->get_result();
$prestamos = $result_prestamos->fetch_assoc();

ob_start();
?>

<!-- BREADCRUMB -->
<nav aria-label="breadcrumb" class="mb-4">
    <ol class="breadcrumb">
        <li class="breadcrumb-item">
            <a href="catalogo_libros.php">
                <i class="fas fa-book"></i> Catálogo
            </a>
        </li>
        <li class="breadcrumb-item">
            <a href="catalogo_libros.php?busqueda=<?php echo urlencode($libro['titulo']); ?>">
                <?php echo htmlspecialchars(substr($libro['titulo'], 0, 30)); ?>...
            </a>
        </li>
        <li class="breadcrumb-item active" aria-current="page">
            <i class="fas fa-edit"></i> Editar
        </li>
    </ol>
</nav>

<!-- MENSAJES -->
<?php if (!empty($mensaje_exito)): ?>
<div class="alert alert-success alert-dismissible fade show" role="alert">
    <i class="fas fa-check-circle me-2"></i>
    <?php echo $mensaje_exito; ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    <div class="mt-2">
        <a href="catalogo_libros.php" class="btn btn-sm btn-outline-success me-2">
            <i class="fas fa-list me-1"></i> Volver al Catálogo
        </a>
        <a href="editar_libro.php?id=<?php echo $id_libro; ?>" class="btn btn-sm btn-success">
            <i class="fas fa-edit me-1"></i> Seguir Editando
        </a>
    </div>
</div>
<?php endif; ?>

<?php if (!empty($mensaje_error)): ?>
<div class="alert alert-danger alert-dismissible fade show" role="alert">
    <i class="fas fa-exclamation-triangle me-2"></i>
    <?php echo $mensaje_error; ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<div class="row">
    <div class="col-lg-8">
        <!-- FORMULARIO DE EDICIÓN -->
        <div class="card">
            <div class="card-header gradient-book text-white">
                <div class="d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">
                        <i class="fas fa-edit me-2"></i>
                        Editar Libro: <?php echo htmlspecialchars($libro['titulo']); ?>
                    </h5>
                    <div class="badge bg-light text-dark">
                        ID: <?php echo $id_libro; ?>
                    </div>
                </div>
            </div>
            <div class="card-body">
                <form method="POST" action="" id="formEditarLibro">
                    <div class="row">
                        <!-- CÓDIGO INTERNO -->
                        <div class="col-md-6 mb-3">
                            <label for="codigo_interno" class="form-label">
                                Código Interno <span class="text-danger">*</span>
                            </label>
                            <input type="text" class="form-control" id="codigo_interno" 
                                   name="codigo_interno" 
                                   value="<?php echo htmlspecialchars($libro['codigo_interno']); ?>" 
                                   required>
                            <div class="form-text">
                                Identificador único del libro en el sistema.
                            </div>
                        </div>
                        
                        <!-- ISBN -->
                        <div class="col-md-6 mb-3">
                            <label for="isbn" class="form-label">ISBN</label>
                            <input type="text" class="form-control" id="isbn" 
                                   name="isbn" 
                                   value="<?php echo htmlspecialchars($libro['isbn'] ?? ''); ?>">
                            <div class="form-text">
                                Código ISBN internacional.
                            </div>
                        </div>
                    </div>
                    
                    <!-- TÍTULO -->
                    <div class="mb-3">
                        <label for="titulo" class="form-label">
                            Título <span class="text-danger">*</span>
                        </label>
                        <input type="text" class="form-control" id="titulo" 
                               name="titulo" 
                               value="<?php echo htmlspecialchars($libro['titulo']); ?>" 
                               required>
                    </div>
                    
                    <!-- AUTOR -->
                    <div class="mb-3">
                        <label for="autor" class="form-label">
                            Autor <span class="text-danger">*</span>
                        </label>
                        <input type="text" class="form-control" id="autor" 
                               name="autor" 
                               value="<?php echo htmlspecialchars($libro['autor']); ?>" 
                               required>
                    </div>
                    
                    <div class="row">
                        <!-- AÑO PUBLICACIÓN -->
                        <div class="col-md-6 mb-3">
                            <label for="ano_publicacion" class="form-label">Año de Publicación</label>
                            <input type="number" class="form-control" id="ano_publicacion" 
                                   name="ano_publicacion" 
                                   value="<?php echo htmlspecialchars($libro['año_publicacion'] ?? ''); ?>" 
                                   min="1000" 
                                   max="<?php echo date('Y'); ?>">
                        </div>
                        
                        <!-- STOCK -->
                        <div class="col-md-6 mb-3">
                            <label for="stock" class="form-label">Stock Disponible</label>
                            <input type="number" class="form-control" id="stock" 
                                   name="stock" 
                                   value="<?php echo $libro['stock']; ?>" 
                                   min="0" max="1000" 
                                   required>
                            <div class="form-text">
                                <?php if ($libro['stock'] == 0): ?>
                                    <span class="text-danger">
                                        <i class="fas fa-exclamation-triangle"></i> Libro agotado
                                    </span>
                                <?php elseif ($libro['stock'] < 3): ?>
                                    <span class="text-warning">
                                        <i class="fas fa-exclamation-circle"></i> Stock bajo
                                    </span>
                                <?php else: ?>
                                    <span class="text-success">
                                        <i class="fas fa-check-circle"></i> Stock suficiente
                                    </span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    
                    <!-- CATEGORÍAS -->
                    <div class="mb-4">
                        <label class="form-label">Categorías</label>
                        <div class="border rounded p-3 bg-light">
                            <div class="row">
                                <?php foreach ($categorias as $categoria): ?>
                                <div class="col-md-4 mb-2">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" 
                                               name="categorias[]" 
                                               value="<?php echo $categoria['id']; ?>" 
                                               id="cat_<?php echo $categoria['id']; ?>"
                                               <?php echo in_array($categoria['id'], $categorias_actuales) ? 'checked' : ''; ?>>
                                        <label class="form-check-label" for="cat_<?php echo $categoria['id']; ?>">
                                            <?php echo htmlspecialchars($categoria['nombre']); ?>
                                        </label>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                    
                    <!-- INFORMACIÓN ADICIONAL -->
                    <div class="card bg-light mb-4">
                        <div class="card-body">
                            <h6 class="card-title">
                                <i class="fas fa-info-circle me-2"></i>Información del Sistema
                            </h6>
                            <div class="row small">
                                <div class="col-md-6">
                                    <strong>Creado:</strong> 
                                    <?php echo date('d/m/Y H:i', strtotime($libro['fecha_creacion'])); ?>
                                </div>
                                <div class="col-md-6">
                                    <strong>Estado:</strong> 
                                    <span class="badge bg-success">Activo</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- BOTONES -->
                    <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                        <a href="catalogo_libros.php" class="btn btn-outline-secondary me-2">
                            <i class="fas fa-times me-1"></i> Cancelar
                        </a>
                        
                        <button type="button" class="btn btn-outline-danger me-2" 
                                onclick="confirmarEliminacion(<?php echo $id_libro; ?>, '<?php echo htmlspecialchars(addslashes($libro['titulo'])); ?>')">
                            <i class="fas fa-trash me-1"></i> Eliminar
                        </button>
                        
                        <button type="submit" name="actualizar_libro" class="btn btn-primary">
                            <i class="fas fa-save me-1"></i> Guardar Cambios
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <div class="col-lg-4">
        <!-- PANEL DE INFORMACIÓN -->
        <div class="card mb-4">
            <div class="card-header bg-info text-white">
                <h6 class="mb-0">
                    <i class="fas fa-chart-bar me-2"></i>
                    Estadísticas del Libro
                </h6>
            </div>
            <div class="card-body">
                <div class="list-group list-group-flush">
                    <div class="list-group-item d-flex justify-content-between align-items-center">
                        <span>Total de Préstamos</span>
                        <span class="badge bg-primary rounded-pill">
                            <?php echo $prestamos['total_prestamos']; ?>
                        </span>
                    </div>
                    <div class="list-group-item d-flex justify-content-between align-items-center">
                        <span>Préstamos Activos</span>
                        <span class="badge bg-<?php echo $prestamos['prestamos_activos'] > 0 ? 'warning' : 'success'; ?> rounded-pill">
                            <?php echo $prestamos['prestamos_activos']; ?>
                        </span>
                    </div>
                    <div class="list-group-item d-flex justify-content-between align-items-center">
                        <span>Disponibilidad</span>
                        <span class="badge bg-<?php echo ($libro['stock'] - $prestamos['prestamos_activos']) > 0 ? 'success' : 'danger'; ?> rounded-pill">
                            <?php echo max(0, $libro['stock'] - $prestamos['prestamos_activos']); ?> disponible(s)
                        </span>
                    </div>
                </div>
                
                <?php if ($prestamos['prestamos_activos'] > 0): ?>
                <div class="alert alert-warning small mt-3">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    Este libro tiene <?php echo $prestamos['prestamos_activos']; ?> préstamo(s) activo(s).
                    No se puede eliminar hasta que se devuelvan.
                </div>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- PANEL DE ACCIONES RÁPIDAS -->
        <div class="card">
            <div class="card-header bg-light">
                <h6 class="mb-0">
                    <i class="fas fa-bolt me-2"></i>
                    Acciones Rápidas
                </h6>
            </div>
            <div class="card-body">
                <div class="d-grid gap-2">
                    <a href="catalogo_libros.php?busqueda=<?php echo urlencode($libro['codigo_interno']); ?>" 
                       class="btn btn-outline-primary btn-sm">
                        <i class="fas fa-search me-1"></i> Buscar en Catálogo
                    </a>
                    
                    <a href="agregar_libro.php?duplicar=<?php echo $id_libro; ?>" 
                       class="btn btn-outline-success btn-sm">
                        <i class="fas fa-copy me-1"></i> Duplicar Libro
                    </a>
                    
                    <button type="button" class="btn btn-outline-info btn-sm" 
                            onclick="imprimirFicha()">
                        <i class="fas fa-print me-1"></i> Imprimir Ficha
                    </button>
                    
                    <a href="javascript:history.back()" 
                       class="btn btn-outline-secondary btn-sm">
                        <i class="fas fa-arrow-left me-1"></i> Volver Atrás
                    </a>
                </div>
            </div>
        </div>
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
                
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-circle me-2"></i>
                    <strong>ADVERTENCIA:</strong> 
                    <?php if ($prestamos['prestamos_activos'] > 0): ?>
                        Este libro tiene préstamos activos. No se puede eliminar hasta que se devuelvan.
                    <?php else: ?>
                        Esta acción archivará el libro y no podrá ser prestado nuevamente.
                    <?php endif; ?>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <?php if ($prestamos['prestamos_activos'] == 0): ?>
                <a href="catalogo_libros.php?eliminar=<?php echo $id_libro; ?>&redirigir=1" 
                   id="btnConfirmarEliminar" class="btn btn-danger">
                    <i class="fas fa-trash me-1"></i> Sí, Eliminar
                </a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script>
function confirmarEliminacion(id, titulo) {
    document.getElementById('tituloLibroEliminar').textContent = titulo;
    
    const modal = new bootstrap.Modal(document.getElementById('modalConfirmarEliminacion'));
    modal.show();
}

function imprimirFicha() {
    // Abrir ventana de impresión con ficha del libro
    const ventana = window.open('', '_blank');
    ventana.document.write(`
        <html>
        <head>
            <title>Ficha del Libro - ${document.getElementById('titulo').value}</title>
            <style>
                body { font-family: Arial, sans-serif; padding: 20px; }
                .ficha { border: 2px solid #333; padding: 20px; max-width: 600px; margin: 0 auto; }
                .titulo { font-size: 24px; font-weight: bold; margin-bottom: 10px; }
                .campo { margin-bottom: 10px; }
                .label { font-weight: bold; display: inline-block; width: 150px; }
                .categorias { margin-top: 20px; }
                .badge { background: #6c757d; color: white; padding: 2px 8px; border-radius: 10px; margin-right: 5px; }
                .footer { margin-top: 30px; text-align: center; font-size: 12px; color: #666; }
                @media print {
                    button { display: none; }
                }
            </style>
        </head>
        <body>
            <div class="ficha">
                <div class="titulo">${document.getElementById('titulo').value}</div>
                <div class="campo">
                    <span class="label">Código Interno:</span>
                    ${document.getElementById('codigo_interno').value}
                </div>
                <div class="campo">
                    <span class="label">Autor:</span>
                    ${document.getElementById('autor').value}
                </div>
                <div class="campo">
                    <span class="label">ISBN:</span>
                    ${document.getElementById('isbn').value || 'N/A'}
                </div>
                <div class="campo">
                    <span class="label">Año:</span>
                    ${document.getElementById('ano_publicacion').value || 'N/A'}
                </div>
                <div class="campo">
                    <span class="label">Stock:</span>
                    ${document.getElementById('stock').value} copias
                </div>
                <div class="categorias">
                    <div class="label">Categorías:</div>
                    ${Array.from(document.querySelectorAll('input[name="categorias[]"]:checked'))
                      .map(cb => `<span class="badge">${cb.nextElementSibling.textContent}</span>`)
                      .join('') || 'Sin categorías'}
                </div>
                <div class="footer">
                    Ficha generada el ${new Date().toLocaleDateString()} - Sistema de Gestión de Libros
                </div>
            </div>
            <div style="text-align:center; margin-top:20px;">
                <button onclick="window.print()">Imprimir Ficha</button>
            </div>
        </body>
        </html>
    `);
    ventana.document.close();
}

// Validar stock al enviar formulario
document.getElementById('formEditarLibro').addEventListener('submit', function(e) {
    const stock = parseInt(document.getElementById('stock').value);
    const prestamosActivos = <?php echo $prestamos['prestamos_activos']; ?>;
    
    if (stock < prestamosActivos) {
        e.preventDefault();
        alert(`Error: No puede reducir el stock a ${stock} porque hay ${prestamosActivos} préstamos activos.`);
        document.getElementById('stock').focus();
    }
});

// Poner foco en primer campo
document.getElementById('titulo').focus();
</script>

<?php
$contenido = ob_get_clean();
include 'layout.php';
?>