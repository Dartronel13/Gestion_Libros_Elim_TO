<?php
// obtener_detalles_prestamo.php
require_once 'db.php';

$prestamo_id = $_GET['id'] ?? 0;

// Obtener detalles completos del préstamo con categorías
$sql = "SELECT p.*, 
               l.*,
               lec.*,
               GROUP_CONCAT(c.nombre SEPARATOR ', ') as categorias,
               DATEDIFF(p.fecha_devolucion, CURDATE()) as dias_restantes,
               CASE 
                  WHEN p.devuelto = 1 THEN 'DEVUELTO'
                  WHEN p.fecha_devolucion < CURDATE() THEN 'VENCIDO'
                  WHEN DATEDIFF(p.fecha_devolucion, CURDATE()) <= 3 THEN 'POR VENCER'
                  ELSE 'ACTIVO'
               END as estado_texto
        FROM prestamos p
        JOIN libros l ON p.id_libro = l.id
        LEFT JOIN libro_categoria lc ON l.id = lc.id_libro
        LEFT JOIN categorias c ON lc.id_categoria = c.id
        JOIN lectores lec ON p.id_lector = lec.id
        WHERE p.id = ?
        GROUP BY p.id";

$stmt = $db->query($sql, [$prestamo_id]);
$result = mysqli_stmt_get_result($stmt);
$prestamo = mysqli_fetch_assoc($result);

if (!$prestamo) {
    echo '<div class="alert alert-danger">Préstamo no encontrado</div>';
    exit;
}

// Obtener historial del lector
$sql_historial = "SELECT COUNT(*) as total_prestamos, 
                         SUM(CASE WHEN devuelto = 0 THEN 1 ELSE 0 END) as activos
                  FROM prestamos 
                  WHERE id_lector = ?";
$stmt_historial = $db->query($sql_historial, [$prestamo['id_lector']]);
$historial = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt_historial));

// Determinar clase de estado
$estado_clase = '';
switch ($prestamo['estado_texto']) {
    case 'ACTIVO': $estado_clase = 'success'; break;
    case 'POR VENCER': $estado_clase = 'warning'; break;
    case 'VENCIDO': $estado_clase = 'danger'; break;
    case 'DEVUELTO': $estado_clase = 'secondary'; break;
}
?>

