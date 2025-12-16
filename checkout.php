<?php
/**
 * ============================================
 * CineMaster - Checkout Page with Stripe
 * ============================================
 * Processes payment using Stripe API
 * Creates booking on successful payment
 */

require_once 'config/config.php';

// Require login
requireLogin();

$pageTitle = 'Checkout';
$errors = [];

// Validate POST data
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    setFlashMessage('error', 'Invalid request.');
    redirect(SITE_URL . '/movies.php');
}

// Validate CSRF
if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
    setFlashMessage('error', 'Invalid request. Please try again.');
    redirect(SITE_URL . '/movies.php');
}

// Get booking data
$showtimeId = isset($_POST['showtime_id']) ? (int)$_POST['showtime_id'] : 0;
$seatsString = $_POST['seats'] ?? '';
$totalAmount = isset($_POST['total']) ? (float)$_POST['total'] : 0;

// Validate data
if (!$showtimeId || empty($seatsString) || $totalAmount <= 0) {
    setFlashMessage('error', 'Invalid booking data.');
    redirect(SITE_URL . '/movies.php');
}

// Parse seats
$seats = array_filter(array_map('trim', explode(',', $seatsString)));

if (empty($seats)) {
    setFlashMessage('error', 'Please select at least one seat.');
    redirect(SITE_URL . '/booking.php?showtime=' . $showtimeId);
}

// Get showtime details
$showtime = getShowtimeById($showtimeId);

if (!$showtime) {
    setFlashMessage('error', 'Showtime not found.');
    redirect(SITE_URL . '/movies.php');
}

// Check if showtime is still valid
if (strtotime($showtime['start_time']) <= time()) {
    setFlashMessage('error', 'This showtime has already passed.');
    redirect(SITE_URL . '/movies.php');
}

// Verify seats are still available
$bookedSeats = getBookedSeats($showtimeId);
foreach ($seats as $seat) {
    if (in_array($seat, $bookedSeats)) {
        setFlashMessage('error', "Seat {$seat} is no longer available. Please select different seats.");
        redirect(SITE_URL . '/booking.php?showtime=' . $showtimeId);
    }
}

// Calculate expected total (for security)
$bookingFee = 1.50;
$expectedTotal = (count($seats) * $showtime['price']) + $bookingFee;

// Verify total matches (with small tolerance for floating point)
if (abs($totalAmount - $expectedTotal) > 0.01) {
    setFlashMessage('error', 'Price verification failed. Please try again.');
    redirect(SITE_URL . '/booking.php?showtime=' . $showtimeId);
}

$csrfToken = generateCSRFToken();
$user = getCurrentUser();

include INCLUDES_PATH . 'header.php';
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            
            <!-- Order Summary -->
            <div class="card bg-dark border-secondary mb-4">
                <div class="card-header bg-danger">
                    <h5 class="mb-0">
                        <i class="bi bi-receipt me-2"></i>Order Summary
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-3 mb-3 mb-md-0">
                            <img src="<?php echo UPLOADS_URL; ?>/movies/<?php echo $showtime['poster_image']; ?>" 
                                 class="img-fluid rounded"
                                 alt="<?php echo htmlspecialchars($showtime['movie_title']); ?>"
                                 onerror="this.src='<?php echo ASSETS_URL; ?>/images/default-poster.jpg'">
                        </div>
                        <div class="col-md-9">
                            <h4 class="text-danger mb-3"><?php echo htmlspecialchars($showtime['movie_title']); ?></h4>
                            <div class="row">
                                <div class="col-sm-6 mb-2">
                                    <small class="text-muted">Date & Time</small>
                                    <p class="mb-0"><?php echo formatDateTime($showtime['start_time']); ?></p>
                                </div>
                                <div class="col-sm-6 mb-2">
                                    <small class="text-muted">Theater</small>
                                    <p class="mb-0"><?php echo htmlspecialchars($showtime['theater_name']); ?></p>
                                </div>
                                <div class="col-sm-6 mb-2">
                                    <small class="text-muted">Seats (<?php echo count($seats); ?>)</small>
                                    <p class="mb-0">
                                        <?php foreach ($seats as $seat): ?>
                                            <span class="badge bg-danger me-1"><?php echo htmlspecialchars($seat); ?></span>
                                        <?php endforeach; ?>
                                    </p>
                                </div>
                                <div class="col-sm-6 mb-2">
                                    <small class="text-muted">Duration</small>
                                    <p class="mb-0"><?php echo $showtime['duration']; ?> minutes</p>
                                </div>
                            </div>
                            
                            <hr class="border-secondary my-3">
                            
                            <div class="d-flex justify-content-between">
                                <span>Tickets (<?php echo count($seats); ?> x <?php echo formatPrice($showtime['price']); ?>)</span>
                                <span><?php echo formatPrice(count($seats) * $showtime['price']); ?></span>
                            </div>
                            <div class="d-flex justify-content-between text-muted">
                                <span>Booking Fee</span>
                                <span><?php echo formatPrice($bookingFee); ?></span>
                            </div>
                            <hr class="border-secondary my-2">
                            <div class="d-flex justify-content-between fw-bold fs-5">
                                <span>Total</span>
                                <span class="text-success"><?php echo formatPrice($totalAmount); ?></span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Payment Form -->
            <div class="card bg-dark border-secondary">
                <div class="card-header bg-secondary">
                    <h5 class="mb-0">
                        <i class="bi bi-credit-card me-2"></i>Payment Details
                    </h5>
                </div>
                <div class="card-body">
                    <!-- Customer Info -->
                    <div class="mb-4">
                        <h6 class="text-muted mb-3">Customer Information</h6>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Name</label>
                                <input type="text" class="form-control bg-dark text-light border-secondary" 
                                       value="<?php echo htmlspecialchars($user['name']); ?>" readonly>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Email</label>
                                <input type="email" class="form-control bg-dark text-light border-secondary" 
                                       value="<?php echo htmlspecialchars($user['email']); ?>" readonly>
                            </div>
                        </div>
                    </div>
                    
                    <hr class="border-secondary">
                    
                    <!-- Stripe Card Element -->
                    <form id="payment-form">
                        <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                        <input type="hidden" name="showtime_id" value="<?php echo $showtimeId; ?>">
                        <input type="hidden" name="seats" value="<?php echo htmlspecialchars($seatsString); ?>">
                        <input type="hidden" name="total" value="<?php echo $totalAmount; ?>">
                        
                        <div class="mb-4">
                            <h6 class="text-muted mb-3">Card Information</h6>
                            
                            <!-- Demo Mode Notice -->
                            <div class="alert alert-info mb-3">
                                <i class="bi bi-info-circle me-2"></i>
                                <strong>Test Mode:</strong> Use card number <code>4242 4242 4242 4242</code> with any future date and CVC.
                            </div>
                            
                            <div id="card-element" class="form-control bg-dark text-light border-secondary py-3">
                                <!-- Stripe Card Element will be inserted here -->
                            </div>
                            <div id="card-errors" class="text-danger mt-2 small"></div>
                        </div>
                        
                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-danger btn-lg" id="submit-button">
                                <span id="button-text">
                                    <i class="bi bi-lock me-2"></i>Pay <?php echo formatPrice($totalAmount); ?>
                                </span>
                                <span id="spinner" class="d-none">
                                    <span class="spinner-border spinner-border-sm me-2"></span>Processing...
                                </span>
                            </button>
                            <a href="<?php echo SITE_URL; ?>/booking.php?showtime=<?php echo $showtimeId; ?>" 
                               class="btn btn-outline-secondary">
                                <i class="bi bi-arrow-left me-2"></i>Back to Seat Selection
                            </a>
                        </div>
                    </form>
                    
                    <div class="text-center mt-4">
                        <p class="text-muted small mb-0">
                            <i class="bi bi-shield-lock me-1"></i>
                            Your payment is secured with SSL encryption
                        </p>
                        <img src="https://cdn.brandfolder.io/KGT2DTA4/at/8vbr8k4mr5xjwk4hxq4t9vs/Stripe_wordmark_-_blurple.png" 
                             alt="Powered by Stripe" style="height: 30px; margin-top: 10px;">
                    </div>
                </div>
            </div>
            
        </div>
    </div>
