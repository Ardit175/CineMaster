<?php
/**
 * ============================================
 * CineMaster - Homepage
 * ============================================
 * Main landing page featuring:
 * - Now Showing carousel
 * - Coming Soon movies
 * - Quick search
 */

require_once 'config/config.php';

$pageTitle = 'Home';

// Get movies for display
$nowShowing = getMovies('now_showing', 6);
$comingSoon = getMovies('coming_soon', 4);
$genres = getAllGenres();

include INCLUDES_PATH . 'header.php';
?>

<!-- Hero Section with Carousel -->
<section class="hero-section">
    <div id="heroCarousel" class="carousel slide" data-bs-ride="carousel">
        <div class="carousel-indicators">
            <?php foreach ($nowShowing as $index => $movie): ?>
                <button type="button" data-bs-target="#heroCarousel" data-bs-slide-to="<?php echo $index; ?>" 
                        class="<?php echo $index === 0 ? 'active' : ''; ?>"></button>
            <?php endforeach; ?>
        </div>
        
        <div class="carousel-inner">
            <?php foreach ($nowShowing as $index => $movie): ?>
                <div class="carousel-item <?php echo $index === 0 ? 'active' : ''; ?>">
                    <div class="hero-slide" style="background-image: linear-gradient(rgba(0,0,0,0.7), rgba(0,0,0,0.9)), url('<?php echo UPLOADS_URL; ?>/movies/<?php echo $movie['poster_image']; ?>');">
                        <div class="container h-100">
                            <div class="row h-100 align-items-center">
                                <div class="col-lg-6">
                                    <span class="badge bg-danger mb-3">NOW SHOWING</span>
                                    <h1 class="display-4 fw-bold mb-3"><?php echo htmlspecialchars($movie['title']); ?></h1>
                                    <p class="lead mb-3 text-muted">
                                        <?php echo htmlspecialchars(substr($movie['description'], 0, 150)); ?>...
                                    </p>
                                    <div class="mb-4">
                                        <span class="me-3"><i class="bi bi-clock me-1"></i> <?php echo $movie['duration']; ?> min</span>
                                        <span class="me-3"><i class="bi bi-star-fill text-warning me-1"></i> <?php echo $movie['rating']; ?>/10</span>
                                        <span><i class="bi bi-tags me-1"></i> <?php echo $movie['genres'] ?? 'N/A'; ?></span>
                                    </div>
                                    <a href="<?php echo SITE_URL; ?>/movie.php?id=<?php echo $movie['id']; ?>" class="btn btn-danger btn-lg me-2">
                                        <i class="bi bi-ticket-perforated me-2"></i>Book Now
                                    </a>
                                    <a href="<?php echo SITE_URL; ?>/movie.php?id=<?php echo $movie['id']; ?>#trailer" class="btn btn-outline-light btn-lg">
                                        <i class="bi bi-play-circle me-2"></i>Watch Trailer
                                    </a>
                                </div>
                                <div class="col-lg-6 d-none d-lg-block text-center">
                                    <img src="<?php echo UPLOADS_URL; ?>/movies/<?php echo $movie['poster_image']; ?>" 
                                         alt="<?php echo htmlspecialchars($movie['title']); ?>"
                                         class="img-fluid rounded shadow poster-hover"
                                         style="max-height: 500px;"
                                         onerror="this.src='<?php echo ASSETS_URL; ?>/images/default-poster.jpg'">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        
        <button class="carousel-control-prev" type="button" data-bs-target="#heroCarousel" data-bs-slide="prev">
            <span class="carousel-control-prev-icon"></span>
        </button>
        <button class="carousel-control-next" type="button" data-bs-target="#heroCarousel" data-bs-slide="next">
            <span class="carousel-control-next-icon"></span>
        </button>
    </div>
</section>

