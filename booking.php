<?php
/**
 * ============================================
 * CineMaster - Booking Page
 * ============================================
 * Handles seat selection and booking process
 * Integrates with Stripe for payment processing
 */

require_once 'config/config.php';

// Require login
requireLogin(SITE_URL . '/booking.php?showtime=' . ($_GET['showtime'] ?? ''));

$pageTitle = 'Book Tickets';

// Get showtime ID
$showtimeId = isset($_GET['showtime']) ? (int)$_GET['showtime'] : 0;

if (!$showtimeId) {
    setFlashMessage('error', 'Please select a showtime first.');
    redirect(SITE_URL . '/movies.php');
}

// Get showtime details
$showtime = getShowtimeById($showtimeId);

if (!$showtime) {
    setFlashMessage('error', 'Showtime not found.');
    redirect(SITE_URL . '/movies.php');
}

// Check if showtime is in the future
if (strtotime($showtime['start_time']) <= time()) {
    setFlashMessage('error', 'This showtime has already passed.');
    redirect(SITE_URL . '/movie.php?id=' . $showtime['movie_id']);
}

// Get already booked seats
$bookedSeats = getBookedSeats($showtimeId);

// Generate seat layout
$rows = $showtime['rows_count'];
$seatsPerRow = $showtime['seats_per_row'];

// Create row labels (A, B, C, etc.)
$rowLabels = range('A', chr(ord('A') + $rows - 1));

$errors = [];
$csrfToken = generateCSRFToken();

include INCLUDES_PATH . 'header.php';
?>

