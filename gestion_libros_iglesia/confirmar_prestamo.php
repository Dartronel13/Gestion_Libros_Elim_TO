[file name]: confirmar_prestamo.php
[file content begin]
<?php
// confirmar_prestamo.php 
require_once 'db.php';
verificarAutenticacion();
$db->registrarAccion('acceso', 'prestamos', "Accedió a confirmación de préstamo");

if (!isset($_SESSION['datos_prestamo'])) {
    $db->registrarAccion('error_sesion', 'prestamos', "Intento de acceso sin datos de préstamo");
    header('Location: agregar_prestamo.php');
    exit();
}

$datos = $_SESSION['datos_prestamo'];

if (empty($datos['id_libro']) || empty($datos['fecha_prestamo'])) {
    $db->registrarAccion('error_datos', 'prestamos', "Datos incompletos del préstamo");
    die("Error: Datos incompletos del préstamo.");
}

$db->registrarAccion('inicio_confirmacion', 'prestamos', "Iniciando confirmación - Libro ID: {$datos['id_libro']}");

// Obtener información del libro
$sql_libro = "SELECT * FROM libros WHERE id = ?";
$stmt_libro = $db->query($sql_libro, [$datos['id_libro']]);
if (!$stmt_libro) {
    die("Error en consulta de libro.");
}
$result_libro = mysqli_stmt_get_result($stmt_libro);
$libro = mysqli_fetch_assoc($result_libro);

if (!$libro) {
    die("Error: Libro no encontrado.");
}

// Determinar ID del lector
if ($datos['tipo_lector'] === 'nuevo') {
    $db->registrarAccion('creando_nuevo_lector', 'lectores', "Creando nuevo lector: {$datos['nombre']} {$datos['apellido']}");
    
    $sql_nuevo_lector = "INSERT INTO lectores (nombre, apellido, email, direccion, telefono, codigo_fiscal) VALUES (?, ?, ?, ?, ?, ?)";
    $params_lector = [$datos['nombre'], $datos['apellido'], $datos['email'], $datos['direccion'], $datos['telefono'], $datos['codigo_fiscal']];
    $stmt_lector = $db->query($sql_nuevo_lector, $params_lector);
    
    if (!$stmt_lector) {
        die("Error al registrar nuevo lector: " . mysqli_error($link));
    }
    
    $id_lector = mysqli_insert_id($link);
    $db->registrarAccion('lector_creado', 'lectores', "Nuevo lector creado ID: {$id_lector}");
} else {
    if (empty($datos['id_lector']) || !is_numeric($datos['id_lector'])) {
        die("Error: ID de lector inválido.");
    }
    $id_lector = $datos['id_lector'];
}

// Registrar el préstamo
$db->registrarAccion('creando_prestamo', 'prestamos', "Creando préstamo - Libro: '{$libro['titulo']}'");

$sql_prestamo = "INSERT INTO prestamos (id_libro, id_lector, fecha_prestamo, fecha_devolucion, devuelto) VALUES (?, ?, ?, ?, 0)";
$params_prestamo = [$datos['id_libro'], $id_lector, $datos['fecha_prestamo'], $datos['fecha_devolucion_estimada']];
$stmt_prestamo = $db->query($sql_prestamo, $params_prestamo);

if (!$stmt_prestamo) {
    die("Error al registrar el préstamo: " . mysqli_error($link));
}

$id_prestamo = mysqli_insert_id($link);
$db->registrarAccion('prestamo_creado', 'prestamos', "Préstamo creado exitosamente ID: {$id_prestamo}");

// Actualizar stock del libro
$sql_update = "UPDATE libros SET stock = stock - 1 WHERE id = ?";
$stmt_update = $db->query($sql_update, [$datos['id_libro']]);

if (!$stmt_update) {
    die("Error al actualizar el stock: " . mysqli_error($link));
}

// Obtener información del lector
$sql_lector_info = "SELECT * FROM lectores WHERE id = ?";
$stmt_lector_info = $db->query($sql_lector_info, [$id_lector]);
$result_lector_info = mysqli_stmt_get_result($stmt_lector_info);
$lector = mysqli_fetch_assoc($result_lector_info);

// Generar número de recibo
$numero_recibo = 'REC-' . str_pad($id_prestamo, 6, '0', STR_PAD_LEFT) . '-' . date('Ymd');

// Limpiar sesión después de usar
unset($_SESSION['datos_prestamo']);
$db->registrarAccion('proceso_completado', 'prestamos', "Préstamo completado exitosamente - ID: {$id_prestamo}");
?>

