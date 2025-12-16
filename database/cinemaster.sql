-- ============================================
-- CineMaster Database Schema
-- Cinema Ticket Booking Platform
-- ============================================
-- This SQL file creates all necessary tables for the CineMaster platform
-- Run this in phpMyAdmin or MySQL command line

-- Create Database
CREATE DATABASE IF NOT EXISTS cinemaster;
USE cinemaster;

-- ============================================
-- TABLE 1: users
-- Stores user account information
-- ============================================
CREATE TABLE users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(255) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,                    -- Hashed password using password_hash()
    role ENUM('admin', 'user') DEFAULT 'user',         -- User role for access control
    profile_photo VARCHAR(255) DEFAULT 'default.png',  -- Profile picture filename
    is_verified TINYINT(1) DEFAULT 0,                  -- Email verification status
    token VARCHAR(255) DEFAULT NULL,                   -- Verification/Reset token
    token_expire DATETIME DEFAULT NULL,                -- Token expiration time
    remember_token VARCHAR(255) DEFAULT NULL,          -- Remember me cookie token
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================
-- TABLE 2: login_attempts
-- Tracks failed login attempts for security (brute force protection)
-- ============================================
CREATE TABLE login_attempts (
    id INT PRIMARY KEY AUTO_INCREMENT,
    ip_address VARCHAR(45) NOT NULL,                   -- Supports IPv6 addresses
    email VARCHAR(255) DEFAULT NULL,                   -- Email attempted (optional)
    attempt_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_ip_time (ip_address, attempt_time)       -- Index for faster lookups
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================
-- TABLE 3: genres
-- Movie genre categories
-- ============================================
CREATE TABLE genres (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(50) NOT NULL UNIQUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================
-- TABLE 4: movies
-- Stores movie information
-- ============================================
CREATE TABLE movies (
    id INT PRIMARY KEY AUTO_INCREMENT,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    duration INT NOT NULL,                             -- Duration in minutes
    release_date DATE NOT NULL,
    poster_image VARCHAR(255) DEFAULT 'default_poster.jpg',
    trailer_url VARCHAR(500) DEFAULT NULL,             -- YouTube/Vimeo embed URL
    status ENUM('now_showing', 'coming_soon', 'archived') DEFAULT 'coming_soon',
    rating DECIMAL(3,1) DEFAULT 0.0,                   -- Average rating (0-10)
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================
-- TABLE 5: movie_genres (Junction Table)
-- Many-to-Many relationship between movies and genres
-- ============================================
CREATE TABLE movie_genres (
    id INT PRIMARY KEY AUTO_INCREMENT,
    movie_id INT NOT NULL,
    genre_id INT NOT NULL,
    FOREIGN KEY (movie_id) REFERENCES movies(id) ON DELETE CASCADE,
    FOREIGN KEY (genre_id) REFERENCES genres(id) ON DELETE CASCADE,
    UNIQUE KEY unique_movie_genre (movie_id, genre_id) -- Prevent duplicates
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================
-- TABLE 6: theaters
-- Cinema halls/screens information
-- ============================================
CREATE TABLE theaters (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    total_seats INT NOT NULL DEFAULT 100,
    rows_count INT NOT NULL DEFAULT 10,                -- Number of rows
    seats_per_row INT NOT NULL DEFAULT 10,             -- Seats per row
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================
-- TABLE 7: showtimes
-- Movie screening schedules
-- ============================================
CREATE TABLE showtimes (
    id INT PRIMARY KEY AUTO_INCREMENT,
    movie_id INT NOT NULL,
    theater_id INT NOT NULL,
    start_time DATETIME NOT NULL,
    end_time DATETIME NOT NULL,                        -- Calculated: start_time + movie duration
    price DECIMAL(10,2) NOT NULL DEFAULT 10.00,        -- Ticket price
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (movie_id) REFERENCES movies(id) ON DELETE CASCADE,
    FOREIGN KEY (theater_id) REFERENCES theaters(id) ON DELETE CASCADE,
    INDEX idx_showtime (start_time, movie_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================
-- TABLE 8: bookings
-- User ticket bookings
-- ============================================
CREATE TABLE bookings (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    showtime_id INT NOT NULL,
    total_amount DECIMAL(10,2) NOT NULL,
    booking_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    payment_status ENUM('pending', 'completed', 'failed', 'refunded') DEFAULT 'pending',
    stripe_payment_id VARCHAR(255) DEFAULT NULL,       -- Stripe payment intent ID
    booking_reference VARCHAR(20) NOT NULL UNIQUE,     -- Unique booking code (e.g., CM-ABC123)
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (showtime_id) REFERENCES showtimes(id) ON DELETE CASCADE,
    INDEX idx_user_booking (user_id, booking_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================
-- TABLE 9: seats
-- Individual seats for each booking
-- ============================================
CREATE TABLE seats (
    id INT PRIMARY KEY AUTO_INCREMENT,
    booking_id INT NOT NULL,
    showtime_id INT NOT NULL,                          -- For easier seat availability lookup
    seat_number VARCHAR(10) NOT NULL,                  -- Format: A1, B5, etc.
    FOREIGN KEY (booking_id) REFERENCES bookings(id) ON DELETE CASCADE,
    FOREIGN KEY (showtime_id) REFERENCES showtimes(id) ON DELETE CASCADE,
    UNIQUE KEY unique_seat_showtime (showtime_id, seat_number) -- One seat per showtime
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================
-- TABLE 10: logs
-- System activity and API logs
-- ============================================
CREATE TABLE logs (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT DEFAULT NULL,                          -- NULL for system/guest actions
    action VARCHAR(255) NOT NULL,                      -- Description of action
    action_type ENUM('auth', 'booking', 'payment', 'admin', 'api', 'error') DEFAULT 'auth',
    ip_address VARCHAR(45) DEFAULT NULL,
    user_agent TEXT DEFAULT NULL,
    details TEXT DEFAULT NULL,                         -- JSON encoded additional data
    timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_log_type (action_type, timestamp)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================
-- INSERT SAMPLE DATA
-- ============================================

-- Insert default admin user (Password: Admin@123)
INSERT INTO users (name, email, password, role, is_verified) VALUES 
('Admin', 'admin@cinemaster.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', 1);

-- Insert sample genres
INSERT INTO genres (name) VALUES 
('Action'),
('Comedy'),
('Drama'),
('Horror'),
('Sci-Fi'),
('Romance'),
('Thriller'),
('Animation'),
('Documentary'),
('Adventure');

-- Insert sample theaters
INSERT INTO theaters (name, total_seats, rows_count, seats_per_row) VALUES 
('Screen 1 - IMAX', 150, 10, 15),
('Screen 2 - Standard', 100, 10, 10),
('Screen 3 - Premium', 80, 8, 10),
('Screen 4 - VIP Lounge', 50, 5, 10);

-- Insert sample movies
INSERT INTO movies (title, description, duration, release_date, poster_image, trailer_url, status, rating) VALUES 
('The Dark Knight Returns', 'Batman faces his greatest challenge yet as a new villain threatens Gotham City with chaos and destruction.', 165, '2024-12-01', 'dark_knight.jpg', 'https://www.youtube.com/embed/EXeTwQWrcwY', 'now_showing', 9.2),
('Interstellar 2', 'A team of explorers travel through a newly discovered wormhole to ensure humanity survival.', 180, '2024-11-15', 'interstellar.jpg', 'https://www.youtube.com/embed/zSWdZVtXT7E', 'now_showing', 8.8),
('The Comedy Club', 'A hilarious journey of a failed comedian trying to make it big in New York City.', 120, '2024-12-10', 'comedy_club.jpg', 'https://www.youtube.com/embed/dQw4w9WgXcQ', 'now_showing', 7.5),
('Haunted Mansion', 'A family discovers their new home has terrifying secrets that refuse to stay buried.', 110, '2024-12-20', 'haunted.jpg', 'https://www.youtube.com/embed/dQw4w9WgXcQ', 'now_showing', 7.8),
('Space Warriors', 'An epic space adventure where humanity fight for survival against an alien invasion.', 145, '2025-01-15', 'space_warriors.jpg', 'https://www.youtube.com/embed/dQw4w9WgXcQ', 'coming_soon', 0.0),
('Love in Paris', 'A romantic tale of two strangers who meet in the city of lights and discover true love.', 125, '2025-02-14', 'love_paris.jpg', 'https://www.youtube.com/embed/dQw4w9WgXcQ', 'coming_soon', 0.0);

-- Link movies to genres
INSERT INTO movie_genres (movie_id, genre_id) VALUES 
(1, 1), (1, 7),  -- Dark Knight: Action, Thriller
(2, 5), (2, 10), -- Interstellar: Sci-Fi, Adventure
(3, 2),          -- Comedy Club: Comedy
(4, 4), (4, 7),  -- Haunted Mansion: Horror, Thriller
(5, 5), (5, 1), (5, 10), -- Space Warriors: Sci-Fi, Action, Adventure
(6, 6), (6, 3);  -- Love in Paris: Romance, Drama

-- Insert sample showtimes (for the next 7 days)
INSERT INTO showtimes (movie_id, theater_id, start_time, end_time, price) VALUES 
-- Dark Knight Returns showtimes
(1, 1, DATE_ADD(CURDATE(), INTERVAL 10 HOUR), DATE_ADD(CURDATE(), INTERVAL 12 HOUR) + INTERVAL 45 MINUTE, 15.00),
(1, 1, DATE_ADD(CURDATE(), INTERVAL 14 HOUR), DATE_ADD(CURDATE(), INTERVAL 16 HOUR) + INTERVAL 45 MINUTE, 15.00),
(1, 1, DATE_ADD(CURDATE(), INTERVAL 19 HOUR), DATE_ADD(CURDATE(), INTERVAL 21 HOUR) + INTERVAL 45 MINUTE, 18.00),
(1, 2, DATE_ADD(CURDATE(), INTERVAL 1 DAY) + INTERVAL 11 HOUR, DATE_ADD(CURDATE(), INTERVAL 1 DAY) + INTERVAL 13 HOUR + INTERVAL 45 MINUTE, 12.00),

-- Interstellar 2 showtimes
(2, 1, DATE_ADD(CURDATE(), INTERVAL 13 HOUR), DATE_ADD(CURDATE(), INTERVAL 16 HOUR), 15.00),
(2, 3, DATE_ADD(CURDATE(), INTERVAL 18 HOUR), DATE_ADD(CURDATE(), INTERVAL 21 HOUR), 20.00),
(2, 4, DATE_ADD(CURDATE(), INTERVAL 1 DAY) + INTERVAL 20 HOUR, DATE_ADD(CURDATE(), INTERVAL 1 DAY) + INTERVAL 23 HOUR, 25.00),

-- Comedy Club showtimes
(3, 2, DATE_ADD(CURDATE(), INTERVAL 12 HOUR), DATE_ADD(CURDATE(), INTERVAL 14 HOUR), 10.00),
(3, 2, DATE_ADD(CURDATE(), INTERVAL 17 HOUR), DATE_ADD(CURDATE(), INTERVAL 19 HOUR), 10.00),
(3, 3, DATE_ADD(CURDATE(), INTERVAL 2 DAY) + INTERVAL 15 HOUR, DATE_ADD(CURDATE(), INTERVAL 2 DAY) + INTERVAL 17 HOUR, 12.00),

-- Haunted Mansion showtimes
(4, 2, DATE_ADD(CURDATE(), INTERVAL 21 HOUR), DATE_ADD(CURDATE(), INTERVAL 22 HOUR) + INTERVAL 50 MINUTE, 12.00),
(4, 3, DATE_ADD(CURDATE(), INTERVAL 1 DAY) + INTERVAL 22 HOUR, DATE_ADD(CURDATE(), INTERVAL 1 DAY) + INTERVAL 23 HOUR + INTERVAL 50 MINUTE, 14.00);

-- ============================================
-- VIEWS (Optional - For easier queries)
-- ============================================

-- View for movie listings with genres
CREATE OR REPLACE VIEW v_movies_with_genres AS
SELECT 
    m.id,
    m.title,
    m.description,
    m.duration,
    m.release_date,
    m.poster_image,
    m.trailer_url,
    m.status,
    m.rating,
    GROUP_CONCAT(g.name SEPARATOR ', ') AS genres
FROM movies m
LEFT JOIN movie_genres mg ON m.id = mg.movie_id
LEFT JOIN genres g ON mg.genre_id = g.id
GROUP BY m.id;

-- View for available showtimes
CREATE OR REPLACE VIEW v_available_showtimes AS
SELECT 
    s.id AS showtime_id,
    m.id AS movie_id,
    m.title AS movie_title,
    m.poster_image,
    t.id AS theater_id,
    t.name AS theater_name,
    t.total_seats,
    s.start_time,
    s.end_time,
    s.price,
    (t.total_seats - COALESCE(booked.booked_seats, 0)) AS available_seats
FROM showtimes s
JOIN movies m ON s.movie_id = m.id
JOIN theaters t ON s.theater_id = t.id
LEFT JOIN (
    SELECT showtime_id, COUNT(*) AS booked_seats 
    FROM seats 
    GROUP BY showtime_id
) booked ON s.id = booked.showtime_id
WHERE s.start_time > NOW() AND s.is_active = 1;

-- ============================================
-- End of Database Schema
-- ============================================