<div class="container py-5">
    <!-- Breadcrumb -->
    <nav aria-label="breadcrumb" class="mb-4">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="<?php echo SITE_URL; ?>">Home</a></li>
            <li class="breadcrumb-item"><a href="<?php echo SITE_URL; ?>/movies.php">Movies</a></li>
            <li class="breadcrumb-item">
                <a href="<?php echo SITE_URL; ?>/movie.php?id=<?php echo $showtime['movie_id']; ?>">
                    <?php echo htmlspecialchars($showtime['movie_title']); ?>
                </a>
            </li>
            <li class="breadcrumb-item active">Book Tickets</li>
        </ol>
    </nav>
    
    <div class="row g-4">
        <!-- Seat Selection -->
        <div class="col-lg-8">
            <div class="card bg-dark border-secondary">
                <div class="card-header bg-danger">
                    <h5 class="mb-0">
                        <i class="bi bi-grid-3x3-gap me-2"></i>Select Your Seats
                    </h5>
                </div>
                <div class="card-body">
                    <!-- Screen Indicator -->
                    <div class="screen-indicator text-center mb-4">
                        <div class="screen bg-light text-dark py-2 rounded mx-auto" style="max-width: 80%;">
                            <i class="bi bi-display me-2"></i>SCREEN
                        </div>
                        <small class="text-muted">All eyes this way please!</small>
                    </div>
                    
                    <!-- Seat Legend -->
                    <div class="seat-legend d-flex justify-content-center gap-4 mb-4">
                        <div><span class="seat-demo available"></span> Available</div>
                        <div><span class="seat-demo selected"></span> Selected</div>
                        <div><span class="seat-demo booked"></span> Booked</div>
                    </div>
                    
                    <!-- Seat Layout -->
                    <div class="seat-layout text-center">
                        <?php foreach ($rowLabels as $index => $rowLabel): ?>
                            <div class="seat-row d-flex justify-content-center align-items-center mb-2">
                                <span class="row-label me-3"><?php echo $rowLabel; ?></span>
                                
                                <?php for ($seatNum = 1; $seatNum <= $seatsPerRow; $seatNum++): ?>
                                    <?php 
                                    $seatId = $rowLabel . $seatNum;
                                    $isBooked = in_array($seatId, $bookedSeats);
                                    ?>
                                    
                                    <?php if ($seatNum == ceil($seatsPerRow / 2) + 1): ?>
                                        <span class="seat-gap"></span>
                                    <?php endif; ?>
                                    
                                    <div class="seat <?php echo $isBooked ? 'booked' : 'available'; ?>" 
                                         data-seat="<?php echo $seatId; ?>"
                                         data-price="<?php echo $showtime['price']; ?>"
                                         title="Seat <?php echo $seatId; ?> - <?php echo $isBooked ? 'Booked' : formatPrice($showtime['price']); ?>">
                                        <?php echo $seatNum; ?>
                                    </div>
                                <?php endfor; ?>
                                
                                <span class="row-label ms-3"><?php echo $rowLabel; ?></span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <p class="text-center text-muted mt-4 small">
                        <i class="bi bi-info-circle me-1"></i>
                        Click on available seats to select them. Maximum 10 seats per booking.
                    </p>
                </div>
            </div>
        </div>
        
        <!-- Booking Summary -->
        <div class="col-lg-4">
            <!-- Movie Info Card -->
            <div class="card bg-dark border-secondary mb-4">
                <div class="row g-0">
                    <div class="col-4">
                        <img src="<?php echo UPLOADS_URL; ?>/movies/<?php echo $showtime['poster_image']; ?>" 
                             class="img-fluid rounded-start h-100 object-fit-cover"
                             alt="<?php echo htmlspecialchars($showtime['movie_title']); ?>"
                             onerror="this.src='<?php echo ASSETS_URL; ?>/images/default-poster.jpg'">
                    </div>
                    <div class="col-8">
                        <div class="card-body">
                            <h5 class="card-title text-danger"><?php echo htmlspecialchars($showtime['movie_title']); ?></h5>
                            <p class="card-text small mb-1">
                                <i class="bi bi-calendar me-1"></i>
                                <?php echo formatDateTime($showtime['start_time']); ?>
                            </p>
                            <p class="card-text small mb-1">
                                <i class="bi bi-building me-1"></i>
                                <?php echo htmlspecialchars($showtime['theater_name']); ?>
                            </p>
                            <p class="card-text small mb-0">
                                <i class="bi bi-clock me-1"></i>
                                <?php echo $showtime['duration']; ?> minutes
                            </p>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Booking Summary Card -->
            <div class="card bg-dark border-secondary sticky-top" style="top: 100px;">
                <div class="card-header bg-secondary">
                    <h5 class="mb-0">
                        <i class="bi bi-receipt me-2"></i>Booking Summary
                    </h5>
                </div>
                <div class="card-body">
                    <form id="bookingForm" action="<?php echo SITE_URL; ?>/checkout.php" method="POST">
                        <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                        <input type="hidden" name="showtime_id" value="<?php echo $showtimeId; ?>">
                        <input type="hidden" name="seats" id="selectedSeatsInput" value="">
                        <input type="hidden" name="total" id="totalAmountInput" value="">
                        
                        <div class="mb-3">
                            <label class="form-label text-muted small">Selected Seats</label>
                            <div id="selectedSeatsList" class="fw-bold text-light">
                                <span class="text-muted">No seats selected</span>
                            </div>
                        </div>
                        
                        <hr class="border-secondary">
                        
                        <div class="d-flex justify-content-between mb-2">
                            <span>Price per seat:</span>
                            <span><?php echo formatPrice($showtime['price']); ?></span>
                        </div>
                        <div class="d-flex justify-content-between mb-2">
                            <span>Seats:</span>
                            <span id="seatCount">0</span>
                        </div>
                        <div class="d-flex justify-content-between mb-2">
                            <span>Subtotal:</span>
                            <span id="subtotal">$0.00</span>
                        </div>
                        <div class="d-flex justify-content-between mb-2 text-muted small">
                            <span>Booking Fee:</span>
                            <span>$1.50</span>
                        </div>
                        
                        <hr class="border-secondary">
                        
                        <div class="d-flex justify-content-between mb-4">
                            <span class="fw-bold fs-5">Total:</span>
                            <span class="fw-bold fs-5 text-success" id="totalAmount">$0.00</span>
                        </div>
                        
                        <div class="d-grid">
                            <button type="submit" class="btn btn-danger btn-lg" id="proceedBtn" disabled>
                                <i class="bi bi-credit-card me-2"></i>Proceed to Payment
                            </button>
                        </div>
                    </form>
                    
                    <p class="text-muted small mt-3 mb-0 text-center">
                        <i class="bi bi-shield-check me-1"></i>Secure payment powered by Stripe
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
/* Seat Selection Styles */
.screen {
    background: linear-gradient(to bottom, #dee2e6, #adb5bd);
    box-shadow: 0 5px 15px rgba(255,255,255,0.1);
}

.seat-demo {
    display: inline-block;
    width: 20px;
    height: 20px;
    border-radius: 4px;
    margin-right: 5px;
    vertical-align: middle;
}
.seat-demo.available { background: #28a745; }
.seat-demo.selected { background: #dc3545; }
.seat-demo.booked { background: #6c757d; }

.seat {
    width: 35px;
    height: 35px;
    margin: 2px;
    border-radius: 5px 5px 8px 8px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-size: 12px;
    font-weight: bold;
    cursor: pointer;
    transition: all 0.2s;
}

.seat.available {
    background: #28a745;
    color: white;
}
.seat.available:hover {
    background: #218838;
    transform: scale(1.1);
}

.seat.selected {
    background: #dc3545;
    color: white;
    transform: scale(1.05);
}

.seat.booked {
    background: #6c757d;
    color: #999;
    cursor: not-allowed;
}

.row-label {
    width: 25px;
    font-weight: bold;
    color: #888;
}

.seat-gap {
    width: 30px;
    display: inline-block;
}

@media (max-width: 768px) {
    .seat {
        width: 28px;
        height: 28px;
        font-size: 10px;
        margin: 1px;
    }
    .seat-gap {
        width: 15px;
    }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const pricePerSeat = <?php echo $showtime['price']; ?>;
    const bookingFee = 1.50;
    const maxSeats = 10;
    let selectedSeats = [];
    
    // Handle seat clicks
    document.querySelectorAll('.seat.available').forEach(seat => {
        seat.addEventListener('click', function() {
            const seatId = this.dataset.seat;
            
            if (this.classList.contains('selected')) {
                // Deselect
                this.classList.remove('selected');
                selectedSeats = selectedSeats.filter(s => s !== seatId);
            } else {
                // Check max seats
                if (selectedSeats.length >= maxSeats) {
                    alert('Maximum ' + maxSeats + ' seats per booking.');
                    return;
                }
                // Select
                this.classList.add('selected');
                selectedSeats.push(seatId);
            }
            
            updateSummary();
        });
    });
    
    // Update booking summary
    function updateSummary() {
        const seatCount = selectedSeats.length;
        const subtotal = seatCount * pricePerSeat;
        const total = seatCount > 0 ? subtotal + bookingFee : 0;
        
        // Update display
        document.getElementById('seatCount').textContent = seatCount;
        document.getElementById('subtotal').textContent = '$' + subtotal.toFixed(2);
        document.getElementById('totalAmount').textContent = '$' + total.toFixed(2);
        
        // Update selected seats display
        const seatsList = document.getElementById('selectedSeatsList');
        if (selectedSeats.length > 0) {
            // Sort seats
            selectedSeats.sort();
            seatsList.innerHTML = selectedSeats.map(s => 
                '<span class="badge bg-danger me-1 mb-1">' + s + '</span>'
            ).join('');
        } else {
            seatsList.innerHTML = '<span class="text-muted">No seats selected</span>';
        }
        
        // Update form inputs
        document.getElementById('selectedSeatsInput').value = selectedSeats.join(',');
        document.getElementById('totalAmountInput').value = total.toFixed(2);
        
        // Enable/disable button
        document.getElementById('proceedBtn').disabled = seatCount === 0;
    }
    
    // Form validation
    document.getElementById('bookingForm').addEventListener('submit', function(e) {
        if (selectedSeats.length === 0) {
            e.preventDefault();
            alert('Please select at least one seat.');
        }
    });
});
</script>

<?php include INCLUDES_PATH . 'footer.php'; ?>
