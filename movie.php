<?php
/**
 * ============================================
 * CineMaster - Movie Details Page
 * ============================================
 * Shows detailed information about a movie
 * including trailer, showtimes, and booking option
 */

require_once 'config/config.php';

// Get movie ID
$movieId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$movieId) {
    setFlashMessage('error', 'Movie not found.');
    redirect(SITE_URL . '/movies.php');
}

// Get movie details
$movie = getMovieById($movieId);

if (!$movie) {
    setFlashMessage('error', 'Movie not found.');
    redirect(SITE_URL . '/movies.php');
}

$pageTitle = $movie['title'];

// Get showtimes for this movie
$showtimes = getShowtimesByMovie($movieId);

// Group showtimes by date
$showtimesByDate = [];
foreach ($showtimes as $showtime) {
    $date = date('Y-m-d', strtotime($showtime['start_time']));
    if (!isset($showtimesByDate[$date])) {
        $showtimesByDate[$date] = [];
    }
    $showtimesByDate[$date][] = $showtime;
}

include INCLUDES_PATH . 'header.php';
?>

<!-- Movie Hero Section -->
<section class="movie-hero py-5" style="background: linear-gradient(rgba(0,0,0,0.8), rgba(0,0,0,0.95)), url('<?php echo UPLOADS_URL; ?>/movies/<?php echo $movie['poster_image']; ?>') center/cover;">
    <div class="container">
        <div class="row g-5">
            <!-- Poster -->
            <div class="col-lg-4 col-md-5">
                <img src="<?php echo UPLOADS_URL; ?>/movies/<?php echo $movie['poster_image']; ?>" 
                     alt="<?php echo htmlspecialchars($movie['title']); ?>"
                     class="img-fluid rounded shadow-lg w-100"
                     onerror="this.src='<?php echo ASSETS_URL; ?>/images/default-poster.jpg'">
            </div>
            
            <!-- Movie Info -->
            <div class="col-lg-8 col-md-7">
                <!-- Status Badge -->
                <span class="badge <?php echo $movie['status'] === 'now_showing' ? 'bg-danger' : 'bg-warning text-dark'; ?> mb-3">
                    <?php echo $movie['status'] === 'now_showing' ? 'NOW SHOWING' : 'COMING SOON'; ?>
                </span>
                
                <h1 class="display-4 fw-bold mb-3"><?php echo htmlspecialchars($movie['title']); ?></h1>
                
                <!-- Movie Meta -->
                <div class="movie-meta mb-4">
                    <span class="me-4">
                        <i class="bi bi-star-fill text-warning me-1"></i>
                        <strong><?php echo $movie['rating']; ?></strong>/10
                    </span>
                    <span class="me-4">
                        <i class="bi bi-clock me-1"></i>
                        <?php echo $movie['duration']; ?> minutes
                    </span>
                    <span class="me-4">
                        <i class="bi bi-calendar me-1"></i>
                        <?php echo formatDate($movie['release_date']); ?>
                    </span>
                </div>
                
                <!-- Genres -->
                <div class="mb-4">
                    <?php 
                    $genreList = explode(', ', $movie['genres'] ?? '');
                    foreach ($genreList as $genre): 
                        if (!empty($genre)):
                    ?>
                        <span class="badge bg-secondary me-1"><?php echo htmlspecialchars($genre); ?></span>
                    <?php 
                        endif;
                    endforeach; 
                    ?>
                </div>
                
                <!-- Description -->
                <p class="lead text-light mb-4"><?php echo htmlspecialchars($movie['description']); ?></p>
                
                <!-- Action Buttons -->
                <div class="d-flex gap-3">
                    <?php if ($movie['status'] === 'now_showing'): ?>
                        <a href="#showtimes" class="btn btn-danger btn-lg">
                            <i class="bi bi-ticket-perforated me-2"></i>Book Tickets
                        </a>
                    <?php endif; ?>
                    <?php if ($movie['trailer_url']): ?>
                        <button class="btn btn-outline-light btn-lg" data-bs-toggle="modal" data-bs-target="#trailerModal">
                            <i class="bi bi-play-circle me-2"></i>Watch Trailer
                        </button>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Showtimes Section -->