<div class="row">
    <!-- Información del Préstamo -->
    <div class="col-md-6">
        <div class="card border-primary mb-3">
            <div class="card-header bg-primary text-white">
                <h6 class="mb-0"><i class="fas fa-info-circle me-2"></i>Información del Préstamo</h6>
            </div>
            <div class="card-body">
                <div class="row mb-2">
                    <div class="col-6"><strong>ID Préstamo:</strong></div>
                    <div class="col-6">#<?php echo str_pad($prestamo['id'], 6, '0', STR_PAD_LEFT); ?></div>
                </div>
                <div class="row mb-2">
                    <div class="col-6"><strong>Fecha Préstamo:</strong></div>
                    <div class="col-6"><?php echo date('d/m/Y', strtotime($prestamo['fecha_prestamo'])); ?></div>
                </div>
                <div class="row mb-2">
                    <div class="col-6"><strong>Fecha Devolución:</strong></div>
                    <div class="col-6"><?php echo date('d/m/Y', strtotime($prestamo['fecha_devolucion'])); ?></div>
                </div>
                <div class="row mb-2">
                    <div class="col-6"><strong>Días restantes:</strong></div>
                    <div class="col-6">
                        <span class="badge bg-<?php echo $estado_clase; ?>">
                            <?php echo abs($prestamo['dias_restantes']); ?> días
                            <?php echo $prestamo['dias_restantes'] < 0 ? 'de retraso' : 'restantes'; ?>
                        </span>
                    </div>
                </div>
                <div class="row mb-2">
                    <div class="col-6"><strong>Estado:</strong></div>
                    <div class="col-6">
                        <span class="badge bg-<?php echo $estado_clase; ?>">
                            <?php echo $prestamo['estado_texto']; ?>
                        </span>
                    </div>
                </div>
                <div class="row">
                    <div class="col-6"><strong>Devuelto:</strong></div>
                    <div class="col-6">
                        <?php echo $prestamo['devuelto'] ? '✅ Sí' : '❌ No'; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Información del Libro -->
    <div class="col-md-6">
        <div class="card border-info mb-3">
            <div class="card-header bg-info text-white">
                <h6 class="mb-0"><i class="fas fa-book me-2"></i>Información del Libro</h6>
            </div>
            <div class="card-body">
                <h6 class="card-title"><?php echo htmlspecialchars($prestamo['titulo']); ?></h6>
                <p class="card-text mb-1"><strong>Autor:</strong> <?php echo htmlspecialchars($prestamo['autor']); ?></p>
                <p class="card-text mb-1"><strong>Código interno:</strong> <?php echo htmlspecialchars($prestamo['codigo_interno']); ?></p>
                <p class="card-text mb-1"><strong>ISBN:</strong> <?php echo htmlspecialchars($prestamo['isbn'] ?? 'No disponible'); ?></p>
                <p class="card-text mb-1"><strong>Año:</strong> <?php echo htmlspecialchars($prestamo['año_publicacion'] ?? '--'); ?></p>
                <p class="card-text mb-1"><strong>Stock actual:</strong> <?php echo $prestamo['stock']; ?></p>
                <p class="card-text mb-0"><strong>Categorías:</strong> <?php echo htmlspecialchars($prestamo['categorias'] ?? 'Sin categorías'); ?></p>
            </div>
        </div>
    </div>
</div>

<!-- Información del Lector -->
<div class="card border-warning mb-3">
    <div class="card-header bg-warning text-white">
        <h6 class="mb-0"><i class="fas fa-user me-2"></i>Información del Lector</h6>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-4">
                <p class="mb-2"><strong>Nombre completo:</strong><br>
                <?php echo htmlspecialchars($prestamo['nombre'] . ' ' . $prestamo['apellido']); ?></p>
            </div>
            <div class="col-md-4">
                <p class="mb-2"><strong>Email:</strong><br>
                <?php echo htmlspecialchars($prestamo['email']); ?></p>
            </div>
            <div class="col-md-4">
                <p class="mb-2"><strong>Teléfono:</strong><br>
                <?php echo htmlspecialchars($prestamo['telefono'] ?? 'No registrado'); ?></p>
            </div>
        </div>
        <div class="row">
            <div class="col-md-6">
                <p class="mb-2"><strong>Dirección:</strong><br>
                <?php echo htmlspecialchars($prestamo['direccion'] ?? 'No registrada'); ?></p>
            </div>
            <div class="col-md-6">
                <p class="mb-2"><strong>Código Fiscal:</strong><br>
                <?php echo htmlspecialchars($prestamo['codigo_fiscal'] ?? 'No registrado'); ?></p>
            </div>
        </div>
        
        <!-- Historial del lector -->
        <div class="alert alert-secondary mt-2">
            <div class="d-flex justify-content-between">
                <div>
                    <i class="fas fa-history me-2"></i>
                    <strong>Historial del lector:</strong>
                </div>
                <div>
                    <span class="badge bg-info">Total: <?php echo $historial['total_prestamos']; ?> préstamos</span>
                    <span class="badge bg-warning ms-1">Activos: <?php echo $historial['activos']; ?></span>
                </div>
            </div>
        </div>
    </div>
</div>

<?php if ($prestamo['devuelto'] == 0): ?>
<div class="alert alert-info">
    <i class="fas fa-lightbulb me-2"></i>
    <strong>Acciones disponibles:</strong> Este préstamo está activo. Puede marcarlo como devuelto o enviar un recordatorio.
</div>
<?php endif; ?>