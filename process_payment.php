<?php
/**
 * ============================================
 * CineMaster - Payment Processing (AJAX Handler)
 * ============================================
 * Processes Stripe payment and creates booking
 * Returns JSON response for AJAX request
 */

require_once 'config/config.php';

// Set JSON response header
header('Content-Type: application/json');

// Require login
if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Please log in to continue.']);
    exit;
}

// Validate request method
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
    exit;
}

// Validate CSRF token
if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
    echo json_encode(['success' => false, 'message' => 'Invalid request. Please refresh and try again.']);
    exit;
}

// Get payment data
$stripeToken = $_POST['stripe_token'] ?? '';
$showtimeId = isset($_POST['showtime_id']) ? (int)$_POST['showtime_id'] : 0;
$seatsString = $_POST['seats'] ?? '';
$totalAmount = isset($_POST['total']) ? (float)$_POST['total'] : 0;

// Validate required fields
if (empty($stripeToken) || !$showtimeId || empty($seatsString) || $totalAmount <= 0) {
    echo json_encode(['success' => false, 'message' => 'Missing required payment information.']);
    exit;
}

// Parse seats
$seats = array_filter(array_map('trim', explode(',', $seatsString)));

if (empty($seats)) {
    echo json_encode(['success' => false, 'message' => 'No seats selected.']);
    exit;
}

// Get showtime details
$showtime = getShowtimeById($showtimeId);

if (!$showtime) {
    echo json_encode(['success' => false, 'message' => 'Showtime not found.']);
    exit;
}

// Verify seats are still available (double-check)
$bookedSeats = getBookedSeats($showtimeId);
foreach ($seats as $seat) {
    if (in_array($seat, $bookedSeats)) {
        echo json_encode([
            'success' => false, 
            'message' => "Seat {$seat} has been booked by another user. Please select different seats."
        ]);
        exit;
    }
}

// Verify total amount
$bookingFee = 1.50;
$expectedTotal = (count($seats) * $showtime['price']) + $bookingFee;
if (abs($totalAmount - $expectedTotal) > 0.01) {
    echo json_encode(['success' => false, 'message' => 'Price verification failed.']);
    exit;
}

$userId = $_SESSION['user_id'];

/**
 * STRIPE PAYMENT PROCESSING
 * In production, use the Stripe PHP SDK:
 * composer require stripe/stripe-php
 * 
 * For this demo, we'll simulate a successful payment
 */

try {
    // Simulate Stripe API call
    // In production, you would do:
    /*
    \Stripe\Stripe::setApiKey(STRIPE_SECRET_KEY);
    
    $charge = \Stripe\Charge::create([
        'amount' => $totalAmount * 100, // Stripe uses cents
        'currency' => STRIPE_CURRENCY,
        'source' => $stripeToken,
        'description' => "CineMaster Booking - {$showtime['movie_title']}",
        'metadata' => [
            'user_id' => $userId,
            'showtime_id' => $showtimeId,
            'seats' => implode(', ', $seats)
        ]
    ]);
    
    $stripePaymentId = $charge->id;
    $paymentStatus = 'completed';
    */
    
    // DEMO: Simulate successful payment
    $stripePaymentId = 'ch_demo_' . bin2hex(random_bytes(12));
    $paymentStatus = 'completed';
    
    // Log the Stripe API response (simulated)
    $apiResponse = [
        'id' => $stripePaymentId,
        'amount' => $totalAmount * 100,
        'currency' => STRIPE_CURRENCY,
        'status' => 'succeeded',
        'created' => time(),
        'metadata' => [
            'user_id' => $userId,
            'showtime_id' => $showtimeId,
            'seats' => implode(', ', $seats)
        ]
    ];
    
    // Log the API response to database
    logAction(
        $userId, 
        'Stripe payment processed', 
        'api', 
        json_encode($apiResponse)
    );
    
    // Create the booking
    $bookingResult = createBooking(
        $userId,
        $showtimeId,
        $seats,
        $totalAmount,
        $paymentStatus,
        $stripePaymentId
    );
    
    if ($bookingResult['success']) {
        // Log successful payment
        logAction(
            $userId,
            "Payment completed for booking {$bookingResult['booking_reference']}",
            'payment',
            json_encode([
                'booking_id' => $bookingResult['booking_id'],
                'amount' => $totalAmount,
                'stripe_id' => $stripePaymentId
            ])
        );
        
        // Store booking reference in session for confirmation page
        $_SESSION['last_booking_ref'] = $bookingResult['booking_reference'];
        $_SESSION['last_booking_id'] = $bookingResult['booking_id'];
        
        echo json_encode([
            'success' => true,
            'message' => 'Payment successful!',
            'booking_reference' => $bookingResult['booking_reference'],
            'redirect' => SITE_URL . '/booking_confirmation.php?ref=' . $bookingResult['booking_reference']
        ]);
        
    } else {
        // Booking creation failed - in production, you would refund the charge
        logAction(
            $userId,
            'Booking creation failed after payment',
            'error',
            json_encode([
                'stripe_id' => $stripePaymentId,
                'error' => $bookingResult['message']
            ])
        );
        
        echo json_encode([
            'success' => false,
            'message' => $bookingResult['message']
        ]);
    }
    
} catch (Exception $e) {
    // Log the error
    logAction(
        $userId,
        'Payment processing error',
        'error',
        json_encode([
            'error' => $e->getMessage(),
            'showtime_id' => $showtimeId
        ])
    );
    
    echo json_encode([
        'success' => false,
        'message' => 'Payment processing failed. Please try again.'
    ]);
}
?>
