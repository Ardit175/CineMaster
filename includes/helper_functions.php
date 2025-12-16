<?php
/**
 * ============================================
 * CineMaster - Helper Functions
 * ============================================
 * This file contains general helper functions for the application
 * including movie operations, booking functions, and utility functions.
 */

/**
 * ============================================
 * MOVIE FUNCTIONS
 * ============================================
 */

/**
 * Gets all movies with optional filtering
 * 
 * @param string $status Movie status filter (now_showing, coming_soon, all)
 * @param int $limit Number of movies to return
 * @return array Array of movies
 */
function getMovies($status = 'all', $limit = null) {
    $pdo = getDBConnection();
    
    $sql = "SELECT m.*, GROUP_CONCAT(g.name SEPARATOR ', ') AS genres 
            FROM movies m 
            LEFT JOIN movie_genres mg ON m.id = mg.movie_id 
            LEFT JOIN genres g ON mg.genre_id = g.id ";
    
    if ($status !== 'all') {
        $sql .= "WHERE m.status = ? ";
    }
    
    $sql .= "GROUP BY m.id ORDER BY m.release_date DESC";
    
    if ($limit) {
        $sql .= " LIMIT " . (int)$limit;
    }
    
    $stmt = $pdo->prepare($sql);
    
    if ($status !== 'all') {
        $stmt->execute([$status]);
    } else {
        $stmt->execute();
    }
    
    return $stmt->fetchAll();
}

/**
 * Gets a single movie by ID with full details
 * 
 * @param int $movieId The movie ID
 * @return array|null Movie data or null
 */
