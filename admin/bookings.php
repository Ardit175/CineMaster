<?php
/**
 * ============================================
 * CineMaster - Admin Bookings Management
 * ============================================
 * View and manage all bookings in the system
 */

require_once '../config/config.php';

requireAdmin();

$pageTitle = 'Manage Bookings';
$errors = [];
$success = '';

$pdo = getDBConnection();

// Handle actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Invalid request.';
    } else {
        $action = $_POST['action'] ?? '';
        
        // Update booking status
        if ($action === 'update_status' && isset($_POST['booking_id'], $_POST['status'])) {
            $bookingId = (int)$_POST['booking_id'];
            $newStatus = $_POST['status'];
            
            $stmt = $pdo->prepare("UPDATE bookings SET status = ? WHERE id = ?");
            if ($stmt->execute([$newStatus, $bookingId])) {
                $success = 'Booking status updated.';
                logAction($_SESSION['user_id'], "Updated booking #{$bookingId} status to {$newStatus}", 'admin');
            } else {
                $errors[] = 'Failed to update booking status.';
            }
        }
        
        // Cancel booking
        if ($action === 'cancel' && isset($_POST['booking_id'])) {
            $bookingId = (int)$_POST['booking_id'];
            
            try {
                $pdo->beginTransaction();
                
                // Get booking details
                $bookingStmt = $pdo->prepare("SELECT * FROM bookings WHERE id = ?");
                $bookingStmt->execute([$bookingId]);
                $booking = $bookingStmt->fetch();
                
                if ($booking) {
                    // Update booking status
                    $updateStmt = $pdo->prepare("UPDATE bookings SET status = 'cancelled' WHERE id = ?");
                    $updateStmt->execute([$bookingId]);
                    
                    // Delete associated seats
                    $seatStmt = $pdo->prepare("DELETE FROM seats WHERE booking_id = ?");
                    $seatStmt->execute([$bookingId]);
                    
                    $pdo->commit();
                    $success = 'Booking cancelled successfully.';
                    logAction($_SESSION['user_id'], "Cancelled booking #{$bookingId}", 'admin');
                } else {
                    throw new Exception('Booking not found.');
                }
                
            } catch (Exception $e) {
                $pdo->rollBack();
                $errors[] = $e->getMessage();
            }
        }
    }
}

// Get filter values
$filterStatus = $_GET['status'] ?? '';
$filterDate = $_GET['date'] ?? '';
$filterSearch = $_GET['search'] ?? '';

// Build query with filters
$query = "
    SELECT b.*, u.name as user_name, u.email as user_email,
           m.title as movie_title, t.name as theater_name,
           s.show_date, s.show_time
    FROM bookings b
    JOIN users u ON b.user_id = u.id
    JOIN showtimes s ON b.showtime_id = s.id
    JOIN movies m ON s.movie_id = m.id
    JOIN theaters t ON s.theater_id = t.id
    WHERE 1=1
";
$params = [];

if ($filterStatus) {
    $query .= " AND b.status = ?";
    $params[] = $filterStatus;
}
if ($filterDate) {
    $query .= " AND DATE(b.created_at) = ?";
    $params[] = $filterDate;
}
if ($filterSearch) {
    $query .= " AND (b.booking_number LIKE ? OR u.name LIKE ? OR u.email LIKE ? OR m.title LIKE ?)";
    $search = "%{$filterSearch}%";
    $params = array_merge($params, [$search, $search, $search, $search]);
}

$query .= " ORDER BY b.created_at DESC";

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$bookings = $stmt->fetchAll();

// Get booking statistics
$statsStmt = $pdo->query("
    SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN status = 'confirmed' THEN 1 ELSE 0 END) as confirmed,
        SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
        SUM(CASE WHEN status = 'cancelled' THEN 1 ELSE 0 END) as cancelled,
        SUM(CASE WHEN status = 'confirmed' THEN total_amount ELSE 0 END) as total_revenue
    FROM bookings
