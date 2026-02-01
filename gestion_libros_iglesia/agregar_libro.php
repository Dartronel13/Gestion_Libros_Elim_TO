<?php
// agregar_libro.php
session_start();
require_once 'db.php';

// Configurar variables para layout
$titulo_pagina = '➕ Agregar Nuevo Libro';
$icono_titulo = 'fas fa-plus-circle';

$mensaje_exito = '';
$mensaje_error = '';
$errores = [];

// Obtener categorías
$sql_categorias = "SELECT id, nombre FROM categorias ORDER BY nombre";
$result_categorias = mysqli_query($link, $sql_categorias);
$categorias = [];
while ($row = mysqli_fetch_assoc($result_categorias)) {
    $categorias[] = $row;
}

// Procesar duplicación de libro
if (isset($_GET['duplicar']) && is_numeric($_GET['duplicar'])) {
    $id_duplicar = intval($_GET['duplicar']);
    
    // Obtener datos del libro a duplicar
    $sql_duplicar = "SELECT * FROM libros WHERE id = ? AND activo = 1";
    $stmt_duplicar = $db->query($sql_duplicar, [$id_duplicar]);
    $libro_duplicar = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt_duplicar));
    
    if ($libro_duplicar) {
        // Precargar datos del libro a duplicar
        $_POST['codigo_interno'] = $libro_duplicar['codigo_interno'] . '-COPY';
        $_POST['titulo'] = $libro_duplicar['titulo'] . ' (Copia)';
        $_POST['autor'] = $libro_duplicar['autor'];
        $_POST['ano_publicacion'] = $libro_duplicar['año_publicacion'];
        $_POST['isbn'] = $libro_duplicar['isbn'];
        $_POST['stock'] = 1;
        
        // Obtener categorías del libro original
        $sql_cats_dup = "SELECT id_categoria FROM libro_categoria WHERE id_libro = ?";
        $stmt_cats_dup = $db->query($sql_cats_dup, [$id_duplicar]);
        $categorias_dup = [];
        while ($row = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt_cats_dup))) {
            $categorias_dup[] = $row['id_categoria'];
        }
        $_POST['categorias'] = $categorias_dup;
    }
}