<!-- HTML PARA LA PÁGINA - Versión simplificada -->
<div class="row">
    <div class="col-md-8">
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
                
                <div class="text-center mb-4">
                    <h5 class="text-dark mb-3">Vista Previa del Recibo</h5>
                    <div class="border p-3 bg-light rounded">
                        <p class="mb-2"><strong>Recibo No:</strong> <?php echo $numero_recibo; ?></p>
                        <p class="mb-2"><strong>Libro:</strong> <?php echo htmlspecialchars($libro['titulo']); ?></p>
                        <p class="mb-0"><strong>Lector:</strong> <?php echo htmlspecialchars($lector['nombre'] . ' ' . $lector['apellido']); ?></p>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-4">
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
                </div>
            </div>
        </div>
        
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

<!-- VERSIÓN CON ESPACIO RESTAURADO EN "RECIBÍ CONFORME" -->
<div id="printable-recibo" style="display: none;">
    <div style="font-family: 'Arial', sans-serif; padding: 5px; max-width: 650px; margin: 0 auto; font-size: 11px; line-height: 1.2;">
        
        <!-- Encabezado compacto -->
        <div style="text-align: center; margin-bottom: 8px; padding-bottom: 5px; border-bottom: 1px solid #000;">
            <div style="margin-bottom: 2px;">
                <img src="images/logo.png" alt="Logo" style="max-height: 40px;">
            </div>
            <div style="font-size: 12px; font-weight: bold;">IGLESIA ELIM TORINO</div>
            <div style="font-size: 10px;">Biblioteca Cristiana</div>
            <div style="font-size: 10px; margin-top: 2px;">
                <strong>COMPROBANTE DE PRÉSTAMO</strong>
            </div>
            <div style="font-size: 9px; margin-top: 2px;">
                <strong>No:</strong> <?php echo $numero_recibo; ?> | 
                <strong>Fecha:</strong> <?php echo date('d/m/Y H:i'); ?>
            </div>
        </div>
        
        <!-- Datos del préstamo -->
        <table width="100%" style="margin-bottom: 8px; border-collapse: collapse; font-size: 10px;">
            <tr>
                <td style="padding: 4px; border: 1px solid #000; background: #f0f0f0; font-weight: bold; text-align: center;">
                    DATOS DEL PRÉSTAMO
                </td>
            </tr>
            <tr>
                <td style="padding: 4px; border: 1px solid #000;">
                    <table width="100%" style="border-collapse: collapse;">
                        <tr>
                            <td style="padding: 2px; width: 33%;">
                                <strong>N°:</strong> #<?php echo str_pad($id_prestamo, 6, '0', STR_PAD_LEFT); ?>
                            </td>
                            <td style="padding: 2px; width: 33%;">
                                <strong>Préstamo:</strong> <?php echo date('d/m/Y', strtotime($datos['fecha_prestamo'])); ?>
                            </td>
                            <td style="padding: 2px; width: 34%;">
                                <strong>Devolución:</strong> <?php echo date('d/m/Y', strtotime($datos['fecha_devolucion_estimada'])); ?>
                            </td>
                        </tr>
                    </table>
                </td>
            </tr>
        </table>
        
        <!-- Libro y Lector -->
        <table width="100%" style="margin-bottom: 8px; border-collapse: collapse; font-size: 10px;">
            <tr>
                <td colspan="2" style="padding: 4px; border: 1px solid #000; background: #f0f0f0; font-weight: bold; text-align: center;">
                    INFORMACIÓN
                </td>
            </tr>
            <tr>
                <td style="padding: 4px; border: 1px solid #000; width: 50%; vertical-align: top;">
                    <strong>LIBRO:</strong><br>
                    <strong>Título:</strong> <?php echo htmlspecialchars($libro['titulo']); ?><br>
                    <strong>Autor:</strong> <?php echo htmlspecialchars($libro['autor']); ?><br>
                    <strong>Código:</strong> <?php echo htmlspecialchars($libro['codigo_interno']); ?>
                </td>
                <td style="padding: 4px; border: 1px solid #000; width: 50%; vertical-align: top;">
                    <strong>LECTOR:</strong><br>
                    <strong>Nombre:</strong> <?php echo htmlspecialchars($lector['nombre'] . ' ' . $lector['apellido']); ?><br>
                    <strong>Email:</strong> <?php echo htmlspecialchars($lector['email']); ?><br>
                    <strong>Tel:</strong> <?php echo !empty($lector['telefono']) ? htmlspecialchars($lector['telefono']) : 'N/R'; ?>
                </td>
            </tr>
            <tr>
                <td colspan="2" style="padding: 4px; border: 1px solid #000;">
                    <strong>Dirección:</strong> <?php echo !empty($lector['direccion']) ? htmlspecialchars(substr($lector['direccion'], 0, 60)) . (strlen($lector['direccion']) > 60 ? '...' : '') : 'No registrada'; ?><br>
                    <strong>Código Fiscal:</strong> <?php echo !empty($lector['codigo_fiscal']) ? htmlspecialchars($lector['codigo_fiscal']) : 'No registrado'; ?>
                </td>
            </tr>
        </table>
        
        <!-- ESPACIO ADICIONAL ANTES DE LAS FIRMAS -->
        <div style="height: 10px;"></div>
        
        <!-- FIRMAS Y SELLOS CON MÁS ESPACIO -->
        <table width="100%" style="margin-bottom: 15px; border-collapse: collapse; font-size: 10px;">
            <tr>
                <!-- Firma del Lector - MUCHO MÁS ESPACIO -->
                <td width="50%" style="padding: 10px; text-align: center; vertical-align: bottom;">
                    <div style="border-top: 1px solid #000; width: 90%; margin: 0 auto; padding-top: 40px; min-height: 100px; position: relative;">
                        <div style="position: absolute; top: 5px; left: 0; width: 100%;">
                            <strong>FIRMA DEL LECTOR</strong><br>
                            <span style="font-size: 9px;"><?php echo htmlspecialchars($lector['nombre'] . ' ' . $lector['apellido']); ?></span><br>
                            <span style="font-size: 8px; color: #666;">Código Fiscal: <?php echo !empty($lector['codigo_fiscal']) ? htmlspecialchars($lector['codigo_fiscal']) : '_________________'; ?></span>
                        </div>
                        <!-- Espacio en blanco para firmar -->
                        <div style="height: 60px;"></div>
                    </div>
                </td>
                
                <!-- Sello y Firma de la Iglesia - MÁS ESPACIO -->
                <td width="50%" style="padding: 10px; text-align: center; vertical-align: bottom;">
                    <div style="border: 2px dashed #000; padding: 15px; display: inline-block; min-height: 120px; min-width: 180px; position: relative;">
                        <div style="position: absolute; top: 10px; left: 0; width: 100%;">
                            <strong>SELLO Y FIRMA</strong><br>
                            <span style="font-size: 10px;">IGLESIA ELIM TORINO</span><br>
                            <span style="font-size: 9px; color: #666;">Biblioteca Cristiana</span>
                        </div>
                        <!-- Espacio para sello y firma -->
                        <div style="height: 70px; margin-top: 40px;">
                            <strong>Responsable:</strong><br>
                            _______________________<br>
                            <span style="font-size: 8px;">(Nombre y Firma)</span>
                        </div>
                    </div>
                </td>
            </tr>
        </table>
        
        <!-- Versículo -->
        <div style="text-align: center; font-size: 9px; font-style: italic; color: #666; margin-bottom: 5px;">
            "Instruye al sabio, y será más sabio; enseña al justo, y aumentará su saber." Proverbios 9:9
        </div>
        
        <!-- Pie de página -->
        <div style="padding-top: 5px; border-top: 1px solid #000; text-align: center; font-size: 8px; color: #666;">
            <div><strong>Iglesia Elim Torino - Biblioteca Cristiana</strong></div>
            <div>Tel: +39 389 599 2466 | Email: iglesiaelimtorino20@gmail.com</div>
            <div>Dirección: Via Saint Bon 58, TO</div>
            <div style="font-size: 7px; margin-top: 3px;">Recibo generado: <?php echo date('d/m/Y H:i'); ?></div>
        </div>
        
        <!-- Línea de corte -->
        <div style="text-align: center; margin-top: 15px; padding-top: 5px; border-top: 1px dashed #999; font-size: 8px; color: #999;">
            --- CORTAR Y ENTREGAR AL LECTOR ---
        </div>
        
        <!-- SECCIÓN PARA DEVOLUCIÓN CON MÁS ESPACIO RESTAURADO -->
        <div style="margin-top: 15px; padding: 8px; border: 1px solid #000; background: #f9f9f9; font-size: 8px;">
            <div style="text-align: center; font-weight: bold; margin-bottom: 6px; font-size: 9px;">PRESENTAR PARA DEVOLUCIÓN</div>
            <table width="100%">
                <tr>
                    <td style="padding-bottom: 4px;"><strong>Recibo:</strong> <?php echo $numero_recibo; ?></td>
                    <td style="padding-bottom: 4px;"><strong>Préstamo:</strong> <?php echo date('d/m/Y', strtotime($datos['fecha_prestamo'])); ?></td>
                </tr>
                <tr>
                    <td style="padding-bottom: 8px;"><strong>Lector:</strong> <?php echo htmlspecialchars(substr($lector['nombre'] . ' ' . $lector['apellido'], 0, 20)); ?></td>
                    <td style="padding-bottom: 8px;"><strong>Devolución:</strong> <?php echo date('d/m/Y', strtotime($datos['fecha_devolucion_estimada'])); ?></td>
                </tr>
                
                <!-- LÍNEA "RECIBÍ CONFORME" MODIFICADA - LÍNEA ABAJO Y ESPACIO ARRIBA PARA FIRMAR -->
                <tr>
                    <td colspan="2" style="text-align: center; padding-top: 15px;">
                        <div style="border-top: 1px solid #000; width: 80%; margin: 0 auto; padding-top: 5px; min-height: 60px; position: relative;">
                            <!-- ESPACIO PARA FIRMAR ARRIBA DE LA LÍNEA -->
                            <div style="height: 25px; margin-bottom: 5px;">
                                <!-- Aquí es donde se debe firmar -->
                            </div>
                            <!-- LA LÍNEA ESTÁ AQUÍ ABAJO -->
                            <div style="position: absolute; bottom: 5px; left: 0; width: 100%; text-align: center;">
                                <strong>Recibí conforme</strong><br>
                                <span style="font-size: 7px;">(Firma responsable)</span>
                            </div>
                        </div>
                    </td>
                </tr>
                
                <!-- CONDICIONES MOVIDAS AQUÍ DESPUÉS DEL RECIBÍ CONFORME -->
                <tr>
                    <td colspan="2" style="padding-top: 10px;">
                        <div style="margin-top: 8px; padding: 4px; border: 1px dashed #666; background: #fff8dc; font-size: 8px;">
                            <strong>CONDICIONES:</strong>
                            <div style="margin-left: 8px;">
                                1. Devolución: <strong><?php echo date('d/m/Y', strtotime($datos['fecha_devolucion_estimada'])); ?></strong><br>
                                2. Devuelva en mismo estado<br>
                                3. En pérdida/daño, debera reponer el precio del libro<br>
                                4. Es posible aumentar el plazo del prestamo por 15 dias si es necesario<br>
                                5. Presente este comprobante para realizar la devolucion.
                            </div>
                        </div>
                    </td>
                </tr>
            </table>
        </div>
    </div>
