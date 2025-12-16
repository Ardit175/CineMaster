<?php
/**
 * ============================================
 * CineMaster - Admin Dashboard
 * ============================================
 * Main admin control panel with statistics
 */

require_once '../config/config.php';

requireAdmin();

$pageTitle = 'Admin Dashboard';

// Get dashboard statistics
$stats = getDashboardStats();
$recentLogs = getRecentLogs(10);

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
                    <a href="<?php echo SITE_URL; ?>/admin/dashboard.php" class="list-group-item list-group-item-action bg-dark text-light border-secondary active">
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
                    <a href="<?php echo SITE_URL; ?>/admin/logs.php" class="list-group-item list-group-item-action bg-dark text-light border-secondary">
                        <i class="bi bi-journal-text me-2"></i>Logs
                    </a>
                    <a href="<?php echo SITE_URL; ?>" class="list-group-item list-group-item-action bg-dark text-warning border-secondary">
                        <i class="bi bi-house me-2"></i>Back to Site
                    </a>
                </div>
            </div>
        </div>
        
        <!-- Main Content -->
        <div class="col-lg-10">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h2 class="fw-bold mb-1">
                        <i class="bi bi-speedometer2 text-danger me-2"></i>Dashboard
                    </h2>
                    <p class="text-muted mb-0">Welcome back, <?php echo htmlspecialchars($_SESSION['user_name']); ?>!</p>
                </div>
                <div>
                    <span class="text-muted">
                        <i class="bi bi-calendar me-1"></i>
                        <?php echo date('l, F j, Y'); ?>
                    </span>
                </div>
            </div>
            
            <!-- Stats Cards -->
            <div class="row g-4 mb-4">
                <div class="col-xl-3 col-md-6">
                    <div class="card bg-primary text-white h-100">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="text-white-50">Total Users</h6>
                                    <h2 class="mb-0"><?php echo number_format($stats['total_users']); ?></h2>
                                </div>
                                <i class="bi bi-people fs-1 opacity-50"></i>
                            </div>
                        </div>
                        <div class="card-footer bg-transparent border-0">
                            <a href="<?php echo SITE_URL; ?>/admin/users.php" class="text-white text-decoration-none small">
                                View all <i class="bi bi-arrow-right"></i>
                            </a>
                        </div>
                    </div>
                </div>
                
                <div class="col-xl-3 col-md-6">
                    <div class="card bg-success text-white h-100">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="text-white-50">Total Revenue</h6>
                                    <h2 class="mb-0"><?php echo formatPrice($stats['total_revenue']); ?></h2>
                                </div>
                                <i class="bi bi-currency-dollar fs-1 opacity-50"></i>
                            </div>
                        </div>
                        <div class="card-footer bg-transparent border-0">
                            <small class="text-white-50">From completed bookings</small>
                        </div>
                    </div>
                </div>
                
                <div class="col-xl-3 col-md-6">
                    <div class="card bg-warning text-dark h-100">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="text-dark opacity-75">Total Bookings</h6>
                                    <h2 class="mb-0"><?php echo number_format($stats['total_bookings']); ?></h2>
                                </div>
                                <i class="bi bi-ticket-perforated fs-1 opacity-50"></i>
                            </div>
                        </div>
                        <div class="card-footer bg-transparent border-0">
                            <a href="<?php echo SITE_URL; ?>/admin/bookings.php" class="text-dark text-decoration-none small">
                                View all <i class="bi bi-arrow-right"></i>
                            </a>
                        </div>
                    </div>
                </div>
                
                <div class="col-xl-3 col-md-6">
                    <div class="card bg-danger text-white h-100">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="text-white-50">Total Movies</h6>
                                    <h2 class="mb-0"><?php echo number_format($stats['total_movies']); ?></h2>
                                </div>
                                <i class="bi bi-film fs-1 opacity-50"></i>
                            </div>
                        </div>
                        <div class="card-footer bg-transparent border-0">
                            <a href="<?php echo SITE_URL; ?>/admin/movies.php" class="text-white text-decoration-none small">
                                Manage movies <i class="bi bi-arrow-right"></i>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="row g-4">
                <!-- Recent Bookings -->
                <div class="col-lg-8">
                    <div class="card bg-dark border-secondary h-100">
                        <div class="card-header bg-secondary d-flex justify-content-between align-items-center">
                            <h5 class="mb-0">
                                <i class="bi bi-receipt me-2"></i>Recent Bookings
                            </h5>
                            <a href="<?php echo SITE_URL; ?>/admin/bookings.php" class="btn btn-sm btn-outline-light">
                                View All
                            </a>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-dark table-hover mb-0">
                                    <thead>
                                        <tr>
                                            <th>Reference</th>
                                            <th>Customer</th>
                                            <th>Movie</th>
                                            <th>Amount</th>
                                            <th>Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (empty($stats['recent_bookings'])): ?>
                                            <tr>
                                                <td colspan="5" class="text-center text-muted py-4">No bookings yet</td>
                                            </tr>
                                        <?php else: ?>
                                            <?php foreach ($stats['recent_bookings'] as $booking): ?>
                                                <tr>
                                                    <td>
                                                        <span class="badge bg-secondary"><?php echo $booking['booking_reference']; ?></span>
                                                    </td>
                                                    <td><?php echo htmlspecialchars($booking['user_name']); ?></td>
                                                    <td><?php echo htmlspecialchars(substr($booking['movie_title'], 0, 20)); ?></td>
                                                    <td><?php echo formatPrice($booking['total_amount']); ?></td>
                                                    <td>
                                                        <span class="badge bg-<?php echo $booking['payment_status'] === 'completed' ? 'success' : 'warning'; ?>">
                                                            <?php echo ucfirst($booking['payment_status']); ?>
                                                        </span>
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
                
                <!-- Quick Stats & Activity -->
                <div class="col-lg-4">
                    <!-- Monthly Stats -->
                    <div class="card bg-dark border-secondary mb-4">
                        <div class="card-header bg-info text-dark">
                            <h6 class="mb-0">
                                <i class="bi bi-graph-up me-2"></i>This Month
                            </h6>
                        </div>
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <span>Bookings</span>
                                <span class="badge bg-info text-dark fs-6"><?php echo $stats['monthly_bookings']; ?></span>
                            </div>
                            <div class="progress" style="height: 10px;">
                                <div class="progress-bar bg-info" style="width: <?php echo min(100, ($stats['monthly_bookings'] / max(1, $stats['total_bookings'])) * 100); ?>%"></div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Recent Activity -->
                    <div class="card bg-dark border-secondary">
                        <div class="card-header bg-secondary">
                            <h6 class="mb-0">
                                <i class="bi bi-activity me-2"></i>Recent Activity
                            </h6>
                        </div>
                        <div class="card-body p-0">
                            <ul class="list-group list-group-flush">
                                <?php foreach (array_slice($recentLogs, 0, 5) as $log): ?>
                                    <li class="list-group-item bg-dark text-light border-secondary">
                                        <small class="text-muted d-block">
                                            <?php echo formatDateTime($log['timestamp']); ?>
                                        </small>
                                        <span class="small">
                                            <strong><?php echo htmlspecialchars($log['user_name'] ?? 'System'); ?></strong>
                                            - <?php echo htmlspecialchars($log['action']); ?>
                                        </span>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                        <div class="card-footer bg-transparent border-secondary">
                            <a href="<?php echo SITE_URL; ?>/admin/logs.php" class="text-danger text-decoration-none small">
                                View all logs <i class="bi bi-arrow-right"></i>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Quick Actions -->
            <div class="row mt-4">
                <div class="col-12">
                    <div class="card bg-dark border-secondary">
                        <div class="card-header">
                            <h5 class="mb-0">
                                <i class="bi bi-lightning me-2"></i>Quick Actions
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="row g-3">
                                <div class="col-md-3">
                                    <a href="<?php echo SITE_URL; ?>/admin/movies.php?action=add" class="btn btn-outline-danger w-100 py-3">
                                        <i class="bi bi-plus-circle fs-4 d-block mb-1"></i>
                                        Add New Movie
                                    </a>
                                </div>
                                <div class="col-md-3">
                                    <a href="<?php echo SITE_URL; ?>/admin/showtimes.php?action=add" class="btn btn-outline-warning w-100 py-3">
                                        <i class="bi bi-calendar-plus fs-4 d-block mb-1"></i>
                                        Add Showtime
                                    </a>
                                </div>
                                <div class="col-md-3">
                                    <a href="<?php echo SITE_URL; ?>/admin/users.php" class="btn btn-outline-info w-100 py-3">
                                        <i class="bi bi-people fs-4 d-block mb-1"></i>
                                        Manage Users
                                    </a>
                                </div>
                                <div class="col-md-3">
                                    <a href="<?php echo SITE_URL; ?>/admin/logs.php" class="btn btn-outline-secondary w-100 py-3">
                                        <i class="bi bi-journal-text fs-4 d-block mb-1"></i>
                                        View Logs
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
        </div>
    </div>
</div>

<?php include INCLUDES_PATH . 'footer.php'; ?>