<?php if ($movie['status'] === 'now_showing' && !empty($showtimes)): ?>
<section id="showtimes" class="showtimes-section py-5 bg-black">
    <div class="container">
        <h2 class="fw-bold mb-4">
            <i class="bi bi-clock text-danger me-2"></i>Showtimes & Tickets
        </h2>
        
        <?php if (!isLoggedIn()): ?>
            <div class="alert alert-warning">
                <i class="bi bi-info-circle me-2"></i>
                Please <a href="<?php echo SITE_URL; ?>/login.php" class="alert-link">login</a> to book tickets.
            </div>
        <?php endif; ?>
        
        <!-- Date Tabs -->
        <ul class="nav nav-pills mb-4" id="dateTabs" role="tablist">
            <?php $first = true; ?>
            <?php foreach (array_keys($showtimesByDate) as $date): ?>
                <li class="nav-item" role="presentation">
                    <button class="nav-link <?php echo $first ? 'active' : ''; ?> bg-dark text-light border-secondary me-2" 
                            id="date-<?php echo $date; ?>-tab" data-bs-toggle="pill" 
                            data-bs-target="#date-<?php echo $date; ?>" type="button">
                        <?php echo date('D', strtotime($date)); ?><br>
                        <small><?php echo date('M d', strtotime($date)); ?></small>
                    </button>
                </li>
                <?php $first = false; ?>
            <?php endforeach; ?>
        </ul>
        
        <!-- Showtime Content -->
        <div class="tab-content" id="dateTabsContent">
            <?php $first = true; ?>
            <?php foreach ($showtimesByDate as $date => $dayShowtimes): ?>
                <div class="tab-pane fade <?php echo $first ? 'show active' : ''; ?>" 
                     id="date-<?php echo $date; ?>" role="tabpanel">
                    <div class="row g-3">
                        <?php foreach ($dayShowtimes as $showtime): ?>
                            <div class="col-lg-3 col-md-4 col-6">
                                <div class="showtime-card card bg-dark border-secondary h-100">
                                    <div class="card-body text-center">
                                        <h5 class="text-danger mb-2">
                                            <?php echo date('h:i A', strtotime($showtime['start_time'])); ?>
                                        </h5>
                                        <p class="mb-2">
                                            <i class="bi bi-building me-1"></i>
                                            <?php echo htmlspecialchars($showtime['theater_name']); ?>
                                        </p>
                                        <p class="text-success fw-bold mb-2">
                                            <?php echo formatPrice($showtime['price']); ?>
                                        </p>
                                        <p class="small text-muted mb-3">
                                            <i class="bi bi-grid-3x3 me-1"></i>
                                            <?php echo $showtime['available_seats']; ?> seats available
                                        </p>
                                        <?php if (isLoggedIn() && $showtime['available_seats'] > 0): ?>
                                            <a href="<?php echo SITE_URL; ?>/booking.php?showtime=<?php echo $showtime['id']; ?>" 
                                               class="btn btn-danger btn-sm w-100">
                                                Select Seats
                                            </a>
                                        <?php elseif ($showtime['available_seats'] == 0): ?>
                                            <button class="btn btn-secondary btn-sm w-100" disabled>Sold Out</button>
                                        <?php else: ?>
                                            <a href="<?php echo SITE_URL; ?>/login.php" class="btn btn-outline-danger btn-sm w-100">
                                                Login to Book
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php $first = false; ?>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php elseif ($movie['status'] === 'coming_soon'): ?>
<section class="py-5 bg-black text-center">
    <div class="container">
        <i class="bi bi-calendar-event fs-1 text-warning mb-3 d-block"></i>
        <h3>Coming Soon!</h3>
        <p class="text-muted">This movie will be released on <?php echo formatDate($movie['release_date']); ?></p>
    </div>
</section>
<?php endif; ?>

<!-- Trailer Modal -->
<?php if ($movie['trailer_url']): ?>
<div class="modal fade" id="trailerModal" tabindex="-1" aria-labelledby="trailerModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content bg-dark">
            <div class="modal-header border-secondary">
                <h5 class="modal-title" id="trailerModalLabel">
                    <i class="bi bi-play-circle me-2"></i><?php echo htmlspecialchars($movie['title']); ?> - Trailer
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-0">
                <div class="ratio ratio-16x9" id="trailer">
                    <iframe src="" 
                            data-src="<?php echo htmlspecialchars($movie['trailer_url']); ?>"
                            title="Movie Trailer" 
                            allowfullscreen></iframe>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Load trailer only when modal opens (to prevent autoplay issues)
document.getElementById('trailerModal').addEventListener('show.bs.modal', function () {
    const iframe = this.querySelector('iframe');
    iframe.src = iframe.dataset.src;
});
document.getElementById('trailerModal').addEventListener('hide.bs.modal', function () {
    const iframe = this.querySelector('iframe');
    iframe.src = '';
});
</script>
<?php endif; ?>

<?php include INCLUDES_PATH . 'footer.php'; ?>
