<?php
/**
 * ============================================
 * CineMaster - Admin Movies Management
 * ============================================
 * Allows admin to add, edit, and manage movies
 */

require_once '../config/config.php';

requireAdmin();

$pageTitle = 'Manage Movies';
$errors = [];
$success = '';

$pdo = getDBConnection();

// Handle actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Invalid request.';
    } else {
        $action = $_POST['action'] ?? '';
        
        // Add new movie
        if ($action === 'add') {
            $title = sanitize($_POST['title'] ?? '');
            $description = sanitize($_POST['description'] ?? '');
            $duration = (int)($_POST['duration'] ?? 0);
            $releaseDate = $_POST['release_date'] ?? '';
            $trailerUrl = sanitize($_POST['trailer_url'] ?? '');
            $status = $_POST['status'] ?? 'coming_soon';
            $rating = (float)($_POST['rating'] ?? 0);
            $genres = $_POST['genres'] ?? [];
            
            // Validation
            if (empty($title)) $errors[] = 'Title is required.';
            if ($duration <= 0) $errors[] = 'Valid duration is required.';
            if (empty($releaseDate)) $errors[] = 'Release date is required.';
            
            // Handle poster upload
            $posterImage = 'default_poster.jpg';
            if (isset($_FILES['poster']) && $_FILES['poster']['error'] === UPLOAD_ERR_OK) {
                $uploadResult = uploadFile($_FILES['poster'], UPLOADS_PATH . 'movies/', 'movie_');
                if ($uploadResult['success']) {
                    $posterImage = $uploadResult['filename'];
                } else {
                    $errors[] = $uploadResult['message'];
                }
            }
            
            if (empty($errors)) {
                try {
                    $pdo->beginTransaction();
                    
                    $stmt = $pdo->prepare("
                        INSERT INTO movies (title, description, duration, release_date, poster_image, trailer_url, status, rating)
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?)
                    ");
                    $stmt->execute([$title, $description, $duration, $releaseDate, $posterImage, $trailerUrl, $status, $rating]);
                    
                    $movieId = $pdo->lastInsertId();
                    
                    // Add genres
                    if (!empty($genres)) {
                        $genreStmt = $pdo->prepare("INSERT INTO movie_genres (movie_id, genre_id) VALUES (?, ?)");
                        foreach ($genres as $genreId) {
                            $genreStmt->execute([$movieId, $genreId]);
                        }
                    }
                    
                    $pdo->commit();
                    $success = 'Movie added successfully!';
                    logAction($_SESSION['user_id'], "Added movie: {$title}", 'admin');
                    
                } catch (Exception $e) {
                    $pdo->rollBack();
                    $errors[] = 'Failed to add movie.';
                }
            }
        }
        
        // Delete movie
        if ($action === 'delete' && isset($_POST['movie_id'])) {
            $movieId = (int)$_POST['movie_id'];
            
            $stmt = $pdo->prepare("DELETE FROM movies WHERE id = ?");
            if ($stmt->execute([$movieId])) {
                $success = 'Movie deleted successfully.';
                logAction($_SESSION['user_id'], "Deleted movie ID: {$movieId}", 'admin');
            } else {
                $errors[] = 'Failed to delete movie.';
            }
        }
        
        // Update status
        if ($action === 'update_status' && isset($_POST['movie_id'], $_POST['status'])) {
            $movieId = (int)$_POST['movie_id'];
            $newStatus = $_POST['status'];
            
            $stmt = $pdo->prepare("UPDATE movies SET status = ? WHERE id = ?");
            if ($stmt->execute([$newStatus, $movieId])) {
                $success = 'Movie status updated.';
            }
        }
    }
}

// Get all movies
$movies = getMovies('all');
$genres = getAllGenres();
$csrfToken = generateCSRFToken();

