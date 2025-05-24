<?php
// Verificar que el usuario está logueado
require_once __DIR__ . '/../../includes/config.php'; // Corregir ruta de inclusión
if (!esAdmin()) {
    redireccionar('admin/index.php');
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $titulo_pagina ?? 'Panel de Administración'; ?> - <?php echo SITE_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="../css/style.css">
    <style>
        :root {
            --primary-gradient: linear-gradient(135deg, #6a11cb 0%, #2575fc 100%);
            --secondary-gradient: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
            --green-gradient: linear-gradient(135deg, #43a047 0%, #7cb342 100%);
            --danger-gradient: linear-gradient(135deg, #ff416c 0%, #ff4b2b 100%);
            --warning-gradient: linear-gradient(135deg, #ff9a44 0%, #fc6076 100%);
            --sidebar-bg: linear-gradient(180deg, #3a46d1 0%, #2575fc 100%);
        }
        
        body {
            background-color: #f8f9fd;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .admin-wrapper {
            display: flex;
            min-height: 100vh;
        }
        
        .sidebar {
            width: 250px;
            background: var(--sidebar-bg);
            color: white;
            transition: all 0.3s;
            position: fixed;
            top: 0;
            left: 0;
            height: 100vh;
            z-index: 1000;
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.15);
        }
        
        .sidebar .brand {
            padding: 20px 25px;
            font-size: 1.5rem;
            font-weight: 600;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .sidebar .menu {
            padding: 20px 0;
        }
        
        .sidebar .menu-item {
            padding: 10px 25px;
            display: flex;
            align-items: center;
            transition: all 0.3s;
            color: rgba(255, 255, 255, 0.8);
            text-decoration: none;
        }
        
        .sidebar .menu-item:hover,
        .sidebar .menu-item.active {
            background: rgba(255, 255, 255, 0.1);
            color: white;
        }
        
        .sidebar .menu-item i {
            margin-right: 10px;
            font-size: 1.1rem;
        }
        
        .sidebar .user-info {
            position: absolute;
            bottom: 0;
            width: 100%;
            padding: 15px;
            background: rgba(0, 0, 0, 0.1);
            display: flex;
            align-items: center;
        }
        
        .sidebar .user-info .user-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: white;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 10px;
        }
        
        .content-wrapper {
            margin-left: 250px;
            width: calc(100% - 250px);
            min-height: 100vh;
            padding: 20px;
        }
        
        .top-bar {
            background: white;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.05);
            padding: 15px 25px;
            border-radius: 10px;
            margin-bottom: 25px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .top-bar h2 {
            margin: 0;
            font-size: 1.5rem;
            font-weight: 600;
        }
        
        .stat-card {
            padding: 20px;
            border-radius: 10px;
            color: white;
            margin-bottom: 20px;
            position: relative;
            overflow: hidden;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        }
        
        .stat-card.primary {
            background: var(--primary-gradient);
        }
        
        .stat-card.secondary {
            background: var(--secondary-gradient);
        }
        
        .stat-card.green {
            background: var(--green-gradient);
        }
        
        .stat-card.danger {
            background: var(--danger-gradient);
        }
        
        .stat-card h3 {
            font-size: 1.1rem;
            font-weight: 500;
            margin-bottom: 15px;
        }
        
        .stat-card .value {
            font-size: 2rem;
            font-weight: 600;
        }
        
        .stat-card .icon {
            position: absolute;
            top: 20px;
            right: 20px;
            font-size: 2.5rem;
            opacity: 0.3;
        }
        
        .progress-bar-custom {
            height: 6px;
            border-radius: 3px;
            margin-top: 15px;
            background: rgba(255, 255, 255, 0.3);
            overflow: hidden;
        }
        
        .progress-bar-custom .progress {
            height: 100%;
            background: rgba(255, 255, 255, 0.8);
        }
        
        .card {
            border: none;
            border-radius: 10px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
            margin-bottom: 20px;
        }
        
        .card .card-header {
            background: white;
            border-bottom: 1px solid rgba(0, 0, 0, 0.05);
            padding: 15px 20px;
            font-weight: 600;
        }
        
        .card .card-body {
            padding: 20px;
        }
        
        .badge {
            padding: 6px 10px;
            font-weight: 500;
        }
        
        .table > :not(caption) > * > * {
            padding: 12px 15px;
        }
        
        .btn-gradient-primary {
            background: var(--primary-gradient);
            border: none;
            color: white;
        }
        
        .btn-gradient-secondary {
            background: var(--secondary-gradient);
            border: none;
            color: white;
        }
        
        .btn-gradient-success {
            background: var(--green-gradient);
            border: none;
            color: white;
        }
        
        .btn-gradient-danger {
            background: var(--danger-gradient);
            border: none;
            color: white;
        }
    </style>
</head>
<body>
    <div class="admin-wrapper">
        <div class="sidebar">
            <div class="brand">
                <?php echo SITE_NAME; ?>
            </div>
            <div class="menu">
                <a href="dashboard.php" class="menu-item <?php echo $pagina_actual === 'dashboard' ? 'active' : ''; ?>">
                    <i class="bi bi-speedometer2"></i> Dashboard
                </a>
                <a href="tickets.php" class="menu-item <?php echo $pagina_actual === 'tickets' ? 'active' : ''; ?>">
                    <i class="bi bi-ticket-perforated"></i> Tickets
                </a>
                <a href="content-requests.php" class="menu-item <?php echo $pagina_actual === 'content_requests' ? 'active' : ''; ?>">
                    <i class="bi bi-collection-play"></i> Solicitudes
                </a>
            </div>
            <div class="user-info">
                <div class="user-avatar">
                    <i class="bi bi-person-fill"></i>
                </div>
                <div>
                    <div class="user-name"><?php echo $_SESSION['admin_name']; ?></div>
                    <a href="logout.php" class="text-white-50 small">Cerrar sesión <i class="bi bi-box-arrow-right"></i></a>
                </div>
            </div>
        </div>
        
        <div class="content-wrapper">
            <div class="top-bar">
                <h2>
                    <button id="sidebarToggle" class="btn d-md-none me-2">
                        <i class="bi bi-list fs-5"></i>
                    </button>
                    <?php echo $titulo_pagina ?? 'Panel de Administración'; ?>
                </h2>
                <div>
                    <span class="text-muted"><?php echo date('d/m/Y'); ?></span>
                </div>
            </div>
        
</body>
</html>