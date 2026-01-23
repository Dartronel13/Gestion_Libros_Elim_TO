<?php
// layout.php - Plantilla base para Sistema de Biblioteca Local
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $titulo ?? '📚 Sistema de Biblioteca - Iglesia' ?></title>
    <!-- Logo pestaña -->
    <link rel="icon" type="image/png" href="images/logo.png">
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&family=Roboto:wght@300;400;500&display=swap" rel="stylesheet">
    
    <!-- DataTables CSS (opcional, para tablas avanzadas) -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.4/css/dataTables.bootstrap5.min.css">
    
    <style>
        :root {
            --primary-color: #2c3e50;
            --secondary-color: #3498db;
            --accent-color: #8e44ad;
            --success-color: #27ae60;
            --warning-color: #f39c12;
            --danger-color: #e74c3c;
            --light-color: #f8f9fa;
            --dark-color: #343a40;
            --book-color: #16a085;
            --member-color: #2980b9;
        }
        
        * {
            font-family: 'Roboto', sans-serif;
        }
        
        body {
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }
        
        h1, h2, h3, h4, h5, h6, .navbar-brand {
            font-family: 'Poppins', sans-serif;
            font-weight: 600;
        }
        
        /* NAVBAR SIMPLIFICADO */
        .navbar {
            background: linear-gradient(135deg, var(--primary-color), #34495e) !important;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
            padding: 12px 0;
            margin-bottom: 25px;
        }
        
        .navbar-brand {
            font-size: 1.5rem;
            font-weight: 700;
            color: white !important;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .navbar-brand i {
            font-size: 1.8rem;
            color: #f1c40f;
        }
        
        .nav-link {
            color: rgba(255,255,255,0.85) !important;
            font-weight: 500;
            padding: 8px 15px !important;
            border-radius: 8px;
            margin: 0 3px;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .nav-link:hover {
            background: rgba(255,255,255,0.15);
            color: white !important;
            transform: translateY(-2px);
        }
        
        .nav-link.active {
            background: var(--book-color);
            color: white !important;
        }
        
        .navbar-toggler {
            border: 2px solid rgba(255,255,255,0.3);
            padding: 5px 10px;
        }
        
        .navbar-toggler:focus {
            box-shadow: 0 0 0 3px rgba(255,255,255,0.3);
        }
        
        /* CONTENIDO PRINCIPAL */
        .container {
            max-width: 1400px;
            padding: 0 20px;
            flex: 1;
        }
        
        /* CARD MEJORADOS */
        .card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.08);
            transition: all 0.3s ease;
            overflow: hidden;
            margin-bottom: 20px;
        }
        
        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 30px rgba(0,0,0,0.15);
        }
        
        .card-header {
            border-bottom: none;
            font-weight: 600;
            background: linear-gradient(135deg, var(--primary-color), #4a6491);
            color: white;
        }
        
        /* BOTONES MEJORADOS */
        .btn {
            border-radius: 10px;
            font-weight: 600;
            padding: 10px 25px;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            border: 2px solid transparent;
        }
        
        .btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        
        .btn-primary {
            background: linear-gradient(135deg, var(--secondary-color), #2980b9);
            border: none;
        }
        
        .btn-success {
            background: linear-gradient(135deg, var(--success-color), #229954);
            border: none;
        }
        
        .btn-warning {
            background: linear-gradient(135deg, var(--warning-color), #d68910);
            border: none;
        }
        
        .btn-danger {
            background: linear-gradient(135deg, var(--danger-color), #c0392b);
            border: none;
        }
        
        .btn-info {
            background: linear-gradient(135deg, var(--book-color), #1abc9c);
            border: none;
        }
        
        /* ESTILOS PARA BIBLIOTECA */
        .book-status {
            padding: 5px 15px;
            border-radius: 20px;
            font-weight: 600;
            font-size: 0.85rem;
        }
        
        .book-available {
            background: #d4edda;
            color: #155724;
        }
        
        .book-borrowed {
            background: #f8d7da;
            color: #721c24;
        }
        
        .book-reserved {
            background: #fff3cd;
            color: #856404;
        }
        
        /* TABLAS MEJORADAS */
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
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .table tbody tr {
            transition: all 0.2s ease;
        }
        
        .table tbody tr:hover {
            background-color: rgba(52, 152, 219, 0.05);
        }
        
        /* FORMULARIOS MEJORADOS */
        .form-control, .form-select {
            border-radius: 10px;
            border: 2px solid #e0e0e0;
            padding: 12px 15px;
            transition: all 0.3s ease;
        }
        
        .form-control:focus, .form-select:focus {
            border-color: var(--secondary-color);
            box-shadow: 0 0 0 3px rgba(52, 152, 219, 0.1);
        }
        
        .form-label {
            font-weight: 600;
            color: var(--primary-color);
            margin-bottom: 8px;
        }
        
        /* DASHBOARD / ESTADÍSTICAS */
        .stats-card {
            text-align: center;
            padding: 25px 15px;
            border: none;
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
        
        /* FOOTER SIMPLIFICADO */
        footer {
            background: linear-gradient(135deg, var(--primary-color), #2c3e50);
            color: white;
            margin-top: 50px;
            padding: 20px 0;
            text-align: center;
            font-size: 0.9rem;
            position: relative;
        }
        
        footer::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, var(--book-color), var(--accent-color));
        }
        
        footer .footer-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 20px;
        }
        
        footer .footer-logo {
            font-size: 1.1rem;
            font-weight: 700;
            color: white;
        }
        
        /* UTILIDADES */
        .gradient-primary {
            background: linear-gradient(135deg, var(--primary-color), #4a6491);
            color: white;
        }
        
        .gradient-book {
            background: linear-gradient(135deg, var(--book-color), #1abc9c);
            color: white;
        }
        
        .shadow-soft {
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
        }
        
        .rounded-lg {
            border-radius: 15px;
        }
        
        /* ANIMACIONES */
        .fade-in {
            animation: fadeIn 0.5s ease;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        /* RESPONSIVE */
        @media (max-width: 768px) {
            .navbar-nav {
                text-align: center;
                padding: 15px 0;
            }
            
            .nav-link {
                margin: 5px 0;
                justify-content: center;
            }
            
            .container {
                padding: 0 15px;
            }
            
            footer .footer-content {
                flex-direction: column;
                text-align: center;
                gap: 15px;
            }
            
            .btn {
                width: 100%;
                margin-bottom: 10px;
            }
            
            .stats-card {
                margin-bottom: 20px;
            }
            
            .table-responsive {
                font-size: 0.9rem;
            }
        }
        
        /* BADGES MEJORADOS */
        .badge {
            border-radius: 20px;
            padding: 6px 12px;
            font-weight: 600;
        }
        
        /* SCROLLBAR PERSONALIZADO */
        ::-webkit-scrollbar {
            width: 8px;
        }
        
        ::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 10px;
        }
        
        ::-webkit-scrollbar-thumb {
            background: var(--book-color);
            border-radius: 10px;
        }
        
        ::-webkit-scrollbar-thumb:hover {
            background: var(--primary-color);
        }
        
        /* LOGO DE NAVBAR */
        .navbar-logo {
            height: 40px;
            width: auto;
            border-radius: 6px;
            object-fit: contain;
            margin-right: 10px;
            border: 2px solid rgba(255,255,255,0.3);
            padding: 2px;
            background: white;
        }
        
        /* ESTILOS PARA BUSQUEDA */
        .search-box {
            position: relative;
        }
        
        .search-box .form-control {
            padding-right: 45px;
        }
        
        .search-box .search-icon {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #6c757d;
        }
        
        /* MENSAJES DEL SISTEMA */
        .alert {
            border-radius: 12px;
            border: none;
            box-shadow: 0 3px 10px rgba(0,0,0,0.08);
            padding: 15px 20px;
        }
        
        .alert-success {
            background: linear-gradient(135deg, #d4edda, #c3e6cb);
            color: #155724;
            border-left: 5px solid var(--success-color);
        }
        
        .alert-warning {
            background: linear-gradient(135deg, #fff3cd, #ffeaa7);
            color: #856404;
            border-left: 5px solid var(--warning-color);
        }
        
        .alert-danger {
            background: linear-gradient(135deg, #f8d7da, #f5c6cb);
            color: #721c24;
            border-left: 5px solid var(--danger-color);
        }
        
        .alert-info {
            background: linear-gradient(135deg, #d1ecf1, #bee5eb);
            color: #0c5460;
            border-left: 5px solid var(--secondary-color);
        }
        
        /* BREADCRUMB (opcional) */
        .breadcrumb {
            background: rgba(0,0,0,0.03);
            border-radius: 10px;
            padding: 10px 15px;
        }
        
        /* PAGINACIÓN */
        .pagination .page-link {
            border-radius: 8px;
            margin: 0 3px;
            border: none;
            color: var(--primary-color);
        }
        
        .pagination .page-item.active .page-link {
            background: var(--book-color);
            color: white;
            border: none;
        }
    </style>
</head>
<body class="fade-in">
    <!-- NAVBAR CON LAS 5 OPCIONES -->
    <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container">
            <a class="navbar-brand" href="menu.php">
                <i class="fas fa-book"></i>
                <span>Biblioteca Iglesia</span>
            </a>
            
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <!-- Menú Principal -->
                    <li class="nav-item">
                        <a class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'menu.php' ? 'active' : '' ?>" 
                           href="menu.php">
                            <i class="fas fa-home"></i> Menú
                        </a>
                    </li>
                    
                    <!-- Agregar Préstamo -->
                    <li class="nav-item">
                        <a class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'agregar_prestamo.php' ? 'active' : '' ?>" 
                           href="agregar_prestamo.php">
                            <i class="fas fa-plus-circle"></i> Agregar Préstamo
                        </a>
                    </li>
                    
                    <!-- Gestionar Préstamos -->
                    <li class="nav-item">
                        <a class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'gestion_prestamo.php' ? 'active' : '' ?>" 
                           href="gestion_prestamo.php">
                            <i class="fas fa-tasks"></i> Gestionar Préstamos
                        </a>
                    </li>
                    
                    <!-- Devolución -->
                    <li class="nav-item">
                        <a class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'devolucion_libro.php' ? 'active' : '' ?>" 
                           href="devolucion_libro.php">
                            <i class="fas fa-exchange-alt"></i> Devolución
                        </a>
                    </li>
                    
                    <!-- Historial -->
                    <li class="nav-item">
                        <a class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'historial_prestamo.php' ? 'active' : '' ?>" 
                           href="historial_prestamo.php">
                            <i class="fas fa-history"></i> Historial
                        </a>
                    </li>
                    
                    <!-- Catálogo -->
                    <li class="nav-item">
                        <a class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'catalogo_libros.php' ? 'active' : '' ?>" 
                           href="catalogo_libros.php">
                            <i class="fas fa-book-open"></i> Catálogo
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- CONTENIDO PRINCIPAL -->
    <main class="container">
        <!-- Mensajes del sistema (opcional) -->
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
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="h2 mb-0">
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
        
        <!-- Breadcrumb (opcional) -->
        <?php if (!empty($breadcrumb)): ?>
        <nav aria-label="breadcrumb" class="mb-4">
            <ol class="breadcrumb">
                <?= $breadcrumb ?>
            </ol>
        </nav>
        <?php endif; ?>
        
        <!-- Contenido dinámico -->
        <div class="mb-5">
            <?= $contenido ?? '<div class="alert alert-warning">Contenido no disponible.</div>' ?>
        </div>
    </main>

    <!-- FOOTER SIMPLIFICADO -->
    <footer>
        <div class="container">
            <div class="footer-content">
                <div class="footer-logo">
                    <i class="fas fa-church me-2"></i>Sistema de Biblioteca Local
                </div>
                <div class="footer-copy">
                    Control Local &copy; <?= date('Y') ?> - Iglesia
                </div>
            </div>
        </div>
    </footer>

<!-- jQuery DEBE estar en el head, no al final -->
<script src="https://code.jquery.com/jquery-3.6.4.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<!-- DataTables JS (opcional) -->
<script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.4/js/dataTables.bootstrap5.min.js"></script>
    
    <!-- Scripts adicionales -->
    <script>
        // Auto-ocultar alertas después de 5 segundos
        document.addEventListener('DOMContentLoaded', function() {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(alert => {
                setTimeout(() => {
                    const bsAlert = new bootstrap.Alert(alert);
                    bsAlert.close();
                }, 5000);
            });
            
            // Activar tooltips
            const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
            const tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
                return new bootstrap.Tooltip(tooltipTriggerEl);
            });
            
            // Inicializar DataTables si hay tablas con la clase 'data-table'
            if ($('.data-table').length) {
                $('.data-table').DataTable({
                    language: {
                        url: '//cdn.datatables.net/plug-ins/1.13.4/i18n/es-ES.json'
                    },
                    responsive: true,
                    pageLength: 25,
                    order: [[0, 'desc']]
                });
            }
            
            // Confirmación para acciones peligrosas
            document.querySelectorAll('.confirm-action').forEach(button => {
                button.addEventListener('click', function(e) {
                    if (!confirm('¿Está seguro de realizar esta acción?')) {
                        e.preventDefault();
                    }
                });
            });
            
            // Calcular fecha de devolución automática (15 días por defecto)
            if (document.getElementById('fecha_prestamo')) {
                document.getElementById('fecha_prestamo').addEventListener('change', function() {
                    const fechaPrestamo = new Date(this.value);
                    if (!isNaN(fechaPrestamo.getTime())) {
                        const fechaDevolucion = new Date(fechaPrestamo);
                        fechaDevolucion.setDate(fechaDevolucion.getDate() + 15);
                        
                        const fechaDevolucionInput = document.getElementById('fecha_devolucion_estimada');
                        if (fechaDevolucionInput) {
                            fechaDevolucionInput.value = fechaDevolucion.toISOString().split('T')[0];
                        }
                    }
                });
            }
        });
        
        // Validación de formularios
        (function () {
            'use strict'
            var forms = document.querySelectorAll('.needs-validation')
            Array.prototype.slice.call(forms).forEach(function (form) {
                form.addEventListener('submit', function (event) {
                    if (!form.checkValidity()) {
                        event.preventDefault()
                        event.stopPropagation()
                    }
                    form.classList.add('was-validated')
                }, false)
            })
        })()
        
        // Función para imprimir tablas
        function imprimirTabla(tablaId, titulo = 'Reporte') {
            const printWindow = window.open('', '_blank');
            printWindow.document.write('<html><head><title>' + titulo + '</title>');
            printWindow.document.write('<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">');
            printWindow.document.write('<style>body{padding:20px;} @media print{body{-webkit-print-color-adjust:exact;}}</style>');
            printWindow.document.write('</head><body>');
            printWindow.document.write('<h4 class="mb-4">' + titulo + '</h4>');
            printWindow.document.write(document.getElementById(tablaId).outerHTML);
            printWindow.document.write('</body></html>');
            printWindow.document.close();
            printWindow.print();
        }
        
        // Función para exportar a Excel (simplificada)
        function exportarExcel(tablaId, nombreArchivo = 'reporte') {
            const tabla = document.getElementById(tablaId);
            const html = tabla.outerHTML;
            const blob = new Blob([html], {type: 'application/vnd.ms-excel'});
            const url = URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = nombreArchivo + '.xls';
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);
            URL.revokeObjectURL(url);
        }
    </script>
</body>
</html>