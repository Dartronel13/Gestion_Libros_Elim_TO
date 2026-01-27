<?php
// layout.php - Plantilla base para Sistema de Biblioteca Elim TO
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $titulo ?? '📚 Sistema Biblioteca Elim TO' ?></title>
    
    <!-- Favicon -->
    <link rel="icon" type="image/png" href="images/logo.png">
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&family=Roboto:wght@300;400;500&display=swap" rel="stylesheet">
    
    <!-- DataTables CSS (opcional) -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.4/css/dataTables.bootstrap5.min.css">
    
    <!-- CSS personalizado -->
    <link rel="stylesheet" href="css/style-layout.css">

    <!-- Estilos específicos de página -->
<?php if (!empty($GLOBALS['pageStyles'] ?? '')) echo $GLOBALS['pageStyles']; ?>
    
    <!-- jQuery (en head para compatibilidad) -->
    <script src="https://code.jquery.com/jquery-3.6.4.min.js"></script>
</head>
<body class="fade-in">
        <!-- NAVBAR COMPACTO Y ELEGANTE -->
    <nav class="navbar navbar-expand-lg navbar-dark fixed-top">
        <div class="container">
            <!-- Logo -->
            <a class="navbar-brand d-flex align-items-center" href="menu.php">
                <div class="logo-container">
                    <?php if(file_exists('images/logo.png')): ?>
                       <img src="images/logo.png" alt="Logo Biblioteca Elim TO" 
                            class="navbar-logo">
                    <?php else: ?>
                        <div class="logo-placeholder">
                            <i class="fas fa-book"></i>
                        </div>
                    <?php endif; ?>
                </div>
                <div class="brand-text ms-2">
                    <div class="brand-title">Biblioteca Elim TO</div>
                </div>
            </a>
            
            <!-- Botón menú móvil -->
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            
            <!-- Todo el contenido del menú -->
            <div class="collapse navbar-collapse" id="navbarNav">
                <!-- Menú principal - CENTRO -->
                <ul class="navbar-nav mx-auto">
                    <?php
                    $menuItems = [
                        'menu.php' => ['icon' => 'fas fa-home', 'text' => 'Inicio'],
                        'agregar_prestamo.php' => ['icon' => 'fas fa-book-medical', 'text' => 'Nuevo Préstamo'],
                        'gestion_prestamo.php' => ['icon' => 'fas fa-clipboard-list', 'text' => 'Gestionar'],
                        'devolucion_libro.php' => ['icon' => 'fas fa-exchange-alt', 'text' => 'Devoluciones'],
                        'historial_prestamo.php' => ['icon' => 'fas fa-history', 'text' => 'Historial'],
                        'catalogo_libros.php' => ['icon' => 'fas fa-book-open', 'text' => 'Catálogo']
                    ];
                    
                    $currentPage = basename($_SERVER['PHP_SELF'] ?? 'menu.php');
                    
                    foreach ($menuItems as $url => $item):
                        $active = $currentPage === $url;
                    ?>
                    <li class="nav-item">
                        <a class="nav-link <?= $active ? 'active' : '' ?>" href="<?= $url ?>">
                            <i class="<?= $item['icon'] ?> me-1"></i>
                            <?= $item['text'] ?>
                        </a>
                    </li>
                    <?php endforeach; ?>
                </ul>
                
                <!-- Usuario y cerrar sesión - DERECHA -->
                <div class="navbar-right">
                    <span class="navbar-user d-none d-md-inline">
                        <i class="fas fa-user me-1"></i>
                        <?php echo htmlspecialchars($_SESSION['nombre_completo'] ?? 'Usuario'); ?>
                    </span>
                    <a href="login/logout.php" class="btn-logout">
                        <i class="fas fa-sign-out-alt"></i>
                        <span class="ms-1">Salir</span>
                    </a>
                </div>
            </div>
        </div>
    </nav>
    <!-- Espacio para compensar navbar fijo -->
    <div class="navbar-spacer"></div>

    <!-- CONTENIDO PRINCIPAL -->
    <main class="container main-content">
        <!-- Mensajes del sistema -->
        <?php if (!empty($mensaje_exito)): ?>
            <div class="alert alert-success alert-dismissible fade show mb-4" role="alert">
                <div class="d-flex align-items-center">
                    <i class="fas fa-check-circle fa-lg me-3"></i>
                    <div class="flex-grow-1"><?= $mensaje_exito ?></div>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            </div>
        <?php endif; ?>
        
        <?php if (!empty($mensaje_error)): ?>
            <div class="alert alert-danger alert-dismissible fade show mb-4" role="alert">
                <div class="d-flex align-items-center">
                    <i class="fas fa-exclamation-circle fa-lg me-3"></i>
                    <div class="flex-grow-1"><?= $mensaje_error ?></div>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            </div>
        <?php endif; ?>
        
        <?php if (!empty($mensaje_info)): ?>
            <div class="alert alert-info alert-dismissible fade show mb-4" role="alert">
                <div class="d-flex align-items-center">
                    <i class="fas fa-info-circle fa-lg me-3"></i>
                    <div class="flex-grow-1"><?= $mensaje_info ?></div>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            </div>
        <?php endif; ?>
        
        <!-- Título de página -->
        <?php if (!empty($titulo_pagina)): ?>
        <div class="d-flex justify-content-between align-items-center mb-4 page-header">
            <h1 class="h2 mb-0 fw-bold">
                <?php if (!empty($icono_titulo)): ?>
                    <i class="<?= $icono_titulo ?> me-2"></i>
                <?php endif; ?>
                <?= $titulo_pagina ?>
            </h1>
            <?php if (!empty($acciones_pagina)): ?>
                <div class="d-flex gap-2">
                    <?= $acciones_pagina ?>
                </div>
            <?php endif; ?>
        </div>
        <?php endif; ?>
        
        <!-- Breadcrumb -->
        <?php if (!empty($breadcrumb)): ?>
        <nav aria-label="breadcrumb" class="mb-4">
            <ol class="breadcrumb">
                <?= $breadcrumb ?>
            </ol>
        </nav>
        <?php endif; ?>
        
        <!-- Contenido dinámico -->
        <div class="content-area mb-5">
            <?= $contenido ?? '<div class="alert alert-warning">Contenido no disponible.</div>' ?>
        </div>
    </main>

    <!-- FOOTER -->
    <footer class="footer">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-6">
                    <div class="d-flex align-items-center">
                        <?php if(file_exists('images/logo.png')): ?>
                            <img src="images/logo.png" alt="Logo" class="footer-logo me-3 rounded-circle">
                        <?php endif; ?>
                        <div>
                            <h6 class="mb-0 fw-bold text-white">Biblioteca Elim TO</h6> <!-- AÑADIR text-white -->
                            <small class="text-white-75">Sistema de Gestión Bibliotecaria</small> <!-- CAMBIAR text-muted -->
                        </div>
                    </div>
                </div>
                <div class="col-md-6 text-md-end mt-3 mt-md-0">
                    <p class="mb-0 small text-white"> <!-- AÑADIR text-white -->
                        <i class="fas fa-copyright me-1"></i>
                        <?= date('Y') ?> Todos los derechos reservados
                        <br>
                        <span class="text-white-75">v1.0.0</span> <!-- CAMBIAR text-muted -->
                    </p>
                </div>
            </div>
            <hr class="my-3 text-white-50"> <!-- AÑADIR text-white-50 -->
            <div class="text-center small text-white-75"> <!-- CAMBIAR text-muted -->
                <i class="fas fa-map-marker-alt me-1"></i> Via Saint Bon 58
                <span class="mx-2">•</span>
                <i class="fas fa-phone me-1"></i> +39 389 599 2466
            </div>
        </div>
    </footer>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- DataTables JS (opcional) -->
    <script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.4/js/dataTables.bootstrap5.min.js"></script>
    
    <!-- Scripts personalizados -->
    <script src="js/main.js"></script>

    <!-- Scripts específicos de página -->
<?php if (!empty($GLOBALS['pageScripts'] ?? '')) echo $GLOBALS['pageScripts']; ?>
</body>
</html>