function getMovieById($movieId) {
    $pdo = getDBConnection();
    
    $stmt = $pdo->prepare("
        SELECT m.*, GROUP_CONCAT(g.name SEPARATOR ', ') AS genres 
        FROM movies m 
        LEFT JOIN movie_genres mg ON m.id = mg.movie_id 
        LEFT JOIN genres g ON mg.genre_id = g.id 
        WHERE m.id = ? 
        GROUP BY m.id
    ");
    $stmt->execute([$movieId]);
    
    return $stmt->fetch();
}

/**
 * Searches movies by title or genre
 * 
 * @param string $query Search query
 * @return array Array of matching movies
 */
function searchMovies($query) {
    $pdo = getDBConnection();
    $searchTerm = '%' . $query . '%';
    
    $stmt = $pdo->prepare("
        SELECT DISTINCT m.*, GROUP_CONCAT(g.name SEPARATOR ', ') AS genres 
        FROM movies m 
        LEFT JOIN movie_genres mg ON m.id = mg.movie_id 
        LEFT JOIN genres g ON mg.genre_id = g.id 
        WHERE m.title LIKE ? OR g.name LIKE ? 
        GROUP BY m.id 
        ORDER BY m.status = 'now_showing' DESC, m.release_date DESC
    ");
    $stmt->execute([$searchTerm, $searchTerm]);
    
    return $stmt->fetchAll();
}

/**
 * Gets movies by genre
 * 
 * @param int $genreId The genre ID
 * @return array Array of movies
 */
function getMoviesByGenre($genreId) {
    $pdo = getDBConnection();
    
    $stmt = $pdo->prepare("
        SELECT m.*, GROUP_CONCAT(g.name SEPARATOR ', ') AS genres 
        FROM movies m 
        JOIN movie_genres mg ON m.id = mg.movie_id 
        LEFT JOIN genres g2 ON mg.genre_id = g2.id 
        LEFT JOIN genres g ON m.id IN (SELECT movie_id FROM movie_genres WHERE genre_id = g.id)
        WHERE mg.genre_id = ? 
        GROUP BY m.id 
        ORDER BY m.release_date DESC
    ");
    $stmt->execute([$genreId]);
    
    return $stmt->fetchAll();
}

/**
 * Gets all genres
 * 
 * @return array Array of genres
 */
function getAllGenres() {
    $pdo = getDBConnection();
    $stmt = $pdo->query("SELECT * FROM genres ORDER BY name");
    return $stmt->fetchAll();
}

/**
 * ============================================
 * SHOWTIME FUNCTIONS
 * ============================================
 */

/**
 * Gets showtimes for a movie
 * 
 * @param int $movieId The movie ID
 * @param string $date Optional date filter (Y-m-d format)
 * @return array Array of showtimes
 */
function getShowtimesByMovie($movieId, $date = null) {
    $pdo = getDBConnection();
    
    $sql = "
        SELECT s.*, t.name AS theater_name, t.total_seats,
               (t.total_seats - COALESCE(booked.booked_seats, 0)) AS available_seats
        FROM showtimes s
        JOIN theaters t ON s.theater_id = t.id
        LEFT JOIN (
            SELECT showtime_id, COUNT(*) AS booked_seats 
            FROM seats 
            GROUP BY showtime_id
        ) booked ON s.id = booked.showtime_id
        WHERE s.movie_id = ? AND s.start_time > NOW() AND s.is_active = 1
    ";
    
    $params = [$movieId];
    
    if ($date) {
        $sql .= " AND DATE(s.start_time) = ?";
        $params[] = $date;
    }
    
    $sql .= " ORDER BY s.start_time ASC";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    
    return $stmt->fetchAll();
}

/**
 * Gets a showtime by ID
 * 
 * @param int $showtimeId The showtime ID
 * @return array|null Showtime data
 */
function getShowtimeById($showtimeId) {
    $pdo = getDBConnection();
    
    $stmt = $pdo->prepare("
        SELECT s.*, m.title AS movie_title, m.poster_image, m.duration,
               t.name AS theater_name, t.total_seats, t.rows_count, t.seats_per_row,
               (t.total_seats - COALESCE(booked.booked_seats, 0)) AS available_seats
        FROM showtimes s
        JOIN movies m ON s.movie_id = m.id
        JOIN theaters t ON s.theater_id = t.id
        LEFT JOIN (
            SELECT showtime_id, COUNT(*) AS booked_seats 
            FROM seats 
            GROUP BY showtime_id
        ) booked ON s.id = booked.showtime_id
        WHERE s.id = ?
    ");
    $stmt->execute([$showtimeId]);
    
    return $stmt->fetch();
}

/**
 * Gets booked seats for a showtime
 * 
 * @param int $showtimeId The showtime ID
 * @return array Array of booked seat numbers
 */
function getBookedSeats($showtimeId) {
    $pdo = getDBConnection();
    
    $stmt = $pdo->prepare("SELECT seat_number FROM seats WHERE showtime_id = ?");
    $stmt->execute([$showtimeId]);
    
    return array_column($stmt->fetchAll(), 'seat_number');
}

/**
 * ============================================
 * BOOKING FUNCTIONS
 * ============================================
 */

/**
 * Creates a new booking
 * 
 * @param int $userId User ID
 * @param int $showtimeId Showtime ID
 * @param array $seats Array of seat numbers
 * @param float $totalAmount Total booking amount
 * @param string $paymentStatus Payment status
 * @param string|null $stripePaymentId Stripe payment ID
 * @return array Result with success status and booking reference
 */
function createBooking($userId, $showtimeId, $seats, $totalAmount, $paymentStatus = 'pending', $stripePaymentId = null) {
    $pdo = getDBConnection();
    
    try {
        $pdo->beginTransaction();
        
        // Generate unique booking reference
        $bookingRef = 'CM-' . strtoupper(substr(md5(uniqid()), 0, 8));
        
        // Check if seats are still available
        $bookedSeats = getBookedSeats($showtimeId);
        foreach ($seats as $seat) {
            if (in_array($seat, $bookedSeats)) {
                $pdo->rollBack();
                return [
                    'success' => false,
                    'message' => "Seat {$seat} is no longer available."
                ];
            }
        }
        
        // Insert booking
        $stmt = $pdo->prepare("
            INSERT INTO bookings (user_id, showtime_id, total_amount, payment_status, stripe_payment_id, booking_reference)
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([$userId, $showtimeId, $totalAmount, $paymentStatus, $stripePaymentId, $bookingRef]);
        
        $bookingId = $pdo->lastInsertId();
        
        // Insert seats
        $seatStmt = $pdo->prepare("
            INSERT INTO seats (booking_id, showtime_id, seat_number) VALUES (?, ?, ?)
        ");
        
        foreach ($seats as $seat) {
            $seatStmt->execute([$bookingId, $showtimeId, $seat]);
        }
        
        $pdo->commit();
        
        // Log the booking
        logAction($userId, "Booking created: {$bookingRef}", 'booking', json_encode([
            'booking_id' => $bookingId,
            'showtime_id' => $showtimeId,
            'seats' => $seats,
            'amount' => $totalAmount
        ]));
        
        return [
            'success' => true,
            'message' => 'Booking created successfully!',
            'booking_id' => $bookingId,
            'booking_reference' => $bookingRef
        ];
        
    } catch (PDOException $e) {
        $pdo->rollBack();
        error_log("Booking Error: " . $e->getMessage());
        return [
            'success' => false,
            'message' => 'Booking failed. Please try again.'
        ];
    }
}

/**
 * Updates booking payment status
 * 
 * @param int $bookingId Booking ID
 * @param string $status New payment status
 * @param string|null $stripePaymentId Stripe payment ID
 * @return bool Success status
 */
function updateBookingPayment($bookingId, $status, $stripePaymentId = null) {
    $pdo = getDBConnection();
    
    $stmt = $pdo->prepare("
        UPDATE bookings 
        SET payment_status = ?, stripe_payment_id = ? 
        WHERE id = ?
    ");
    
    return $stmt->execute([$status, $stripePaymentId, $bookingId]);
}

/**
 * Gets user's bookings
 * 
 * @param int $userId User ID
 * @return array Array of bookings
 */
function getUserBookings($userId) {
    $pdo = getDBConnection();
    
    $stmt = $pdo->prepare("
        SELECT b.*, s.start_time, s.price,
               m.title AS movie_title, m.poster_image,
               t.name AS theater_name,
               GROUP_CONCAT(st.seat_number SEPARATOR ', ') AS seats
        FROM bookings b
        JOIN showtimes s ON b.showtime_id = s.id
        JOIN movies m ON s.movie_id = m.id
        JOIN theaters t ON s.theater_id = t.id
        LEFT JOIN seats st ON b.id = st.booking_id
        WHERE b.user_id = ?
        GROUP BY b.id
        ORDER BY b.booking_date DESC
    ");
    $stmt->execute([$userId]);
    
    return $stmt->fetchAll();
}

/**
 * Gets a single booking by ID
 * 
 * @param int $bookingId Booking ID
 * @return array|null Booking data
 */
function getBookingById($bookingId) {
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
        WHERE b.id = ?
        GROUP BY b.id
    ");
    $stmt->execute([$bookingId]);
    
    return $stmt->fetch();
}

/**
 * ============================================
 * THEATER FUNCTIONS
 * ============================================
 */

/**
 * Gets all theaters
 * 
 * @return array Array of theaters
 */
function getAllTheaters() {
    $pdo = getDBConnection();
    $stmt = $pdo->query("SELECT * FROM theaters WHERE is_active = 1 ORDER BY name");
    return $stmt->fetchAll();
}

/**
 * ============================================
 * USER FUNCTIONS
 * ============================================
 */

/**
 * Gets user by ID
 * 
 * @param int $userId User ID
 * @return array|null User data
 */
function getUserById($userId) {
    $pdo = getDBConnection();
    $stmt = $pdo->prepare("SELECT id, name, email, role, profile_photo, is_verified, created_at FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    return $stmt->fetch();
}

/**
 * Updates user profile
 * 
 * @param int $userId User ID
 * @param array $data Data to update
 * @return bool Success status
 */
function updateUserProfile($userId, $data) {
    $pdo = getDBConnection();
    
    $updates = [];
    $params = [];
    
    foreach ($data as $field => $value) {
        if (in_array($field, ['name', 'profile_photo'])) {
            $updates[] = "{$field} = ?";
            $params[] = $value;
        }
    }
    
    if (empty($updates)) {
        return false;
    }
    
    $params[] = $userId;
    $sql = "UPDATE users SET " . implode(', ', $updates) . " WHERE id = ?";
    
    $stmt = $pdo->prepare($sql);
    return $stmt->execute($params);
}

/**
 * Changes user password
 * 
 * @param int $userId User ID
 * @param string $currentPassword Current password
 * @param string $newPassword New password
 * @return array Result with success status
 */
function changePassword($userId, $currentPassword, $newPassword) {
    $pdo = getDBConnection();
    
    // Get current password hash
    $stmt = $pdo->prepare("SELECT password FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $user = $stmt->fetch();
    
    if (!$user || !password_verify($currentPassword, $user['password'])) {
        return [
            'success' => false,
            'message' => 'Current password is incorrect.'
        ];
    }
    
    // Hash and update new password
    $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
    $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
    $stmt->execute([$hashedPassword, $userId]);
    
    logAction($userId, 'Password changed', 'auth');
    
    return [
        'success' => true,
        'message' => 'Password changed successfully!'
    ];
}

/**
 * Gets all users (admin function)
 * 
 * @return array Array of users
 */
function getAllUsers() {
    $pdo = getDBConnection();
    $stmt = $pdo->query("SELECT id, name, email, role, is_verified, created_at FROM users ORDER BY created_at DESC");
    return $stmt->fetchAll();
}

/**
 * Deletes a user (admin function)
 * 
 * @param int $userId User ID to delete
 * @return bool Success status
 */
function deleteUser($userId) {
    $pdo = getDBConnection();
    $stmt = $pdo->prepare("DELETE FROM users WHERE id = ? AND role != 'admin'");
    return $stmt->execute([$userId]);
}

/**
 * ============================================
 * FILE UPLOAD FUNCTIONS
 * ============================================
 */

/**
 * Handles file upload
 * 
 * @param array $file $_FILES array element
 * @param string $destination Upload directory
 * @param string $prefix Filename prefix
 * @return array Result with success status and filename
 */
function uploadFile($file, $destination, $prefix = '') {
    // Check for upload errors
    if ($file['error'] !== UPLOAD_ERR_OK) {
        return [
            'success' => false,
            'message' => 'File upload error.'
        ];
    }
    
    // Check file size
    if ($file['size'] > MAX_FILE_SIZE) {
        return [
            'success' => false,
            'message' => 'File is too large. Maximum size is 5MB.'
        ];
    }
    
    // Check file type
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mimeType = $finfo->file($file['tmp_name']);
    
    if (!in_array($mimeType, ALLOWED_IMAGE_TYPES)) {
        return [
            'success' => false,
            'message' => 'Invalid file type. Only images are allowed.'
        ];
    }
    
    // Generate unique filename
    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = $prefix . uniqid() . '.' . strtolower($extension);
    
    // Create directory if it doesn't exist
    if (!is_dir($destination)) {
        mkdir($destination, 0777, true);
    }
    
    $filepath = $destination . $filename;
    
    if (move_uploaded_file($file['tmp_name'], $filepath)) {
        return [
            'success' => true,
            'filename' => $filename
        ];
    }
    
    return [
        'success' => false,
        'message' => 'Failed to move uploaded file.'
    ];
}

/**
 * ============================================
 * STATISTICS FUNCTIONS (Admin Dashboard)
 * ============================================
 */

/**
 * Gets dashboard statistics
 * 
 * @return array Statistics data
 */
function getDashboardStats() {
    $pdo = getDBConnection();
    
    // Total users
    $stmt = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'user'");
    $totalUsers = $stmt->fetchColumn();
    
    // Total movies
    $stmt = $pdo->query("SELECT COUNT(*) FROM movies");
    $totalMovies = $stmt->fetchColumn();
    
    // Total bookings
    $stmt = $pdo->query("SELECT COUNT(*) FROM bookings");
    $totalBookings = $stmt->fetchColumn();
    
    // Total revenue
    $stmt = $pdo->query("SELECT COALESCE(SUM(total_amount), 0) FROM bookings WHERE payment_status = 'completed'");
    $totalRevenue = $stmt->fetchColumn();
    
    // Recent bookings
    $stmt = $pdo->query("
        SELECT b.*, u.name AS user_name, m.title AS movie_title
        FROM bookings b
        JOIN users u ON b.user_id = u.id
        JOIN showtimes s ON b.showtime_id = s.id
        JOIN movies m ON s.movie_id = m.id
        ORDER BY b.booking_date DESC
        LIMIT 5
    ");
    $recentBookings = $stmt->fetchAll();
    
    // Bookings this month
    $stmt = $pdo->query("
        SELECT COUNT(*) FROM bookings 
        WHERE MONTH(booking_date) = MONTH(CURRENT_DATE()) 
        AND YEAR(booking_date) = YEAR(CURRENT_DATE())
    ");
    $monthlyBookings = $stmt->fetchColumn();
    
    return [
        'total_users' => $totalUsers,
        'total_movies' => $totalMovies,
        'total_bookings' => $totalBookings,
        'total_revenue' => $totalRevenue,
        'recent_bookings' => $recentBookings,
        'monthly_bookings' => $monthlyBookings
    ];
}

/**
 * Gets recent activity logs
 * 
 * @param int $limit Number of logs to return
 * @return array Array of logs
 */
function getRecentLogs($limit = 20) {
    $pdo = getDBConnection();
    
    $stmt = $pdo->prepare("
        SELECT l.*, u.name AS user_name 
        FROM logs l 
        LEFT JOIN users u ON l.user_id = u.id 
        ORDER BY l.timestamp DESC 
        LIMIT ?
    ");
    $stmt->execute([$limit]);
    
    return $stmt->fetchAll();
}


/**
 * Gets all theaters
 * 
 * @return array Array of theaters
 */
function getTheaters() {
    $pdo = getDBConnection();
    
    $stmt = $pdo->query("
        SELECT * FROM theaters 
        ORDER BY name ASC
    ");
    
    return $stmt->fetchAll();
}
?>
