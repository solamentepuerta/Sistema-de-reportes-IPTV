<?php
require_once '../includes/config.php';

// Verificar que se ha proporcionado un ID válido
if (!isset($_GET['id']) || empty($_GET['id'])) {
    redireccionar('content-requests.php');
}

$solicitud_id = (int)$_GET['id'];
$mensaje = '';
$tipo_mensaje = '';

// Conexión a la base de datos
$db = conectarDB();

// Procesar cambio de estado si se envía el formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cambiar_estado'])) {
    $nuevo_estado = limpiarDato($_POST['nuevo_estado']);
    
    // Actualizar el estado de la solicitud
    $stmt_update = $db->prepare("UPDATE content_requests SET status = ? WHERE id = ?");
    $stmt_update->bind_param("si", $nuevo_estado, $solicitud_id);
    
    if ($stmt_update->execute()) {
        // Enviar notificación por email al cliente si proporcionó correo y el contenido está disponible
        if ($nuevo_estado === 'completado') {
            $query_email = "SELECT email, title, type FROM content_requests WHERE id = ? AND email IS NOT NULL AND email != ''";
            $stmt_email = $db->prepare($query_email);
            $stmt_email->bind_param("i", $solicitud_id);
            $stmt_email->execute();
            $result_email = $stmt_email->get_result();
            
            if ($result_email->num_rows === 1) {
                $datos = $result_email->fetch_assoc();
                $email_cliente = $datos['email'];
                $titulo = $datos['title'];
                $tipo = ucfirst($datos['type']);
                
                $asunto = "$tipo solicitado ya disponible - " . SITE_NAME;
                $mensaje_cliente = "
                    <html>
                    <head>
                        <title>Contenido ya disponible</title>
                    </head>
                    <body>
                        <h2>¡Buenas noticias!</h2>
                        <p>El contenido que solicitaste ya está disponible en nuestra plataforma:</p>
                        <p><strong>$tipo:</strong> $titulo</p>
                        <p>Ya puedes disfrutarlo iniciando sesión en nuestra aplicación.</p>
                        <p>Gracias por ayudarnos a mejorar nuestro catálogo.</p>
                        <p>Equipo de Contenidos<br>" . SITE_NAME . "</p>
                    </body>
                    </html>
                ";
                
                enviarEmail($email_cliente, $asunto, $mensaje_cliente);
            }
            
            $stmt_email->close();
        }
        
        $mensaje = "Estado de la solicitud actualizado correctamente.";
        $tipo_mensaje = "success";
    } else {
        $mensaje = "Error al actualizar el estado de la solicitud.";
        $tipo_mensaje = "danger";
    }
    
    $stmt_update->close();
}

// Obtener detalles completos de la solicitud
$query = "SELECT * FROM content_requests WHERE id = ?";
$stmt = $db->prepare($query);
$stmt->bind_param("i", $solicitud_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    // La solicitud no existe
    $stmt->close();
    $db->close();
    redireccionar('content-requests.php');
}

$solicitud = $result->fetch_assoc();

// Variables para el header
$titulo_pagina = "Solicitud #$solicitud_id";
$pagina_actual = 'content_requests';

// Incluir el encabezado
require_once 'includes/header.php';
?>

<div class="row mb-3">
    <div class="col-12">
        <a href="content-requests.php" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left"></i> Volver a la lista
        </a>
    </div>
</div>

<?php if (!empty($mensaje)): ?>
    <div class="row mb-3">
        <div class="col-12">
            <div class="alert alert-<?php echo $tipo_mensaje; ?>" role="alert">
                <?php echo $mensaje; ?>
            </div>
        </div>
    </div>
<?php endif; ?>

