<?php
/**
 * ============================================
 * CineMaster - Admin Showtimes Management
 * ============================================
 * Manage movie showtimes and scheduling
 */

require_once '../config/config.php';

requireAdmin();

$pageTitle = 'Manage Showtimes';
$errors = [];
$success = '';

$pdo = getDBConnection();

// Handle actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Invalid request.';
    } else {
        $action = $_POST['action'] ?? '';
        
        // Add new showtime
        if ($action === 'add') {
            $movieId = (int)($_POST['movie_id'] ?? 0);
            $theaterId = (int)($_POST['theater_id'] ?? 0);
            $showDate = $_POST['show_date'] ?? '';
            $showTime = $_POST['show_time'] ?? '';
            $price = (float)($_POST['price'] ?? 0);
            
            // Validation
            if ($movieId <= 0) $errors[] = 'Please select a movie.';
            if ($theaterId <= 0) $errors[] = 'Please select a theater.';
            if (empty($showDate)) $errors[] = 'Show date is required.';
            if (empty($showTime)) $errors[] = 'Show time is required.';
            if ($price <= 0) $errors[] = 'Valid price is required.';
            
            // Check for scheduling conflicts
            if (empty($errors)) {
                $conflictStmt = $pdo->prepare("
                    SELECT s.*, m.duration 
                    FROM showtimes s
                    JOIN movies m ON s.movie_id = m.id
                    WHERE s.theater_id = ? AND s.show_date = ?
                    ORDER BY s.show_time
                ");
                $conflictStmt->execute([$theaterId, $showDate]);
                $existingShows = $conflictStmt->fetchAll();
                
                // Get new movie duration
                $movieStmt = $pdo->prepare("SELECT duration FROM movies WHERE id = ?");
                $movieStmt->execute([$movieId]);
                $newDuration = $movieStmt->fetchColumn();
                
                $newStart = strtotime($showTime);
                $newEnd = $newStart + ($newDuration * 60) + (20 * 60); // Add 20 min buffer
                
                foreach ($existingShows as $existing) {
                    $existStart = strtotime($existing['show_time']);
                    $existEnd = $existStart + ($existing['duration'] * 60) + (20 * 60);
                    
                    if (($newStart >= $existStart && $newStart < $existEnd) || 
                        ($newEnd > $existStart && $newEnd <= $existEnd)) {
                        $errors[] = 'Scheduling conflict with another showtime in this theater.';
                        break;
                    }
                }
            }
            
            if (empty($errors)) {
                $stmt = $pdo->prepare("
                    INSERT INTO showtimes (movie_id, theater_id, show_date, show_time, price)
                    VALUES (?, ?, ?, ?, ?)
                ");
                
                if ($stmt->execute([$movieId, $theaterId, $showDate, $showTime, $price])) {
                    $success = 'Showtime added successfully!';
                    logAction($_SESSION['user_id'], "Added showtime for movie ID: {$movieId}", 'admin');
                } else {
                    $errors[] = 'Failed to add showtime.';
                }
            }
        }
        
        // Delete showtime
        if ($action === 'delete' && isset($_POST['showtime_id'])) {
            $showtimeId = (int)$_POST['showtime_id'];
            
            // Check if there are bookings
            $bookingStmt = $pdo->prepare("SELECT COUNT(*) FROM bookings WHERE showtime_id = ?");
            $bookingStmt->execute([$showtimeId]);
            $bookingCount = $bookingStmt->fetchColumn();
            
            if ($bookingCount > 0) {
                $errors[] = "Cannot delete showtime - {$bookingCount} booking(s) exist.";
            } else {
                $stmt = $pdo->prepare("DELETE FROM showtimes WHERE id = ?");
                if ($stmt->execute([$showtimeId])) {
                    $success = 'Showtime deleted successfully.';
                    logAction($_SESSION['user_id'], "Deleted showtime ID: {$showtimeId}", 'admin');
                } else {
                    $errors[] = 'Failed to delete showtime.';
                }
            }
        }
        
        // Update price
        if ($action === 'update_price' && isset($_POST['showtime_id'], $_POST['price'])) {
            $showtimeId = (int)$_POST['showtime_id'];
            $newPrice = (float)$_POST['price'];
            
            $stmt = $pdo->prepare("UPDATE showtimes SET price = ? WHERE id = ?");
            if ($stmt->execute([$newPrice, $showtimeId])) {
                $success = 'Price updated successfully.';
            }
        }
    }
}

// Get filter values
$filterDate = $_GET['date'] ?? '';
$filterMovie = $_GET['movie'] ?? '';
$filterTheater = $_GET['theater'] ?? '';

// Build query with filters
$query = "
    SELECT s.*, m.title as movie_title, m.duration, t.name as theater_name, t.seats_per_row, t.total_rows,
           (SELECT COUNT(*) FROM bookings WHERE showtime_id = s.id) as booking_count
    FROM showtimes s
    JOIN movies m ON s.movie_id = m.id
    JOIN theaters t ON s.theater_id = t.id
    WHERE 1=1
";
$params = [];

if ($filterDate) {
    $query .= " AND s.show_date = ?";
    $params[] = $filterDate;
}
if ($filterMovie) {
    $query .= " AND s.movie_id = ?";
    $params[] = $filterMovie;
}
if ($filterTheater) {
    $query .= " AND s.theater_id = ?";
    $params[] = $filterTheater;
}

$query .= " ORDER BY s.show_date DESC, s.show_time ASC";

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$showtimes = $stmt->fetchAll();

// Get movies and theaters for dropdowns
$movies = getMovies('all');
$theaters = getTheaters();
$csrfToken = generateCSRFToken();

// Check if adding new showtime
$showAddForm = isset($_GET['action']) && $_GET['action'] === 'add';

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
                    <a href="<?php echo SITE_URL; ?>/admin/showtimes.php" class="list-group-item list-group-item-action bg-dark text-light border-secondary active">
                        <i class="bi bi-calendar-event me-2"></i>Showtimes
                    </a>
                    <a href="<?php echo SITE_URL; ?>/admin/bookings.php" class="list-group-item list-group-item-action bg-dark text-light border-secondary">
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
                    <i class="bi bi-calendar-event text-danger me-2"></i>Showtime Management
                </h2>
                <?php if (!$showAddForm): ?>
                    <a href="<?php echo SITE_URL; ?>/admin/showtimes.php?action=add" class="btn btn-danger">
                        <i class="bi bi-plus-circle me-2"></i>Add Showtime
                    </a>
                <?php else: ?>
                    <a href="<?php echo SITE_URL; ?>/admin/showtimes.php" class="btn btn-secondary">
                        <i class="bi bi-arrow-left me-2"></i>Back to List
                    </a>
                <?php endif; ?>
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
            
            <?php if ($showAddForm): ?>
            <!-- Add Showtime Form -->
            <div class="card bg-dark border-secondary">
                <div class="card-header bg-danger">
                    <h5 class="mb-0"><i class="bi bi-plus-circle me-2"></i>Schedule New Showtime</h5>
                </div>
                <div class="card-body">
                    <form method="POST" action="">
                        <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                        <input type="hidden" name="action" value="add">
                        
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Select Movie</label>
                                <select name="movie_id" class="form-select bg-dark text-light border-secondary" required>
                                    <option value="">-- Select Movie --</option>
                                    <?php foreach ($movies as $movie): ?>
                                        <option value="<?php echo $movie['id']; ?>">
                                            <?php echo htmlspecialchars($movie['title']); ?> 
                                            (<?php echo $movie['duration']; ?> min)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Select Theater</label>
                                <select name="theater_id" class="form-select bg-dark text-light border-secondary" required>
                                    <option value="">-- Select Theater --</option>
                                    <?php foreach ($theaters as $theater): ?>
                                        <option value="<?php echo $theater['id']; ?>">
                                            <?php echo htmlspecialchars($theater['name']); ?> 
                                            (<?php echo $theater['total_rows'] * $theater['seats_per_row']; ?> seats)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Show Date</label>
                                <input type="date" class="form-control bg-dark text-light border-secondary" 
                                       name="show_date" required 
                                       min="<?php echo date('Y-m-d'); ?>">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Show Time</label>
                                <input type="time" class="form-control bg-dark text-light border-secondary" 
                                       name="show_time" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Ticket Price ($)</label>
                                <input type="number" class="form-control bg-dark text-light border-secondary" 
                                       name="price" step="0.01" min="0" value="12.99" required>
                            </div>
                        </div>
                        
                        <div class="alert alert-info mt-3">
                            <i class="bi bi-info-circle me-2"></i>
                            The system automatically checks for scheduling conflicts and adds a 20-minute buffer between shows.
                        </div>
                        
                        <div class="mt-4">
                            <button type="submit" class="btn btn-danger">
                                <i class="bi bi-plus-circle me-2"></i>Add Showtime
                            </button>
                            <a href="<?php echo SITE_URL; ?>/admin/showtimes.php" class="btn btn-secondary">Cancel</a>
                        </div>
                    </form>
                </div>
            </div>
            
            <?php else: ?>
            
            <!-- Filters -->
            <div class="card bg-dark border-secondary mb-4">
                <div class="card-body">
                    <form method="GET" class="row g-3">
                        <div class="col-md-3">
                            <label class="form-label">Filter by Date</label>
                            <input type="date" name="date" class="form-control bg-dark text-light border-secondary"
                                   value="<?php echo htmlspecialchars($filterDate); ?>">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Filter by Movie</label>
                            <select name="movie" class="form-select bg-dark text-light border-secondary">
                                <option value="">All Movies</option>
                                <?php foreach ($movies as $movie): ?>
                                    <option value="<?php echo $movie['id']; ?>" 
                                            <?php echo $filterMovie == $movie['id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($movie['title']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Filter by Theater</label>
                            <select name="theater" class="form-select bg-dark text-light border-secondary">
                                <option value="">All Theaters</option>
                                <?php foreach ($theaters as $theater): ?>
                                    <option value="<?php echo $theater['id']; ?>" 
                                            <?php echo $filterTheater == $theater['id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($theater['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-3 d-flex align-items-end gap-2">
                            <button type="submit" class="btn btn-danger">
                                <i class="bi bi-funnel me-1"></i>Filter
                            </button>
                            <a href="<?php echo SITE_URL; ?>/admin/showtimes.php" class="btn btn-secondary">Clear</a>
                        </div>
                    </form>
                </div>
            </div>
            
            <!-- Showtimes Table -->
            <div class="card bg-dark border-secondary">
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-dark table-hover mb-0">
                            <thead class="table-secondary">
                                <tr>
                                    <th>Movie</th>
                                    <th>Theater</th>
                                    <th>Date</th>
                                    <th>Time</th>
                                    <th>Price</th>
                                    <th>Bookings</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($showtimes)): ?>
                                    <tr>
                                        <td colspan="7" class="text-center py-4 text-muted">
                                            No showtimes found.
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($showtimes as $show): ?>
                                        <?php 
                                            $totalSeats = $show['seats_per_row'] * $show['total_rows'];
                                            $isPast = strtotime($show['show_date']) < strtotime(date('Y-m-d'));
                                        ?>
                                        <tr class="<?php echo $isPast ? 'text-muted' : ''; ?>">
                                            <td>
                                                <strong><?php echo htmlspecialchars($show['movie_title']); ?></strong>
                                                <br><small class="text-muted"><?php echo $show['duration']; ?> min</small>
                                            </td>
                                            <td><?php echo htmlspecialchars($show['theater_name']); ?></td>
                                            <td><?php echo formatDate($show['show_date']); ?></td>
                                            <td><?php echo date('g:i A', strtotime($show['show_time'])); ?></td>
                                            <td>
                                                <form method="POST" class="d-inline">
                                                    <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                                                    <input type="hidden" name="action" value="update_price">
                                                    <input type="hidden" name="showtime_id" value="<?php echo $show['id']; ?>">
                                                    <div class="input-group input-group-sm" style="width: 100px;">
                                                        <span class="input-group-text bg-dark text-light border-secondary">$</span>
                                                        <input type="number" name="price" step="0.01"
                                                               class="form-control form-control-sm bg-dark text-light border-secondary"
                                                               value="<?php echo $show['price']; ?>"
                                                               onchange="this.form.submit()">
                                                    </div>
                                                </form>
                                            </td>
                                            <td>
                                                <span class="badge <?php echo $show['booking_count'] > 0 ? 'bg-success' : 'bg-secondary'; ?>">
                                                    <?php echo $show['booking_count']; ?> / <?php echo $totalSeats; ?>
                                                </span>
                                            </td>
                                            <td>
                                                <?php if ($show['booking_count'] == 0): ?>
                                                    <form method="POST" class="d-inline" 
                                                          onsubmit="return confirm('Delete this showtime?');">
                                                        <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                                                        <input type="hidden" name="action" value="delete">
                                                        <input type="hidden" name="showtime_id" value="<?php echo $show['id']; ?>">
                                                        <button type="submit" class="btn btn-sm btn-outline-danger" title="Delete">
                                                            <i class="bi bi-trash"></i>
                                                        </button>
                                                    </form>
                                                <?php else: ?>
                                                    <span class="text-muted" title="Has bookings">
                                                        <i class="bi bi-lock"></i>
                                                    </span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <?php endif; ?>
            
        </div>
    </div>
</div>

<?php include INCLUDES_PATH . 'footer.php'; ?>
