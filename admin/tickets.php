<?php
require_once '../includes/config.php';

// Variables para el header
$titulo_pagina = 'Tickets de Soporte';
$pagina_actual = 'tickets';

// Parámetros de filtrado
$estado = isset($_GET['estado']) ? limpiarDato($_GET['estado']) : '';
$categoria = isset($_GET['categoria']) ? (int)$_GET['categoria'] : 0;
$busqueda = isset($_GET['busqueda']) ? limpiarDato($_GET['busqueda']) : '';

// Conexión a la base de datos
$db = conectarDB();

// Consulta para obtener categorías (para el filtro)
$query_categorias = "SELECT id, name FROM categories ORDER BY name";
$result_categorias = $db->query($query_categorias);

// Construcción de la consulta principal
$query = "SELECT t.id, t.email, t.problem_type, t.content_name, t.status, c.name as category, t.created_at 
          FROM tickets t 
          INNER JOIN categories c ON t.category_id = c.id";

$where_clauses = [];
$params = [];
$param_types = '';

// Aplicar filtros
if (!empty($estado)) {
    $where_clauses[] = "t.status = ?";
    $params[] = $estado;
    $param_types .= 's';
}

if ($categoria > 0) {
    $where_clauses[] = "t.category_id = ?";
    $params[] = $categoria;
    $param_types .= 'i';
}

if (!empty($busqueda)) {
    $where_clauses[] = "(t.email LIKE ? OR t.problem_type LIKE ? OR t.content_name LIKE ? OR t.description LIKE ?)";
    $busqueda_param = "%$busqueda%";
    $params[] = $busqueda_param;
    $params[] = $busqueda_param;
    $params[] = $busqueda_param;
    $params[] = $busqueda_param;
    $param_types .= 'ssss';
}

// Agregar cláusulas WHERE si existen
if (!empty($where_clauses)) {
    $query .= " WHERE " . implode(" AND ", $where_clauses);
}

// Ordenar por fecha de creación (más recientes primero)
$query .= " ORDER BY t.created_at DESC";

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
                        <label for="estado" class="form-label">Estado</label>
                        <select name="estado" id="estado" class="form-select">
                            <option value="">Todos</option>
                            <option value="pendiente" <?php echo $estado === 'pendiente' ? 'selected' : ''; ?>>Pendiente</option>
                            <option value="resuelto" <?php echo $estado === 'resuelto' ? 'selected' : ''; ?>>Resuelto</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label for="categoria" class="form-label">Categoría</label>
                        <select name="categoria" id="categoria" class="form-select">
                            <option value="0">Todas</option>
                            <?php while ($cat = $result_categorias->fetch_assoc()): ?>
                                <option value="<?php echo $cat['id']; ?>" <?php echo $categoria === (int)$cat['id'] ? 'selected' : ''; ?>>
                                    <?php echo $cat['name']; ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label for="busqueda" class="form-label">Buscar</label>
                        <input type="text" class="form-control" id="busqueda" name="busqueda" value="<?php echo $busqueda; ?>" placeholder="Email, problema, contenido...">
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
                <h5 class="mb-0">Lista de Tickets</h5>
                <span class="badge bg-info"><?php echo $result->num_rows; ?> tickets</span>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Problema</th>
                                <th>Categoría</th>
                                <th>Email</th>
                                <th>Contenido</th>
                                <th>Estado</th>
                                <th>Fecha</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($result->num_rows > 0): ?>
                                <?php while ($ticket = $result->fetch_assoc()): ?>
                                    <tr>
                                        <td><?php echo $ticket['id']; ?></td>
                                        <td><?php echo $ticket['problem_type']; ?></td>
                                        <td><?php echo $ticket['category']; ?></td>
                                        <td><?php echo $ticket['email']; ?></td>
                                        <td><?php echo $ticket['content_name'] ?: '-'; ?></td>
                                        <td>
                                            <span class="badge bg-<?php echo $ticket['status'] === 'pendiente' ? 'warning' : 'success'; ?>">
                                                <?php echo $ticket['status']; ?>
                                            </span>
                                        </td>
                                        <td><?php echo date('d/m/Y H:i', strtotime($ticket['created_at'])); ?></td>
                                        <td>
                                            <a href="view-ticket.php?id=<?php echo $ticket['id']; ?>" class="btn btn-sm btn-primary">
                                                <i class="bi bi-eye"></i> Ver
                                            </a>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="8" class="text-center">No se encontraron tickets</td>
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