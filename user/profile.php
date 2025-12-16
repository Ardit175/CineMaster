<?php
/**
 * ============================================
 * CineMaster - User Profile Page
 * ============================================
 * Allows users to view and update their profile
 */

require_once '../config/config.php';

requireLogin();

$pageTitle = 'My Profile';
$user = getCurrentUser();
$errors = [];
$success = '';

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Invalid request.';
    } else {
        $action = $_POST['action'] ?? '';
        
        if ($action === 'update_profile') {
            $name = sanitize($_POST['name'] ?? '');
            
            if (empty($name)) {
                $errors[] = 'Name is required.';
            } elseif (strlen($name) < 2 || strlen($name) > 100) {
                $errors[] = 'Name must be between 2 and 100 characters.';
            }
            
            // Handle profile photo upload
            $profilePhoto = $user['profile_photo'];
            if (isset($_FILES['profile_photo']) && $_FILES['profile_photo']['error'] === UPLOAD_ERR_OK) {
                $uploadResult = uploadFile(
                    $_FILES['profile_photo'],
                    UPLOADS_PATH . 'profiles/',
                    'profile_'
                );
                
                if ($uploadResult['success']) {
                    $profilePhoto = $uploadResult['filename'];
                } else {
                    $errors[] = $uploadResult['message'];
                }
            }
            
            if (empty($errors)) {
                $updateResult = updateUserProfile($_SESSION['user_id'], [
                    'name' => $name,
                    'profile_photo' => $profilePhoto
                ]);
                
                if ($updateResult) {
                    $_SESSION['user_name'] = $name;
                    $user = getCurrentUser();
                    $success = 'Profile updated successfully!';
                    logAction($_SESSION['user_id'], 'Profile updated', 'auth');
                } else {
                    $errors[] = 'Failed to update profile.';
                }
            }
            
        } elseif ($action === 'change_password') {
            $currentPassword = $_POST['current_password'] ?? '';
            $newPassword = $_POST['new_password'] ?? '';
            $confirmPassword = $_POST['confirm_password'] ?? '';
            
            if (empty($currentPassword)) {
                $errors[] = 'Current password is required.';
            }
            if (empty($newPassword)) {
                $errors[] = 'New password is required.';
            } elseif (strlen($newPassword) < 8) {
                $errors[] = 'New password must be at least 8 characters.';
            } elseif (!preg_match('/[A-Z]/', $newPassword) || !preg_match('/[a-z]/', $newPassword) || !preg_match('/[0-9]/', $newPassword)) {
                $errors[] = 'Password must contain uppercase, lowercase, and a number.';
            }
            if ($newPassword !== $confirmPassword) {
                $errors[] = 'New passwords do not match.';
            }
            
            if (empty($errors)) {
                $result = changePassword($_SESSION['user_id'], $currentPassword, $newPassword);
                if ($result['success']) {
                    $success = $result['message'];
                } else {
                    $errors[] = $result['message'];
                }
            }
        }
    }
}

$csrfToken = generateCSRFToken();

// Get user's booking stats
$pdo = getDBConnection();
$stmt = $pdo->prepare("SELECT COUNT(*) FROM bookings WHERE user_id = ?");
$stmt->execute([$_SESSION['user_id']]);
$totalBookings = $stmt->fetchColumn();

$stmt = $pdo->prepare("SELECT COALESCE(SUM(total_amount), 0) FROM bookings WHERE user_id = ? AND payment_status = 'completed'");
$stmt->execute([$_SESSION['user_id']]);
$totalSpent = $stmt->fetchColumn();

include INCLUDES_PATH . 'header.php';
?>

