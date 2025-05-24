<?php
require_once '../includes/config.php';

// Verificar que se ha proporcionado un ID válido
if (!isset($_GET['id']) || empty($_GET['id'])) {
    redireccionar('tickets.php');
}

$ticket_id = (int)$_GET['id'];
$mensaje = '';
$tipo_mensaje = '';

// Conexión a la base de datos
$db = conectarDB();

// Procesar cambio de estado si se envía el formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cambiar_estado'])) {
    $nuevo_estado = limpiarDato($_POST['nuevo_estado']);
    
    // Actualizar el estado del ticket
    $stmt_update = $db->prepare("UPDATE tickets SET status = ? WHERE id = ?");
    $stmt_update->bind_param("si", $nuevo_estado, $ticket_id);
    
    if ($stmt_update->execute()) {
        // Enviar notificación por email al cliente
        $query_email = "SELECT email FROM tickets WHERE id = ?";
        $stmt_email = $db->prepare($query_email);
        $stmt_email->bind_param("i", $ticket_id);
        $stmt_email->execute();
        $result_email = $stmt_email->get_result();
        
        if ($result_email->num_rows === 1) {
            $email_cliente = $result_email->fetch_assoc()['email'];
            
            $asunto = "Actualización de ticket #$ticket_id - " . SITE_NAME;
            $mensaje_cliente = "
                <html>
                <head>
                    <title>Actualización de Ticket</title>
                </head>
                <body>
                    <h2>Actualización de Ticket #$ticket_id</h2>
                    <p>El estado de tu ticket ha sido actualizado a: <strong>" . ucfirst($nuevo_estado) . "</strong></p>
                    <p>Gracias por contactarnos.</p>
                    <p>Equipo de Soporte<br>" . SITE_NAME . "</p>
                </body>
                </html>
            ";
            
            enviarEmail($email_cliente, $asunto, $mensaje_cliente);
        }
        
        $stmt_email->close();
        
        $mensaje = "Estado del ticket actualizado correctamente.";
        $tipo_mensaje = "success";
    } else {
        $mensaje = "Error al actualizar el estado del ticket.";
        $tipo_mensaje = "danger";
    }
    
    $stmt_update->close();
}

// Obtener detalles completos del ticket
$query = "SELECT t.*, c.name as category_name 
          FROM tickets t 
          INNER JOIN categories c ON t.category_id = c.id 
          WHERE t.id = ?";
$stmt = $db->prepare($query);
$stmt->bind_param("i", $ticket_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    // El ticket no existe
    $stmt->close();
    $db->close();
    redireccionar('tickets.php');
}

$ticket = $result->fetch_assoc();

// Obtener adjuntos del ticket
$query_adjuntos = "SELECT * FROM ticket_attachments WHERE ticket_id = ?";
$stmt_adjuntos = $db->prepare($query_adjuntos);
$stmt_adjuntos->bind_param("i", $ticket_id);
$stmt_adjuntos->execute();
$result_adjuntos = $stmt_adjuntos->get_result();

// Variables para el header
$titulo_pagina = "Ticket #$ticket_id";
$pagina_actual = 'tickets';

// Incluir el encabezado
require_once 'includes/header.php';
?>

<div class="row mb-3">
    <div class="col-12">
        <a href="tickets.php" class="btn btn-outline-secondary">
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
                <h5 class="mb-0">Detalles del Ticket</h5>
                <span class="badge bg-<?php echo $ticket['status'] === 'pendiente' ? 'warning' : 'success'; ?>">
                    <?php echo ucfirst($ticket['status']); ?>
                </span>
            </div>
            <div class="card-body">
                <div class="row mb-3">
                    <div class="col-md-6">
                        <p><strong>ID:</strong> <?php echo $ticket['id']; ?></p>
                        <p><strong>Categoría:</strong> <?php echo $ticket['category_name']; ?></p>
                        <p><strong>Tipo de problema:</strong> <?php echo $ticket['problem_type']; ?></p>
                        <?php if (!empty($ticket['content_name'])): ?>
                            <p><strong>Contenido:</strong> <?php echo $ticket['content_name']; ?></p>
                        <?php endif; ?>
                    </div>
                    <div class="col-md-6">
                        <p><strong>Email:</strong> <a href="mailto:<?php echo $ticket['email']; ?>"><?php echo $ticket['email']; ?></a></p>
                        <?php if (!empty($ticket['device_type'])): ?>
                            <p><strong>Dispositivo:</strong> <?php echo $ticket['device_type']; ?></p>
                        <?php endif; ?>
                        <p><strong>Fecha:</strong> <?php echo date('d/m/Y H:i', strtotime($ticket['created_at'])); ?></p>
                    </div>
                </div>
                
                <?php if (!empty($ticket['description'])): ?>
                    <div class="row mb-3">
                        <div class="col-12">
                            <h6 class="fw-bold">Descripción</h6>
                            <div class="border rounded p-3 bg-light">
                                <?php echo nl2br($ticket['description']); ?>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
                
                <?php if ($result_adjuntos->num_rows > 0): ?>
                    <div class="row mb-3">
                        <div class="col-12">
                            <h6 class="fw-bold">Adjuntos</h6>
                            <div class="d-flex flex-wrap gap-2">
                                <?php while ($adjunto = $result_adjuntos->fetch_assoc()): ?>
                                    <div class="card" style="width: 200px;">
                                        <img src="../<?php echo $adjunto['file_path']; ?>" class="card-img-top" alt="Adjunto">
                                        <div class="card-body p-2 text-center">
                                            <a href="../<?php echo $adjunto['file_path']; ?>" class="btn btn-sm btn-primary" target="_blank">
                                                Ver completo
                                            </a>
                                        </div>
                                    </div>
                                <?php endwhile; ?>
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
                            <option value="pendiente" <?php echo $ticket['status'] === 'pendiente' ? 'selected' : ''; ?>>Pendiente</option>
                            <option value="resuelto" <?php echo $ticket['status'] === 'resuelto' ? 'selected' : ''; ?>>Resuelto</option>
                        </select>
                    </div>
                    <button type="submit" name="cambiar_estado" class="btn btn-primary w-100">Actualizar Estado</button>
                </form>
                
                <hr>
                
                <div class="d-grid gap-2">
                    <a href="mailto:<?php echo $ticket['email']; ?>" class="btn btn-outline-secondary">
                        <i class="bi bi-envelope"></i> Enviar Email
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
// Cerrar recursos
$stmt->close();
$stmt_adjuntos->close();
$db->close();

// Incluir el footer
require_once 'includes/footer.php';
?> 