</div>

<script>
// Función para imprimir el recibo optimizada
function imprimirRecibo() {
    const printContent = document.getElementById('printable-recibo').innerHTML;
    
    const printWindow = window.open('', '_blank');
    printWindow.document.write('<html><head><title>Recibo de Préstamo - Iglesia Elim Torino</title>');
    printWindow.document.write('<style>');
    printWindow.document.write('@media print {');
    printWindow.document.write('  body { font-family: Arial, sans-serif; margin: 8mm 5mm 5mm 5mm !important; padding: 0 !important; font-size: 11px !important; line-height: 1.2 !important; }');
    printWindow.document.write('  @page { margin: 8mm 5mm 5mm 5mm !important; size: A4 portrait; }');
    printWindow.document.write('  table { border-collapse: collapse; width: 100%; font-size: 10px !important; }');
    printWindow.document.write('  td, th { border: 1px solid #000; padding: 3px !important; }');
    printWindow.document.write('  img { max-height: 40px !important; }');
    printWindow.document.write('  * { box-sizing: border-box; }');
    printWindow.document.write('}');
    printWindow.document.write('</style>');
    printWindow.document.write('</head><body>');
    printWindow.document.write(printContent);
    printWindow.document.write('</body></html>');
    printWindow.document.close();
    
    // Esperar un momento para que cargue la imagen del logo
    setTimeout(function() {
        printWindow.print();
        printWindow.close();
    }, 300);
}

// Opción para auto-imprimir (descomentar si se desea)
/*
setTimeout(() => {
    if (confirm('¿Desea imprimir el recibo ahora?')) {
        imprimirRecibo();
    }
}, 1500);
*/
</script>

<?php
$contenido = ob_get_clean();
include 'layout.php';
?>
[file content end]