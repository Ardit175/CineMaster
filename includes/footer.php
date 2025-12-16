    </main>
    <!-- End Main Content -->
    
    <!-- Footer -->
    <footer class="bg-black text-light py-5 mt-5">
        <div class="container">
            <div class="row">
                <!-- About Section -->
                <div class="col-lg-4 col-md-6 mb-4">
                    <h5 class="text-danger fw-bold mb-3">
                        <i class="bi bi-film me-2"></i>CineMaster
                    </h5>
                    <p class="text-muted">
                        Your premier destination for the best cinema experience. 
                        Book tickets for the latest movies, enjoy premium seating, 
                        and create unforgettable memories.
                    </p>
                    <div class="social-links">
                        <a href="#" class="text-light me-3"><i class="bi bi-facebook fs-5"></i></a>
                        <a href="#" class="text-light me-3"><i class="bi bi-twitter fs-5"></i></a>
                        <a href="#" class="text-light me-3"><i class="bi bi-instagram fs-5"></i></a>
                        <a href="#" class="text-light"><i class="bi bi-youtube fs-5"></i></a>
                    </div>
                </div>
                
                <!-- Quick Links -->
                <div class="col-lg-2 col-md-6 mb-4">
                    <h6 class="text-uppercase fw-bold mb-3">Quick Links</h6>
                    <ul class="list-unstyled">
                        <li class="mb-2">
                            <a href="<?php echo SITE_URL; ?>" class="text-muted text-decoration-none hover-effect">
                                <i class="bi bi-chevron-right me-1"></i> Home
                            </a>
                        </li>
                        <li class="mb-2">
                            <a href="<?php echo SITE_URL; ?>/movies.php" class="text-muted text-decoration-none hover-effect">
                                <i class="bi bi-chevron-right me-1"></i> Movies
                            </a>
                        </li>
                        <li class="mb-2">
                            <a href="<?php echo SITE_URL; ?>/movies.php?status=coming_soon" class="text-muted text-decoration-none hover-effect">
                                <i class="bi bi-chevron-right me-1"></i> Coming Soon
                            </a>
                        </li>
                        <li class="mb-2">
                            <a href="#" class="text-muted text-decoration-none hover-effect">
                                <i class="bi bi-chevron-right me-1"></i> Contact Us
                            </a>
                        </li>
                    </ul>
                </div>
                
                <!-- Genres -->
                <div class="col-lg-2 col-md-6 mb-4">
                    <h6 class="text-uppercase fw-bold mb-3">Genres</h6>
                    <ul class="list-unstyled">
                        <li class="mb-2">
                            <a href="<?php echo SITE_URL; ?>/movies.php?genre=1" class="text-muted text-decoration-none hover-effect">
                                <i class="bi bi-chevron-right me-1"></i> Action
                            </a>
                        </li>
                        <li class="mb-2">
                            <a href="<?php echo SITE_URL; ?>/movies.php?genre=2" class="text-muted text-decoration-none hover-effect">
                                <i class="bi bi-chevron-right me-1"></i> Comedy
                            </a>
                        </li>
                        <li class="mb-2">
                            <a href="<?php echo SITE_URL; ?>/movies.php?genre=4" class="text-muted text-decoration-none hover-effect">
                                <i class="bi bi-chevron-right me-1"></i> Horror
                            </a>
                        </li>
                        <li class="mb-2">
                            <a href="<?php echo SITE_URL; ?>/movies.php?genre=5" class="text-muted text-decoration-none hover-effect">
                                <i class="bi bi-chevron-right me-1"></i> Sci-Fi
                            </a>
                        </li>
                    </ul>
                </div>
                
                <!-- Contact Info -->
                <div class="col-lg-4 col-md-6 mb-4">
                    <h6 class="text-uppercase fw-bold mb-3">Contact Us</h6>
                    <ul class="list-unstyled text-muted">
                        <li class="mb-2">
                            <i class="bi bi-geo-alt text-danger me-2"></i>
                            123 Cinema Street, Movie City, MC 12345
                        </li>
                        <li class="mb-2">
                            <i class="bi bi-telephone text-danger me-2"></i>
                            +1 (555) 123-4567
                        </li>
                        <li class="mb-2">
                            <i class="bi bi-envelope text-danger me-2"></i>
                            info@cinemaster.com
                        </li>
                        <li class="mb-2">
                            <i class="bi bi-clock text-danger me-2"></i>
                            Mon - Sun: 10:00 AM - 11:00 PM
                        </li>
                    </ul>
                </div>
            </div>
            
            <hr class="border-secondary">
            
            <!-- Copyright -->
            <div class="row align-items-center">
                <div class="col-md-6 text-center text-md-start">
                    <p class="text-muted mb-0">
                        &copy; <?php echo date('Y'); ?> CineMaster. All Rights Reserved.
                    </p>
                </div>
                <div class="col-md-6 text-center text-md-end">
                    <p class="text-muted mb-0">
                        <small>
                            <a href="#" class="text-muted text-decoration-none">Privacy Policy</a>
                            <span class="mx-2">|</span>
                            <a href="#" class="text-muted text-decoration-none">Terms of Service</a>
                        </small>
                    </p>
                </div>
            </div>
        </div>
    </footer>
    
    <!-- Bootstrap 5 JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    
    <!-- Custom JavaScript -->
    <script src="<?php echo ASSETS_URL; ?>/js/main.js"></script>
    
    <?php if (isset($extraJS)): ?>
        <?php foreach ($extraJS as $js): ?>
            <script src="<?php echo $js; ?>"></script>
        <?php endforeach; ?>
    <?php endif; ?>
</body>
</html>
