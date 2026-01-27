<?php
// confirmar_prestamo.php 

// 1. VERIFICACIÓN DE ACCESO (AGREGAR ESTO AL INICIO)
require_once 'db.php';
verificarAutenticacion(); // ← ESTA LÍNEA ES NUEVA

// 2. REGISTRAR ACCESO A ESTA PÁGINA
$db->registrarAccion('acceso', 'prestamos', "Accedió a confirmación de préstamo");

// Verificar que hay datos del préstamo
if (!isset($_SESSION['datos_prestamo'])) {
    $db->registrarAccion('error_sesion', 'prestamos', "Intento de acceso sin datos de préstamo");
    header('Location: agregar_prestamo.php');
    exit();
}

$datos = $_SESSION['datos_prestamo'];

// Validar datos mínimos
if (empty($datos['id_libro']) || empty($datos['fecha_prestamo'])) {
    $db->registrarAccion('error_datos', 'prestamos', "Datos incompletos del préstamo");
    die("Error: Datos incompletos del préstamo.");
}

// 3. REGISTRAR INICIO DE CONFIRMACIÓN
$db->registrarAccion(
    'inicio_confirmacion', 
    'prestamos', 
    "Iniciando confirmación - Libro ID: {$datos['id_libro']}, Tipo lector: {$datos['tipo_lector']}"
);

// Obtener información completa del libro
$sql_libro = "SELECT * FROM libros WHERE id = ?";
$stmt_libro = $db->query($sql_libro, [$datos['id_libro']]);
if (!$stmt_libro) {
    $db->registrarAccion('error_consulta', 'prestamos', "Error en consulta de libro ID: {$datos['id_libro']}");
    die("Error en consulta de libro.");
}

$result_libro = mysqli_stmt_get_result($stmt_libro);
$libro = mysqli_fetch_assoc($result_libro);

if (!$libro) {
    $db->registrarAccion('error_no_encontrado', 'prestamos', "Libro no encontrado ID: {$datos['id_libro']}");
    die("Error: Libro no encontrado.");
}

// Determinar ID del lector
if ($datos['tipo_lector'] === 'nuevo') {
    
    // 4. REGISTRAR CREACIÓN DE NUEVO LECTOR
    $db->registrarAccion(
        'creando_nuevo_lector', 
        'lectores', 
        "Creando nuevo lector: {$datos['nombre']} {$datos['apellido']}, Email: {$datos['email']}"
    );
    
    // Insertar nuevo lector
    $sql_nuevo_lector = "INSERT INTO lectores (nombre, apellido, email, direccion, telefono, codigo_fiscal) 
                         VALUES (?, ?, ?, ?, ?, ?)";
    
    $params_lector = [
        $datos['nombre'],
        $datos['apellido'],
        $datos['email'],
        $datos['direccion'],
        $datos['telefono'],
        $datos['codigo_fiscal']
    ];
    
    $stmt_lector = $db->query($sql_nuevo_lector, $params_lector);
    
    if (!$stmt_lector) {
        $db->registrarAccion(
            'error_lector', 
            'lectores', 
            "Error al crear lector: " . mysqli_error($link)
        );
        die("Error al registrar nuevo lector: " . mysqli_error($link));
    }
    
    $id_lector = mysqli_insert_id($link);
    
    // 5. REGISTRAR LECTOR CREADO EXITOSAMENTE
    $db->registrarAccion(
        'lector_creado', 
        'lectores', 
        "Nuevo lector creado ID: {$id_lector} - {$datos['nombre']} {$datos['apellido']}"
    );
    
} else {
    // Lector existente
    if (empty($datos['id_lector']) || !is_numeric($datos['id_lector'])) {
        $db->registrarAccion('error_id_lector', 'prestamos', "ID de lector inválido: " . $datos['id_lector']);
        die("Error: ID de lector inválido.");
    }
    
    $id_lector = $datos['id_lector'];
    
    // 6. REGISTRAR USO DE LECTOR EXISTENTE
    $db->registrarAccion(
        'usando_lector_existente', 
        'prestamos', 
        "Usando lector existente ID: {$id_lector}"
    );
}

// 7. REGISTRAR ANTES DE CREAR PRÉSTAMO
$db->registrarAccion(
    'creando_prestamo', 
    'prestamos', 
    "Creando préstamo - Libro: '{$libro['titulo']}' (ID: {$datos['id_libro']}), " .
    "Lector ID: {$id_lector}, Fecha: {$datos['fecha_prestamo']}"
);

// Registrar el préstamo
$sql_prestamo = "INSERT INTO prestamos (id_libro, id_lector, fecha_prestamo, fecha_devolucion, devuelto) 
                 VALUES (?, ?, ?, ?, 0)";