<!-- Quick Booking Section -->
<section class="quick-booking py-4 bg-black">
    <div class="container">
        <form action="<?php echo SITE_URL; ?>/search.php" method="GET" class="row g-3 align-items-center justify-content-center">
            <div class="col-md-4">
                <select class="form-select bg-dark text-light border-secondary" name="genre">
                    <option value="">All Genres</option>
                    <?php foreach ($genres as $genre): ?>
                        <option value="<?php echo $genre['id']; ?>"><?php echo htmlspecialchars($genre['name']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-4">
                <input type="text" class="form-control bg-dark text-light border-secondary" 
                       name="q" placeholder="Search by movie title...">
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-danger w-100">
                    <i class="bi bi-search me-2"></i>Search
                </button>
            </div>
        </form>
    </div>
</section>

<!-- Now Showing Section -->
<section class="now-showing py-5">
    <div class="container">
        <div class="section-header d-flex justify-content-between align-items-center mb-4">
            <div>
                <h2 class="fw-bold">
                    <i class="bi bi-film text-danger me-2"></i>Now Showing
                </h2>
                <p class="text-muted mb-0">Currently playing in our theaters</p>
            </div>
            <a href="<?php echo SITE_URL; ?>/movies.php?status=now_showing" class="btn btn-outline-danger">
                View All <i class="bi bi-arrow-right ms-1"></i>
            </a>
        </div>
        
        <div class="row g-4">
            <?php foreach ($nowShowing as $movie): ?>
                <div class="col-lg-2 col-md-4 col-6">
                    <div class="movie-card">
                        <a href="<?php echo SITE_URL; ?>/movie.php?id=<?php echo $movie['id']; ?>">
                            <div class="poster-wrapper">
                                <img src="<?php echo UPLOADS_URL; ?>/movies/<?php echo $movie['poster_image']; ?>" 
                                     alt="<?php echo htmlspecialchars($movie['title']); ?>"
                                     class="img-fluid rounded"
                                     onerror="this.src='<?php echo ASSETS_URL; ?>/images/default-poster.jpg'">
                                <div class="overlay">
                                    <span class="rating">
                                        <i class="bi bi-star-fill text-warning"></i> <?php echo $movie['rating']; ?>
                                    </span>
                                    <button class="btn btn-danger btn-sm">Book Now</button>
                                </div>
                            </div>
                        </a>
                        <div class="movie-info mt-2">
                            <h6 class="mb-1 text-truncate">
                                <a href="<?php echo SITE_URL; ?>/movie.php?id=<?php echo $movie['id']; ?>" class="text-light text-decoration-none">
                                    <?php echo htmlspecialchars($movie['title']); ?>
                                </a>
                            </h6>
                            <small class="text-muted">
                                <i class="bi bi-clock me-1"></i><?php echo $movie['duration']; ?> min
                            </small>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- Coming Soon Section -->
<section class="coming-soon py-5 bg-black">
    <div class="container">
        <div class="section-header d-flex justify-content-between align-items-center mb-4">
            <div>
                <h2 class="fw-bold">
                    <i class="bi bi-calendar-event text-danger me-2"></i>Coming Soon
                </h2>
                <p class="text-muted mb-0">Upcoming releases you won't want to miss</p>
            </div>
            <a href="<?php echo SITE_URL; ?>/movies.php?status=coming_soon" class="btn btn-outline-danger">
                View All <i class="bi bi-arrow-right ms-1"></i>
            </a>
        </div>
        
        <div class="row g-4">
            <?php foreach ($comingSoon as $movie): ?>
                <div class="col-lg-3 col-md-6">
                    <div class="coming-soon-card card bg-dark border-secondary h-100">
                        <div class="row g-0">
                            <div class="col-4">
                                <img src="<?php echo UPLOADS_URL; ?>/movies/<?php echo $movie['poster_image']; ?>" 
                                     alt="<?php echo htmlspecialchars($movie['title']); ?>"
                                     class="img-fluid rounded-start h-100 object-fit-cover"
                                     onerror="this.src='<?php echo ASSETS_URL; ?>/images/default-poster.jpg'">
                            </div>
                            <div class="col-8">
                                <div class="card-body">
                                    <h6 class="card-title"><?php echo htmlspecialchars($movie['title']); ?></h6>
                                    <p class="card-text text-muted small">
                                        <?php echo htmlspecialchars(substr($movie['description'], 0, 60)); ?>...
                                    </p>
                                    <div class="text-danger small mb-2">
                                        <i class="bi bi-calendar me-1"></i>
                                        <?php echo formatDate($movie['release_date']); ?>
                                    </div>
                                    <span class="badge bg-secondary"><?php echo $movie['genres'] ?? 'N/A'; ?></span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- Features Section -->
<section class="features py-5">
    <div class="container">
        <div class="row g-4 text-center">
            <div class="col-lg-3 col-md-6">
                <div class="feature-box p-4">
                    <i class="bi bi-ticket-perforated-fill fs-1 text-danger mb-3 d-block"></i>
                    <h5>Easy Booking</h5>
                    <p class="text-muted mb-0">Book your tickets in just a few clicks</p>
                </div>
            </div>
            <div class="col-lg-3 col-md-6">
                <div class="feature-box p-4">
                    <i class="bi bi-credit-card fs-1 text-danger mb-3 d-block"></i>
                    <h5>Secure Payments</h5>
                    <p class="text-muted mb-0">Safe and secure payment with Stripe</p>
                </div>
            </div>
            <div class="col-lg-3 col-md-6">
                <div class="feature-box p-4">
                    <i class="bi bi-grid-3x3-gap fs-1 text-danger mb-3 d-block"></i>
                    <h5>Choose Your Seats</h5>
                    <p class="text-muted mb-0">Select your preferred seats</p>
                </div>
            </div>
            <div class="col-lg-3 col-md-6">
                <div class="feature-box p-4">
                    <i class="bi bi-phone fs-1 text-danger mb-3 d-block"></i>
                    <h5>E-Tickets</h5>
                    <p class="text-muted mb-0">Get tickets delivered to your email</p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Browse by Genre -->
<section class="browse-genre py-5 bg-black">
    <div class="container">
        <h2 class="fw-bold text-center mb-4">
            <i class="bi bi-tags text-danger me-2"></i>Browse by Genre
        </h2>
        <div class="d-flex flex-wrap justify-content-center gap-2">
            <?php foreach ($genres as $genre): ?>
                <a href="<?php echo SITE_URL; ?>/movies.php?genre=<?php echo $genre['id']; ?>" 
                   class="btn btn-outline-secondary btn-lg rounded-pill">
                    <?php echo htmlspecialchars($genre['name']); ?>
                </a>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<?php include INCLUDES_PATH . 'footer.php'; ?>
