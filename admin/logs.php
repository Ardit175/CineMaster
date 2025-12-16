<?php
/**
 * ============================================
 * CineMaster - Admin System Logs
 * ============================================
 * View and filter system activity logs
 */

require_once '../config/config.php';

requireAdmin();

$pageTitle = 'System Logs';
$pdo = getDBConnection();

// Handle log clearing (only super admin)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['clear_logs'])) {
    if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Invalid request.';
    } else {
        $olderThan = $_POST['older_than'] ?? '30';
        
        $stmt = $pdo->prepare("DELETE FROM logs WHERE created_at < DATE_SUB(NOW(), INTERVAL ? DAY)");
        if ($stmt->execute([$olderThan])) {
            $deleted = $stmt->rowCount();
            logAction($_SESSION['user_id'], "Cleared {$deleted} logs older than {$olderThan} days", 'admin');
            $success = "Deleted {$deleted} log entries.";
        }
    }
}

// Get filter values
$filterCategory = $_GET['category'] ?? '';
$filterUser = $_GET['user'] ?? '';
$filterDate = $_GET['date'] ?? '';
$filterSearch = $_GET['search'] ?? '';

// Build query with filters
$query = "
    SELECT l.*, u.name as user_name, u.email as user_email
    FROM logs l
    LEFT JOIN users u ON l.user_id = u.id
    WHERE 1=1
";
$params = [];

if ($filterCategory) {
    $query .= " AND l.category = ?";
    $params[] = $filterCategory;
}
if ($filterUser) {
    $query .= " AND l.user_id = ?";
    $params[] = $filterUser;
}
if ($filterDate) {
    $query .= " AND DATE(l.created_at) = ?";
    $params[] = $filterDate;
}
if ($filterSearch) {
    $query .= " AND (l.action LIKE ? OR l.ip_address LIKE ?)";
    $search = "%{$filterSearch}%";
    $params = array_merge($params, [$search, $search]);
}

$query .= " ORDER BY l.created_at DESC LIMIT 500";

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$logs = $stmt->fetchAll();