// Procesar formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['agregar_libro'])) {
    $codigo_interno = trim($_POST['codigo_interno'] ?? '');
    $titulo = trim($_POST['titulo'] ?? '');
    $autor = trim($_POST['autor'] ?? '');
    $ano_publicacion = !empty($_POST['ano_publicacion']) ? intval($_POST['ano_publicacion']) : null;
    
    // MODIFICACIÓN IMPORTANTE: Procesar ISBN para que sea NULL cuando esté vacío
    $isbn_raw = trim($_POST['isbn'] ?? '');
    $isbn = ($isbn_raw === '') ? null : $isbn_raw;
    
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
    
    // 2. Validar código interno único
    if (!empty($codigo_interno)) {
        $sql_check_codigo = "SELECT id FROM libros WHERE codigo_interno = ? AND activo = 1";
        $stmt_check = $db->query($sql_check_codigo, [$codigo_interno]);
        $result_check = mysqli_stmt_get_result($stmt_check);
        if (mysqli_num_rows($result_check) > 0) {
            $errores[] = "El código interno '$codigo_interno' ya existe en el catálogo.";
        }
    }
    
    // 3. Validar ISBN único (solo si no es NULL)
    if ($isbn !== null) {
        $sql_check_isbn = "SELECT id, codigo_interno, titulo FROM libros WHERE isbn = ? AND activo = 1";
        $stmt_check_isbn = $db->query($sql_check_isbn, [$isbn]);
        $result_check_isbn = mysqli_stmt_get_result($stmt_check_isbn);
        if (mysqli_num_rows($result_check_isbn) > 0) {
            $libro_existente = mysqli_fetch_assoc($result_check_isbn);
            $errores[] = "El ISBN '$isbn' ya está registrado para el libro: " . 
                        htmlspecialchars($libro_existente['titulo']) . 
                        " (Código: " . htmlspecialchars($libro_existente['codigo_interno']) . ").";
        }
    }
    
    // 4. Validar año de publicación
    if (!empty($ano_publicacion)) {
        $ano_actual = date('Y');
        if ($ano_publicacion < 1000 || $ano_publicacion > $ano_actual) {
            $errores[] = "El año de publicación debe estar entre 1000 y $ano_actual.";
        }
    }
    
    // 5. Validar stock
    if ($stock < 1 || $stock > 1000) {
        $errores[] = "El stock debe estar entre 1 y 1000.";
    }
    
    // Si no hay errores, insertar
    if (empty($errores)) {
        try {
            // Iniciar transacción
            mysqli_begin_transaction($link);
            
            // MODIFICACIÓN IMPORTANTE: Preparar los valores para la inserción
            $valores_insert = [
                $codigo_interno, 
                $titulo, 
                $autor, 
                $ano_publicacion, 
                $isbn,  // Este puede ser NULL o string
                $stock
            ];
            
            // Insertar libro
            $sql_insert = "INSERT INTO libros (codigo_interno, titulo, autor, año_publicacion, isbn, stock) 
                          VALUES (?, ?, ?, ?, ?, ?)";
            $stmt_insert = mysqli_prepare($link, $sql_insert);
            
            if (!$stmt_insert) {
                throw new Exception("Error al preparar la consulta: " . mysqli_error($link));
            }
            
            // Determinar tipos de parámetros (isbn puede ser NULL)
            $tipos = "sssisi"; // s=string, i=integer
            
            // Para manejar NULL en MySQLi, necesitamos preparar los valores
            mysqli_stmt_bind_param($stmt_insert, $tipos, ...$valores_insert);
            
            if (!mysqli_stmt_execute($stmt_insert)) {
                throw new Exception("Error al insertar el libro: " . mysqli_stmt_error($stmt_insert));
            }
            
            $id_libro = mysqli_insert_id($link);
            mysqli_stmt_close($stmt_insert);
            
            // Asignar categorías
            if (!empty($categorias_seleccionadas)) {
                foreach ($categorias_seleccionadas as $id_categoria) {
                    if (is_numeric($id_categoria)) {
                        $sql_cat = "INSERT INTO libro_categoria (id_libro, id_categoria) VALUES (?, ?)";
                        $db->query($sql_cat, [$id_libro, $id_categoria]);
                    }
                }
            }
            
            // Confirmar transacción
            mysqli_commit($link);
            
            $mensaje_exito = "Libro '$titulo' agregado exitosamente al catálogo.";
            
            // Limpiar formulario
            $_POST = [];
            
        } catch (Exception $e) {
            mysqli_rollback($link);
            $mensaje_error = "Error al agregar el libro: " . $e->getMessage();
        }
    } else {
        $mensaje_error = implode("<br>", $errores);
    }
}

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
        <li class="breadcrumb-item active" aria-current="page">
            <i class="fas fa-plus-circle"></i> Agregar Libro
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
            <i class="fas fa-list me-1"></i> Ver Catálogo
        </a>
        <a href="agregar_libro.php" class="btn btn-sm btn-success">
            <i class="fas fa-plus me-1"></i> Agregar Otro
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
        <!-- FORMULARIO PRINCIPAL -->
        <div class="card">
            <div class="card-header gradient-book text-dark">
                <h5 class="mb-0">
                    <i class="fas fa-book-medical me-2"></i>
                    Información del Libro
                </h5>
            </div>
            <div class="card-body">
                <form method="POST" action="" id="formAgregarLibro">
                    <div class="row">
                        <!-- CÓDIGO INTERNO -->
                        <div class="col-md-6 mb-3">
                            <label for="codigo_interno" class="form-label">
                                Código Interno <span class="text-danger">*</span>
                            </label>
                            <div class="input-group">
                                <input type="text" class="form-control" id="codigo_interno" 
                                       name="codigo_interno" 
                                       value="<?php echo htmlspecialchars($_POST['codigo_interno'] ?? ''); ?>" 
                                       placeholder="LIB-001, BIB-2024-01" 
                                       required
                                       autofocus>
                                <button type="button" class="btn btn-outline-secondary" 
                                        onclick="generarCodigoAutomatico()"
                                        title="Generar código automático">
                                    <i class="fas fa-magic"></i>
                                </button>
                            </div>
                            <div class="form-text">
                                Identificador único. Puede usar escáner o generar automáticamente.
                            </div>
                        </div>
                        
                        <!-- ISBN -->
                        <div class="col-md-6 mb-3">
                            <label for="isbn" class="form-label">ISBN</label>
                            <div class="input-group">
                                <input type="text" class="form-control" id="isbn" 
                                       name="isbn" 
                                       value="<?php echo htmlspecialchars($_POST['isbn'] ?? ''); ?>" 
                                       placeholder="978-3-16-148410-0 (opcional)">
                                <button type="button" class="btn btn-outline-primary" 
                                        onclick="buscarPorISBN()"
                                        title="Buscar información por ISBN">
                                    <i class="fas fa-search"></i>
                                </button>
                            </div>
                            <div class="form-text">
                                Escanee el código de barras ISBN o ingréselo manualmente. Déjelo vacío si no tiene ISBN.
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
                               value="<?php echo htmlspecialchars($_POST['titulo'] ?? ''); ?>" 
                               placeholder="Título completo del libro" 
                               required>
                    </div>
                    
                    <!-- AUTOR -->
                    <div class="mb-3">
                        <label for="autor" class="form-label">
                            Autor <span class="text-danger">*</span>
                        </label>
                        <input type="text" class="form-control" id="autor" 
                               name="autor" 
                               value="<?php echo htmlspecialchars($_POST['autor'] ?? ''); ?>" 
                               placeholder="Nombre completo del autor" 
                               required>
                    </div>
                    
                    <div class="row">
                        <!-- AÑO PUBLICACIÓN -->
                        <div class="col-md-6 mb-3">
                            <label for="ano_publicacion" class="form-label">Año de Publicación</label>
                            <input type="number" class="form-control" id="ano_publicacion" 
                                   name="ano_publicacion" 
                                   value="<?php echo htmlspecialchars($_POST['ano_publicacion'] ?? ''); ?>" 
                                   placeholder="Ej: 2024 (opcional)" 
                                   min="1000" 
                                   max="<?php echo date('Y'); ?>">
                        </div>
                        
                        <!-- STOCK -->
                        <div class="col-md-6 mb-3">
                            <label for="stock" class="form-label">Stock Inicial</label>
                            <input type="number" class="form-control" id="stock" 
                                   name="stock" 
                                   value="<?php echo htmlspecialchars($_POST['stock'] ?? '1'); ?>" 
                                   min="1" max="1000" 
                                   required>
                            <div class="form-text">Número de copias disponibles.</div>
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
                                               <?php echo (isset($_POST['categorias']) && in_array($categoria['id'], $_POST['categorias'])) ? 'checked' : ''; ?>>
                                        <label class="form-check-label" for="cat_<?php echo $categoria['id']; ?>">
                                            <?php echo htmlspecialchars($categoria['nombre']); ?>
                                        </label>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                            <div class="form-text mt-2">
                                Seleccione todas las categorías que correspondan al libro.
                            </div>
                        </div>
                    </div>
                    
                    <!-- BOTONES -->
                    <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                        <a href="catalogo_libros.php" class="btn btn-outline-secondary me-2">
                            <i class="fas fa-times me-1"></i> Cancelar
                        </a>
                        <button type="submit" name="agregar_libro" class="btn btn-success">
                            <i class="fas fa-save me-1"></i> Guardar Libro
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <div class="col-lg-4">
        <!-- PANEL DE ESCÁNER -->
        <div class="card mb-4">
            <div class="card-header bg-primary text-white">
                <h6 class="mb-0">
                    <i class="fas fa-barcode me-2"></i>
                    Escáner Rápido
                </h6>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <label class="form-label">Escanear Código de Barras</label>
                    <input type="text" class="form-control" id="inputEscanner" 
                           placeholder="Pase el código de barras aquí"
                           autocomplete="off">
                    <div class="form-text">
                        Pase el código de barras del libro (ISBN o código interno).
                    </div>
                </div>
                
                <div class="alert alert-info small">
                    <i class="fas fa-info-circle me-2"></i>
                    <strong>Tip:</strong> El sistema detectará automáticamente si es ISBN 
                    (13 dígitos) o código interno, y completará los campos.
                </div>
                
                <div class="text-center">
                    <button type="button" class="btn btn-outline-primary btn-sm" 
                            onclick="simularEscaneo()">
                        <i class="fas fa-barcode me-1"></i> Simular Escaneo (Demo)
                    </button>
                </div>
            </div>
        </div>
        
        <!-- PANEL DE AYUDA -->
        <div class="card">
            <div class="card-header bg-light">
                <h6 class="mb-0">
                    <i class="fas fa-lightbulb me-2"></i>
                    Sugerencias y Formatos
                </h6>
            </div>
            <div class="card-body">
                <h6 class="mb-2">Formatos de Código:</h6>
                <ul class="small mb-3">
                    <li><code>LIB-001</code> - Libro secuencial</li>
                    <li><code>BIB-2024-015</code> - Año y número</li>
                    <li><code>TEOL-001</code> - Por categoría</li>
                    <li><code>AUTOR-001</code> - Iniciales autor</li>
                </ul>
                
                <h6 class="mb-2">Categorías Disponibles:</h6>
                <div class="d-flex flex-wrap gap-1 mb-3">
                    <?php foreach ($categorias as $categoria): ?>
                        <span class="badge bg-info"><?php echo htmlspecialchars($categoria['nombre']); ?></span>
                    <?php endforeach; ?>
                </div>
                
                <h6 class="mb-2">Stock Recomendado:</h6>
                <p class="small mb-0">
                    • Libros de referencia: 1-2 copias<br>
                    • Libros de estudio: 3-5 copias<br>
                    • Best-sellers: 2-3 copias<br>
                    • Colecciones especiales: 1 copia
                </p>
            </div>
        </div>
    </div>
