<?php
require_once 'includes/config.php';

$mensaje = '';
$tipo_mensaje = '';

// Procesar el formulario cuando se envía
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validar y procesar datos
    $tipo = limpiarDato($_POST['tipo']);
    $titulo = limpiarDato($_POST['titulo']);
    $anio = isset($_POST['anio']) ? limpiarDato($_POST['anio']) : null;
    $email = limpiarDato($_POST['email']);
    $notas = isset($_POST['notas']) ? limpiarDato($_POST['notas']) : null;
    
    // Validación básica
    if (empty($tipo) || empty($titulo)) {
        $mensaje = 'Por favor, completa todos los campos obligatorios.';
        $tipo_mensaje = 'danger';
    } elseif (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $mensaje = 'Por favor, ingresa un correo electrónico válido.';
        $tipo_mensaje = 'danger';
    } else {
        $imagen_path = null;
        
        // Procesar imagen si se ha subido
        if (!empty($_FILES['imagen']['name'])) {
            $file_name = $_FILES['imagen']['name'];
            $file_tmp = $_FILES['imagen']['tmp_name'];
            $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
            
            // Validar extensión
            $allowed_ext = ['jpg', 'jpeg', 'png'];
            if (!in_array($file_ext, $allowed_ext)) {
                $mensaje = 'Solo se permiten imágenes en formato JPG o PNG.';
                $tipo_mensaje = 'danger';
            } else if ($_FILES['imagen']['size'] > 1048576) { // 1MB límite (1048576 bytes)
                $mensaje = 'La imagen es demasiado grande. El tamaño máximo es 1MB.';
                $tipo_mensaje = 'danger';
            } else {
                // Generar nombre único para evitar sobrescritura
                $unique_name = 'cover_' . time() . '_' . uniqid() . '.' . $file_ext;
                $upload_dir = 'uploads/covers/';
                
                // Crear directorio si no existe
                if (!is_dir($upload_dir)) {
                    mkdir($upload_dir, 0755, true);
                }
                
                $target_file = $upload_dir . $unique_name;
                
                // Subir archivo
                if (move_uploaded_file($file_tmp, $target_file)) {
                    $imagen_path = $target_file;
                } else {
                    $mensaje = 'Error al subir la imagen. Por favor, intenta nuevamente.';
                    $tipo_mensaje = 'danger';
                }
            }
        }
        
        // Si no hay errores con la imagen o no se subió ninguna, continuar con la inserción
        if (empty($mensaje)) {
            // Conectar a la base de datos
            $db = conectarDB();
            
            // Modificar la consulta para incluir el campo de imagen
            $stmt = $db->prepare("INSERT INTO content_requests (type, title, year, email, notes, cover_image) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("ssssss", $tipo, $titulo, $anio, $email, $notas, $imagen_path);
            
            if ($stmt->execute()) {
                $solicitud_id = $stmt->insert_id;
                
                // Si se proporcionó un correo, enviar confirmación
                if (!empty($email)) {
                    $asunto = "Confirmación de solicitud de contenido - " . SITE_NAME;
                    $mensaje_cliente = "
                        <html>
                        <head>
                            <title>Confirmación de Solicitud</title>
                        </head>
                        <body>
                            <h2>Tu solicitud ha sido registrada</h2>
                            <p>Gracias por contactarnos. Hemos recibido tu solicitud de contenido:</p>
                            <p><strong>Contenido solicitado:</strong> $titulo</p>
                            <p>Estamos trabajando para agregar este contenido a nuestra plataforma lo antes posible.</p>
                            <p>Equipo de Contenidos<br>" . SITE_NAME . "</p>
                        </body>
                        </html>
                    ";
                    
                    enviarEmail($email, $asunto, $mensaje_cliente);
                }
                
                // Mensaje de texto sobre imagen para la notificación
                $imagen_texto = !empty($imagen_path) ? "Sí (Ver en panel de administración)" : "No proporcionada";
                
                // Enviar notificación al administrador
                $asunto_admin = "Nueva solicitud de contenido - " . SITE_NAME;
                $mensaje_admin = "
                    <html>
                    <head>
                        <title>Nueva Solicitud de Contenido</title>
                    </head>
                    <body>
                        <h2>Nueva Solicitud de Contenido</h2>
                        <p>Se ha registrado una nueva solicitud en el sistema:</p>
                        <ul>
                            <li><strong>ID:</strong> $solicitud_id</li>
                            <li><strong>Tipo:</strong> $tipo</li>
                            <li><strong>Título:</strong> $titulo</li>
                            <li><strong>Año:</strong> " . ($anio ?: 'No especificado') . "</li>
                            <li><strong>Correo del cliente:</strong> " . ($email ?: 'No proporcionado') . "</li>
                            <li><strong>Imagen/Carátula:</strong> " . $imagen_texto . "</li>
                        </ul>
                        <p>Para ver los detalles completos, accede al <a href='" . BASE_URL . "admin/content-requests.php'>panel de administración</a>.</p>
                    </body>
                    </html>
                ";
                
                enviarEmail('admin@' . strtolower(preg_replace('/[^a-zA-Z0-9]/', '', SITE_NAME)) . '.com', $asunto_admin, $mensaje_admin);
                
                // Mensaje de éxito
                $mensaje = "Tu solicitud ha sido enviada exitosamente. Gracias por ayudarnos a mejorar nuestro catálogo.";
                $tipo_mensaje = 'success';
                
                // Limpiar los campos del formulario
                $_POST = array();
            } else {
                $mensaje = "Hubo un problema al enviar tu solicitud. Por favor, intenta nuevamente.";
                $tipo_mensaje = 'danger';
            }
            
            $stmt->close();
            $db->close();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Solicitar Contenido - <?php echo SITE_NAME; ?></title>
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
            max-width: 1100px;
            margin: 40px auto;
            padding: 0 20px;
        }
        
        .page-header {
            margin-bottom: 30px;
        }
        
        .btn-back {
            color: #64748b;
            text-decoration: none;
            display: flex;
            align-items: center;
            margin-bottom: 20px;
            font-weight: 500;
            transition: all 0.3s ease;
        }
        
        .btn-back:hover {
            color: var(--primary-color);
        }
        
        .btn-back i {
            margin-right: 6px;
        }
        
        .page-title {
            font-size: 1.8rem;
            font-weight: 600;
            color: #1e293b;
            margin-bottom: 5px;
        }
        
        .page-subtitle {
            color: #64748b;
            font-size: 1.1rem;
        }
        
        .form-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05);
            overflow: hidden;
            border: none;
        }
        
        .form-card .card-header {
            background: var(--primary-gradient);
            padding: 20px 30px;
            border: none;
        }
        
        .form-card .card-header h3 {
            color: white;
            font-weight: 600;
            margin: 0;
            font-size: 1.4rem;
        }
        
        .form-card .card-body {
            padding: 30px;
        }
        
        .form-label {
            font-weight: 500;
            color: #475569;
            margin-bottom: 8px;
        }
        
        .form-control {
            padding: 12px 15px;
            border-radius: 8px;
            border: 1px solid #e2e8f0;
            background-color: #f8fafc;
            transition: all 0.3s ease;
        }
        
        .form-control:focus {
            border-color: #22c55e;
            box-shadow: 0 0 0 3px rgba(34, 197, 94, 0.2);
        }
        
        .form-text {
            color: #94a3b8;
            font-size: 0.85rem;
            margin-top: 6px;
        }
        
        .imagen-preview {
            max-width: 100%;
            max-height: 200px;
            display: none;
            margin-top: 15px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }
        
        .form-check-input:checked {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
        }
        
        .form-check-label {
            font-weight: 500;
        }
        
        .btn-submit {
            background: var(--primary-gradient);
            border: none;
            padding: 12px 25px;
            border-radius: 8px;
            color: white;
            font-weight: 500;
            transition: all 0.3s ease;
        }
        
        .btn-submit:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(34, 197, 94, 0.3);
            color: white;
        }
        
        .btn-return {
            background: var(--secondary-gradient);
            border: none;
            padding: 12px 25px;
            border-radius: 8px;
            color: white;
            font-weight: 500;
            transition: all 0.3s ease;
        }
        
        .btn-return:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3);
            color: white;
        }
    </style>
