<?php
// install.php

// Asegurar que la sesión esté iniciada correctamente desde el principio
session_start();

// Flag to check if installation is complete (e.g., by checking for config.php or a lock file)
$installation_complete = file_exists('includes/config.php'); // Basic check, can be improved

if ($installation_complete) {
    // If already installed, redirect to the main page or show a message
    // For now, just exit to prevent access
    die("El sistema ya está instalado.");
    // header('Location: index.php'); // Or your main entry point
    // exit;
}

$errors = [];
$success_message = '';
$current_step = isset($_POST['step']) ? (int)$_POST['step'] : 1;

// --- Auto-detect Base URL ---
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
$host = $_SERVER['HTTP_HOST'];
// Get the directory path of the current script, relative to the document root
$script_dir = dirname($_SERVER['PHP_SELF']);
// Ensure it ends with a slash if it's not the root
$base_path = rtrim($script_dir, '/') . '/';
if ($base_path === '//') { // Handle root installation case
    $base_path = '/';
}
$detected_base_url = $protocol . $host . $base_path;
// Remove install.php from the path if present (it shouldn't be, but just in case)
$detected_base_url = str_replace('install.php', '', $detected_base_url);
// Ensure trailing slash
if (substr($detected_base_url, -1) !== '/') {
    $detected_base_url .= '/';
}

