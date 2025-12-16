<?php
/**
 * ============================================
 * CineMaster - Booking Confirmation Page
 * ============================================
 * Shows booking confirmation and ticket details
 */

require_once 'config/config.php';

requireLogin();

$pageTitle = 'Booking Confirmed';

// Get booking reference
$bookingRef = $_GET['ref'] ?? '';

if (empty($bookingRef)) {
    setFlashMessage('error', 'Invalid booking reference.');
    redirect(SITE_URL . '/user/my_bookings.php');
}

// Get booking details
$pdo = getDBConnection();
$stmt = $pdo->prepare("
    SELECT b.*, s.start_time, s.price,
           m.title AS movie_title, m.poster_image, m.duration,
           t.name AS theater_name,
           GROUP_CONCAT(st.seat_number SEPARATOR ', ') AS seats,
           u.name AS user_name, u.email AS user_email
    FROM bookings b
    JOIN showtimes s ON b.showtime_id = s.id
    JOIN movies m ON s.movie_id = m.id
    JOIN theaters t ON s.theater_id = t.id
    JOIN users u ON b.user_id = u.id
    LEFT JOIN seats st ON b.id = st.booking_id
    WHERE b.booking_reference = ? AND b.user_id = ?
    GROUP BY b.id
");
$stmt->execute([$bookingRef, $_SESSION['user_id']]);
$booking = $stmt->fetch();

if (!$booking) {
    setFlashMessage('error', 'Booking not found.');
    redirect(SITE_URL . '/user/my_bookings.php');
}

include INCLUDES_PATH . 'header.php';
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            
            <!-- Success Message -->
            <div class="text-center mb-5">
                <div class="success-checkmark mb-4">
                    <i class="bi bi-check-circle-fill text-success" style="font-size: 80px;"></i>
                </div>
                <h1 class="text-success fw-bold">Booking Confirmed!</h1>
                <p class="lead text-muted">Thank you for your purchase. Your tickets have been booked successfully.</p>
            </div>
            
            <!-- Ticket Card -->
            <div class="ticket-card bg-dark border-0 rounded-4 overflow-hidden shadow-lg mb-4">
                <!-- Ticket Header -->
                <div class="ticket-header bg-danger p-4">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h2 class="text-white mb-0"><?php echo htmlspecialchars($booking['movie_title']); ?></h2>
                            <p class="text-white-50 mb-0">E-Ticket</p>
                        </div>
                        <div class="text-end">
                            <span class="badge bg-light text-dark fs-6"><?php echo $booking['booking_reference']; ?></span>
                        </div>
                    </div>
                </div>
                
                <!-- Ticket Body -->
                <div class="ticket-body p-4">
                    <div class="row g-4">
                        <div class="col-md-3 text-center">
                            <img src="<?php echo UPLOADS_URL; ?>/movies/<?php echo $booking['poster_image']; ?>" 
                                 class="img-fluid rounded shadow"
                                 alt="<?php echo htmlspecialchars($booking['movie_title']); ?>"
                                 style="max-height: 200px;"
                                 onerror="this.src='<?php echo ASSETS_URL; ?>/images/default-poster.jpg'">
                        </div>
                        <div class="col-md-9">
                            <div class="row g-3">
                                <div class="col-6">
                                    <small class="text-muted d-block">Date & Time</small>
                                    <strong><?php echo formatDateTime($booking['start_time']); ?></strong>
                                </div>
                                <div class="col-6">
                                    <small class="text-muted d-block">Theater</small>
                                    <strong><?php echo htmlspecialchars($booking['theater_name']); ?></strong>
                                </div>
                                <div class="col-6">
                                    <small class="text-muted d-block">Seats</small>
                                    <strong class="text-danger">
                                        <?php 
                                        $seatArray = explode(', ', $booking['seats']);
                                        echo implode(', ', $seatArray); 
                                        ?> 
                                        <span class="text-muted">(<?php echo count($seatArray); ?> ticket<?php echo count($seatArray) > 1 ? 's' : ''; ?>)</span>
                                    </strong>
                                </div>
                                <div class="col-6">
                                    <small class="text-muted d-block">Duration</small>
                                    <strong><?php echo $booking['duration']; ?> minutes</strong>
                                </div>
                                <div class="col-6">
                                    <small class="text-muted d-block">Booking Date</small>
                                    <strong><?php echo formatDateTime($booking['booking_date']); ?></strong>
                                </div>
                                <div class="col-6">
                                    <small class="text-muted d-block">Total Paid</small>
                                    <strong class="text-success fs-5"><?php echo formatPrice($booking['total_amount']); ?></strong>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Dashed Line -->
                    <div class="ticket-divider my-4 border-top border-secondary" style="border-style: dashed !important;"></div>
                    
                    <!-- Customer & Payment Info -->
                    <div class="row g-3">
                        <div class="col-md-6">
                            <small class="text-muted d-block">Customer</small>
                            <strong><?php echo htmlspecialchars($booking['user_name']); ?></strong>
                            <br><small class="text-muted"><?php echo htmlspecialchars($booking['user_email']); ?></small>
                        </div>
                        <div class="col-md-6 text-md-end">
                            <small class="text-muted d-block">Payment Status</small>
                            <span class="badge bg-success fs-6">
                                <i class="bi bi-check-circle me-1"></i>
                                <?php echo ucfirst($booking['payment_status']); ?>
                            </span>
                            <?php if ($booking['stripe_payment_id']): ?>
                                <br><small class="text-muted">Ref: <?php echo htmlspecialchars($booking['stripe_payment_id']); ?></small>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                
                <!-- QR Code Section -->
                <div class="ticket-footer bg-black p-4 text-center">
                    <div class="qr-placeholder bg-white d-inline-block p-3 rounded mb-2">
                        <!-- QR Code would go here - using placeholder -->
                        <svg width="120" height="120" viewBox="0 0 120 120">
                            <rect fill="#fff" width="120" height="120"/>
                            <text x="60" y="60" text-anchor="middle" dominant-baseline="middle" font-size="10" fill="#000">
                                QR Code
                            </text>
                            <text x="60" y="75" text-anchor="middle" dominant-baseline="middle" font-size="8" fill="#666">
                                <?php echo $booking['booking_reference']; ?>
                            </text>
                        </svg>
                    </div>
                    <p class="text-muted small mb-0">
                        Show this QR code at the cinema entrance
                    </p>
                </div>
            </div>
            
            <!-- Action Buttons -->
            <div class="d-flex gap-3 justify-content-center mb-5">
                <button onclick="window.print()" class="btn btn-outline-light">
                    <i class="bi bi-printer me-2"></i>Print Ticket
                </button>
                <a href="<?php echo SITE_URL; ?>/user/my_bookings.php" class="btn btn-outline-danger">
                    <i class="bi bi-ticket-perforated me-2"></i>View All Bookings
                </a>
                <a href="<?php echo SITE_URL; ?>/movies.php" class="btn btn-danger">
                    <i class="bi bi-film me-2"></i>Book More Movies
                </a>
            </div>
            
            <!-- Important Info -->
            <div class="card bg-dark border-secondary">
                <div class="card-body">
                    <h5 class="card-title">
                        <i class="bi bi-info-circle text-info me-2"></i>Important Information
                    </h5>
                    <ul class="text-muted small mb-0">
                        <li>Please arrive at least 15 minutes before the showtime.</li>
                        <li>Show your e-ticket (printed or on mobile) at the entrance.</li>
                        <li>Outside food and beverages are not allowed.</li>
                        <li>Tickets are non-refundable and non-transferable.</li>
                        <li>For assistance, contact support@cinemaster.com</li>
                    </ul>
                </div>
            </div>
            
        </div>
    </div>
</div>

<!-- Print Styles -->
<style>
@media print {
    .navbar, footer, .btn, .card:last-child {
        display: none !important;
    }
    body {
        background: white !important;
        color: black !important;
    }
    .ticket-card {
        box-shadow: none !important;
        border: 2px solid #000 !important;
    }
    .text-muted {
        color: #666 !important;
    }
}

.success-checkmark {
    animation: checkmark 0.5s ease-in-out;
}

@keyframes checkmark {
    0% { transform: scale(0); opacity: 0; }
    50% { transform: scale(1.2); }
    100% { transform: scale(1); opacity: 1; }
}
</style>

<?php include INCLUDES_PATH . 'footer.php'; ?>
