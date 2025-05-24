<?php
require_once 'includes/config.php';

$mensaje = '';
$tipo_mensaje = '';

// Procesar el formulario cuando se envía
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validar y procesar datos
    $categoria_id = isset($_POST['categoria']) ? (int)$_POST['categoria'] : 0;
    $email = limpiarDato($_POST['email']);
    $descripcion = limpiarDato($_POST['descripcion']);
    $problema_tipo = limpiarDato($_POST['problema_tipo']);
    
    // Campos condicionales
    $nombre_contenido = isset($_POST['nombre_contenido']) ? limpiarDato($_POST['nombre_contenido']) : null;
    $dispositivo = isset($_POST['dispositivo']) ? limpiarDato($_POST['dispositivo']) : null;
    
    // Validación básica
    if (empty($categoria_id) || empty($email) || empty($problema_tipo)) {
        $mensaje = 'Por favor, completa todos los campos obligatorios.';
        $tipo_mensaje = 'danger';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $mensaje = 'Por favor, ingresa un correo electrónico válido.';
        $tipo_mensaje = 'danger';
    } else {
        // Conectar a la base de datos
        $db = conectarDB();
        
        // Insertar el ticket
        $stmt = $db->prepare("INSERT INTO tickets (category_id, email, description, problem_type, content_name, device_type) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("isssss", $categoria_id, $email, $descripcion, $problema_tipo, $nombre_contenido, $dispositivo);
        
        if ($stmt->execute()) {
            $ticket_id = $stmt->insert_id;
            
            // Procesar archivo adjunto si existe
            if (isset($_FILES['screenshot']) && $_FILES['screenshot']['error'] === UPLOAD_ERR_OK) {
                $file_name = $_FILES['screenshot']['name'];
                $file_tmp = $_FILES['screenshot']['tmp_name'];
                $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
                
                // Validar extensión
                $allowed_ext = ['jpg', 'jpeg', 'png'];
                if (!in_array($file_ext, $allowed_ext)) {
                    $mensaje = 'Solo se permiten imágenes en formato JPG o PNG.';
                    $tipo_mensaje = 'danger';
                } else if ($_FILES['screenshot']['size'] > 1048576) { // 1MB límite (1048576 bytes)
                    $mensaje = 'La imagen es demasiado grande. El tamaño máximo es 1MB.';
                    $tipo_mensaje = 'danger';
                } else {
                    $nombre_archivo = time() . '_' . basename($_FILES['screenshot']['name']);
                    $ruta_destino = 'uploads/' . $nombre_archivo;
                    
                    if (move_uploaded_file($_FILES['screenshot']['tmp_name'], $ruta_destino)) {
                        // Guardar información del archivo en la base de datos
                        $stmt_archivo = $db->prepare("INSERT INTO ticket_attachments (ticket_id, file_path) VALUES (?, ?)");
                        $stmt_archivo->bind_param("is", $ticket_id, $ruta_destino);
                        $stmt_archivo->execute();
                        $stmt_archivo->close();
                    }
                }
            }
            
            // Enviar email de notificación al administrador
            $asunto = "Nuevo ticket #$ticket_id - " . SITE_NAME;
            $mensaje_admin = "
                <html>
                <head>
                    <title>Nuevo Ticket de Soporte</title>
                </head>
                <body>
                    <h2>Nuevo Ticket de Soporte #$ticket_id</h2>
                    <p>Se ha registrado un nuevo ticket en el sistema:</p>
                    <ul>
                        <li><strong>ID:</strong> $ticket_id</li>
                        <li><strong>Categoría:</strong> $categoria_id</li>
                        <li><strong>Tipo de Problema:</strong> $problema_tipo</li>
                        <li><strong>Correo del cliente:</strong> $email</li>
                    </ul>
                    <p>Para ver los detalles completos, accede al <a href='" . BASE_URL . "/admin/view-ticket.php?id=$ticket_id'>panel de administración</a>.</p>
                </body>
                </html>
            ";
            
            enviarEmail('admin@' . strtolower(preg_replace('/[^a-zA-Z0-9]/', '', SITE_NAME)) . '.com', $asunto, $mensaje_admin);
            
            // Enviar confirmación al cliente
            $asunto_cliente = "Confirmación de ticket #$ticket_id - " . SITE_NAME;
            $mensaje_cliente = "
                <html>
                <head>
                    <title>Confirmación de Ticket</title>
                </head>
                <body>
                    <h2>Tu ticket ha sido registrado</h2>
                    <p>Gracias por contactarnos. Hemos recibido tu reporte y lo estamos procesando.</p>
                    <p><strong>Número de Ticket:</strong> #$ticket_id</p>
                    <p>Te contactaremos a la brevedad posible.</p>
                    <p>Equipo de Soporte<br>" . SITE_NAME . "</p>
                </body>
                </html>
            ";
            
            enviarEmail($email, $asunto_cliente, $mensaje_cliente);
            
            // Mensaje de éxito
            $mensaje = "Tu reporte ha sido enviado exitosamente. Hemos enviado una confirmación a tu correo electrónico.";
            $tipo_mensaje = 'success';
            
            // Limpiar los campos del formulario
            $_POST = array();
        } else {
            $mensaje = "Hubo un problema al enviar tu reporte. Por favor, intenta nuevamente.";
            $tipo_mensaje = 'danger';
        }
        
        $stmt->close();
        $db->close();
    }
}