// Definición de la estructura de la base de datos con IF NOT EXISTS para prevenir errores
$database_schema = [
    // Tabla de categorías
    "CREATE TABLE IF NOT EXISTS `categories` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `name` varchar(100) NOT NULL,
        `created_at` timestamp NULL DEFAULT current_timestamp(),
        PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;",

    // Tabla de solicitudes de contenido
    "CREATE TABLE IF NOT EXISTS `content_requests` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `type` enum('pelicula','serie','canal') NOT NULL,
        `title` varchar(255) NOT NULL,
        `year` varchar(10) DEFAULT NULL,
        `email` varchar(255) DEFAULT NULL,
        `notes` text DEFAULT NULL,
        `cover_image` varchar(255) DEFAULT NULL,
        `status` enum('pendiente','completado','rechazado') NOT NULL DEFAULT 'pendiente',
        `created_at` timestamp NULL DEFAULT current_timestamp(),
        PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;",

    // Tabla de configuraciones
    "CREATE TABLE IF NOT EXISTS `settings` (
        `setting_key` varchar(255) NOT NULL,
        `setting_value` text DEFAULT NULL,
        PRIMARY KEY (`setting_key`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;",

    // Tabla de tickets
    "CREATE TABLE IF NOT EXISTS `tickets` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `category_id` int(11) NOT NULL,
        `email` varchar(255) NOT NULL,
        `description` text DEFAULT NULL,
        `problem_type` varchar(100) NOT NULL,
        `content_name` varchar(255) DEFAULT NULL,
        `device_type` varchar(100) DEFAULT NULL,
        `status` enum('pendiente','resuelto') NOT NULL DEFAULT 'pendiente',
        `created_at` timestamp NULL DEFAULT current_timestamp(),
        PRIMARY KEY (`id`),
        KEY `category_id` (`category_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;",

    // Tabla de adjuntos de tickets
    "CREATE TABLE IF NOT EXISTS `ticket_attachments` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `ticket_id` int(11) NOT NULL,
        `file_path` varchar(255) NOT NULL,
        `created_at` timestamp NULL DEFAULT current_timestamp(),
        PRIMARY KEY (`id`),
        KEY `ticket_id` (`ticket_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;",

    // Tabla de usuarios
    "CREATE TABLE IF NOT EXISTS `users` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `name` varchar(100) NOT NULL,
        `email` varchar(255) NOT NULL,
        `password` varchar(255) NOT NULL,
        `created_at` timestamp NULL DEFAULT current_timestamp(),
        PRIMARY KEY (`id`),
        UNIQUE KEY `email` (`email`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;"
];

// Datos iniciales para insertar
$initial_data = [
    // Categorías predefinidas - Verificar si ya existen antes de insertar
    "INSERT INTO `categories` (`name`) 
     SELECT 'Televisión en Vivo' WHERE NOT EXISTS (SELECT 1 FROM `categories` WHERE `name` = 'Televisión en Vivo' LIMIT 1);",
    
    "INSERT INTO `categories` (`name`) 
     SELECT 'Películas (VOD)' WHERE NOT EXISTS (SELECT 1 FROM `categories` WHERE `name` = 'Películas (VOD)' LIMIT 1);",
    
    "INSERT INTO `categories` (`name`) 
     SELECT 'Series (VOD)' WHERE NOT EXISTS (SELECT 1 FROM `categories` WHERE `name` = 'Series (VOD)' LIMIT 1);",
    
    "INSERT INTO `categories` (`name`) 
     SELECT 'Acceso / Inicio de Sesión' WHERE NOT EXISTS (SELECT 1 FROM `categories` WHERE `name` = 'Acceso / Inicio de Sesión' LIMIT 1);",
    
    "INSERT INTO `categories` (`name`) 
     SELECT 'Problema de la Aplicación' WHERE NOT EXISTS (SELECT 1 FROM `categories` WHERE `name` = 'Problema de la Aplicación' LIMIT 1);",
    
    "INSERT INTO `categories` (`name`) 
     SELECT 'Otro' WHERE NOT EXISTS (SELECT 1 FROM `categories` WHERE `name` = 'Otro' LIMIT 1);"
];

// Restricciones de clave foránea - asegurarse que se apliquen solo si no existen
$foreign_keys = [
    "ALTER TABLE `tickets` 
     ADD CONSTRAINT IF NOT EXISTS `tickets_ibfk_1` 
     FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`);",

    "ALTER TABLE `ticket_attachments` 
     ADD CONSTRAINT IF NOT EXISTS `ticket_attachments_ibfk_1` 
     FOREIGN KEY (`ticket_id`) REFERENCES `tickets` (`id`) ON DELETE CASCADE;"
];

// Procesar el formulario cuando se envía
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // --- Paso 1: Configuración de base de datos y Email ---
    if ($current_step === 1) {
        // --- Recoger datos del paso 1 ---
        $db_host = trim($_POST['db_host'] ?? '');
        $db_name = trim($_POST['db_name'] ?? '');
        $db_user = trim($_POST['db_user'] ?? '');
        $db_pass = trim($_POST['db_pass'] ?? '');
        $email_host = trim($_POST['email_host'] ?? '');
        $email_user = trim($_POST['email_user'] ?? '');
        $email_pass = trim($_POST['email_pass'] ?? '');
        $email_port = trim($_POST['email_port'] ?? '587');
        $site_name = trim($_POST['site_name'] ?? 'Mi Sistema');
        
        // Variable para almacenar la ruta del logo
        $logo_path = null;

        // --- Validación del paso 1 ---
        if (empty($db_host)) $errors[] = "El host de la base de datos es requerido.";
        if (empty($db_name)) $errors[] = "El nombre de la base de datos es requerido.";
        if (empty($db_user)) $errors[] = "El usuario de la base de datos es requerido.";
        if (empty($email_host)) $errors[] = "El host del correo electrónico es requerido.";
        if (empty($email_user)) $errors[] = "El usuario del correo electrónico es requerido.";
        if (empty($email_pass)) $errors[] = "La contraseña del correo electrónico es requerida.";
        if (empty($email_port) || !filter_var($email_port, FILTER_VALIDATE_INT)) {
            $errors[] = "El puerto del correo electrónico debe ser un número válido.";
        }
        if (empty($site_name)) $errors[] = "El nombre del sitio es requerido.";
        
        // Procesar el logo si fue subido
        if (!empty($_FILES['site_logo']['name'])) {
            $file_name = $_FILES['site_logo']['name'];
            $file_tmp = $_FILES['site_logo']['tmp_name'];
            $file_size = $_FILES['site_logo']['size'];
            $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
            
            // Validar extensión
            $allowed_ext = ['jpg', 'jpeg', 'png', 'svg'];
            if (!in_array($file_ext, $allowed_ext)) {
                $errors[] = "El logo debe ser un archivo en formato JPG, PNG o SVG.";
            } elseif ($file_size > 2097152) { // 2MB límite
                $errors[] = "El tamaño del logo es demasiado grande. El tamaño máximo es 2MB.";
            } else {
                // Crear directorio de imágenes si no existe
                $upload_dir = 'img/';
                if (!is_dir($upload_dir)) {
                    if (!mkdir($upload_dir, 0755, true)) {
                        $errors[] = "No se pudo crear el directorio para el logo. Verifique los permisos.";
                    }
                }
                
                // Guardar con nombre estándar para fácil referencia
                $logo_path = $upload_dir . 'logo.' . $file_ext;
                
                // No mover el archivo todavía, lo haremos después de verificar la BD
            }
        }

        if (empty($errors)) {
            mysqli_report(MYSQLI_REPORT_STRICT);
            try {
                // Conectar a MySQL
                $test_conn = new mysqli($db_host, $db_user, $db_pass);
                if ($test_conn->connect_error) {
                    throw new Exception("Error al conectar a MySQL: " . $test_conn->connect_error);
                }
                
                // Asegurar que usamos UTF-8
                $test_conn->set_charset("utf8mb4");
                
                // Verificar si la BD existe, si no, crearla
                $db_exists = $test_conn->select_db($db_name);
                if (!$db_exists) {
                    if (!$test_conn->query("CREATE DATABASE IF NOT EXISTS `$db_name` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci")) {
                        throw new Exception("No se pudo crear la base de datos: " . $test_conn->error);
                    }
                    $test_conn->select_db($db_name);
                } else {
                    // La base de datos existe, seleccionarla
                    $test_conn->select_db($db_name);
                }
                
                // Crear las tablas una por una
                foreach ($database_schema as $query) {
                    if (!$test_conn->query($query)) {
                        throw new Exception("Error al crear tabla: " . $test_conn->error);
                    }
                }
                
                // Insertar datos iniciales
                foreach ($initial_data as $query) {
                    if (!$test_conn->query($query)) {
                        throw new Exception("Error al insertar datos iniciales: " . $test_conn->error);
                    }
                }
                
                // Aplicar restricciones de clave foránea
                // La cláusula IF NOT EXISTS no es compatible con todas las versiones de MySQL para ALTER TABLE,
                // así que lo intentamos pero capturamos el error si ocurre
                foreach ($foreign_keys as $query) {
                    try {
                        $test_conn->query($query);
                    } catch (Exception $e) {
                        // Si ya existe la restricción, continuamos sin error
                        if (strpos($e->getMessage(), 'Duplicate foreign key constraint') === false) {
                            throw $e; // Si es otro tipo de error, lo lanzamos
                        }
                    }
                }

                // Si llegamos hasta aquí, configuración de BD exitosa - continuar al paso 2
                $test_conn->close();
                
                // Ahora que sabemos que la BD está bien, movemos el logo si existe
                if (!empty($_FILES['site_logo']['name']) && empty($errors)) {
                    if (!move_uploaded_file($_FILES['site_logo']['tmp_name'], $logo_path)) {
                        $errors[] = "Error al guardar el logo. Verifique los permisos de escritura.";
                        // Si hay error en el logo, volvemos al paso 1
                        $current_step = 1;
                    }
                }
                
                // Si no hubo errores con el logo, continuamos
                if (empty($errors)) {
                    // Guardar los valores del paso 1 para usarlos después
                    $_SESSION['install_step1'] = [
                        'db_host' => $db_host,
                        'db_name' => $db_name,
                        'db_user' => $db_user,
                        'db_pass' => $db_pass,
                        'email_host' => $email_host,
                        'email_user' => $email_user,
                        'email_pass' => $email_pass,
                        'email_port' => $email_port,
                        'site_name' => $site_name,
                        'base_url' => $detected_base_url,
                        'logo_path' => $logo_path
                    ];
                    
                    // Verificar que la sesión esté guardada correctamente
                    if (!isset($_SESSION['install_step1'])) {
                        throw new Exception("Error al guardar los datos en la sesión. Compruebe la configuración de PHP.");
                    }
                    
                    $current_step = 2;
                }
                
            } catch (mysqli_sql_exception $e) {
                $errors[] = "Error de conexión a la base de datos: " . $e->getMessage() . ". Verifique las credenciales.";
            } catch (Exception $e) {
                $errors[] = "Error durante la instalación: " . $e->getMessage();
            }
            mysqli_report(MYSQLI_REPORT_OFF);
        }
    }
    
    // --- Paso 2: Configuración del administrador ---
    elseif ($current_step === 2) {
        // Depuración: Verificar contenido de la sesión
        if (!isset($_SESSION) || empty($_SESSION)) {
            $errors[] = "Error: La sesión no está disponible o está vacía.";
            $current_step = 1;
        }
        elseif (!isset($_SESSION['install_step1'])) {
            $errors[] = "Error en la secuencia de instalación. Por favor, comience nuevamente.";
            $current_step = 1;
        } else {
            // Recuperar datos del paso 1
            $step1 = $_SESSION['install_step1'];
            
            // Recoger datos del administrador
            $admin_name = trim($_POST['admin_name'] ?? '');
            $admin_email = trim($_POST['admin_email'] ?? '');
            $admin_password = trim($_POST['admin_password'] ?? '');
            $admin_password_confirm = trim($_POST['admin_password_confirm'] ?? '');
            
            // Validación
            if (empty($admin_name)) $errors[] = "El nombre del administrador es requerido.";
            if (empty($admin_email)) $errors[] = "El correo del administrador es requerido.";
            elseif (!filter_var($admin_email, FILTER_VALIDATE_EMAIL)) $errors[] = "El correo del administrador no es válido.";
            if (empty($admin_password)) $errors[] = "La contraseña del administrador es requerida.";
            elseif (strlen($admin_password) < 8) $errors[] = "La contraseña debe tener al menos 8 caracteres.";
            if ($admin_password !== $admin_password_confirm) $errors[] = "Las contraseñas no coinciden.";
            
            if (empty($errors)) {
                mysqli_report(MYSQLI_REPORT_STRICT);
                try {
                    // Conectar a la base de datos
                    $conn = new mysqli($step1['db_host'], $step1['db_user'], $step1['db_pass'], $step1['db_name']);
                    $conn->set_charset("utf8mb4");
                    
                    // Insertar el usuario administrador
                    $hashed_password = password_hash($admin_password, PASSWORD_DEFAULT);
                    $stmt = $conn->prepare("INSERT INTO users (name, email, password) VALUES (?, ?, ?)");
                    $stmt->bind_param("sss", $admin_name, $admin_email, $hashed_password);
                    
                    if (!$stmt->execute()) {
                        throw new Exception("No se pudo crear el usuario administrador: " . $stmt->error);
                    }
                    
                    $stmt->close();
                    $conn->close();
                    
                    // Si llegamos hasta aquí, configuración exitosa - generar archivo config.php
                    $db_host = $step1['db_host'];
                    $db_name = $step1['db_name']; 
                    $db_user = $step1['db_user'];
                    $db_pass = $step1['db_pass'];
                    $email_host = $step1['email_host'];
                    $email_user = $step1['email_user'];
                    $email_pass = $step1['email_pass'];
                    $email_port = $step1['email_port'];
                    $site_name = $step1['site_name'];
                    $detected_base_url = $step1['base_url'];
                    $logo_path = $step1['logo_path'] ?? null;
                    
                    // --- Generate config.php content ---
                    $config_content = <<<PHP
<?php
// Configuración generada por el instalador

// Configuración de base de datos
define('DB_HOST', '$db_host');
define('DB_NAME', '$db_name');
define('DB_USER', '$db_user');
define('DB_PASS', '$db_pass');

// Configuración de email
define('EMAIL_HOST', '$email_host');
define('EMAIL_USER', '$email_user');
define('EMAIL_PASS', '$email_pass');
define('EMAIL_PORT', $email_port);

// URL base del sitio (detectada automáticamente)
define('BASE_URL', '$detected_base_url');

// Nombre del sitio (configurado durante la instalación)
define('SITE_NAME', '$site_name');

// Ruta del logo (configurada durante la instalación)
define('SITE_LOGO', '$logo_path');

// Configuración de sesión
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Función para conexión a base de datos
function conectarDB() {
    mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT); // Optional: Report errors as exceptions
    try {
        \$conexion = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
        \$conexion->set_charset("utf8");
        return \$conexion;
    } catch (mysqli_sql_exception \$e) {
        // Log error or handle gracefully in production
         error_log("Error de conexión a la base de datos: " . \$e->getMessage());
        // You might want to display a user-friendly error message
        die("Error de conexión a la base de datos. Por favor, verifique la configuración o contacte al administrador.");
    }
}

// Función para enviar emails (Consider using a library like PHPMailer for robustness)
function enviarEmail(\$destinatario, \$asunto, \$mensaje) {
    \$headers = "MIME-Version: 1.0" . "\\r\\n";
    \$headers .= "Content-type:text/html;charset=UTF-8" . "\\r\\n";
    // Usar SITE_NAME para el remitente
    \$headers .= "From: Soporte " . SITE_NAME . " <" . EMAIL_USER . ">" . "\\r\\n";

    // Basic mail function, prone to issues. Consider PHPMailer.
    // Note: Email sending depends heavily on server configuration (sendmail, SMTP relay)
    // This basic function might not work without proper server setup.
    // You might need SMTP authentication which mail() doesn't directly support easily.
    // return mail(\$destinatario, \$asunto, \$mensaje, \$headers);

    // Placeholder for email sending logic - Replace with PHPMailer or similar
    error_log("Intento de envío de correo a: \$destinatario, Asunto: \$asunto"); // Log attempt
    // For testing, you might return true, but implement real sending later
     return true; // WARNING: Email sending not implemented fully here.
}

// Función para limpiar entradas
function limpiarDato(\$dato) {
    \$dato = trim(\$dato);
    \$dato = stripslashes(\$dato);
    \$dato = htmlspecialchars(\$dato, ENT_QUOTES, 'UTF-8'); // Specify encoding
    return \$dato;
}

// Función para redireccionar
function redireccionar(\$ubicacion) {
    // Determine if we're in admin context
    \$in_admin = strpos(\$_SERVER['PHP_SELF'], '/admin/') !== false;
    
    // Don't modify URLs that already have http/https protocol
    if (preg_match('/^(http|https):\\/\\//', \$ubicacion)) {
        // Full URL, keep as is
    }
    // Handle absolute paths (starting with /)
    elseif (substr(\$ubicacion, 0, 1) === '/') {
        // Keep as is - absolute path from domain root
    }
    // Handle navigation from frontend to admin area
    elseif (strpos(\$ubicacion, 'admin/') === 0) {
        // Already has admin/ prefix, keep as is
    }
    // Special case for admin context
    elseif (\$in_admin && \$ubicacion !== 'index.php') {
        // In admin directory - create proper URL for admin pages
        if (strpos(\$ubicacion, '../') === 0) {
            // Going back to main site from admin
            \$ubicacion = BASE_URL . substr(\$ubicacion, 3);
        } else {
            // Admin to admin navigation - keep relative
        }
    }
    // Regular relative paths - append to BASE_URL
    else {
        \$ubicacion = BASE_URL . \$ubicacion;
    }
    
    header("Location: " . \$ubicacion);
    exit;
}

// Función para verificar si el administrador está logueado
function esAdmin() {
    return isset(\$_SESSION['admin_id']);
}

// Añade aquí otras funciones o configuraciones necesarias
// por ejemplo, SITE_NAME si decides hacerlo configurable
// define('SITE_NAME', 'Mi Aplicación');

?>
PHP;

                    // --- Write config.php ---
                    $config_dir = 'includes';
                    $config_file_path = $config_dir . '/config.php';

                    if (!is_dir($config_dir)) {
                        if (!mkdir($config_dir, 0755, true)) {
                             throw new Exception("No se pudo crear el directorio de configuración '$config_dir'. Verifique los permisos.");
                        }
                    }

                    if (file_put_contents($config_file_path, $config_content)) {
                        // Crear directorio de uploads si no existe
                        if (!is_dir('uploads')) {
                            mkdir('uploads', 0755, true);
                        }
                        
                        // Limpiar la sesión de instalación
                        unset($_SESSION['install_step1']);
                        
                        $success_message = "¡Instalación completada con éxito! El sistema está listo para usar.";
                        
                    } else {
                         throw new Exception("No se pudo escribir el archivo de configuración '$config_file_path'. Verifique los permisos.");
                    }

                } catch (mysqli_sql_exception $e) {
                    $errors[] = "Error de base de datos: " . $e->getMessage();
                } catch (Exception $e) {
                    $errors[] = "Error durante la instalación: " . $e->getMessage();
                }
                mysqli_report(MYSQLI_REPORT_OFF);
            }
        }
    }
}

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Instalación del Sistema</title>
    <style>
        body { font-family: sans-serif; line-height: 1.6; padding: 20px; max-width: 600px; margin: auto; border: 1px solid #ccc; margin-top: 30px; }
        h1, h2 { text-align: center; color: #333; }
        form div { margin-bottom: 15px; }
        label { display: block; margin-bottom: 5px; font-weight: bold; }
        input[type="text"], input[type="password"], input[type="number"], input[type="email"] { width: 100%; padding: 8px; border: 1px solid #ccc; box-sizing: border-box; }
        button { background-color: #28a745; color: white; padding: 10px 15px; border: none; border-radius: 4px; cursor: pointer; font-size: 16px; }
        button:hover { background-color: #218838; }
        .error { color: red; border: 1px solid red; padding: 10px; margin-bottom: 15px; }
        .success { color: green; border: 1px solid green; padding: 10px; margin-bottom: 15px; text-align: center;}
        .info { background-color: #eef; border: 1px solid #ccd; padding: 10px; margin-bottom: 15px; }
        .step-indicator { display: flex; margin-bottom: 20px; }
        .step { flex: 1; text-align: center; padding: 10px; background: #f8f9fa; border: 1px solid #ddd; }
        .step.active { background: #007bff; color: white; font-weight: bold; }
        .step.completed { background: #28a745; color: white; }
        .logo-preview { max-width: 200px; max-height: 100px; margin-top: 10px; display: none; border: 1px solid #ccc; padding: 5px; }
    </style>
</head>
<body>

    <h1>Instalación del Sistema</h1>
    
    <?php if (!empty($success_message)): ?>
        <div class="success">
            <?php echo htmlspecialchars($success_message); ?>
            <p>Serás redirigido en unos segundos o puedes <a href="<?php echo htmlspecialchars($detected_base_url); ?>">hacer clic aquí</a> para ir a la página principal.</p>
            <script>
                setTimeout(function() {
                    window.location.href = '<?php echo htmlspecialchars($detected_base_url); ?>';
                }, 5000); // 5 seconds delay
            </script>
        </div>
    <?php else: // Mostrar el formulario de instalación si no hay mensaje de éxito ?>

    <div class="step-indicator">
        <div class="step <?php echo $current_step === 1 ? 'active' : ($current_step > 1 ? 'completed' : ''); ?>">
            1. Configuración
        </div>
        <div class="step <?php echo $current_step === 2 ? 'active' : ($current_step > 2 ? 'completed' : ''); ?>">
            2. Administrador
        </div>
    </div>

    <?php if (!empty($errors)): ?>
        <div class="error">
            <strong>Por favor, corrija los siguientes errores:</strong>
            <ul>
                <?php foreach ($errors as $error): ?>
                    <li><?php echo htmlspecialchars($error); ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <?php if ($current_step === 1): // Formulario de configuración de base de datos y email ?>
        <div class="info">
            <p>URL Base detectada: <strong><?php echo htmlspecialchars($detected_base_url); ?></strong></p>
            <p>Esta URL se usará para configurar el sistema.</p>
        </div>

        <form action="install.php" method="post" enctype="multipart/form-data">
            <input type="hidden" name="step" value="1">
            
            <div>
                <label for="site_name" class="form-label">Nombre del Sitio/Marca:</label>
                <input type="text" id="site_name" name="site_name" value="<?php echo htmlspecialchars($_POST['site_name'] ?? ''); ?>" placeholder="Ej: Mi Empresa" required>
                <div class="form-text">Este nombre se mostrará en todo el sistema y en los emails enviados.</div>
            </div>
            
            <div>
                <label for="site_logo" class="form-label">Logo del Sitio:</label>
                <input type="file" id="site_logo" name="site_logo" accept="image/jpeg, image/png, image/svg+xml">
                <div class="form-text">Sube un logo para tu sitio (formato JPG, PNG o SVG). Tamaño máximo: 2MB.</div>
                <img id="logo-preview" class="logo-preview" alt="Vista previa del logo">
            </div>

            <h2>Configuración de Base de Datos</h2>
            <div>
                <label for="db_host">Host:</label>
                <input type="text" id="db_host" name="db_host" value="<?php echo htmlspecialchars($_POST['db_host'] ?? 'localhost'); ?>" required>
            </div>
            <div>
                <label for="db_name">Nombre de la Base de Datos:</label>
                <input type="text" id="db_name" name="db_name" value="<?php echo htmlspecialchars($_POST['db_name'] ?? ''); ?>" required>
            </div>
            <div>
                <label for="db_user">Usuario:</label>
                <input type="text" id="db_user" name="db_user" value="<?php echo htmlspecialchars($_POST['db_user'] ?? ''); ?>" required>
            </div>
            <div>
                <label for="db_pass">Contraseña:</label>
                <input type="password" id="db_pass" name="db_pass" value="<?php echo htmlspecialchars($_POST['db_pass'] ?? ''); ?>">
            </div>

            <h2>Configuración de Correo Electrónico (SMTP)</h2>
             <p><small>Nota: El envío de correo depende de la configuración del servidor. Se recomienda usar una librería como PHPMailer para mayor fiabilidad, pero esta configuración básica es necesaria.</small></p>
            <div>
                <label for="email_host">Host (ej. smtp.example.com):</label>
                <input type="text" id="email_host" name="email_host" value="<?php echo htmlspecialchars($_POST['email_host'] ?? ''); ?>" required>
            </div>
             <div>
                <label for="email_port">Puerto (ej. 587 para TLS, 465 para SSL):</label>
                <input type="number" id="email_port" name="email_port" value="<?php echo htmlspecialchars($_POST['email_port'] ?? '587'); ?>" required>
            </div>
            <div>
                <label for="email_user">Usuario (ej. user@example.com):</label>
                <input type="text" id="email_user" name="email_user" value="<?php echo htmlspecialchars($_POST['email_user'] ?? ''); ?>" required>
            </div>
            <div>
                <label for="email_pass">Contraseña:</label>
                <input type="password" id="email_pass" name="email_pass" value="<?php echo htmlspecialchars($_POST['email_pass'] ?? ''); ?>" required>
            </div>

            <div>
                <button type="submit">Siguiente</button>
            </div>
        </form>
        
    <?php elseif ($current_step === 2): // Formulario para crear el usuario administrador ?>
        <div class="info">
            <p>Configuración básica completada. Ahora crearemos un usuario administrador para gestionar el sistema.</p>
        </div>

        <form action="install.php" method="post">
            <input type="hidden" name="step" value="2">
            
            <h2>Crear Usuario Administrador</h2>
            <div>
                <label for="admin_name">Nombre completo:</label>
                <input type="text" id="admin_name" name="admin_name" value="<?php echo htmlspecialchars($_POST['admin_name'] ?? ''); ?>" required>
            </div>
            <div>
                <label for="admin_email">Correo electrónico:</label>
                <input type="email" id="admin_email" name="admin_email" value="<?php echo htmlspecialchars($_POST['admin_email'] ?? ''); ?>" required>
            </div>
            <div>
                <label for="admin_password">Contraseña:</label>
                <input type="password" id="admin_password" name="admin_password" required>
                <small>Mínimo 8 caracteres.</small>
            </div>
            <div>
                <label for="admin_password_confirm">Confirmar contraseña:</label>
                <input type="password" id="admin_password_confirm" name="admin_password_confirm" required>
            </div>
            
            <div>
                <button type="submit">Finalizar Instalación</button>
            </div>
        </form>
    <?php endif; ?>
    
    <?php endif; // Fin del bloque que muestra el formulario si no hay mensaje de éxito ?>

    <script>
        // Script para mostrar vista previa del logo
        document.addEventListener('DOMContentLoaded', function() {
            const logoInput = document.getElementById('site_logo');
            const logoPreview = document.getElementById('logo-preview');
            
            if (logoInput && logoPreview) {
                logoInput.addEventListener('change', function() {
                    if (this.files && this.files[0]) {
                        const reader = new FileReader();
                        
                        reader.onload = function(e) {
                            logoPreview.src = e.target.result;
                            logoPreview.style.display = 'block';
                        };
                        
                        reader.readAsDataURL(this.files[0]);
                    } else {
                        logoPreview.style.display = 'none';
                    }
                });
            }
        });
    </script>

</body>
</html> 