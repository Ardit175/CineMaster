<?php
/**
 * ============================================
 * CineMaster - Movies Listing Page
 * ============================================
 * Displays all movies with filtering options
 */

require_once 'config/config.php';

$pageTitle = 'Movies';

// Get filters
$status = $_GET['status'] ?? 'all';
$genreFilter = isset($_GET['genre']) ? (int)$_GET['genre'] : null;

// Get movies based on filters
if ($genreFilter) {
    $movies = getMoviesByGenre($genreFilter);
    
    // Get genre name for title
    $pdo = getDBConnection();
    $stmt = $pdo->prepare("SELECT name FROM genres WHERE id = ?");
    $stmt->execute([$genreFilter]);
    $genreName = $stmt->fetchColumn();
    $pageTitle = $genreName ? $genreName . ' Movies' : 'Movies';
} else {
    $movies = getMovies($status);
    
    if ($status === 'now_showing') {
        $pageTitle = 'Now Showing';
    } elseif ($status === 'coming_soon') {
        $pageTitle = 'Coming Soon';
    }
}

$genres = getAllGenres();

include INCLUDES_PATH . 'header.php';
?>

<div class="container py-5">
    <!-- Page Header -->
    <div class="row mb-4">
        <div class="col-md-8">
            <h1 class="fw-bold">
                <i class="bi bi-camera-reels text-danger me-2"></i><?php echo htmlspecialchars($pageTitle); ?>
            </h1>
            <p class="text-muted">
                <?php echo count($movies); ?> movie<?php echo count($movies) !== 1 ? 's' : ''; ?> found
            </p>
        </div>
        <div class="col-md-4">
            <!-- Filter Dropdown -->
            <div class="d-flex gap-2 justify-content-md-end">
                <select class="form-select bg-dark text-light border-secondary" id="statusFilter" onchange="applyFilter()">
                    <option value="all" <?php echo $status === 'all' ? 'selected' : ''; ?>>All Movies</option>
                    <option value="now_showing" <?php echo $status === 'now_showing' ? 'selected' : ''; ?>>Now Showing</option>
                    <option value="coming_soon" <?php echo $status === 'coming_soon' ? 'selected' : ''; ?>>Coming Soon</option>
                </select>
                <select class="form-select bg-dark text-light border-secondary" id="genreFilter" onchange="applyFilter()">
                    <option value="">All Genres</option>
                    <?php foreach ($genres as $genre): ?>
                        <option value="<?php echo $genre['id']; ?>" <?php echo $genreFilter == $genre['id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($genre['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
    </div>
    
    <!-- Status Tabs -->
    <ul class="nav nav-pills mb-4">
        <li class="nav-item">
            <a class="nav-link <?php echo $status === 'all' && !$genreFilter ? 'active' : ''; ?>" 
               href="<?php echo SITE_URL; ?>/movies.php">All</a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?php echo $status === 'now_showing' ? 'active' : ''; ?>" 
               href="<?php echo SITE_URL; ?>/movies.php?status=now_showing">Now Showing</a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?php echo $status === 'coming_soon' ? 'active' : ''; ?>" 
               href="<?php echo SITE_URL; ?>/movies.php?status=coming_soon">Coming Soon</a>
        </li>
    </ul>
    
    <!-- Movies Grid -->
    <?php if (empty($movies)): ?>
        <div class="text-center py-5">
            <i class="bi bi-film fs-1 text-muted mb-3 d-block"></i>
            <h4>No movies found</h4>
            <p class="text-muted">Try adjusting your filters</p>
            <a href="<?php echo SITE_URL; ?>/movies.php" class="btn btn-outline-danger">View All Movies</a>
        </div>
    <?php else: ?>
        <div class="row g-4">
            <?php foreach ($movies as $movie): ?>
                <div class="col-xl-2 col-lg-3 col-md-4 col-6">
                    <div class="movie-card h-100">
                        <a href="<?php echo SITE_URL; ?>/movie.php?id=<?php echo $movie['id']; ?>" class="text-decoration-none">
                            <div class="poster-wrapper position-relative">
                                <img src="<?php echo UPLOADS_URL; ?>/movies/<?php echo $movie['poster_image']; ?>" 
                                     alt="<?php echo htmlspecialchars($movie['title']); ?>"
                                     class="img-fluid rounded"
                                     onerror="this.src='<?php echo ASSETS_URL; ?>/images/default-poster.jpg'">
                                
                                <!-- Status Badge -->
                                <span class="badge position-absolute top-0 start-0 m-2 
                                    <?php echo $movie['status'] === 'now_showing' ? 'bg-danger' : 'bg-warning text-dark'; ?>">
                                    <?php echo $movie['status'] === 'now_showing' ? 'Now Showing' : 'Coming Soon'; ?>
                                </span>
                                
                                <!-- Overlay -->
                                <div class="overlay">
                                    <span class="rating">
                                        <i class="bi bi-star-fill text-warning"></i> 
                                        <?php echo $movie['rating'] > 0 ? $movie['rating'] : 'N/A'; ?>
                                    </span>
                                    <?php if ($movie['status'] === 'now_showing'): ?>
                                        <span class="btn btn-danger btn-sm">Book Now</span>
                                    <?php else: ?>
                                        <span class="btn btn-warning btn-sm text-dark">View Details</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </a>
                        <div class="movie-info mt-2">
                            <h6 class="mb-1 text-truncate">
                                <a href="<?php echo SITE_URL; ?>/movie.php?id=<?php echo $movie['id']; ?>" 
                                   class="text-light text-decoration-none">
                                    <?php echo htmlspecialchars($movie['title']); ?>
                                </a>
                            </h6>
                            <div class="d-flex justify-content-between align-items-center">
                                <small class="text-muted">
                                    <i class="bi bi-clock me-1"></i><?php echo $movie['duration']; ?> min
                                </small>
                                <small class="text-muted">
                                    <?php echo formatDate($movie['release_date'], 'M Y'); ?>
                                </small>
                            </div>
                            <?php if ($movie['genres']): ?>
                                <small class="text-danger d-block text-truncate mt-1">
                                    <?php echo htmlspecialchars($movie['genres']); ?>
                                </small>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<script>
function applyFilter() {
    const status = document.getElementById('statusFilter').value;
    const genre = document.getElementById('genreFilter').value;
    
    let url = '<?php echo SITE_URL; ?>/movies.php?';
    let params = [];
    
    if (status !== 'all') {
        params.push('status=' + status);
    }
    if (genre) {
        params.push('genre=' + genre);
    }
    
    window.location.href = url + params.join('&');
}
</script>

<?php include INCLUDES_PATH . 'footer.php'; ?>