// Check if adding new movie
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
                    <a href="<?php echo SITE_URL; ?>/admin/movies.php" class="list-group-item list-group-item-action bg-dark text-light border-secondary active">
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
                </div>
            </div>
        </div>
        
        <!-- Main Content -->
        <div class="col-lg-10">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2 class="fw-bold">
                    <i class="bi bi-film text-danger me-2"></i>Movie Management
                </h2>
                <?php if (!$showAddForm): ?>
                    <a href="<?php echo SITE_URL; ?>/admin/movies.php?action=add" class="btn btn-danger">
                        <i class="bi bi-plus-circle me-2"></i>Add Movie
                    </a>
                <?php else: ?>
                    <a href="<?php echo SITE_URL; ?>/admin/movies.php" class="btn btn-secondary">
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
            <!-- Add Movie Form -->
            <div class="card bg-dark border-secondary">
                <div class="card-header bg-danger">
                    <h5 class="mb-0"><i class="bi bi-plus-circle me-2"></i>Add New Movie</h5>
                </div>
                <div class="card-body">
                    <form method="POST" action="" enctype="multipart/form-data">
                        <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                        <input type="hidden" name="action" value="add">
                        
                        <div class="row g-3">
                            <div class="col-md-8">
                                <label class="form-label">Movie Title</label>
                                <input type="text" class="form-control bg-dark text-light border-secondary" 
                                       name="title" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Duration (minutes)</label>
                                <input type="number" class="form-control bg-dark text-light border-secondary" 
                                       name="duration" required>
                            </div>
                            <div class="col-12">
                                <label class="form-label">Description</label>
                                <textarea class="form-control bg-dark text-light border-secondary" 
                                          name="description" rows="4"></textarea>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Release Date</label>
                                <input type="date" class="form-control bg-dark text-light border-secondary" 
                                       name="release_date" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Status</label>
                                <select class="form-select bg-dark text-light border-secondary" name="status">
                                    <option value="coming_soon">Coming Soon</option>
                                    <option value="now_showing">Now Showing</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Rating (0-10)</label>
                                <input type="number" class="form-control bg-dark text-light border-secondary" 
                                       name="rating" step="0.1" min="0" max="10" value="0">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Poster Image</label>
                                <input type="file" class="form-control bg-dark text-light border-secondary" 
                                       name="poster" accept="image/*">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Trailer URL (YouTube Embed)</label>
                                <input type="url" class="form-control bg-dark text-light border-secondary" 
                                       name="trailer_url" placeholder="https://www.youtube.com/embed/...">
                            </div>
                            <div class="col-12">
                                <label class="form-label">Genres</label>
                                <div class="row g-2">
                                    <?php foreach ($genres as $genre): ?>
                                        <div class="col-md-3 col-6">
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" 
                                                       name="genres[]" value="<?php echo $genre['id']; ?>"
                                                       id="genre<?php echo $genre['id']; ?>">
                                                <label class="form-check-label" for="genre<?php echo $genre['id']; ?>">
                                                    <?php echo htmlspecialchars($genre['name']); ?>
                                                </label>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                        
                        <div class="mt-4">
                            <button type="submit" class="btn btn-danger">
                                <i class="bi bi-plus-circle me-2"></i>Add Movie
                            </button>
                            <a href="<?php echo SITE_URL; ?>/admin/movies.php" class="btn btn-secondary">Cancel</a>
                        </div>
                    </form>
                </div>
            </div>
            
            <?php else: ?>
            <!-- Movies Table -->
            <div class="card bg-dark border-secondary">
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-dark table-hover mb-0">
                            <thead class="table-secondary">
                                <tr>
                                    <th>Poster</th>
                                    <th>Title</th>
                                    <th>Duration</th>
                                    <th>Release</th>
                                    <th>Rating</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($movies as $movie): ?>
                                    <tr>
                                        <td>
                                            <img src="<?php echo UPLOADS_URL; ?>/movies/<?php echo $movie['poster_image']; ?>" 
                                                 width="50" class="rounded"
                                                 onerror="this.src='<?php echo ASSETS_URL; ?>/images/default-poster.jpg'">
                                        </td>
                                        <td>
                                            <strong><?php echo htmlspecialchars($movie['title']); ?></strong>
                                            <br><small class="text-muted"><?php echo $movie['genres']; ?></small>
                                        </td>
                                        <td><?php echo $movie['duration']; ?> min</td>
                                        <td><?php echo formatDate($movie['release_date']); ?></td>
                                        <td>
                                            <i class="bi bi-star-fill text-warning"></i>
                                            <?php echo $movie['rating']; ?>
                                        </td>
                                        <td>
                                            <form method="POST" class="d-inline">
                                                <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                                                <input type="hidden" name="action" value="update_status">
                                                <input type="hidden" name="movie_id" value="<?php echo $movie['id']; ?>">
                                                <select name="status" class="form-select form-select-sm bg-dark text-light border-secondary"
                                                        onchange="this.form.submit()" style="width: 130px;">
                                                    <option value="now_showing" <?php echo $movie['status'] === 'now_showing' ? 'selected' : ''; ?>>Now Showing</option>
                                                    <option value="coming_soon" <?php echo $movie['status'] === 'coming_soon' ? 'selected' : ''; ?>>Coming Soon</option>
                                                    <option value="archived" <?php echo $movie['status'] === 'archived' ? 'selected' : ''; ?>>Archived</option>
                                                </select>
                                            </form>
                                        </td>
                                        <td>
                                            <a href="<?php echo SITE_URL; ?>/movie.php?id=<?php echo $movie['id']; ?>" 
                                               class="btn btn-sm btn-outline-info" target="_blank" title="View">
                                                <i class="bi bi-eye"></i>
                                            </a>
                                            <form method="POST" class="d-inline" 
                                                  onsubmit="return confirm('Delete this movie?');">
                                                <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                                                <input type="hidden" name="action" value="delete">
                                                <input type="hidden" name="movie_id" value="<?php echo $movie['id']; ?>">
                                                <button type="submit" class="btn btn-sm btn-outline-danger" title="Delete">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
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