$params_prestamo = [
    $datos['id_libro'],
    $id_lector,
    $datos['fecha_prestamo'],
    $datos['fecha_devolucion_estimada']
];

$stmt_prestamo = $db->query($sql_prestamo, $params_prestamo);

if (!$stmt_prestamo) {
    $db->registrarAccion(
        'error_prestamo', 
        'prestamos', 
        "Error al crear préstamo: " . mysqli_error($link)
    );
    die("Error al registrar el préstamo: " . mysqli_error($link));
}

$id_prestamo = mysqli_insert_id($link);

// 8. REGISTRAR PRÉSTAMO CREADO EXITOSAMENTE
$db->registrarAccion(
    'prestamo_creado', 
    'prestamos', 
    "Préstamo creado exitosamente ID: {$id_prestamo} - " .
    "Libro: '{$libro['titulo']}' (ID: {$datos['id_libro']}), " .
    "Lector ID: {$id_lector}"
);

// 9. REGISTRAR ACTUALIZACIÓN DE STOCK
$db->registrarAccion(
    'actualizando_stock', 
    'inventario', 
    "Actualizando stock del libro ID: {$datos['id_libro']} - Stock anterior: {$libro['stock']}"
);

// Actualizar stock del libro
$sql_update = "UPDATE libros SET stock = stock - 1 WHERE id = ?";
$stmt_update = $db->query($sql_update, [$datos['id_libro']]);

if (!$stmt_update) {
    $db->registrarAccion(
        'error_stock', 
        'inventario', 
        "Error al actualizar stock del libro ID: {$datos['id_libro']}"
    );
    die("Error al actualizar el stock: " . mysqli_error($link));
}

// 10. REGISTRAR STOCK ACTUALIZADO
$db->registrarAccion(
    'stock_actualizado', 
    'inventario', 
    "Stock actualizado - Libro ID: {$datos['id_libro']}, " .
    "Nuevo stock: " . ($libro['stock'] - 1)
);

// Obtener información completa del lector
$sql_lector_info = "SELECT * FROM lectores WHERE id = ?";
$stmt_lector_info = $db->query($sql_lector_info, [$id_lector]);
$result_lector_info = mysqli_stmt_get_result($stmt_lector_info);
$lector = mysqli_fetch_assoc($result_lector_info);

// Generar número de recibo
$numero_recibo = 'REC-' . str_pad($id_prestamo, 6, '0', STR_PAD_LEFT) . '-' . date('Ymd');

// 11. REGISTRAR RECIBO GENERADO
$db->registrarAccion(
    'recibo_generado', 
    'prestamos', 
    "Recibo generado: {$numero_recibo} para préstamo ID: {$id_prestamo}"
);

// Limpiar sesión después de usar
unset($_SESSION['datos_prestamo']);

// 12. REGISTRAR PROCESO COMPLETADO
$db->registrarAccion(
    'proceso_completado', 
    'prestamos', 
    "Préstamo completado exitosamente - ID: {$id_prestamo}, Recibo: {$numero_recibo}"
);
?>