// Get log statistics
$statsStmt = $pdo->query("
    SELECT 
        COUNT(*) as total,
        COUNT(DISTINCT user_id) as unique_users,
        COUNT(DISTINCT ip_address) as unique_ips,
        COUNT(DISTINCT category) as categories
    FROM logs
    WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
");
$stats = $statsStmt->fetch();

// Get categories for filter
$categories = $pdo->query("SELECT DISTINCT category FROM logs ORDER BY category")->fetchAll(PDO::FETCH_COLUMN);

// Get users for filter
$users = $pdo->query("SELECT id, name, email FROM users ORDER BY name")->fetchAll();

$csrfToken = generateCSRFToken();

include INCLUDES_PATH . 'header.php';
?>

<div class="container-fluid py-4">
    <div class="row">
        <!-- Admin Sidebar -->
        <div class="col-lg-2 mb-4">
            <div class="card bg-dark border-secondary sticky-top" style="top: 80px;">
                <div class="card-header bg-danger text-center">
                    <i class="bi bi-speedometer2 fs-3 d-block mb-1"></i>
                    <h6 class="mb-0">Admin Panel</h6>
                </div>
                <div class="list-group list-group-flush">
                    <a href="<?php echo SITE_URL; ?>/admin/dashboard.php" class="list-group-item list-group-item-action bg-dark text-light border-secondary">
                        <i class="bi bi-speedometer2 me-2"></i>Dashboard
                    </a>
                    <a href="<?php echo SITE_URL; ?>/admin/users.php" class="list-group-item list-group-item-action bg-dark text-light border-secondary">
                        <i class="bi bi-people me-2"></i>Users
                    </a>
                    <a href="<?php echo SITE_URL; ?>/admin/movies.php" class="list-group-item list-group-item-action bg-dark text-light border-secondary">
                        <i class="bi bi-film me-2"></i>Movies
                    </a>
                    <a href="<?php echo SITE_URL; ?>/admin/showtimes.php" class="list-group-item list-group-item-action bg-dark text-light border-secondary">
                        <i class="bi bi-calendar-event me-2"></i>Showtimes
                    </a>
                    <a href="<?php echo SITE_URL; ?>/admin/bookings.php" class="list-group-item list-group-item-action bg-dark text-light border-secondary">
                        <i class="bi bi-ticket-perforated me-2"></i>Bookings
                    </a>
                    <a href="<?php echo SITE_URL; ?>/admin/logs.php" class="list-group-item list-group-item-action bg-dark text-light border-secondary active">
                        <i class="bi bi-journal-text me-2"></i>Logs
                    </a>
                </div>
            </div>
        </div>
        
        <!-- Main Content -->
        <div class="col-lg-10">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2 class="fw-bold">
                    <i class="bi bi-journal-text text-danger me-2"></i>System Logs
                </h2>
                <button class="btn btn-outline-danger" data-bs-toggle="modal" data-bs-target="#clearLogsModal">
                    <i class="bi bi-trash me-2"></i>Clear Old Logs
                </button>
            </div>
            
            <?php if (isset($success)): ?>
                <div class="alert alert-success">
                    <i class="bi bi-check-circle me-2"></i><?php echo htmlspecialchars($success); ?>
                </div>
            <?php endif; ?>
            
            <!-- Stats Cards -->
            <div class="row g-3 mb-4">
                <div class="col-md-3">
                    <div class="card bg-dark border-secondary">
                        <div class="card-body text-center">
                            <h4 class="text-danger"><?php echo number_format($stats['total']); ?></h4>
                            <small class="text-muted">Logs (7 days)</small>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card bg-dark border-secondary">
                        <div class="card-body text-center">
                            <h4 class="text-info"><?php echo $stats['unique_users']; ?></h4>
                            <small class="text-muted">Active Users</small>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card bg-dark border-secondary">
                        <div class="card-body text-center">
                            <h4 class="text-warning"><?php echo $stats['unique_ips']; ?></h4>
                            <small class="text-muted">Unique IPs</small>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card bg-dark border-secondary">
                        <div class="card-body text-center">
                            <h4 class="text-success"><?php echo $stats['categories']; ?></h4>
                            <small class="text-muted">Categories</small>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Filters -->
            <div class="card bg-dark border-secondary mb-4">
                <div class="card-body">
                    <form method="GET" class="row g-3">
                        <div class="col-md-2">
                            <label class="form-label">Search</label>
                            <input type="text" name="search" 
                                   class="form-control bg-dark text-light border-secondary"
                                   placeholder="Action, IP..."
                                   value="<?php echo htmlspecialchars($filterSearch); ?>">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Category</label>
                            <select name="category" class="form-select bg-dark text-light border-secondary">
                                <option value="">All Categories</option>
                                <?php foreach ($categories as $cat): ?>
                                    <option value="<?php echo htmlspecialchars($cat); ?>" 
                                            <?php echo $filterCategory === $cat ? 'selected' : ''; ?>>
                                        <?php echo ucfirst($cat); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">User</label>
                            <select name="user" class="form-select bg-dark text-light border-secondary">
                                <option value="">All Users</option>
                                <?php foreach ($users as $user): ?>
                                    <option value="<?php echo $user['id']; ?>" 
                                            <?php echo $filterUser == $user['id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($user['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Date</label>
                            <input type="date" name="date" 
                                   class="form-control bg-dark text-light border-secondary"
                                   value="<?php echo htmlspecialchars($filterDate); ?>">
                        </div>
                        <div class="col-md-3 d-flex align-items-end gap-2">
                            <button type="submit" class="btn btn-danger">
                                <i class="bi bi-funnel me-1"></i>Filter
                            </button>
                            <a href="<?php echo SITE_URL; ?>/admin/logs.php" class="btn btn-secondary">Clear</a>
                        </div>
                    </form>
                </div>
            </div>
            
            <!-- Logs Table -->
            <div class="card bg-dark border-secondary">
                <div class="card-header bg-secondary">
                    <span class="badge bg-primary me-2"><?php echo count($logs); ?></span> log entries
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-dark table-hover table-sm mb-0">
                            <thead class="table-secondary">
                                <tr>
                                    <th>Timestamp</th>
                                    <th>User</th>
                                    <th>Category</th>
                                    <th>Action</th>
                                    <th>IP Address</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($logs)): ?>
                                    <tr>
                                        <td colspan="5" class="text-center py-4 text-muted">
                                            No logs found.
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($logs as $log): ?>
                                        <tr>
                                            <td>
                                                <small><?php echo formatDate($log['created_at'], 'M j, g:i:s A'); ?></small>
                                            </td>
                                            <td>
                                                <?php if ($log['user_name']): ?>
                                                    <span class="text-info"><?php echo htmlspecialchars($log['user_name']); ?></span>
                                                <?php else: ?>
                                                    <span class="text-muted">Guest</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php 
                                                    $categoryColors = [
                                                        'auth' => 'success',
                                                        'booking' => 'primary',
                                                        'admin' => 'danger',
                                                        'system' => 'warning',
                                                        'payment' => 'info'
                                                    ];
                                                    $color = $categoryColors[$log['category']] ?? 'secondary';
                                                ?>
                                                <span class="badge bg-<?php echo $color; ?>">
                                                    <?php echo ucfirst($log['category']); ?>
                                                </span>
                                            </td>
                                            <td><?php echo htmlspecialchars($log['action']); ?></td>
                                            <td>
                                                <code class="text-warning"><?php echo $log['ip_address']; ?></code>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            
        </div>
    </div>
</div>

<!-- Clear Logs Modal -->
<div class="modal fade" id="clearLogsModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content bg-dark text-light">
            <div class="modal-header border-secondary">
                <h5 class="modal-title">
                    <i class="bi bi-trash text-danger me-2"></i>Clear Old Logs
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                <input type="hidden" name="clear_logs" value="1">
                <div class="modal-body">
                    <p class="text-muted">This will permanently delete old log entries.</p>
                    <div class="mb-3">
                        <label class="form-label">Delete logs older than:</label>
                        <select name="older_than" class="form-select bg-dark text-light border-secondary">
                            <option value="7">7 days</option>
                            <option value="14">14 days</option>
                            <option value="30" selected>30 days</option>
                            <option value="60">60 days</option>
                            <option value="90">90 days</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer border-secondary">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger">Delete Logs</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include INCLUDES_PATH . 'footer.php'; ?>
