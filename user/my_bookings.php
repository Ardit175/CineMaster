<?php
/**
 * ============================================
 * CineMaster - My Bookings Page
 * ============================================
 * Shows user's booking history
 */

require_once '../config/config.php';

requireLogin();

$pageTitle = 'My Bookings';
$user = getCurrentUser();

// Get user's bookings
$bookings = getUserBookings($_SESSION['user_id']);

// Separate into upcoming and past
$upcomingBookings = [];
$pastBookings = [];

foreach ($bookings as $booking) {
    if (strtotime($booking['start_time']) > time()) {
        $upcomingBookings[] = $booking;
    } else {
        $pastBookings[] = $booking;
    }
}

include INCLUDES_PATH . 'header.php';
?>

<div class="container py-5">
    <div class="row">
        <!-- Sidebar -->
        <div class="col-lg-3 mb-4">
            <div class="card bg-dark border-secondary">
                <div class="card-body text-center">
                    <img src="<?php echo UPLOADS_URL; ?>/profiles/<?php echo $user['profile_photo'] ?? 'default.png'; ?>" 
                         class="rounded-circle mb-3" width="80" height="80"
                         alt="Profile Photo"
                         onerror="this.src='<?php echo ASSETS_URL; ?>/images/default-avatar.png'"
                         style="object-fit: cover;">
                    <h5 class="mb-1"><?php echo htmlspecialchars($user['name']); ?></h5>
                    <p class="text-muted small mb-0"><?php echo htmlspecialchars($user['email']); ?></p>
                </div>
            </div>
            
            <!-- Navigation -->
            <div class="list-group mt-3">
                <a href="<?php echo SITE_URL; ?>/user/profile.php" class="list-group-item list-group-item-action bg-dark text-light border-secondary">
                    <i class="bi bi-person me-2"></i>Profile Settings
                </a>
                <a href="<?php echo SITE_URL; ?>/user/my_bookings.php" class="list-group-item list-group-item-action bg-dark text-light border-secondary active">
                    <i class="bi bi-ticket-perforated me-2"></i>My Bookings
                </a>
                <a href="<?php echo SITE_URL; ?>/logout.php" class="list-group-item list-group-item-action bg-dark text-danger border-secondary">
                    <i class="bi bi-box-arrow-right me-2"></i>Logout
                </a>
            </div>
        </div>
        
        <!-- Main Content -->
        <div class="col-lg-9">
            <h2 class="fw-bold mb-4">
                <i class="bi bi-ticket-perforated text-danger me-2"></i>My Bookings
            </h2>
            
            <!-- Booking Stats -->
            <div class="row g-3 mb-4">
                <div class="col-md-4">
                    <div class="card bg-dark border-secondary h-100">
                        <div class="card-body text-center">
                            <i class="bi bi-calendar-check text-success fs-1 mb-2 d-block"></i>
                            <h3 class="mb-1"><?php echo count($upcomingBookings); ?></h3>
                            <small class="text-muted">Upcoming</small>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card bg-dark border-secondary h-100">
                        <div class="card-body text-center">
                            <i class="bi bi-check-circle text-info fs-1 mb-2 d-block"></i>
                            <h3 class="mb-1"><?php echo count($pastBookings); ?></h3>
                            <small class="text-muted">Past Bookings</small>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card bg-dark border-secondary h-100">
                        <div class="card-body text-center">
                            <i class="bi bi-receipt text-warning fs-1 mb-2 d-block"></i>
                            <h3 class="mb-1"><?php echo count($bookings); ?></h3>
                            <small class="text-muted">Total Bookings</small>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Tabs -->
            <ul class="nav nav-pills mb-4" id="bookingTabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" id="upcoming-tab" data-bs-toggle="pill" 
                            data-bs-target="#upcoming" type="button">
                        Upcoming (<?php echo count($upcomingBookings); ?>)
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="past-tab" data-bs-toggle="pill" 
                            data-bs-target="#past" type="button">
                        Past Bookings (<?php echo count($pastBookings); ?>)
                    </button>
                </li>
            </ul>
            
            <div class="tab-content" id="bookingTabsContent">
                <!-- Upcoming Bookings -->
                <div class="tab-pane fade show active" id="upcoming" role="tabpanel">
                    <?php if (empty($upcomingBookings)): ?>
                        <div class="text-center py-5">
                            <i class="bi bi-calendar-x fs-1 text-muted mb-3 d-block"></i>
                            <h5>No upcoming bookings</h5>
                            <p class="text-muted">Ready for your next movie experience?</p>
                            <a href="<?php echo SITE_URL; ?>/movies.php" class="btn btn-danger">
                                <i class="bi bi-film me-2"></i>Browse Movies
                            </a>
                        </div>
                    <?php else: ?>
                        <div class="row g-4">
                            <?php foreach ($upcomingBookings as $booking): ?>
                                <div class="col-12">
                                    <div class="card bg-dark border-secondary booking-card">
                                        <div class="row g-0">
                                            <div class="col-md-2">
                                                <img src="<?php echo UPLOADS_URL; ?>/movies/<?php echo $booking['poster_image']; ?>" 
                                                     class="img-fluid rounded-start h-100 object-fit-cover"
                                                     alt="<?php echo htmlspecialchars($booking['movie_title']); ?>"
                                                     onerror="this.src='<?php echo ASSETS_URL; ?>/images/default-poster.jpg'">
                                            </div>
                                            <div class="col-md-10">
                                                <div class="card-body">
                                                    <div class="d-flex justify-content-between align-items-start">
                                                        <div>
                                                            <h5 class="card-title text-danger mb-1">
                                                                <?php echo htmlspecialchars($booking['movie_title']); ?>
                                                            </h5>
                                                            <span class="badge bg-success mb-2">Upcoming</span>
                                                        </div>
                                                        <div class="text-end">
                                                            <span class="badge bg-secondary fs-6">
                                                                <?php echo $booking['booking_reference']; ?>
                                                            </span>
                                                        </div>
                                                    </div>
                                                    
                                                    <div class="row mt-3">
                                                        <div class="col-sm-6 col-md-3 mb-2">
                                                            <small class="text-muted d-block">Date & Time</small>
                                                            <strong><?php echo formatDateTime($booking['start_time']); ?></strong>
                                                        </div>
                                                        <div class="col-sm-6 col-md-3 mb-2">
                                                            <small class="text-muted d-block">Theater</small>
                                                            <strong><?php echo htmlspecialchars($booking['theater_name']); ?></strong>
                                                        </div>
                                                        <div class="col-sm-6 col-md-3 mb-2">
                                                            <small class="text-muted d-block">Seats</small>
                                                            <strong><?php echo $booking['seats']; ?></strong>
                                                        </div>
                                                        <div class="col-sm-6 col-md-3 mb-2">
                                                            <small class="text-muted d-block">Total</small>
                                                            <strong class="text-success"><?php echo formatPrice($booking['total_amount']); ?></strong>
                                                        </div>
                                                    </div>
                                                    
                                                    <div class="mt-3">
                                                        <a href="<?php echo SITE_URL; ?>/booking_confirmation.php?ref=<?php echo $booking['booking_reference']; ?>" 
                                                           class="btn btn-outline-danger btn-sm">
                                                            <i class="bi bi-ticket-perforated me-1"></i>View Ticket
                                                        </a>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
                
                <!-- Past Bookings -->
                <div class="tab-pane fade" id="past" role="tabpanel">
                    <?php if (empty($pastBookings)): ?>
                        <div class="text-center py-5">
                            <i class="bi bi-clock-history fs-1 text-muted mb-3 d-block"></i>
                            <h5>No past bookings</h5>
                            <p class="text-muted">Your booking history will appear here</p>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-dark table-hover">
                                <thead>
                                    <tr>
                                        <th>Movie</th>
                                        <th>Date</th>
                                        <th>Theater</th>
                                        <th>Seats</th>
                                        <th>Total</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($pastBookings as $booking): ?>
                                        <tr>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <img src="<?php echo UPLOADS_URL; ?>/movies/<?php echo $booking['poster_image']; ?>" 
                                                         width="40" class="rounded me-2"
                                                         onerror="this.src='<?php echo ASSETS_URL; ?>/images/default-poster.jpg'">
                                                    <?php echo htmlspecialchars($booking['movie_title']); ?>
                                                </div>
                                            </td>
                                            <td><?php echo formatDateTime($booking['start_time']); ?></td>
                                            <td><?php echo htmlspecialchars($booking['theater_name']); ?></td>
                                            <td><?php echo $booking['seats']; ?></td>
                                            <td><?php echo formatPrice($booking['total_amount']); ?></td>
                                            <td>
                                                <span class="badge bg-<?php echo $booking['payment_status'] === 'completed' ? 'success' : 'warning'; ?>">
                                                    <?php echo ucfirst($booking['payment_status']); ?>
                                                </span>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
        </div>
    </div>
</div>

<?php include INCLUDES_PATH . 'footer.php'; ?>