");
$stats = $statsStmt->fetch();

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
                    <a href="<?php echo SITE_URL; ?>/admin/bookings.php" class="list-group-item list-group-item-action bg-dark text-light border-secondary active">
                        <i class="bi bi-ticket-perforated me-2"></i>Bookings
                    </a>
                    <a href="<?php echo SITE_URL; ?>/admin/logs.php" class="list-group-item list-group-item-action bg-dark text-light border-secondary">
                        <i class="bi bi-journal-text me-2"></i>Logs
                    </a>
                </div>
            </div>
        </div>
        
        <!-- Main Content -->
        <div class="col-lg-10">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2 class="fw-bold">
                    <i class="bi bi-ticket-perforated text-danger me-2"></i>Booking Management
                </h2>
            </div>
            
            <?php if (!empty($errors)): ?>
                <div class="alert alert-danger">
                    <ul class="mb-0">
                        <?php foreach ($errors as $error): ?>
                            <li><?php echo htmlspecialchars($error); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="alert alert-success">
                    <i class="bi bi-check-circle me-2"></i><?php echo htmlspecialchars($success); ?>
                </div>
            <?php endif; ?>
            
            <!-- Stats Cards -->
            <div class="row g-3 mb-4">
                <div class="col-md-3">
                    <div class="card bg-primary text-white">
                        <div class="card-body">
                            <h4><?php echo $stats['total']; ?></h4>
                            <small>Total Bookings</small>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card bg-success text-white">
                        <div class="card-body">
                            <h4><?php echo $stats['confirmed']; ?></h4>
                            <small>Confirmed</small>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card bg-warning text-dark">
                        <div class="card-body">
                            <h4><?php echo $stats['pending']; ?></h4>
                            <small>Pending</small>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card bg-danger text-white">
                        <div class="card-body">
                            <h4>$<?php echo number_format($stats['total_revenue'], 2); ?></h4>
                            <small>Total Revenue</small>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Filters -->
            <div class="card bg-dark border-secondary mb-4">
                <div class="card-body">
                    <form method="GET" class="row g-3">
                        <div class="col-md-3">
                            <label class="form-label">Search</label>
                            <input type="text" name="search" 
                                   class="form-control bg-dark text-light border-secondary"
                                   placeholder="Booking #, Name, Email..."
                                   value="<?php echo htmlspecialchars($filterSearch); ?>">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Status</label>
                            <select name="status" class="form-select bg-dark text-light border-secondary">
                                <option value="">All Statuses</option>
                                <option value="confirmed" <?php echo $filterStatus === 'confirmed' ? 'selected' : ''; ?>>Confirmed</option>
                                <option value="pending" <?php echo $filterStatus === 'pending' ? 'selected' : ''; ?>>Pending</option>
                                <option value="cancelled" <?php echo $filterStatus === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Booking Date</label>
                            <input type="date" name="date" 
                                   class="form-control bg-dark text-light border-secondary"
                                   value="<?php echo htmlspecialchars($filterDate); ?>">
                        </div>
                        <div class="col-md-3 d-flex align-items-end gap-2">
                            <button type="submit" class="btn btn-danger">
                                <i class="bi bi-funnel me-1"></i>Filter
                            </button>
                            <a href="<?php echo SITE_URL; ?>/admin/bookings.php" class="btn btn-secondary">Clear</a>
                        </div>
                    </form>
                </div>
            </div>
            
            <!-- Bookings Table -->
            <div class="card bg-dark border-secondary">
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-dark table-hover mb-0">
                            <thead class="table-secondary">
                                <tr>
                                    <th>Booking #</th>
                                    <th>Customer</th>
                                    <th>Movie</th>
                                    <th>Date & Time</th>
                                    <th>Seats</th>
                                    <th>Amount</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($bookings)): ?>
                                    <tr>
                                        <td colspan="8" class="text-center py-4 text-muted">
                                            No bookings found.
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($bookings as $booking): ?>
                                        <tr>
                                            <td>
                                                <strong class="text-danger"><?php echo $booking['booking_number']; ?></strong>
                                                <br><small class="text-muted"><?php echo formatDate($booking['created_at'], 'M j, g:i A'); ?></small>
                                            </td>
                                            <td>
                                                <?php echo htmlspecialchars($booking['user_name']); ?>
                                                <br><small class="text-muted"><?php echo htmlspecialchars($booking['user_email']); ?></small>
                                            </td>
                                            <td>
                                                <?php echo htmlspecialchars($booking['movie_title']); ?>
                                                <br><small class="text-muted"><?php echo htmlspecialchars($booking['theater_name']); ?></small>
                                            </td>
                                            <td>
                                                <?php echo formatDate($booking['show_date']); ?>
                                                <br><small><?php echo date('g:i A', strtotime($booking['show_time'])); ?></small>
                                            </td>
                                            <td><?php echo $booking['seats_booked']; ?></td>
                                            <td><strong>$<?php echo number_format($booking['total_amount'], 2); ?></strong></td>
                                            <td>
                                                <form method="POST" class="d-inline">
                                                    <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                                                    <input type="hidden" name="action" value="update_status">
                                                    <input type="hidden" name="booking_id" value="<?php echo $booking['id']; ?>">
                                                    <select name="status" class="form-select form-select-sm bg-dark text-light border-secondary"
                                                            onchange="this.form.submit()" style="width: 110px;">
                                                        <option value="confirmed" <?php echo $booking['status'] === 'confirmed' ? 'selected' : ''; ?>>Confirmed</option>
                                                        <option value="pending" <?php echo $booking['status'] === 'pending' ? 'selected' : ''; ?>>Pending</option>
                                                        <option value="cancelled" <?php echo $booking['status'] === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                                                    </select>
                                                </form>
                                            </td>
                                            <td>
                                                <!-- View Details Modal Trigger -->
                                                <button class="btn btn-sm btn-outline-info" 
                                                        data-bs-toggle="modal" 
                                                        data-bs-target="#bookingModal<?php echo $booking['id']; ?>"
                                                        title="View Details">
                                                    <i class="bi bi-eye"></i>
                                                </button>
                                                
                                                <?php if ($booking['status'] !== 'cancelled'): ?>
                                                    <form method="POST" class="d-inline" 
                                                          onsubmit="return confirm('Cancel this booking?');">
                                                        <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                                                        <input type="hidden" name="action" value="cancel">
                                                        <input type="hidden" name="booking_id" value="<?php echo $booking['id']; ?>">
                                                        <button type="submit" class="btn btn-sm btn-outline-danger" title="Cancel">
                                                            <i class="bi bi-x-circle"></i>
                                                        </button>
                                                    </form>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                        
                                        <!-- Booking Details Modal -->
                                        <div class="modal fade" id="bookingModal<?php echo $booking['id']; ?>" tabindex="-1">
                                            <div class="modal-dialog">
                                                <div class="modal-content bg-dark text-light">
                                                    <div class="modal-header border-secondary">
                                                        <h5 class="modal-title">
                                                            <i class="bi bi-ticket-perforated text-danger me-2"></i>
                                                            Booking #<?php echo $booking['booking_number']; ?>
                                                        </h5>
                                                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                                    </div>
                                                    <div class="modal-body">
                                                        <div class="row g-3">
                                                            <div class="col-6">
                                                                <small class="text-muted">Customer</small>
                                                                <p class="mb-0"><?php echo htmlspecialchars($booking['user_name']); ?></p>
                                                            </div>
                                                            <div class="col-6">
                                                                <small class="text-muted">Email</small>
                                                                <p class="mb-0"><?php echo htmlspecialchars($booking['user_email']); ?></p>
                                                            </div>
                                                            <div class="col-6">
                                                                <small class="text-muted">Movie</small>
                                                                <p class="mb-0"><?php echo htmlspecialchars($booking['movie_title']); ?></p>
                                                            </div>
                                                            <div class="col-6">
                                                                <small class="text-muted">Theater</small>
                                                                <p class="mb-0"><?php echo htmlspecialchars($booking['theater_name']); ?></p>
                                                            </div>
                                                            <div class="col-6">
                                                                <small class="text-muted">Show Date</small>
                                                                <p class="mb-0"><?php echo formatDate($booking['show_date']); ?></p>
                                                            </div>
                                                            <div class="col-6">
                                                                <small class="text-muted">Show Time</small>
                                                                <p class="mb-0"><?php echo date('g:i A', strtotime($booking['show_time'])); ?></p>
                                                            </div>
                                                            <div class="col-6">
                                                                <small class="text-muted">Seats</small>
                                                                <p class="mb-0"><?php echo $booking['seats_booked']; ?> seat(s)</p>
                                                            </div>
                                                            <div class="col-6">
                                                                <small class="text-muted">Total Amount</small>
                                                                <p class="mb-0 text-success">$<?php echo number_format($booking['total_amount'], 2); ?></p>
                                                            </div>
                                                            <div class="col-6">
                                                                <small class="text-muted">Status</small>
                                                                <p class="mb-0">
                                                                    <?php
                                                                    $statusClass = [
                                                                        'confirmed' => 'success',
                                                                        'pending' => 'warning',
                                                                        'cancelled' => 'danger'
                                                                    ];
                                                                    ?>
                                                                    <span class="badge bg-<?php echo $statusClass[$booking['status']]; ?>">
                                                                        <?php echo ucfirst($booking['status']); ?>
                                                                    </span>
                                                                </p>
                                                            </div>
                                                            <div class="col-6">
                                                                <small class="text-muted">Booked On</small>
                                                                <p class="mb-0"><?php echo formatDate($booking['created_at'], 'M j, Y g:i A'); ?></p>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
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

<?php include INCLUDES_PATH . 'footer.php'; ?>
