<?php
require_once '../includes/config.php';

// Variables para el header
$titulo_pagina = 'Dashboard';
$pagina_actual = 'dashboard';

// Obtener estadísticas
$db = conectarDB();

// Total de tickets pendientes
$query_tickets_pendientes = "SELECT COUNT(*) as total FROM tickets WHERE status = 'pendiente'";
$result_tickets_pendientes = $db->query($query_tickets_pendientes);
$tickets_pendientes = $result_tickets_pendientes->fetch_assoc()['total'];

// Total de tickets resueltos
$query_tickets_resueltos = "SELECT COUNT(*) as total FROM tickets WHERE status = 'resuelto'";
$result_tickets_resueltos = $db->query($query_tickets_resueltos);
$tickets_resueltos = $result_tickets_resueltos->fetch_assoc()['total'];

// Total de solicitudes pendientes
$query_solicitudes_pendientes = "SELECT COUNT(*) as total FROM content_requests WHERE status = 'pendiente'";
$result_solicitudes_pendientes = $db->query($query_solicitudes_pendientes);
$solicitudes_pendientes = $result_solicitudes_pendientes->fetch_assoc()['total'];

// Total de solicitudes completadas
$query_solicitudes_completadas = "SELECT COUNT(*) as total FROM content_requests WHERE status = 'completado'";
$result_solicitudes_completadas = $db->query($query_solicitudes_completadas);
$solicitudes_completadas = $result_solicitudes_completadas->fetch_assoc()['total'];

// Tickets por categoría
$query_tickets_por_categoria = "SELECT c.name, COUNT(t.id) as total FROM tickets t 
                                INNER JOIN categories c ON t.category_id = c.id 
                                GROUP BY c.id ORDER BY total DESC LIMIT 5";
$result_tickets_por_categoria = $db->query($query_tickets_por_categoria);

// Tickets recientes
$query_tickets_recientes = "SELECT t.id, t.email, t.problem_type, t.status, c.name as category, t.created_at 
                            FROM tickets t 
                            INNER JOIN categories c ON t.category_id = c.id 
                            ORDER BY t.created_at DESC LIMIT 5";
$result_tickets_recientes = $db->query($query_tickets_recientes);

// Solicitudes recientes
$query_solicitudes_recientes = "SELECT id, type, title, status, created_at 
                                FROM content_requests 
                                ORDER BY created_at DESC LIMIT 5";
$result_solicitudes_recientes = $db->query($query_solicitudes_recientes);

// Calcular porcentajes para gráficos
$total_tickets = $tickets_pendientes + $tickets_resueltos;
$porcentaje_tickets_resueltos = $total_tickets > 0 ? round(($tickets_resueltos / $total_tickets) * 100) : 0;

$total_solicitudes = $solicitudes_pendientes + $solicitudes_completadas;
$porcentaje_solicitudes_completadas = $total_solicitudes > 0 ? round(($solicitudes_completadas / $total_solicitudes) * 100) : 0;

$db->close();

// Incluir el encabezado
require_once 'includes/header.php';
?>