// Obtener categorías desde la base de datos
$db = conectarDB();
$query = "SELECT id, name FROM categories ORDER BY name";
$result = $db->query($query);
$categorias = $result->fetch_all(MYSQLI_ASSOC);
$db->close();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reportar Problema - <?php echo SITE_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <div class="container mt-5">
        <div class="row mb-4">
            <div class="col-12">
                <a href="index.php" class="btn btn-outline-secondary">&laquo; Volver</a>
            </div>
        </div>
        
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card shadow">
                    <div class="card-header bg-primary text-white">
                        <h3 class="card-title mb-0">Reportar un problema</h3>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($mensaje)): ?>
                            <div class="alert alert-<?php echo $tipo_mensaje; ?>" role="alert">
                                <?php echo $mensaje; ?>
                            </div>
                            <?php if ($tipo_mensaje === 'success'): ?>
                                <div class="text-center mt-4 mb-3">
                                    <a href="index.php" class="btn btn-primary">Volver al inicio</a>
                                </div>
                            <?php endif; ?>
                        <?php endif; ?>
                        
                        <?php if (empty($mensaje) || $tipo_mensaje !== 'success'): ?>
                            <form id="ticketForm" method="post" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" enctype="multipart/form-data">
                                <!-- Paso 1: Categoría -->
                                <div class="form-step active" id="step1">
                                    <h4 class="mb-4">¿Con qué estás teniendo problemas?</h4>
                                    
                                    <div class="mb-4">
                                        <?php foreach ($categorias as $categoria): ?>
                                            <div class="form-check mb-3">
                                                <input class="form-check-input" type="radio" name="categoria" id="categoria<?php echo $categoria['id']; ?>" value="<?php echo $categoria['id']; ?>" required>
                                                <label class="form-check-label" for="categoria<?php echo $categoria['id']; ?>">
                                                    <?php echo $categoria['name']; ?>
                                                </label>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                    
                                    <div class="form-navigation text-end">
                                        <button type="button" class="btn btn-primary" onclick="nextStep(1, 2)">Siguiente</button>
                                    </div>
                                </div>
                                
                                <!-- Paso 2: Detalles específicos según categoría -->
                                <div class="form-step" id="step2">
                                    <h4 class="mb-4">Detalles del problema</h4>
                                    
                                    <!-- Campos para TV en vivo (categoría 1) -->
                                    <div class="category-fields" id="category1Fields" style="display: none;">
                                        <div class="mb-3">
                                            <label for="nombre_contenido_tv" class="form-label">¿Qué canal(es) presentan problemas?</label>
                                            <input type="text" class="form-control" id="nombre_contenido_tv" name="nombre_contenido" disabled>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">¿Qué tipo de problema experimentas?</label>
                                            <select class="form-select" name="problema_tipo" id="problema_tipo_tv" disabled>
                                                <option value="">Selecciona una opción</option>
                                                <option value="Congelamiento/Buffering">Congelamiento/Buffering</option>
                                                <option value="Sin Sonido">Sin Sonido</option>
                                                <option value="Sin Imagen">Sin Imagen</option>
                                                <option value="Error de Carga">Error de Carga</option>
                                                <option value="Calidad Baja">Calidad Baja</option>
                                            </select>
                                        </div>
                                    </div>
                                    
                                    <!-- Campos para Películas/Series (categorías 2 y 3) -->
                                    <div class="category-fields" id="category2Fields" style="display: none;">
                                        <div class="mb-3">
                                            <label for="nombre_pelicula" class="form-label">Nombre de la película</label>
                                            <input type="text" class="form-control" id="nombre_pelicula" name="nombre_contenido" disabled>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">¿Qué tipo de problema experimentas?</label>
                                            <select class="form-select" name="problema_tipo" id="problema_tipo_pelicula" disabled>
                                                <option value="">Selecciona una opción</option>
                                                <option value="No carga">No carga</option>
                                                <option value="Problema de Audio/Subtítulos">Problema de Audio/Subtítulos</option>
                                                <option value="Error de Reproducción">Error de Reproducción</option>
                                                <option value="Calidad Baja">Calidad Baja</option>
                                            </select>
                                        </div>
                                    </div>
                                    
                                    <div class="category-fields" id="category3Fields" style="display: none;">
                                        <div class="mb-3">
                                            <label for="nombre_serie" class="form-label">Nombre de la serie</label>
                                            <input type="text" class="form-control" id="nombre_serie" name="nombre_contenido" disabled>
                                        </div>
                                        <div class="mb-3">
                                            <label for="temporada_episodio" class="form-label">Temporada/Episodio (opcional)</label>
                                            <input type="text" class="form-control" id="temporada_episodio" name="temporada_episodio" placeholder="Ej: Temporada 2, Episodio 5" disabled>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">¿Qué tipo de problema experimentas?</label>
                                            <select class="form-select" name="problema_tipo" id="problema_tipo_serie" disabled>
                                                <option value="">Selecciona una opción</option>
                                                <option value="No carga">No carga</option>
                                                <option value="Problema de Audio/Subtítulos">Problema de Audio/Subtítulos</option>
                                                <option value="Error de Reproducción">Error de Reproducción</option>
                                                <option value="Calidad Baja">Calidad Baja</option>
                                            </select>
                                        </div>
                                    </div>
                                    
                                    <!-- Campos para Acceso/Login (categoría 4) -->
                                    <div class="category-fields" id="category4Fields" style="display: none;">
                                        <div class="mb-3">
                                            <label class="form-label">¿Qué error recibes?</label>
                                            <select class="form-select" name="problema_tipo" id="problema_tipo_acceso" disabled>
                                                <option value="">Selecciona una opción</option>
                                                <option value="Contraseña incorrecta">Contraseña incorrecta</option>
                                                <option value="Usuario no encontrado">Usuario no encontrado</option>
                                                <option value="No puedo registrarme">No puedo registrarme</option>
                                                <option value="La app no abre">La app no abre</option>
                                            </select>
                                        </div>
                                    </div>
                                    
                                    <!-- Campos para Problema de Aplicación (categoría 5) -->
                                    <div class="category-fields" id="category5Fields" style="display: none;">
                                        <div class="mb-3">
                                            <label class="form-label">¿En qué dispositivo ocurre?</label>
                                            <select class="form-select" name="dispositivo" id="dispositivo" disabled>
                                                <option value="">Selecciona una opción</option>
                                                <option value="Smart TV">Smart TV</option>
                                                <option value="Móvil Android">Móvil Android</option>
                                                <option value="iPhone/iPad">iPhone/iPad</option>
                                                <option value="Web">Web</option>
                                                <option value="TV Box">TV Box</option>
                                            </select>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">¿Qué comportamiento presenta?</label>
                                            <select class="form-select" name="problema_tipo" id="problema_tipo_app" disabled>
                                                <option value="">Selecciona una opción</option>
                                                <option value="Se cierra sola">Se cierra sola</option>
                                                <option value="Va lenta">Va lenta</option>
                                                <option value="Botones no responden">Botones no responden</option>
                                                <option value="Error en la interfaz">Error en la interfaz</option>
                                            </select>
                                        </div>
                                    </div>
                                    
                                    <!-- Campos para Otro (categoría 6) -->
                                    <div class="category-fields" id="category6Fields" style="display: none;">
                                        <div class="mb-3">
                                            <label for="problema_tipo_otro" class="form-label">Tipo de problema</label>
                                            <input type="text" class="form-control" id="problema_tipo_otro" name="problema_tipo" placeholder="Ej: Problema con facturación" disabled>
                                        </div>
                                    </div>
                                    
                                    <div class="form-navigation">
                                        <button type="button" class="btn btn-secondary" onclick="prevStep(2, 1)">Anterior</button>
                                        <button type="button" class="btn btn-primary float-end" onclick="nextStep(2, 3)">Siguiente</button>
                                    </div>
                                </div>
                                
                                <!-- Paso 3: Detalles finales -->
                                <div class="form-step" id="step3">
                                    <h4 class="mb-4">Detalles adicionales</h4>
                                    
                                    <div class="mb-3">
                                        <label for="descripcion" class="form-label">Describe tu problema con más detalle (opcional)</label>
                                        <textarea class="form-control" id="descripcion" name="descripcion" rows="4"></textarea>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="email" class="form-label">Tu dirección de correo electrónico</label>
                                        <input type="email" class="form-control" id="email" name="email" required>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="screenshot" class="form-label">Adjuntar captura de pantalla (opcional)</label>
                                        <input class="form-control" type="file" id="screenshot" name="screenshot" accept="image/jpeg, image/png">
                                        <div class="form-text">Formatos permitidos: JPG, PNG. Tamaño máximo: 1MB.</div>
                                    </div>
                                    
                                    <div class="form-navigation">
                                        <button type="button" class="btn btn-secondary" onclick="prevStep(3, 2)">Anterior</button>
                                        <button type="submit" class="btn btn-success float-end">Enviar reporte</button>
                                    </div>
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
        // Navegación por pasos
        function nextStep(currentStep, nextStep) {
            // Validación básica
            if (currentStep === 1) {
                let categoriaSeleccionada = false;
                let categoriaId = null;
                document.querySelectorAll('input[name="categoria"]').forEach(function(radio) {
                    if (radio.checked) {
                        categoriaSeleccionada = true;
                        categoriaId = radio.value;

                        // Mostrar campos según categoría y habilitarlos/deshabilitarlos
                        document.querySelectorAll('.category-fields').forEach(function(div) {
                            const inputs = div.querySelectorAll('input, select');
                            if (div.id === 'category' + categoriaId + 'Fields') {
                                div.style.display = 'block';
                                inputs.forEach(input => input.disabled = false); // Habilitar campos de la categoría seleccionada
                            } else {
                                div.style.display = 'none';
                                inputs.forEach(input => input.disabled = true); // Deshabilitar campos de otras categorías
                            }
                        });
                    }
                });

                if (!categoriaSeleccionada) {
                    alert('Por favor, selecciona una categoría');
                    return;
                }
            }

            if (currentStep === 2) {
                let categoriaSeleccionada = null;
                document.querySelectorAll('input[name="categoria"]').forEach(function(radio) {
                    if (radio.checked) {
                        categoriaSeleccionada = radio.value;
                    }
                });

                const activeCategoryFields = document.getElementById('category' + categoriaSeleccionada + 'Fields');
                let isStep2Valid = true;

                // Validar campos obligatorios específicos del paso 2 según la categoría activa
                if (activeCategoryFields) {
                    // Validar 'problema_tipo' (puede ser select o input)
                    const problemaTipoField = activeCategoryFields.querySelector('select[name="problema_tipo"], input[name="problema_tipo"]');
                    if (problemaTipoField && problemaTipoField.value.trim() === '') {
                         alert('Por favor, especifica el tipo de problema.');
                         isStep2Valid = false;
                    }

                    // Validar 'nombre_contenido' si es categoría 1, 2 o 3
                    if (['1', '2', '3'].includes(categoriaSeleccionada)) {
                        const nombreContenidoField = activeCategoryFields.querySelector('input[name="nombre_contenido"]');
                        if (nombreContenidoField && nombreContenidoField.value.trim() === '') {
                            alert('Por favor, ingresa el nombre del contenido (canal, película o serie).');
                            isStep2Valid = false;
                        }
                    }

                    // Validar 'dispositivo' si es categoría 5
                    if (categoriaSeleccionada === '5') {
                        const dispositivoField = activeCategoryFields.querySelector('select[name="dispositivo"]');
                        if (dispositivoField && dispositivoField.value === '') {
                            alert('Por favor, selecciona el dispositivo.');
                            isStep2Valid = false;
                        }
                    }
                } else {
                     // Esto no debería pasar si la lógica del paso 1 es correcta
                     console.error("No se encontraron los campos para la categoría seleccionada:", categoriaSeleccionada);
                     isStep2Valid = false;
                }


                if (!isStep2Valid) {
                    return; // Detener si la validación del paso 2 falla
                }
            }

            document.getElementById('step' + currentStep).classList.remove('active');
            document.getElementById('step' + nextStep).classList.add('active');
        }
        
        function prevStep(currentStep, prevStep) {
            document.getElementById('step' + currentStep).classList.remove('active');
            document.getElementById('step' + prevStep).classList.add('active');
        }
    </script>
</body>
</html>