</div>

<!-- Stripe.js -->
<script src="https://js.stripe.com/v3/"></script>

<script>
// Initialize Stripe with public key
// Note: Replace with your actual Stripe public key
const stripe = Stripe('<?php echo STRIPE_PUBLIC_KEY; ?>');
const elements = stripe.elements();

// Custom styling for Stripe Element
const style = {
    base: {
        color: '#ffffff',
        fontFamily: '"Poppins", sans-serif',
        fontSmoothing: 'antialiased',
        fontSize: '16px',
        '::placeholder': {
            color: '#6c757d'
        }
    },
    invalid: {
        color: '#dc3545',
        iconColor: '#dc3545'
    }
};

// Create card element
const cardElement = elements.create('card', { style: style });
cardElement.mount('#card-element');

// Handle validation errors
cardElement.on('change', function(event) {
    const displayError = document.getElementById('card-errors');
    if (event.error) {
        displayError.textContent = event.error.message;
    } else {
        displayError.textContent = '';
    }
});

// Handle form submission
const form = document.getElementById('payment-form');
const submitButton = document.getElementById('submit-button');
const buttonText = document.getElementById('button-text');
const spinner = document.getElementById('spinner');

form.addEventListener('submit', async function(e) {
    e.preventDefault();
    
    // Disable button and show spinner
    submitButton.disabled = true;
    buttonText.classList.add('d-none');
    spinner.classList.remove('d-none');
    
    // For demo purposes, we'll simulate a successful payment
    // In production, you would create a PaymentIntent on the server first
    
    try {
        // Create token (for demo - in production use PaymentIntents)
        const { token, error } = await stripe.createToken(cardElement);
        
        if (error) {
            // Show error
            document.getElementById('card-errors').textContent = error.message;
            submitButton.disabled = false;
            buttonText.classList.remove('d-none');
            spinner.classList.add('d-none');
        } else {
            // Send token to server
            const formData = new FormData(form);
            formData.append('stripe_token', token.id);
            
            const response = await fetch('<?php echo SITE_URL; ?>/process_payment.php', {
                method: 'POST',
                body: formData
            });
            
            const result = await response.json();
            
            if (result.success) {
                // Redirect to confirmation page
                window.location.href = result.redirect;
            } else {
                document.getElementById('card-errors').textContent = result.message;
                submitButton.disabled = false;
                buttonText.classList.remove('d-none');
                spinner.classList.add('d-none');
            }
        }
    } catch (err) {
        document.getElementById('card-errors').textContent = 'An error occurred. Please try again.';
        submitButton.disabled = false;
        buttonText.classList.remove('d-none');
        spinner.classList.add('d-none');
    }
});
</script>

<?php include INCLUDES_PATH . 'footer.php'; ?>
