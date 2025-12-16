<?php
/**
 * ============================================
 * CineMaster - Search Results Page
 * ============================================
 * Displays search results for movies
 */

require_once 'config/config.php';

$pageTitle = 'Search Results';

$query = $_GET['q'] ?? '';
$genreFilter = isset($_GET['genre']) ? (int)$_GET['genre'] : null;

$movies = [];

if (!empty($query)) {
    $movies = searchMovies($query);
    $pageTitle = 'Search: "' . $query . '"';
} elseif ($genreFilter) {
    $movies = getMoviesByGenre($genreFilter);
    
    // Get genre name
    $pdo = getDBConnection();
    $stmt = $pdo->prepare("SELECT name FROM genres WHERE id = ?");
    $stmt->execute([$genreFilter]);
    $genreName = $stmt->fetchColumn();
    $pageTitle = $genreName . ' Movies';
}

$genres = getAllGenres();

include INCLUDES_PATH . 'header.php';
?>

<div class="container py-5">
    <!-- Search Form -->
    <div class="card bg-dark border-secondary mb-5">
        <div class="card-body p-4">
            <form action="" method="GET" class="row g-3">
                <div class="col-md-6">
                    <div class="input-group">
                        <span class="input-group-text bg-dark border-secondary text-light">
                            <i class="bi bi-search"></i>
                        </span>
                        <input type="text" class="form-control bg-dark text-light border-secondary" 
                               name="q" placeholder="Search by movie title or genre..."
                               value="<?php echo htmlspecialchars($query); ?>">
                    </div>
                </div>
                <div class="col-md-4">
                    <select class="form-select bg-dark text-light border-secondary" name="genre">
                        <option value="">All Genres</option>
                        <?php foreach ($genres as $genre): ?>
                            <option value="<?php echo $genre['id']; ?>" <?php echo $genreFilter == $genre['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($genre['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-danger w-100">
                        <i class="bi bi-search me-2"></i>Search
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Results Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="fw-bold"><?php echo htmlspecialchars($pageTitle); ?></h2>
            <p class="text-muted mb-0">
                <?php echo count($movies); ?> result<?php echo count($movies) !== 1 ? 's' : ''; ?> found
            </p>
        </div>
        <?php if (!empty($query) || $genreFilter): ?>
            <a href="<?php echo SITE_URL; ?>/movies.php" class="btn btn-outline-secondary">
                <i class="bi bi-x me-1"></i>Clear Search
            </a>
        <?php endif; ?>
    </div>
    
    <!-- Results Grid -->
    <?php if (empty($movies)): ?>
        <div class="text-center py-5">
            <i class="bi bi-search fs-1 text-muted mb-3 d-block"></i>
            <h4>No movies found</h4>
            <p class="text-muted">Try different keywords or browse our collection</p>
            <a href="<?php echo SITE_URL; ?>/movies.php" class="btn btn-danger">
                <i class="bi bi-camera-reels me-2"></i>Browse All Movies
            </a>
        </div>
    <?php else: ?>
        <div class="row g-4">
            <?php foreach ($movies as $movie): ?>
                <div class="col-lg-3 col-md-4 col-6">
                    <div class="movie-card h-100">
                        <a href="<?php echo SITE_URL; ?>/movie.php?id=<?php echo $movie['id']; ?>" class="text-decoration-none">
                            <div class="poster-wrapper position-relative">
                                <img src="<?php echo UPLOADS_URL; ?>/movies/<?php echo $movie['poster_image']; ?>" 
                                     alt="<?php echo htmlspecialchars($movie['title']); ?>"
                                     class="img-fluid rounded"
                                     onerror="this.src='<?php echo ASSETS_URL; ?>/images/default-poster.jpg'">
                                
                                <span class="badge position-absolute top-0 start-0 m-2 
                                    <?php echo $movie['status'] === 'now_showing' ? 'bg-danger' : 'bg-warning text-dark'; ?>">
                                    <?php echo $movie['status'] === 'now_showing' ? 'Now Showing' : 'Coming Soon'; ?>
                                </span>
                                
                                <div class="overlay">
                                    <span class="rating">
                                        <i class="bi bi-star-fill text-warning"></i> 
                                        <?php echo $movie['rating'] > 0 ? $movie['rating'] : 'N/A'; ?>
                                    </span>
                                    <span class="btn btn-danger btn-sm">View Details</span>
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
                            <small class="text-muted">
                                <i class="bi bi-clock me-1"></i><?php echo $movie['duration']; ?> min
                            </small>
                            <?php if ($movie['genres']): ?>
                                <small class="text-danger d-block text-truncate">
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

<?php include INCLUDES_PATH . 'footer.php'; ?>