<div class="container py-5">
    <div class="row">
        <!-- Sidebar -->
        <div class="col-lg-3 mb-4">
            <div class="card bg-dark border-secondary">
                <div class="card-body text-center">
                    <img src="<?php echo UPLOADS_URL; ?>/profiles/<?php echo $user['profile_photo'] ?? 'default.png'; ?>" 
                         class="rounded-circle mb-3" width="120" height="120"
                         alt="Profile Photo"
                         onerror="this.src='<?php echo ASSETS_URL; ?>/images/default-avatar.png'"
                         style="object-fit: cover;">
                    <h5 class="mb-1"><?php echo htmlspecialchars($user['name']); ?></h5>
                    <p class="text-muted small mb-3"><?php echo htmlspecialchars($user['email']); ?></p>
                    <span class="badge bg-<?php echo $user['role'] === 'admin' ? 'danger' : 'secondary'; ?>">
                        <?php echo ucfirst($user['role']); ?>
                    </span>
                </div>
                <div class="card-footer bg-transparent border-secondary">
                    <small class="text-muted">
                        Member since <?php echo formatDate($user['created_at']); ?>
                    </small>
                </div>
            </div>
            
            <!-- Quick Stats -->
            <div class="card bg-dark border-secondary mt-3">
                <div class="card-body">
                    <h6 class="text-muted mb-3">Quick Stats</h6>
                    <div class="d-flex justify-content-between mb-2">
                        <span>Total Bookings</span>
                        <span class="fw-bold"><?php echo $totalBookings; ?></span>
                    </div>
                    <div class="d-flex justify-content-between">
                        <span>Total Spent</span>
                        <span class="fw-bold text-success"><?php echo formatPrice($totalSpent); ?></span>
                    </div>
                </div>
            </div>
            
            <!-- Navigation -->
            <div class="list-group mt-3">
                <a href="<?php echo SITE_URL; ?>/user/profile.php" class="list-group-item list-group-item-action bg-dark text-light border-secondary active">
                    <i class="bi bi-person me-2"></i>Profile Settings
                </a>
                <a href="<?php echo SITE_URL; ?>/user/my_bookings.php" class="list-group-item list-group-item-action bg-dark text-light border-secondary">
                    <i class="bi bi-ticket-perforated me-2"></i>My Bookings
                </a>
                <a href="<?php echo SITE_URL; ?>/logout.php" class="list-group-item list-group-item-action bg-dark text-danger border-secondary">
                    <i class="bi bi-box-arrow-right me-2"></i>Logout
                </a>
            </div>
        </div>
        
        <!-- Main Content -->
        <div class="col-lg-9">
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
            
            <!-- Profile Update Form -->
            <div class="card bg-dark border-secondary mb-4">
                <div class="card-header bg-danger">
                    <h5 class="mb-0">
                        <i class="bi bi-person me-2"></i>Profile Information
                    </h5>
                </div>
                <div class="card-body">
                    <form method="POST" action="" enctype="multipart/form-data">
                        <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                        <input type="hidden" name="action" value="update_profile">
                        
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="name" class="form-label">Full Name</label>
                                <input type="text" class="form-control bg-dark text-light border-secondary" 
                                       id="name" name="name" 
                                       value="<?php echo htmlspecialchars($user['name']); ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label for="email" class="form-label">Email Address</label>
                                <input type="email" class="form-control bg-dark text-light border-secondary" 
                                       id="email" value="<?php echo htmlspecialchars($user['email']); ?>" disabled>
                                <small class="text-muted">Email cannot be changed</small>
                            </div>
                            <div class="col-12">
                                <label for="profile_photo" class="form-label">Profile Photo</label>
                                <input type="file" class="form-control bg-dark text-light border-secondary" 
                                       id="profile_photo" name="profile_photo" accept="image/*">
                                <small class="text-muted">Max 5MB. JPG, PNG, or GIF</small>
                            </div>
                        </div>
                        
                        <div class="mt-4">
                            <button type="submit" class="btn btn-danger">
                                <i class="bi bi-check-lg me-2"></i>Update Profile
                            </button>
                        </div>
                    </form>
                </div>
            </div>
            
            <!-- Change Password Form -->
            <div class="card bg-dark border-secondary">
                <div class="card-header bg-secondary">
                    <h5 class="mb-0">
                        <i class="bi bi-key me-2"></i>Change Password
                    </h5>
                </div>
                <div class="card-body">
                    <form method="POST" action="">
                        <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                        <input type="hidden" name="action" value="change_password">
                        
                        <div class="row g-3">
                            <div class="col-md-12">
                                <label for="current_password" class="form-label">Current Password</label>
                                <input type="password" class="form-control bg-dark text-light border-secondary" 
                                       id="current_password" name="current_password" required>
                            </div>
                            <div class="col-md-6">
                                <label for="new_password" class="form-label">New Password</label>
                                <input type="password" class="form-control bg-dark text-light border-secondary" 
                                       id="new_password" name="new_password" required>
                                <small class="text-muted">Min 8 characters with uppercase, lowercase, and number</small>
                            </div>
                            <div class="col-md-6">
                                <label for="confirm_password" class="form-label">Confirm New Password</label>
                                <input type="password" class="form-control bg-dark text-light border-secondary" 
                                       id="confirm_password" name="confirm_password" required>
                            </div>
                        </div>
                        
                        <div class="mt-4">
                            <button type="submit" class="btn btn-warning">
                                <i class="bi bi-key me-2"></i>Change Password
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include INCLUDES_PATH . 'footer.php'; ?>