<!-- Resumen de estadísticas -->
<div class="row">
    <!-- Tarjeta de usuarios -->
    <div class="col-md-6 col-lg-3 mb-4">
        <div class="stat-card primary">
            <div class="icon">
                <i class="bi bi-people-fill"></i>
            </div>
            <h3>Tickets Pendientes</h3>
            <div class="value"><?php echo $tickets_pendientes; ?></div>
            <div class="progress-bar-custom">
                <div class="progress" style="width: 70%"></div>
            </div>
        </div>
    </div>
    
    <!-- Tarjeta de solicitudes -->
    <div class="col-md-6 col-lg-3 mb-4">
        <div class="stat-card secondary">
            <div class="icon">
                <i class="bi bi-collection-play-fill"></i>
            </div>
            <h3>Solicitudes Pendientes</h3>
            <div class="value"><?php echo $solicitudes_pendientes; ?></div>
            <div class="progress-bar-custom">
                <div class="progress" style="width: 50%"></div>
            </div>
        </div>
    </div>
    
    <!-- Tarjeta de tickets resueltos -->
    <div class="col-md-6 col-lg-3 mb-4">
        <div class="stat-card green">
            <div class="icon">
                <i class="bi bi-check-circle-fill"></i>
            </div>
            <h3>Tickets Resueltos</h3>
            <div class="value"><?php echo $tickets_resueltos; ?></div>
            <div class="progress-bar-custom">
                <div class="progress" style="width: <?php echo $porcentaje_tickets_resueltos; ?>%"></div>
            </div>
        </div>
    </div>
    
    <!-- Tarjeta de solicitudes completadas -->
    <div class="col-md-6 col-lg-3 mb-4">
        <div class="stat-card danger">
            <div class="icon">
                <i class="bi bi-film"></i>
            </div>
            <h3>Solicitudes Completadas</h3>
            <div class="value"><?php echo $solicitudes_completadas; ?></div>
            <div class="progress-bar-custom">
                <div class="progress" style="width: <?php echo $porcentaje_solicitudes_completadas; ?>%"></div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <!-- Tickets por categoría -->
    <div class="col-md-6 mb-4">
        <div class="card h-100">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span>Tickets por Categoría</span>
                <span class="badge bg-info"><?php echo $result_tickets_por_categoria->num_rows; ?> categorías</span>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Categoría</th>
                                <th>Total</th>
                                <th>Porcentaje</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            if ($result_tickets_por_categoria->num_rows > 0): 
                                // Calcular el total para los porcentajes
                                $total_categorias = 0;
                                $categorias = [];
                                
                                while ($categoria = $result_tickets_por_categoria->fetch_assoc()) {
                                    $total_categorias += $categoria['total'];
                                    $categorias[] = $categoria;
                                }
                            ?>
                                <?php foreach ($categorias as $categoria): 
                                    $porcentaje = round(($categoria['total'] / $total_categorias) * 100);
                                ?>
                                    <tr>
                                        <td><?php echo $categoria['name']; ?></td>
                                        <td><?php echo $categoria['total']; ?></td>
                                        <td>
                                            <div class="progress" style="height: 6px;">
                                                <div class="progress-bar bg-primary" role="progressbar" style="width: <?php echo $porcentaje; ?>%;" aria-valuenow="<?php echo $porcentaje; ?>" aria-valuemin="0" aria-valuemax="100"></div>
                                            </div>
                                            <span class="small"><?php echo $porcentaje; ?>%</span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="3" class="text-center">No hay datos disponibles</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Gráfico circular -->
    <div class="col-md-6 mb-4">
        <div class="card h-100">
            <div class="card-header">
                <span>Estado de Solicitudes</span>
            </div>
            <div class="card-body">
                <div class="row text-center">
                    <div class="col-md-6 mb-3">
                        <div class="card bg-light border-0">
                            <div class="card-body">
                                <div class="position-relative d-inline-block" style="width: 140px; height: 140px;">
                                    <svg viewBox="0 0 36 36" class="position-absolute top-0 start-0 w-100 h-100">
                                        <path class="stroke-primary" stroke-dasharray="<?php echo $porcentaje_tickets_resueltos; ?>, 100" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" fill="none" stroke="#4e73df" stroke-width="2" style="stroke: var(--bs-primary);"></path>
                                    </svg>
                                    <div class="position-absolute top-0 start-0 w-100 h-100 d-flex align-items-center justify-content-center">
                                        <div>
                                            <h4 class="mb-0"><?php echo $porcentaje_tickets_resueltos; ?>%</h4>
                                            <span class="small">Resueltos</span>
                                        </div>
                                    </div>
                                </div>
                                <h5 class="mt-3">Tickets</h5>
                                <div class="small text-muted"><?php echo $tickets_resueltos; ?> de <?php echo $total_tickets; ?></div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6 mb-3">
                        <div class="card bg-light border-0">
                            <div class="card-body">
                                <div class="position-relative d-inline-block" style="width: 140px; height: 140px;">
                                    <svg viewBox="0 0 36 36" class="position-absolute top-0 start-0 w-100 h-100">
                                        <path class="stroke-success" stroke-dasharray="<?php echo $porcentaje_solicitudes_completadas; ?>, 100" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" fill="none" stroke="#4e73df" stroke-width="2" style="stroke: var(--bs-success);"></path>
                                    </svg>
                                    <div class="position-absolute top-0 start-0 w-100 h-100 d-flex align-items-center justify-content-center">
                                        <div>
                                            <h4 class="mb-0"><?php echo $porcentaje_solicitudes_completadas; ?>%</h4>
                                            <span class="small">Completadas</span>
                                        </div>
                                    </div>
                                </div>
                                <h5 class="mt-3">Solicitudes</h5>
                                <div class="small text-muted"><?php echo $solicitudes_completadas; ?> de <?php echo $total_solicitudes; ?></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <!-- Tickets recientes -->
    <div class="col-md-6 mb-4">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span>Tickets Recientes</span>
                <a href="tickets.php" class="btn btn-sm btn-gradient-primary">Ver todos</a>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Problema</th>
                                <th>Categoría</th>
                                <th>Estado</th>
                                <th>Fecha</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($result_tickets_recientes->num_rows > 0): ?>
                                <?php while ($ticket = $result_tickets_recientes->fetch_assoc()): ?>
                                    <tr>
                                        <td><a href="view-ticket.php?id=<?php echo $ticket['id']; ?>"><?php echo $ticket['id']; ?></a></td>
                                        <td><?php echo $ticket['problem_type']; ?></td>
                                        <td><?php echo $ticket['category']; ?></td>
                                        <td>
                                            <span class="badge bg-<?php echo $ticket['status'] === 'pendiente' ? 'warning' : 'success'; ?>">
                                                <?php echo $ticket['status']; ?>
                                            </span>
                                        </td>
                                        <td><?php echo date('d/m/Y', strtotime($ticket['created_at'])); ?></td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="5" class="text-center">No hay tickets recientes</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Solicitudes recientes -->
    <div class="col-md-6 mb-4">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span>Solicitudes Recientes</span>
                <a href="content-requests.php" class="btn btn-sm btn-gradient-success">Ver todas</a>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Tipo</th>
                                <th>Título</th>
                                <th>Estado</th>
                                <th>Fecha</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($result_solicitudes_recientes->num_rows > 0): ?>
                                <?php while ($solicitud = $result_solicitudes_recientes->fetch_assoc()): ?>
                                    <tr>
                                        <td><a href="view-request.php?id=<?php echo $solicitud['id']; ?>"><?php echo $solicitud['id']; ?></a></td>
                                        <td><?php echo ucfirst($solicitud['type']); ?></td>
                                        <td><?php echo $solicitud['title']; ?></td>
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
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="5" class="text-center">No hay solicitudes recientes</td>
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
// Incluir el footer
require_once 'includes/footer.php';
?> 