</div>

<!-- MODAL BUSCAR POR ISBN -->
<div class="modal fade" id="modalBuscarISBN" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title">
                    <i class="fas fa-search me-2"></i>
                    Buscar por ISBN
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div id="resultadoBusquedaISBN">
                    <div class="text-center py-4">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Buscando...</span>
                        </div>
                        <p class="mt-2">Buscando información del libro...</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // ============================================
    // DETECCIÓN AUTOMÁTICA DE CÓDIGO ESCANEADO
    // ============================================
    const inputEscanner = document.getElementById('inputEscanner');
    const inputISBN = document.getElementById('isbn');
    const inputCodigo = document.getElementById('codigo_interno');
    const inputTitulo = document.getElementById('titulo');
    const inputAutor = document.getElementById('autor');
    
    let tiempoEscaneo = null;
    let codigoAcumulado = '';
    
    if (inputEscanner) {
        inputEscanner.addEventListener('keydown', function(e) {
            // Si es Enter (como si el escáner enviara Enter al final)
            if (e.key === 'Enter') {
                e.preventDefault();
                procesarCodigoEscaneado(this.value.trim());
                this.value = '';
                return;
            }
            
            // Acumular caracteres para detectar escaneo rápido
            if (e.key.length === 1 && !e.ctrlKey && !e.altKey && !e.metaKey) {
                codigoAcumulado += e.key;
                
                clearTimeout(tiempoEscaneo);
                tiempoEscaneo = setTimeout(() => {
                    procesarCodigoEscaneado(codigoAcumulado);
                    codigoAcumulado = '';
                }, 100);
            }
        });
        
        inputEscanner.addEventListener('input', function() {
            const codigo = this.value.trim();
            
            // Si parece un código completo (sin espacios, longitud típica)
            if ((codigo.length === 13 || codigo.length === 10 || codigo.length >= 8) && 
                !/\s/.test(codigo)) {
                
                clearTimeout(tiempoEscaneo);
                tiempoEscaneo = setTimeout(() => {
                    procesarCodigoEscaneado(codigo);
                    this.value = '';
                }, 300);
            }
        });
        
        // Poner foco automático en el escáner
        inputEscanner.focus();
    }
    
    function procesarCodigoEscaneado(codigo) {
        if (!codigo) return;
        
        console.log('Código escaneado:', codigo);
        
        // Determinar si es ISBN (13 dígitos o 10 dígitos)
        const esISBN13 = /^\d{13}$/.test(codigo);
        const esISBN10 = /^\d{9}[\dX]$/i.test(codigo);
        const esISBN = esISBN13 || esISBN10;
        
        if (esISBN) {
            // Formatear ISBN
            let isbnFormateado = codigo;
            if (esISBN13) {
                isbnFormateado = codigo.substring(0, 3) + '-' + 
                                codigo.substring(3, 4) + '-' + 
                                codigo.substring(4, 9) + '-' + 
                                codigo.substring(9, 12) + '-' + 
                                codigo.substring(12);
            } else if (esISBN10) {
                isbnFormateado = codigo.substring(0, 1) + '-' + 
                                codigo.substring(1, 4) + '-' + 
                                codigo.substring(4, 9) + '-' + 
                                codigo.substring(9).toUpperCase();
            }
            
            // Poner en campo ISBN
            inputISBN.value = isbnFormateado;
            
            // Verificar si ya existe (solo si no está vacío)
            if (isbnFormateado.trim() !== '') {
                verificarISBNExistente(isbnFormateado);
            }
            
            // Enfocar siguiente campo
            inputTitulo.focus();
            
            // Mostrar notificación
            mostrarNotificacion('ISBN detectado: ' + isbnFormateado, 'success');
            
        } else {
            // Asumir que es código interno
            inputCodigo.value = codigo;
            
            // Verificar si ya existe
            verificarCodigoExistente(codigo);
            
            // Enfocar siguiente campo
            inputTitulo.focus();
            
            // Mostrar notificación
            mostrarNotificacion('Código interno detectado: ' + codigo, 'info');
        }
    }
    
    function verificarISBNExistente(isbn) {
        fetch('verificar_isbn.php?isbn=' + encodeURIComponent(isbn))
            .then(response => response.json())
            .then(data => {
                if (data.existe) {
                    mostrarAlerta('Este ISBN ya está registrado para el libro: <strong>' + 
                                data.titulo + '</strong> (Código: ' + data.codigo + ')', 'warning');
                }
            })
            .catch(error => console.error('Error:', error));
    }
    
    function verificarCodigoExistente(codigo) {
        fetch('verificar_codigo.php?codigo=' + encodeURIComponent(codigo))
            .then(response => response.json())
            .then(data => {
                if (data.existe) {
                    mostrarAlerta('Este código interno ya existe en el catálogo.', 'warning');
                }
            })
            .catch(error => console.error('Error:', error));
    }
    
    // ============================================
    // FUNCIONES AUXILIARES
    // ============================================
    function generarCodigoAutomatico() {
        const titulo = inputTitulo.value.trim();
        const autor = inputAutor.value.trim();
        
        let codigo = 'LIB-';
        
        if (titulo) {
            // Tomar primeras 3 letras del título
            const palabras = titulo.split(' ');
            if (palabras.length >= 2) {
                codigo += palabras[0].substring(0, 3).toUpperCase() + 
                         palabras[1].substring(0, 2).toUpperCase();
            } else {
                codigo += titulo.substring(0, 5).toUpperCase().replace(/\s/g, '');
            }
        } else if (autor) {
            // Tomar primeras letras del autor
            const partesAutor = autor.split(' ');
            if (partesAutor.length >= 2) {
                codigo += partesAutor[0].substring(0, 1).toUpperCase() + 
                         partesAutor[1].substring(0, 2).toUpperCase();
            } else {
                codigo += autor.substring(0, 3).toUpperCase();
            }
        } else {
            codigo += 'GEN';
        }
        
        // Añadir número secuencial
        codigo += '-001';
        
        inputCodigo.value = codigo;
        mostrarNotificacion('Código generado: ' + codigo, 'info');
    }
    
    function buscarPorISBN() {
        const isbn = inputISBN.value.trim().replace(/-/g, '');
        
        if (!isbn || (isbn.length !== 10 && isbn.length !== 13)) {
            mostrarAlerta('Por favor ingrese un ISBN válido (10 o 13 dígitos).', 'warning');
            return;
        }
        
        // Mostrar modal de búsqueda
        const modal = new bootstrap.Modal(document.getElementById('modalBuscarISBN'));
        modal.show();
        
        // Aquí podrías integrar una API como Google Books o Open Library
        // Por ahora mostramos un mensaje informativo
        setTimeout(() => {
            document.getElementById('resultadoBusquedaISBN').innerHTML = `
                <div class="alert alert-info">
                    <i class="fas fa-info-circle me-2"></i>
                    <strong>Búsqueda por ISBN</strong>
                    <p class="mb-0 mt-2">
                        Para integrar búsqueda automática, puedes conectar con:
                    </p>
                    <ul class="mb-0">
                        <li>Google Books API</li>
                        <li>Open Library API</li>
                        <li>ISBNdb API</li>
                    </ul>
                </div>
            `;
        }, 1500);
    }
    
    function simularEscaneo() {
        const isbnsDemo = [
            '9788408241951', // Código 13 dígitos
            '9788437604947', 
            '9788497593798',
            '9788466338141'
        ];
        
        const codigosDemo = [
            'BIB-2024-001',
            'TEOL-045',
            'LIB-789',
            'DIC-023'
        ];
        
        // Alternar entre ISBN y código interno
        const usarISBN = Math.random() > 0.5;
        const codigo = usarISBN 
            ? isbnsDemo[Math.floor(Math.random() * isbnsDemo.length)]
            : codigosDemo[Math.floor(Math.random() * codigosDemo.length)];
        
        inputEscanner.value = codigo;
        
        // Simular escaneo
        setTimeout(() => {
            procesarCodigoEscaneado(codigo);
            inputEscanner.value = '';
        }, 500);
    }
    
    function mostrarNotificacion(mensaje, tipo = 'info') {
        const alerta = document.createElement('div');
        alerta.className = `alert alert-${tipo} alert-dismissible fade show position-fixed`;
        alerta.style.cssText = 'top: 20px; right: 20px; z-index: 1050; max-width: 300px;';
        alerta.innerHTML = `
            <i class="fas fa-${tipo === 'success' ? 'check-circle' : 'info-circle'} me-2"></i>
            ${mensaje}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        `;
        
        document.body.appendChild(alerta);
        
        setTimeout(() => {
            if (alerta.parentNode) {
                alerta.remove();
            }
        }, 3000);
    }
    
    function mostrarAlerta(mensaje, tipo = 'warning') {
        const alerta = document.createElement('div');
        alerta.className = `alert alert-${tipo} mt-2`;
        alerta.innerHTML = `
            <i class="fas fa-exclamation-triangle me-2"></i>
            ${mensaje}
        `;
        
        // Insertar después del campo correspondiente
        const campo = tipo === 'warning' ? inputISBN : inputCodigo;
        campo.parentNode.appendChild(alerta);
        
        setTimeout(() => {
            if (alerta.parentNode) {
                alerta.remove();
            }
        }, 5000);
    }
    
    // Auto-generar código cuando se pierde foco del título
    inputTitulo.addEventListener('blur', function() {
        if (!inputCodigo.value) {
            generarCodigoAutomatico();
        }
    });
    
    // Formatear ISBN al perder foco
    inputISBN.addEventListener('blur', function() {
        let isbn = this.value.trim().replace(/-/g, '');
        
        if (isbn.length === 10) {
            this.value = isbn.substring(0, 1) + '-' + 
                        isbn.substring(1, 4) + '-' + 
                        isbn.substring(4, 9) + '-' + 
                        isbn.substring(9).toUpperCase();
        } else if (isbn.length === 13) {
            this.value = isbn.substring(0, 3) + '-' + 
                        isbn.substring(3, 4) + '-' + 
                        isbn.substring(4, 9) + '-' + 
                        isbn.substring(9, 12) + '-' + 
                        isbn.substring(12);
        }
        
        // Si después de formatear queda vacío, limpiar el campo
        if (this.value.trim() === '') {
            this.value = '';
        }
    });
});
</script>

<?php
$contenido = ob_get_clean();
include 'layout.php';
?>