</head>
<body>
    <div class="app-container">
        <div class="page-header">
            <a href="index.php" class="btn-back">
                <i class="bi bi-arrow-left"></i> Volver al inicio
            </a>
            <h1 class="page-title">Solicitar Contenido</h1>
            <p class="page-subtitle">Completa el formulario para solicitar una película, serie o canal que te interese</p>
        </div>
        
        <div class="row justify-content-center">
            <div class="col-md-10">
                <div class="form-card">
                    <div class="card-header">
                        <h3><i class="bi bi-collection-play me-2"></i>Formulario de Solicitud</h3>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($mensaje)): ?>
                            <div class="alert alert-<?php echo $tipo_mensaje; ?> alert-dismissible fade show" role="alert">
                                <?php echo $mensaje; ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            </div>
                            <?php if ($tipo_mensaje === 'success'): ?>
                                <div class="text-center mt-4 mb-3">
                                    <a href="index.php" class="btn btn-return">
                                        <i class="bi bi-house-door me-2"></i>Volver al inicio
                                    </a>
                                </div>
                            <?php endif; ?>
                        <?php endif; ?>
                        
                        <?php if (empty($mensaje) || $tipo_mensaje !== 'success'): ?>
                            <form method="post" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" enctype="multipart/form-data">
                                <div class="mb-4">
                                    <label class="form-label">Tipo de contenido</label>
                                    <div class="d-flex gap-4">
                                        <div class="form-check">
                                            <input class="form-check-input" type="radio" name="tipo" id="tipo_pelicula" value="pelicula" required checked>
                                            <label class="form-check-label" for="tipo_pelicula">
                                                <i class="bi bi-film me-1"></i>Película
                                            </label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="radio" name="tipo" id="tipo_serie" value="serie" required>
                                            <label class="form-check-label" for="tipo_serie">
                                                <i class="bi bi-tv me-1"></i>Serie
                                            </label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="radio" name="tipo" id="tipo_canal" value="canal" required>
                                            <label class="form-check-label" for="tipo_canal">
                                                <i class="bi bi-broadcast me-1"></i>Canal de TV
                                            </label>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="mb-4">
                                    <label for="titulo" class="form-label">Nombre / Título</label>
                                    <input type="text" class="form-control" id="titulo" name="titulo" required placeholder="Ej: The Matrix, Game of Thrones, National Geographic...">
                                </div>
                                
                                <div class="mb-4">
                                    <label for="anio" class="form-label">Año de estreno (opcional)</label>
                                    <input type="text" class="form-control" id="anio" name="anio" placeholder="Ej: 2023">
                                </div>
                                
                                <div class="mb-4">
                                    <label for="imagen" class="form-label">Imagen / Carátula (opcional)</label>
                                    <div class="input-group">
                                        <input type="file" class="form-control" id="imagen" name="imagen" accept="image/jpeg, image/png">
                                    </div>
                                    <div class="form-text">
                                        <i class="bi bi-info-circle me-1"></i>Puedes adjuntar una imagen de la carátula para ayudarnos a identificar mejor el contenido. Formatos permitidos: JPG, PNG. Tamaño máximo: 1MB.
                                    </div>
                                    <img id="preview" class="imagen-preview" src="#" alt="Vista previa de la imagen">
                                </div>
                                
                                <div class="mb-4">
                                    <label for="notas" class="form-label">Detalles adicionales (opcional)</label>
                                    <textarea class="form-control" id="notas" name="notas" rows="3" placeholder="Versión específica, idiomas preferidos, calidad esperada, etc."></textarea>
                                </div>
                                
                                <div class="mb-4">
                                    <label for="email" class="form-label">Tu correo electrónico (opcional)</label>
                                    <div class="input-group">
                                        <span class="input-group-text bg-light border-end-0">
                                            <i class="bi bi-envelope"></i>
                                        </span>
                                        <input type="email" class="form-control border-start-0" id="email" name="email" placeholder="ejemplo@correo.com">
                                    </div>
                                    <div class="form-text">
                                        <i class="bi bi-shield-check me-1"></i>Solo lo usaremos para informarte cuando el contenido solicitado esté disponible.
                                    </div>
                                </div>
                                
                                <div class="text-end mt-4">
                                    <button type="submit" class="btn btn-submit">
                                        <i class="bi bi-send me-2"></i>Enviar solicitud
                                    </button>
                                </div>
                            </form>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Vista previa de la imagen
        document.getElementById('imagen').addEventListener('change', function(e) {
            const preview = document.getElementById('preview');
            const file = e.target.files[0];
            
            if (file) {
                const reader = new FileReader();
                
                reader.onload = function(e) {
                    preview.src = e.target.result;
                    preview.style.display = 'block';
                }
                
                reader.readAsDataURL(file);
            } else {
                preview.style.display = 'none';
            }
        });
    </script>
</body>
</html> 