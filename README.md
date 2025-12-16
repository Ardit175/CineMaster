# 🎬 CineMaster - Cinema Ticket Booking Platform

A comprehensive cinema ticket booking system built with **PHP**, **MySQL**, and **Bootstrap 5**. This project was developed as a university assignment demonstrating full-stack web development skills.

---

## 📋 Table of Contents

1. [Features](#-features)
2. [Tech Stack](#-tech-stack)
3. [Installation](#-installation)
4. [Database Structure](#-database-structure)
5. [Project Structure](#-project-structure)
6. [Security Features](#-security-features)
7. [Usage Guide](#-usage-guide)
8. [Admin Panel](#-admin-panel)
9. [API & Payment Integration](#-api--payment-integration)
10. [Defense Preparation](#-defense-preparation)

---

## ✨ Features

### User Features
- **User Registration & Authentication**
  - Secure registration with email verification
  - Password hashing with `password_hash()` (bcrypt)
  - "Remember Me" functionality with secure tokens
  - Password reset via email link

- **Movie Browsing**
  - Homepage with "Now Showing" carousel
  - "Coming Soon" movie listings
  - Search functionality across movies and genres
  - Genre-based filtering
  - Movie details with trailer embeds

- **Booking System**
  - Interactive seat selection
  - Real-time seat availability
  - Multiple seat booking (up to 10 per transaction)
  - Stripe payment integration (test mode)
  - E-ticket generation with booking number

- **User Dashboard**
  - Profile management
  - Password change
  - Booking history (upcoming/past)
  - Downloadable/printable tickets

### Admin Features
- **Dashboard** with statistics and activity logs
- **User Management** (view, edit roles, delete)
- **Movie Management** (CRUD operations)
- **Showtime Scheduling** with conflict detection
- **Booking Management** (view, status updates, cancellation)
- **System Logs** viewing and filtering

---

## 🛠 Tech Stack

| Component | Technology |
|-----------|------------|
| Backend | PHP 7.4+ (Core PHP, no frameworks) |
| Database | MySQL 5.7+ / MariaDB |
| Frontend | HTML5, CSS3, Bootstrap 5.3 |
| JavaScript | Vanilla JS, jQuery 3.6 |
| Icons | Bootstrap Icons |
| Server | Apache (XAMPP) |
| Payment | Stripe API (Test Mode) |

---

## 🚀 Installation

### Prerequisites
- XAMPP (Apache + MySQL + PHP)
- PHP 7.4 or higher
- MySQL 5.7 or higher

### Step-by-Step Setup

1. **Clone/Copy Project**
   ```bash
   # Copy CineMaster folder to XAMPP htdocs
   cp -r CineMaster /Applications/XAMPP/htdocs/
   # Or on Windows:
   # Copy to C:\xampp\htdocs\
   ```

2. **Start XAMPP Services**
   - Start Apache
   - Start MySQL

3. **Create Database**
   ```bash
   # Access phpMyAdmin at http://localhost/phpmyadmin
   # Create a new database named 'cinemaster'
   # Import the SQL file:
   ```
   - Go to phpMyAdmin → cinemaster → Import
   - Select `database/cinemaster.sql`
   - Click "Go"

4. **Configure Database Connection**
   Edit `config/config.php`:
   ```php
   define('DB_HOST', 'localhost');
   define('DB_NAME', 'cinemaster');
   define('DB_USER', 'root');       // Your MySQL username
   define('DB_PASS', '');           // Your MySQL password
   ```

5. **Update Site URL**
   In `config/config.php`:
   ```php
   define('SITE_URL', 'http://localhost/CineMaster');
   ```

6. **Set Stripe Keys (Optional)**
   ```php
   define('STRIPE_PUBLIC_KEY', 'pk_test_your_key');
   define('STRIPE_SECRET_KEY', 'sk_test_your_key');
   ```

7. **Access the Application**
   - Open browser: `http://localhost/CineMaster`

### Default Test Accounts

| Role | Email | Password |
|------|-------|----------|
| Admin | admin@cinemaster.com | Admin@123 |
| User | john@example.com | User@123 |

---

## 🗄 Database Structure

The system uses **10 tables** with proper relationships:

```
┌─────────────────┐
│     users       │◄──────────────┐
├─────────────────┤               │
│ id (PK)         │               │
│ name            │               │
│ email           │               │
│ password        │        ┌──────┴──────┐
│ role            │        │   bookings   │
│ ...             │        ├─────────────┤
└─────────────────┘        │ id (PK)     │
                           │ user_id (FK) │
┌─────────────────┐        │ showtime_id  │◄────┐
│    movies       │        │ ...          │     │
├─────────────────┤        └──────────────┘     │
│ id (PK)         │◄──┐                         │
│ title           │   │    ┌───────────────┐    │
│ description     │   │    │  showtimes    │────┘
│ duration        │   │    ├───────────────┤
│ ...             │   └────│ movie_id (FK) │
└────────┬────────┘        │ theater_id(FK)│────┐
         │                 │ show_date     │    │
         ▼                 │ show_time     │    │
┌─────────────────┐        └───────────────┘    │
│  movie_genres   │                             │
├─────────────────┤        ┌───────────────┐    │
│ movie_id (FK)   │        │   theaters    │◄───┘
│ genre_id (FK)   │        ├───────────────┤
└────────┬────────┘        │ id (PK)       │
         │                 │ name          │
         ▼                 │ seats_per_row │
┌─────────────────┐        │ total_rows    │
│     genres      │        └───────────────┘
├─────────────────┤
│ id (PK)         │
│ name            │
└─────────────────┘

Additional: login_attempts, seats, logs
```

### Key Relationships
- `users` → `bookings` (1:N)
- `movies` → `showtimes` (1:N)
- `theaters` → `showtimes` (1:N)
- `showtimes` → `bookings` (1:N)
- `movies` ↔ `genres` (M:N via movie_genres)

---

## 📁 Project Structure

```
CineMaster/
├── admin/                    # Admin panel pages
│   ├── dashboard.php         # Admin statistics
│   ├── users.php             # User management
│   ├── movies.php            # Movie CRUD
│   ├── showtimes.php         # Showtime scheduling
│   ├── bookings.php          # Booking management
│   └── logs.php              # System logs
│
├── assets/
│   ├── css/
│   │   └── style.css         # Custom dark theme
│   ├── js/
│   │   └── main.js           # Client-side functionality
│   └── images/
│       └── default-poster.svg
│
├── config/
│   └── config.php            # Database & app configuration
│
├── database/
│   └── cinemaster.sql        # Complete database schema
│
├── includes/
│   ├── auth_functions.php    # Authentication logic
│   ├── helper_functions.php  # Business logic
│   ├── header.php            # Page header template
│   └── footer.php            # Page footer template
│
├── logs/                     # Email & error logs
├── uploads/                  # User uploaded files
│   └── movies/               # Movie posters
│
├── user/
│   ├── profile.php           # User profile
│   └── my_bookings.php       # Booking history
│
├── index.php                 # Homepage
├── login.php                 # User login
├── register.php              # User registration
├── logout.php                # Logout handler
├── forgot_password.php       # Password reset request
├── reset_password.php        # Password reset form
├── verify.php                # Email verification
├── movie.php                 # Movie details
├── movies.php                # Movie listing
├── search.php                # Search results
├── booking.php               # Seat selection
├── checkout.php              # Payment page
├── process_payment.php       # Stripe processing
├── booking_confirmation.php  # Ticket display
├── 404.php                   # Error page
├── .htaccess                 # Apache configuration
└── README.md                 # This file
```

---

## 🔒 Security Features

### Authentication Security
- **Password Hashing**: `password_hash()` with bcrypt algorithm
- **SQL Injection Prevention**: PDO with prepared statements
- **XSS Prevention**: `htmlspecialchars()` on all outputs
- **CSRF Protection**: Token validation on all forms
- **Session Security**: HTTPOnly cookies, regenerated IDs
- **Account Lockout**: 7 failed attempts = 30-minute block
- **Session Timeout**: 15-minute inactivity logout

### Code Examples

**SQL Injection Prevention:**
```php
// WRONG (vulnerable):
$sql = "SELECT * FROM users WHERE email = '$email'";

// CORRECT (secure):
$stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
$stmt->execute([$email]);
```

**XSS Prevention:**
```php
// WRONG (vulnerable):
echo $_GET['name'];

// CORRECT (secure):
echo htmlspecialchars($_GET['name'], ENT_QUOTES, 'UTF-8');
```

**CSRF Token Usage:**
```php
// Generate in config.php
$token = generateCSRFToken();

// Include in form
<input type="hidden" name="csrf_token" value="<?php echo $token; ?>">

// Validate on submit
if (!validateCSRFToken($_POST['csrf_token'])) {
    die('Invalid request');
}
```

---

## 📖 Usage Guide

### For Users

1. **Registration**
   - Click "Register" in navbar
   - Fill in name, email, password
   - Submit and check email for verification

2. **Browsing Movies**
   - Homepage shows "Now Showing" carousel
   - Click movie poster for details
   - Use search bar for specific movies

3. **Booking Tickets**
   - Select movie → Choose showtime
   - Select seats on the interactive map
   - Proceed to checkout
   - Enter payment details (test: 4242 4242 4242 4242)
   - Receive booking confirmation

4. **Managing Bookings**
   - Go to "My Bookings" in user dropdown
   - View upcoming and past bookings
   - Print tickets

### For Admins

1. **Access Admin Panel**
   - Login with admin account
   - Click "Admin" in navbar or go to `/admin/dashboard.php`

2. **Managing Content**
   - Add/edit movies via Admin → Movies
   - Schedule showtimes via Admin → Showtimes
   - View bookings via Admin → Bookings

---

## 🔐 Admin Panel

| Section | Features |
|---------|----------|
| Dashboard | Stats, recent bookings, activity logs |
| Users | View all, change roles, delete users |
| Movies | Add new, edit status, delete movies |
| Showtimes | Schedule shows, set prices, conflict detection |
| Bookings | View all, change status, cancel bookings |
| Logs | Filter by user/category/date, clear old logs |

---

## 💳 API & Payment Integration

### Stripe Integration (Test Mode)

```php
// Test Card Numbers:
// Success: 4242 4242 4242 4242
// Decline: 4000 0000 0000 0002
// Any future expiry, any CVC
```

### Payment Flow:
1. User selects seats → Total calculated
2. Checkout page loads Stripe.js
3. Card tokenized client-side
4. Token sent to `process_payment.php`
5. Server creates Stripe charge
6. Success → Booking created
7. Confirmation page displayed

---

## 🎓 Defense Preparation

### Key Concepts to Explain

1. **Why PDO over mysqli?**
   - Supports multiple databases
   - Named placeholders
   - Exception handling
   - Better OOP support

2. **Session vs Cookie Authentication**
   - Sessions: Server-side, secure
   - Cookies: "Remember Me" token, hashed

3. **Password Hashing**
   - Never store plain passwords
   - `password_hash()` generates unique salt
   - `password_verify()` for comparison

4. **CSRF Tokens**
   - Prevent unauthorized form submissions
   - Unique per session
   - Validated server-side

5. **MVC-like Architecture**
   - Config: Database, constants
   - Includes: Functions (Model-like)
   - Templates: Header/Footer (View)
   - Pages: Logic handlers (Controller)

### Common Questions

**Q: How does the booking system prevent double-booking?**
A: The `seats` table stores booked seats with `booking_id` and `showtime_id`. Before booking, we query existing seats for that showtime and validate user's selection against available seats.

**Q: How is the session timeout implemented?**
A: We store `last_activity` timestamp in session. On each request, we check if `time() - last_activity > SESSION_TIMEOUT`. If exceeded, we destroy the session.

**Q: How does the account lockout work?**
A: The `login_attempts` table tracks failed attempts by IP. After `MAX_LOGIN_ATTEMPTS` (7), we check if 30 minutes have passed. If not, login is blocked.

---

## 📞 Support

For questions about this project:
- Email: [Your Email]
- GitHub: [Your GitHub]

---

## 📝 License

This project was created for educational purposes as a university assignment.

---

**Made with ❤️ for the Cinema Experience**
