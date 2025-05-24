<?php
require_once '../includes/config.php';

// Variables para el header
$titulo_pagina = 'Solicitudes de Contenido';
$pagina_actual = 'content_requests';

// Parámetros de filtrado
$tipo = isset($_GET['tipo']) ? limpiarDato($_GET['tipo']) : '';
$estado = isset($_GET['estado']) ? limpiarDato($_GET['estado']) : '';
$busqueda = isset($_GET['busqueda']) ? limpiarDato($_GET['busqueda']) : '';

// Conexión a la base de datos
$db = conectarDB();

// Construcción de la consulta principal
$query = "SELECT * FROM content_requests";

$where_clauses = [];
$params = [];
$param_types = '';

// Aplicar filtros
if (!empty($tipo)) {
    $where_clauses[] = "type = ?";
    $params[] = $tipo;
    $param_types .= 's';
}

if (!empty($estado)) {
    $where_clauses[] = "status = ?";
    $params[] = $estado;
    $param_types .= 's';
}

if (!empty($busqueda)) {
    $where_clauses[] = "(title LIKE ? OR notes LIKE ? OR email LIKE ?)";
    $busqueda_param = "%$busqueda%";
    $params[] = $busqueda_param;
    $params[] = $busqueda_param;
    $params[] = $busqueda_param;
    $param_types .= 'sss';
}

// Agregar cláusulas WHERE si existen
if (!empty($where_clauses)) {
    $query .= " WHERE " . implode(" AND ", $where_clauses);
}

// Ordenar por fecha de creación (más recientes primero)
$query .= " ORDER BY created_at DESC";

// Preparar y ejecutar la consulta
$stmt = $db->prepare($query);

if (!empty($params)) {
    $stmt->bind_param($param_types, ...$params);
}

$stmt->execute();
$result = $stmt->get_result();

// Incluir el encabezado
require_once 'includes/header.php';
?>

<div class="row mb-4">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Filtros</h5>
            </div>
            <div class="card-body">
                <form action="" method="get" class="row g-3">
                    <div class="col-md-3">
                        <label for="tipo" class="form-label">Tipo</label>
                        <select name="tipo" id="tipo" class="form-select">
                            <option value="">Todos</option>
                            <option value="pelicula" <?php echo $tipo === 'pelicula' ? 'selected' : ''; ?>>Película</option>
                            <option value="serie" <?php echo $tipo === 'serie' ? 'selected' : ''; ?>>Serie</option>
                            <option value="canal" <?php echo $tipo === 'canal' ? 'selected' : ''; ?>>Canal</option>
                        </select>
                    </div>
                    
                    <div class="col-md-3">
                        <label for="estado" class="form-label">Estado</label>
                        <select name="estado" id="estado" class="form-select">
                            <option value="">Todos</option>
                            <option value="pendiente" <?php echo $estado === 'pendiente' ? 'selected' : ''; ?>>Pendiente</option>
                            <option value="completado" <?php echo $estado === 'completado' ? 'selected' : ''; ?>>Completado</option>
                            <option value="rechazado" <?php echo $estado === 'rechazado' ? 'selected' : ''; ?>>Rechazado</option>
                        </select>
                    </div>
                    
                    <div class="col-md-4">
                        <label for="busqueda" class="form-label">Buscar</label>
                        <input type="text" class="form-control" id="busqueda" name="busqueda" value="<?php echo $busqueda; ?>" placeholder="Título, notas, email...">
                    </div>
                    
                    <div class="col-md-2 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary w-100">Filtrar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Lista de Solicitudes</h5>
                <span class="badge bg-info"><?php echo $result->num_rows; ?> solicitudes</span>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Título</th>
                                <th>Tipo</th>
                                <th>Año</th>
                                <th>Email</th>
                                <th>Estado</th>
                                <th>Fecha</th>
                                <th>Imagen</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($result->num_rows > 0): ?>
                                <?php while ($solicitud = $result->fetch_assoc()): ?>
                                    <tr>
                                        <td><?php echo $solicitud['id']; ?></td>
                                        <td><?php echo $solicitud['title']; ?></td>
                                        <td><?php echo ucfirst($solicitud['type']); ?></td>
                                        <td><?php echo $solicitud['year'] ?: '-'; ?></td>
                                        <td><?php echo $solicitud['email'] ?: '-'; ?></td>
                                        <td>
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
                                                <?php echo $solicitud['status']; ?>
                                            </span>
                                        </td>
                                        <td><?php echo date('d/m/Y', strtotime($solicitud['created_at'])); ?></td>
                                        <td class="text-center">
                                            <?php if (!empty($solicitud['cover_image'])): ?>
                                                <i class="bi bi-image text-success" title="Con imagen"></i>
                                            <?php else: ?>
                                                <i class="bi bi-dash text-muted"></i>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <a href="view-request.php?id=<?php echo $solicitud['id']; ?>" class="btn btn-sm btn-primary">
                                                <i class="bi bi-eye"></i> Ver
                                            </a>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="9" class="text-center">No se encontraron solicitudes</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
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