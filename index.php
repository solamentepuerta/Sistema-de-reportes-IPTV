<?php
require_once 'includes/config.php';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Soporte Técnico - <?php echo SITE_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="css/style.css">
    <style>
        :root {
            --primary-color: #22c55e;
            --primary-gradient: linear-gradient(135deg, #16a34a 0%, #22c55e 100%);
            --secondary-gradient: linear-gradient(135deg, #10b981 0%, #22d3ee 100%);
        }
        
        body {
            background-color: #f8f9fa;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            color: #333;
        }
        
        .app-container {
            max-width: 1200px;
            margin: 40px auto;
            padding: 30px;
        }
        
        .main-title {
            font-weight: 600;
            color: #1e293b;
            margin-bottom: 1rem;
        }
        
        .lead {
            color: #64748b;
            margin-bottom: 2rem;
        }
        
        .card {
            border: none;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        
        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
        }
        
        .card-feature {
            display: flex;
            overflow: hidden;
        }
        
        .card-feature-content {
            padding: 30px;
            flex: 1;
        }
        
        .card-feature-image {
            background: var(--primary-gradient);
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 40px;
            color: white;
            width: 40%;
        }
        
        .card-feature-image.secondary {
            background: var(--secondary-gradient);
        }
        
        .card-feature-image i {
            font-size: 4rem;
            opacity: 0.7;
        }
        
        .card-title {
            color: #1e293b;
            font-weight: 600;
            margin-bottom: 15px;
        }
        
        .card-text {
            color: #64748b;
            margin-bottom: 20px;
        }
        
        .btn-custom-primary {
            background: var(--primary-gradient);
            border: none;
            padding: 10px 20px;
            border-radius: 6px;
            color: white;
            font-weight: 500;
            transition: all 0.3s ease;
        }
        
        .btn-custom-primary:hover {
            opacity: 0.9;
            transform: translateY(-2px);
            color: white;
        }
        
        .btn-custom-secondary {
            background: var(--secondary-gradient);
            border: none;
            padding: 10px 20px;
            border-radius: 6px;
            color: white;
            font-weight: 500;
            transition: all 0.3s ease;
        }
        
        .btn-custom-secondary:hover {
            opacity: 0.9;
            transform: translateY(-2px);
            color: white;
        }
        
        .footer-custom {
            margin-top: 60px;
            padding: 20px 0;
            border-top: 1px solid rgba(0, 0, 0, 0.05);
        }
    </style>
</head>
<body>
    <div class="app-container">
        <div class="text-center mb-5">
            <div class="logo-container">
                <img src="<?php echo !empty(SITE_LOGO) ? SITE_LOGO : 'img/logo.svg'; ?>" alt="<?php echo SITE_NAME; ?> Logo" style="max-width: 220px; width: 100%; height: auto;">
            </div>
            <h1 class="main-title mt-4">Soporte Técnico - <?php echo SITE_NAME; ?></h1>
            <p class="lead">¿Cómo podemos ayudarte hoy?</p>
        </div>
        
        <div class="row justify-content-center g-4">
            <div class="col-lg-10">
                <div class="card card-feature mb-4">
                    <div class="card-feature-content">
                        <h3 class="card-title">Reportar un problema</h3>
                        <p class="card-text">¿Tienes problemas con nuestro servicio? Nuestro equipo está listo para ayudarte. Completa el formulario y recibirás respuesta lo antes posible.</p>
                        <a href="ticket.php" class="btn btn-custom-primary">
                            <i class="bi bi-ticket-perforated me-2"></i>Reportar problema
                        </a>
                    </div>
                    <div class="card-feature-image">
                        <i class="bi bi-headset"></i>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-10">
                <div class="card card-feature mb-4">
                    <div class="card-feature-content">
                        <h3 class="card-title">Solicitar contenido</h3>
                        <p class="card-text">¿No encuentras una película, serie o canal que te interesa? Solicítalo aquí y trabajaremos para agregarlo a nuestra plataforma lo antes posible.</p>
                        <a href="content-request.php" class="btn btn-custom-secondary">
                            <i class="bi bi-collection-play me-2"></i>Solicitar contenido
                        </a>
                    </div>
                    <div class="card-feature-image secondary">
                        <i class="bi bi-film"></i>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="text-center mt-5">
            <p><a href="admin/index.php" class="text-muted small">Acceso administradores</a></p>
        </div>
    </div>

    <footer class="footer-custom">
        <div class="container">
            <div class="text-center">
                <p class="mb-0 text-muted">Desarrollado por <a href="https://profesordepapel.com/" target="_blank" class="text-decoration-none fw-bold">Profesor de Papel</a></p>
            </div>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>