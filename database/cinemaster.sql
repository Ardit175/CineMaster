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
('The Dark Knight', 'When the menace known as the Joker wreaks havoc and chaos on the people of Gotham, Batman must accept one of the greatest psychological and physical tests of his ability to fight injustice.', 152, '2008-07-18', 'dark_knight.jpg', 'https://www.youtube.com/embed/EXeTwQWrcwY', 'now_showing', 9.0),
('Interstellar', 'A team of explorers travel through a wormhole in space in an attempt to ensure humanity''s survival. A journey beyond the stars where love transcends dimensions.', 169, '2014-11-07', 'interstellar.jpg', 'https://www.youtube.com/embed/zSWdZVtXT7E', 'now_showing', 8.7),
('Dune: Part Two', 'Paul Atreides unites with Chani and the Fremen while seeking revenge against the conspirators who destroyed his family. An epic continuation of the desert saga.', 166, '2024-03-01', 'dune.jpg', 'https://www.youtube.com/embed/Way9Dexny3w', 'now_showing', 8.8),
('Oppenheimer', 'The story of American scientist J. Robert Oppenheimer and his role in the development of the atomic bomb. A thrilling biographical epic.', 180, '2023-07-21', 'oppenheimer.jpg', 'https://www.youtube.com/embed/uYPbbksJxIg', 'now_showing', 8.5),
('Avatar: The Way of Water', 'Jake Sully lives with his newfound family formed on the extrasolar moon Pandora. Once a familiar threat returns to finish what was previously started, Jake must work with Neytiri to protect their family.', 192, '2025-02-15', 'avatar.jpg', 'https://www.youtube.com/embed/d9MyW72ELq0', 'coming_soon', 8.1),
('Barbie', 'Barbie and Ken are having the time of their lives in the colorful and seemingly perfect world of Barbie Land. However, when they get a chance to go to the real world, they discover the joys and perils of living among humans.', 114, '2025-03-20', 'barbie.jpg', 'https://www.youtube.com/embed/pBk4NYhWNMM', 'coming_soon', 7.2);

-- Link movies to genres
INSERT INTO movie_genres (movie_id, genre_id) VALUES 
(1, 1), (1, 7), (1, 3),  -- The Dark Knight: Action, Thriller, Drama
(2, 5), (2, 10), (2, 3), -- Interstellar: Sci-Fi, Adventure, Drama
(3, 5), (3, 1), (3, 10), -- Dune: Sci-Fi, Action, Adventure
(4, 3), (4, 7),          -- Oppenheimer: Drama, Thriller
(5, 5), (5, 1), (5, 10), -- Avatar: Sci-Fi, Action, Adventure
(6, 2), (6, 10), (6, 6); -- Barbie: Comedy, Adventure, Romance

-- Insert sample showtimes (for the next 7 days)
INSERT INTO showtimes (movie_id, theater_id, start_time, end_time, price) VALUES 
-- The Dark Knight showtimes
(1, 1, DATE_ADD(CURDATE(), INTERVAL 10 HOUR), DATE_ADD(CURDATE(), INTERVAL 12 HOUR) + INTERVAL 32 MINUTE, 15.00),
(1, 1, DATE_ADD(CURDATE(), INTERVAL 14 HOUR), DATE_ADD(CURDATE(), INTERVAL 16 HOUR) + INTERVAL 32 MINUTE, 15.00),
(1, 1, DATE_ADD(CURDATE(), INTERVAL 19 HOUR), DATE_ADD(CURDATE(), INTERVAL 21 HOUR) + INTERVAL 32 MINUTE, 18.00),
(1, 2, DATE_ADD(CURDATE(), INTERVAL 1 DAY) + INTERVAL 11 HOUR, DATE_ADD(CURDATE(), INTERVAL 1 DAY) + INTERVAL 13 HOUR + INTERVAL 32 MINUTE, 12.00),

-- Interstellar showtimes
(2, 1, DATE_ADD(CURDATE(), INTERVAL 13 HOUR), DATE_ADD(CURDATE(), INTERVAL 15 HOUR) + INTERVAL 49 MINUTE, 15.00),
(2, 3, DATE_ADD(CURDATE(), INTERVAL 18 HOUR), DATE_ADD(CURDATE(), INTERVAL 20 HOUR) + INTERVAL 49 MINUTE, 20.00),
(2, 4, DATE_ADD(CURDATE(), INTERVAL 1 DAY) + INTERVAL 20 HOUR, DATE_ADD(CURDATE(), INTERVAL 1 DAY) + INTERVAL 22 HOUR + INTERVAL 49 MINUTE, 25.00),

-- Dune Part Two showtimes
(3, 1, DATE_ADD(CURDATE(), INTERVAL 12 HOUR), DATE_ADD(CURDATE(), INTERVAL 14 HOUR) + INTERVAL 46 MINUTE, 18.00),
(3, 2, DATE_ADD(CURDATE(), INTERVAL 17 HOUR), DATE_ADD(CURDATE(), INTERVAL 19 HOUR) + INTERVAL 46 MINUTE, 18.00),
(3, 3, DATE_ADD(CURDATE(), INTERVAL 2 DAY) + INTERVAL 15 HOUR, DATE_ADD(CURDATE(), INTERVAL 2 DAY) + INTERVAL 17 HOUR + INTERVAL 46 MINUTE, 20.00),

-- Oppenheimer showtimes
(4, 2, DATE_ADD(CURDATE(), INTERVAL 21 HOUR), DATE_ADD(CURDATE(), INTERVAL 24 HOUR), 16.00),
(4, 3, DATE_ADD(CURDATE(), INTERVAL 1 DAY) + INTERVAL 22 HOUR, DATE_ADD(CURDATE(), INTERVAL 2 DAY) + INTERVAL 1 HOUR, 18.00);

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
