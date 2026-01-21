<?php
// menu.php - Página de Inicio/Dashboard (sin navbar duplicado)
require_once 'db.php';

// Configurar variables para esta página específica
$titulo = '📚 Inicio - Sistema de Biblioteca Iglesia';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $titulo ?></title>
    <!-- Logo pestaña -->
    <link rel="icon" type="image/png" href="images/logo.png">
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&family=Roboto:wght@300;400;500&display=swap" rel="stylesheet">
    
    <style>
        :root {
            --primary-color: #2c3e50;
            --secondary-color: #3498db;
            --accent-color: #8e44ad;
            --success-color: #27ae60;
            --warning-color: #f39c12;
            --danger-color: #e74c3c;
            --book-color: #16a085;
        }
        
        * {
            font-family: 'Roboto', sans-serif;
        }
        
        body {
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            min-height: 100vh;
        }
        
        h1, h2, h3, h4, h5, h6 {
            font-family: 'Poppins', sans-serif;
            font-weight: 600;
        }
        
        /* HEADER ESPECIAL PARA MENÚ */
        .menu-header {
            background: linear-gradient(135deg, var(--primary-color), #34495e);
            color: white;
            padding: 30px 0;
            margin-bottom: 40px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
            text-align: center;
        }
        
        .menu-header h1 {
            font-size: 2.5rem;
            margin-bottom: 10px;
        }
        
        .menu-header p {
            font-size: 1.1rem;
            opacity: 0.9;
            max-width: 600px;
            margin: 0 auto;
        }
        
        /* ESTADÍSTICAS */
        .stats-card {
            text-align: center;
            padding: 25px 15px;
            border: none;
            border-radius: 15px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.08);
            transition: all 0.3s ease;
            color: white;
            margin-bottom: 20px;
        }
        
        .stats-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 30px rgba(0,0,0,0.15);
        }
        
        .stats-icon {
            font-size: 2.5rem;
            margin-bottom: 15px;
            display: inline-block;
        }
        
        .stats-number {
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 5px;
        }
        
        .stats-label {
            font-size: 0.9rem;
            color: rgba(255,255,255,0.9);
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        
        /* TARJETAS DE OPCIÓN */
        .option-card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.08);
            transition: all 0.3s ease;
            height: 100%;
            margin-bottom: 20px;
        }
        
        .option-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 30px rgba(0,0,0,0.15);
        }
        
        .option-card .card-header {
            border-bottom: none;
            font-weight: 600;
            color: white;
            padding: 20px;
        }
        
        .option-card .card-body {
            padding: 25px;
        }
        
        .btn-option {
            border-radius: 10px;
            font-weight: 600;
            padding: 12px 25px;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            width: 100%;
        }
        
        .btn-option:hover {
            transform: translateY(-3px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        
        /* TABLAS */
        .table {
            border-radius: 10px;
            overflow: hidden;
            background: white;
        }
        
        .table thead th {
            background: linear-gradient(135deg, var(--primary-color), #4a6491);
            color: white;
            border: none;
            font-weight: 600;
            padding: 15px;
        }
        
        /* FOOTER */
        footer {
            background: linear-gradient(135deg, var(--primary-color), #2c3e50);
            color: white;
            margin-top: 50px;
            padding: 20px 0;
            text-align: center;
        }
        
        /* BÚSQUEDA RÁPIDA */
        .search-box {
            position: relative;
        }
        
        .search-box .form-control {
            padding-right: 45px;
            border-radius: 10px;
        }
        
        .search-box .search-icon {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #6c757d;
            pointer-events: none;
        }
        
        /* RESPONSIVE */
        @media (max-width: 768px) {
            .menu-header h1 {
                font-size: 2rem;
            }
            
            .menu-header p {
                font-size: 1rem;
                padding: 0 15px;
            }
            
            .btn-option {
                width: 100%;
                margin-bottom: 10px;
            }
            
            .stats-card {
                margin-bottom: 20px;
            }
        }
    </style>
</head>
<body>
    <!-- HEADER ESPECIAL PARA PÁGINA DE INICIO -->
    <header class="menu-header">
        <div class="container">
            <h1>
                <i class="fas fa-book me-2"></i>Sistema de Biblioteca - Iglesia
            </h1>
            <p>Control local de préstamos y gestión de libros</p>
            
            <!-- ENLACE PARA IR A OTRAS PÁGINAS (solo en móvil) -->
            <div class="d-md-none mt-4">
                <a href="#opciones" class="btn btn-outline-light">
                    <i class="fas fa-arrow-down me-2"></i>Ver Opciones
                </a>
            </div>
        </div>
    </header>

    <!-- CONTENIDO PRINCIPAL -->
    <main class="container">
        <!-- ESTADÍSTICAS -->
        <?php
        // CONSULTAS MODIFICADAS SEGÚN TUS INDICACIONES
        $query_estadisticas = "
            SELECT 
                -- LIBROS DISPONIBLES (total libros - libros prestados)
                (SELECT COUNT(*) FROM libros) - 
                (SELECT COUNT(*) FROM prestamos WHERE devuelto = 0) as libros_disponibles,
                
                -- PRÉSTAMOS ACTIVOS
                (SELECT COUNT(*) FROM prestamos WHERE devuelto = 0) as prestamos_activos,
                
                -- PRÉSTAMOS POR VENCER (5 días o menos)
                (SELECT COUNT(*) FROM prestamos 
                 WHERE devuelto = 0 
                 AND fecha_devolucion BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 5 DAY)) as prestamos_por_vencer,
                
                -- PRÉSTAMOS VENCIDOS
                (SELECT COUNT(*) FROM prestamos 
                 WHERE devuelto = 0 
                 AND fecha_devolucion < CURDATE()) as prestamos_vencidos
        ";
        
        $result = mysqli_query($link, $query_estadisticas);
        $estadisticas = mysqli_fetch_assoc($result);
        ?>
        
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
                    <div class="stats-label">Prestamos por Vencer (≤5 días)</div>
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
            
            <!-- BÚSQUEDA RÁPIDA -->
            <div class="col-md-4 mb-4">
                <div class="option-card">
                    <div class="card-header" style="background: linear-gradient(135deg, #2c3e50, #34495e);">
                        <h5 class="mb-0">
                            <i class="fas fa-search me-2"></i>Búsqueda Rápida
                        </h5>
                    </div>
                    <div class="card-body">
                        <p class="card-text">Buscar libros, personas o préstamos rápidamente.</p>
                        <div class="search-box mb-3">
                            <input type="text" class="form-control" 
                                   placeholder="Buscar libro, persona..." 
                                   id="busqueda-rapida">
                            <i class="fas fa-search search-icon"></i>
                        </div>
                        <div id="resultados-busqueda" class="mt-2" style="display: none;"></div>
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
                        <?php
                        // Consultar préstamos activos recientes
                        $query_prestamos = "
                            SELECT p.*, l.titulo, lec.nombre, lec.apellido,
                                   DATEDIFF(p.fecha_devolucion, CURDATE()) as dias_restantes
                            FROM prestamos p
                            LEFT JOIN libros l ON p.id_libro = l.id
                            LEFT JOIN lectores lec ON p.id_lector = lec.id
                            WHERE p.devuelto = 0
                            ORDER BY p.fecha_prestamo DESC
                            LIMIT 5
                        ";
                        
                        $result_prestamos = mysqli_query($link, $query_prestamos);
                        $num_prestamos = mysqli_num_rows($result_prestamos);
                        ?>
                        
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
                                        
                                        // Determinar estado
                                        if ($dias_restantes < 0) {
                                            $estado_clase = 'badge bg-danger';
                                            $estado_texto = 'VENCIDO';
                                        } elseif ($dias_restantes <= 5) {
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
    </main>

    <!-- FOOTER -->
    <footer>
        <div class="container">
            <div class="row">
                <div class="col-12">
                    <p class="mb-0">
                        <i class="fas fa-church me-2"></i>Sistema de Biblioteca Local
                        &copy; <?= date('Y') ?> - Iglesia
                    </p>
                    <small class="opacity-75">Versión Local - Control de Préstamos</small>
                </div>
            </div>
        </div>
    </footer>

    <!-- Bootstrap JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Script para búsqueda rápida -->
    <script>
    // Función para búsqueda rápida
    function realizarBusqueda(termino) {
        if (termino.length < 2) {
            document.getElementById('resultados-busqueda').style.display = 'none';
            return;
        }
        
        const resultadosDiv = document.getElementById('resultados-busqueda');
        
        // Simulación
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
        resultadosDiv.style.display = 'block';
    }
    
    // Configurar búsqueda
    let timeoutBusqueda;
    const inputBusqueda = document.getElementById('busqueda-rapida');
    
    inputBusqueda.addEventListener('input', function() {
        clearTimeout(timeoutBusqueda);
        timeoutBusqueda = setTimeout(() => {
            realizarBusqueda(this.value);
        }, 300);
    });
    
    // Cerrar resultados al hacer clic fuera
    document.addEventListener('click', function(e) {
        if (!inputBusqueda.contains(e.target) && 
            !document.getElementById('resultados-busqueda').contains(e.target)) {
            document.getElementById('resultados-busqueda').style.display = 'none';
        }
    });
    
    // Auto-focus en búsqueda
    document.addEventListener('DOMContentLoaded', function() {
        inputBusqueda.focus();
    });
    </script>
</body>
</html>