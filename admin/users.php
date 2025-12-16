<?php
/**
 * ============================================
 * CineMaster - Admin Users Management
 * ============================================
 * Allows admin to view, edit, and delete users
 */

require_once '../config/config.php';

requireAdmin();

$pageTitle = 'Manage Users';
$errors = [];
$success = '';

// Handle actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Invalid request.';
    } else {
        $action = $_POST['action'] ?? '';
        
        if ($action === 'delete' && isset($_POST['user_id'])) {
            $userId = (int)$_POST['user_id'];
            
            // Prevent deleting self
            if ($userId === $_SESSION['user_id']) {
                $errors[] = 'You cannot delete your own account.';
            } else {
                if (deleteUser($userId)) {
                    $success = 'User deleted successfully.';
                    logAction($_SESSION['user_id'], "Deleted user ID: {$userId}", 'admin');
                } else {
                    $errors[] = 'Failed to delete user.';
                }
            }
        }
        
        if ($action === 'update_role' && isset($_POST['user_id'], $_POST['role'])) {
            $userId = (int)$_POST['user_id'];
            $newRole = $_POST['role'];
            
            if ($userId === $_SESSION['user_id']) {
                $errors[] = 'You cannot change your own role.';
            } elseif (!in_array($newRole, ['admin', 'user'])) {
                $errors[] = 'Invalid role.';
            } else {
                $pdo = getDBConnection();
                $stmt = $pdo->prepare("UPDATE users SET role = ? WHERE id = ?");
                if ($stmt->execute([$newRole, $userId])) {
                    $success = 'User role updated successfully.';
                    logAction($_SESSION['user_id'], "Changed role of user ID {$userId} to {$newRole}", 'admin');
                } else {
                    $errors[] = 'Failed to update role.';
                }
            }
        }
    }
}

// Get all users
$users = getAllUsers();
$csrfToken = generateCSRFToken();

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
                    <a href="<?php echo SITE_URL; ?>/admin/users.php" class="list-group-item list-group-item-action bg-dark text-light border-secondary active">
                        <i class="bi bi-people me-2"></i>Users
                    </a>
                    <a href="<?php echo SITE_URL; ?>/admin/movies.php" class="list-group-item list-group-item-action bg-dark text-light border-secondary">
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
                    <i class="bi bi-people text-danger me-2"></i>User Management
                </h2>
                <span class="badge bg-secondary fs-6"><?php echo count($users); ?> users</span>
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
            
            <!-- Users Table -->
            <div class="card bg-dark border-secondary">
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-dark table-hover mb-0">
                            <thead class="table-secondary">
                                <tr>
                                    <th>ID</th>
                                    <th>User</th>
                                    <th>Email</th>
                                    <th>Role</th>
                                    <th>Status</th>
                                    <th>Joined</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($users as $user): ?>
                                    <tr>
                                        <td><?php echo $user['id']; ?></td>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <img src="<?php echo ASSETS_URL; ?>/images/default-avatar.png" 
                                                     class="rounded-circle me-2" width="35" height="35">
                                                <?php echo htmlspecialchars($user['name']); ?>
                                                <?php if ($user['id'] === $_SESSION['user_id']): ?>
                                                    <span class="badge bg-info ms-2">You</span>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                        <td><?php echo htmlspecialchars($user['email']); ?></td>
                                        <td>
                                            <?php if ($user['id'] !== $_SESSION['user_id']): ?>
                                                <form method="POST" class="d-inline">
                                                    <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                                                    <input type="hidden" name="action" value="update_role">
                                                    <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                                    <select name="role" class="form-select form-select-sm bg-dark text-light border-secondary" 
                                                            onchange="this.form.submit()" style="width: 100px;">
                                                        <option value="user" <?php echo $user['role'] === 'user' ? 'selected' : ''; ?>>User</option>
                                                        <option value="admin" <?php echo $user['role'] === 'admin' ? 'selected' : ''; ?>>Admin</option>
                                                    </select>
                                                </form>
                                            <?php else: ?>
                                                <span class="badge bg-danger"><?php echo ucfirst($user['role']); ?></span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if ($user['is_verified']): ?>
                                                <span class="badge bg-success">Verified</span>
                                            <?php else: ?>
                                                <span class="badge bg-warning text-dark">Pending</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo formatDate($user['created_at']); ?></td>
                                        <td>
                                            <?php if ($user['id'] !== $_SESSION['user_id']): ?>
                                                <form method="POST" class="d-inline" 
                                                      onsubmit="return confirm('Are you sure you want to delete this user?');">
                                                    <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                                                    <input type="hidden" name="action" value="delete">
                                                    <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                                    <button type="submit" class="btn btn-sm btn-outline-danger">
                                                        <i class="bi bi-trash"></i>
                                                    </button>
                                                </form>
                                            <?php else: ?>
                                                <span class="text-muted">-</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            
        </div>
    </div>
</div>

<?php include INCLUDES_PATH . 'footer.php'; ?>