<div class="row">
    <div class="col-md-8">
        <!-- Recibo del Préstamo -->
        <div class="card mb-4">
            <div class="card-header gradient-success">
                <h5 class="mb-0"><i class="fas fa-check-circle me-2"></i>Préstamo Registrado Exitosamente</h5>
            </div>
            <div class="card-body">
                <div class="alert alert-success">
                    <div class="d-flex align-items-center">
                        <i class="fas fa-check-circle fa-2x me-3"></i>
                        <div>
                            <h4 class="mb-1">¡Préstamo Completado!</h4>
                            <p class="mb-0">El préstamo ha sido registrado correctamente en el sistema.</p>
                        </div>
                    </div>
                </div>
                
                <!-- Encabezado del recibo -->
                <div class="text-center mb-4 border-bottom pb-3">
                    <h2 class="text-primary">Biblioteca Elim Torino</h2>
                    <h4 class="text-dark">COMPROBANTE DE PRÉSTAMO</h4>
                    <div class="row mt-3">
                        <div class="col-md-6">
                            <p class="mb-1"><strong>Número de Recibo:</strong></p>
                            <h5 class="text-info"><?php echo $numero_recibo; ?></h5>
                        </div>
                        <div class="col-md-6">
                            <p class="mb-1"><strong>Fecha de Emisión:</strong></p>
                            <h5><?php echo date('d/m/Y H:i:s'); ?></h5>
                        </div>
                    </div>
                </div>
                
                <!-- Información del Préstamo -->
                <div class="row mb-4">
                    <div class="col-md-4">
                        <div class="card border-primary">
                            <div class="card-header bg-primary text-white">
                                <h6 class="mb-0"><i class="fas fa-info-circle me-2"></i>Préstamo</h6>
                            </div>
                            <div class="card-body">
                                <p class="mb-2"><strong>ID:</strong> #<?php echo str_pad($id_prestamo, 6, '0', STR_PAD_LEFT); ?></p>
                                <p class="mb-2"><strong>Préstamo:</strong> <?php echo date('d/m/Y', strtotime($datos['fecha_prestamo'])); ?></p>
                                <p class="mb-0"><strong>Devolución:</strong> <?php echo date('d/m/Y', strtotime($datos['fecha_devolucion_estimada'])); ?></p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-8">
                        <div class="card border-info">
                            <div class="card-header bg-info text-white">
                                <h6 class="mb-0"><i class="fas fa-book me-2"></i>Libro Prestado</h6>
                            </div>
                            <div class="card-body">
                                <h5 class="card-title"><?php echo htmlspecialchars($libro['titulo']); ?></h5>
                                <p class="card-text mb-1">
                                    <strong>Autor:</strong> <?php echo htmlspecialchars($libro['autor']); ?><br>
                                    <strong>Código interno:</strong> <code><?php echo htmlspecialchars($libro['codigo_interno']); ?></code><br>
                                    <?php if (!empty($libro['isbn'])): ?>
                                    <strong>ISBN:</strong> <code><?php echo htmlspecialchars($libro['isbn']); ?></code><br>
                                    <?php endif; ?>
                                    <strong>Año:</strong> <?php echo htmlspecialchars($libro['año_publicacion'] ?? '--'); ?>
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Información del Lector -->
                <div class="card border-warning mb-4">
                    <div class="card-header bg-warning text-white">
                        <h6 class="mb-0"><i class="fas fa-user me-2"></i>Datos del Lector</h6>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-4">
                                <p class="mb-2"><strong>Nombre:</strong><br>
                                <?php echo htmlspecialchars($lector['nombre'] . ' ' . $lector['apellido']); ?></p>
                            </div>
                            <div class="col-md-4">
                                <p class="mb-2"><strong>Email:</strong><br>
                                <?php echo htmlspecialchars($lector['email']); ?></p>
                            </div>
                            <div class="col-md-4">
                                <p class="mb-2"><strong>Teléfono:</strong><br>
                                <?php echo htmlspecialchars($lector['telefono'] ?? 'No registrado'); ?></p>
                            </div>
                        </div>
                        <?php if (!empty($lector['direccion'])): ?>
                        <div class="row">
                            <div class="col-12">
                                <p class="mb-0"><strong>Dirección:</strong><br>
                                <?php echo htmlspecialchars($lector['direccion']); ?></p>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Mensaje de agradecimiento -->
                <div class="alert alert-secondary text-center">
                    <h5 class="mb-0">
                        <i class="fas fa-quote-left me-2"></i>
                        "Gracias por usar la Biblioteca Elim Torino. ¡Que disfrutes tu lectura!"
                        <i class="fas fa-quote-right ms-2"></i>
                    </h5>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-4">
        <!-- Acciones -->
        <div class="card mb-4">
            <div class="card-header gradient-primary">
                <h5 class="mb-0"><i class="fas fa-print me-2"></i>Imprimir Recibo</h5>
            </div>
            <div class="card-body text-center">
                <div class="mb-4">
                    <i class="fas fa-print fa-4x text-primary mb-3"></i>
                    <p class="text-muted">Imprima este comprobante para entregárselo al lector.</p>
                </div>
                
                <div class="d-grid gap-2">
                    <button onclick="imprimirRecibo()" class="btn btn-primary btn-lg">
                        <i class="fas fa-print me-2"></i>Imprimir Recibo
                    </button>
                    
                    <button onclick="descargarPDF()" class="btn btn-outline-primary">
                        <i class="fas fa-download me-2"></i>Descargar PDF
                    </button>
                </div>
            </div>
        </div>
        
        <!-- Información Importante -->
        <div class="card mb-4">
            <div class="card-header gradient-warning">
                <h5 class="mb-0"><i class="fas fa-exclamation-triangle me-2"></i>Condiciones</h5>
            </div>
            <div class="card-body">
                <div class="alert alert-warning">
                    <strong><i class="fas fa-calendar-times me-2"></i>Fecha de Devolución:</strong><br>
                    <?php echo date('d/m/Y', strtotime($datos['fecha_devolucion_estimada'])); ?>
                </div>
                
                <ul class="mb-0 small">
                    <li class="mb-2">El libro debe ser devuelto en las mismas condiciones</li>
                    <li class="mb-2">En caso de pérdida, el lector deberá reponerlo</li>
                    <li class="mb-2">Se permite una renovación por 15 días adicionales</li>
                    <li>Presente este comprobante al momento de la devolución</li>
                </ul>
            </div>
        </div>
        
        <!-- Navegación -->
        <div class="card">
            <div class="card-body">
                <h6 class="mb-3"><i class="fas fa-arrow-right me-2"></i>¿Qué desea hacer ahora?</h6>
                <div class="d-grid gap-2">
                    <a href="agregar_prestamo.php" class="btn btn-success">
                        <i class="fas fa-plus-circle me-2"></i>Nuevo Préstamo
                    </a>
                    
                    <a href="menu.php" class="btn btn-outline-secondary">
                        <i class="fas fa-home me-2"></i>Volver al Menú
                    </a>
                    
                    <a href="gestion_prestamo.php" class="btn btn-outline-info">
                        <i class="fas fa-tasks me-2"></i>Ver Préstamos Activos
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Versión para Impresión -->
<div id="printable-recibo" style="display: none;">
    <div style="font-family: Arial, sans-serif; padding: 20px;">
        <div style="text-align: center; margin-bottom: 30px; border-bottom: 2px solid #000; padding-bottom: 15px;">
            <h1 style="margin: 0;">Biblioteca Elim Torino</h1>
            <h2 style="margin: 10px 0;">COMPROBANTE DE PRÉSTAMO</h2>
            <div style="font-size: 14px;">
                <strong>Número:</strong> <?php echo $numero_recibo; ?> | 
                <strong>Fecha:</strong> <?php echo date('d/m/Y H:i:s'); ?>
            </div>
        </div>
        
        <table width="100%" style="margin-bottom: 20px;">
            <tr>
                <td width="33%" valign="top">
                    <strong>Préstamo #:</strong> <?php echo str_pad($id_prestamo, 6, '0', STR_PAD_LEFT); ?><br>
                    <strong>Fecha Préstamo:</strong> <?php echo date('d/m/Y', strtotime($datos['fecha_prestamo'])); ?><br>
                    <strong>Fecha Devolución:</strong> <?php echo date('d/m/Y', strtotime($datos['fecha_devolucion_estimada'])); ?>
                </td>
                <td width="67%" valign="top">
                    <strong>Libro:</strong> <?php echo htmlspecialchars($libro['titulo']); ?><br>
                    <strong>Autor:</strong> <?php echo htmlspecialchars($libro['autor']); ?><br>
                    <strong>Código:</strong> <?php echo htmlspecialchars($libro['codigo_interno']); ?>
                </td>
            </tr>
        </table>
        
        <div style="border: 1px solid #000; padding: 15px; margin-bottom: 20px;">
            <strong>Datos del Lector:</strong><br>
            <strong>Nombre:</strong> <?php echo htmlspecialchars($lector['nombre'] . ' ' . $lector['apellido']); ?><br>
            <strong>Email:</strong> <?php echo htmlspecialchars($lector['email']); ?><br>
            <strong>Teléfono:</strong> <?php echo htmlspecialchars($lector['telefono'] ?? '--'); ?><br>
            <strong>Código Fiscal:</strong> <?php echo htmlspecialchars($lector['codigo_fiscal'] ?? '--'); ?>
        </div>
        
        <div style="text-align: center; margin: 30px 0;">
            <div style="border-top: 1px solid #000; width: 60%; margin: 0 auto; padding-top: 20px;">
                Firma del Lector / Responsable
            </div>
        </div>
        
        <div style="font-size: 12px; border-top: 1px dashed #000; padding-top: 10px; text-align: center;">
            <strong>Biblioteca Elim Torino</strong><br>
            "Gracias por usar nuestra biblioteca. ¡Que disfrutes tu lectura!"
        </div>
    </div>
</div>

<script>
// Función para imprimir el recibo
function imprimirRecibo() {
    const printContent = document.getElementById('printable-recibo').innerHTML;
    const originalContent = document.body.innerHTML;
    
    document.body.innerHTML = printContent;
    window.print();
    document.body.innerHTML = originalContent;
    location.reload(); // Recargar para restaurar los botones
}

// Función para simular descarga de PDF
function descargarPDF() {
    alert('En una versión completa, aquí se generaría un archivo PDF.\nPor ahora, use la función de impresión y seleccione "Guardar como PDF".');
}

// Auto-imprimir opcional (descomentar si se desea)
// setTimeout(() => {
//     if (confirm('¿Desea imprimir el recibo ahora?')) {
//         imprimirRecibo();
//     }
// }, 1000);
</script>

<?php
$contenido = ob_get_clean();
include 'layout.php';
?>