<div class="row">
    <div class="col-md-8">
        <div class="card mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Detalles de la Solicitud</h5>
                <?php 
                $color = '';
                switch ($solicitud['status']) {
                    case 'pendiente':
                        $color = 'warning';
                        break;
                    case 'completado':
                        $color = 'success';
                        break;
                    case 'rechazado':
                        $color = 'danger';
                        break;
                }
                ?>
                <span class="badge bg-<?php echo $color; ?>">
                    <?php echo ucfirst($solicitud['status']); ?>
                </span>
            </div>
            <div class="card-body">
                <div class="row mb-3">
                    <div class="col-md-6">
                        <p><strong>ID:</strong> <?php echo $solicitud['id']; ?></p>
                        <p><strong>Título:</strong> <?php echo $solicitud['title']; ?></p>
                        <p><strong>Tipo:</strong> <?php echo ucfirst($solicitud['type']); ?></p>
                        <?php if (!empty($solicitud['year'])): ?>
                            <p><strong>Año:</strong> <?php echo $solicitud['year']; ?></p>
                        <?php endif; ?>
                    </div>
                    <div class="col-md-6">
                        <?php if (!empty($solicitud['email'])): ?>
                            <p><strong>Email:</strong> <a href="mailto:<?php echo $solicitud['email']; ?>"><?php echo $solicitud['email']; ?></a></p>
                        <?php endif; ?>
                        <p><strong>Fecha de solicitud:</strong> <?php echo date('d/m/Y H:i', strtotime($solicitud['created_at'])); ?></p>
                    </div>
                </div>
                
                <?php if (!empty($solicitud['cover_image'])): ?>
                    <div class="row mb-3">
                        <div class="col-12">
                            <h6 class="fw-bold">Imagen / Carátula</h6>
                            <div class="text-center mb-2">
                                <img src="<?php echo '../' . $solicitud['cover_image']; ?>" alt="Carátula de <?php echo htmlspecialchars($solicitud['title']); ?>" class="img-fluid img-thumbnail" style="max-height: 300px;">
                            </div>
                            <div class="d-grid gap-2">
                                <a href="<?php echo '../' . $solicitud['cover_image']; ?>" class="btn btn-sm btn-outline-primary" target="_blank">
                                    <i class="bi bi-eye"></i> Ver imagen completa
                                </a>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
                
                <?php if (!empty($solicitud['notes'])): ?>
                    <div class="row mb-3">
                        <div class="col-12">
                            <h6 class="fw-bold">Notas adicionales</h6>
                            <div class="border rounded p-3 bg-light">
                                <?php echo nl2br($solicitud['notes']); ?>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <div class="col-md-4">
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0">Acciones</h5>
            </div>
            <div class="card-body">
                <form method="post" action="">
                    <div class="mb-3">
                        <label for="nuevo_estado" class="form-label">Cambiar Estado</label>
                        <select class="form-select" id="nuevo_estado" name="nuevo_estado">
                            <option value="pendiente" <?php echo $solicitud['status'] === 'pendiente' ? 'selected' : ''; ?>>Pendiente</option>
                            <option value="completado" <?php echo $solicitud['status'] === 'completado' ? 'selected' : ''; ?>>Completado</option>
                            <option value="rechazado" <?php echo $solicitud['status'] === 'rechazado' ? 'selected' : ''; ?>>Rechazado</option>
                        </select>
                    </div>
                    <button type="submit" name="cambiar_estado" class="btn btn-primary w-100">Actualizar Estado</button>
                </form>
                
                <?php if (!empty($solicitud['email'])): ?>
                    <hr>
                    
                    <div class="d-grid gap-2">
                        <a href="mailto:<?php echo $solicitud['email']; ?>" class="btn btn-outline-secondary">
                            <i class="bi bi-envelope"></i> Enviar Email
                        </a>
                    </div>
                <?php endif; ?>
                
                <hr>
                
                <div class="d-grid gap-2">
                    <a href="https://www.themoviedb.org/search?query=<?php echo urlencode($solicitud['title']); ?>" class="btn btn-outline-info" target="_blank">
                        <i class="bi bi-search"></i> Buscar en TMDB
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
// Cerrar recursos
$stmt->close();
$db->close();

// Incluir el footer
require_once 'includes/footer.php';
?> 