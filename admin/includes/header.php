<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../../config/database.php';

// Check if admin is logged in
if (!isset($_SESSION['admin_id'])) {
    header('Location: /php-enrollment-system/admin/adminlogin.php');
    exit();
}

// Handle: Update admin profile (modal submit)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_admin_profile') {
    $adminId = (int)$_SESSION['admin_id'];
    $full_name = trim($_POST['full_name'] ?? '');
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';

    $errors = [];
    if ($full_name === '') $errors[] = 'Full name is required.';
    if ($username === '') $errors[] = 'Username is required.';
    if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'A valid email is required.';
    if ($password !== '') {
        if (strlen($password) < 8) $errors[] = 'Password must be at least 8 characters.';
        if ($password !== $confirm) $errors[] = 'Password confirmation does not match.';
    }

    // Uniqueness checks for username and email (exclude self)
    if (empty($errors)) {
        $exists = fetchOne('SELECT id FROM admins WHERE username = ? AND id <> ?', [$username, $adminId]);
        if ($exists) $errors[] = 'Username is already taken.';
        $exists = fetchOne('SELECT id FROM admins WHERE email = ? AND id <> ?', [$email, $adminId]);
        if ($exists) $errors[] = 'Email is already in use.';
    }

    if (empty($errors)) {
        if ($password !== '') {
            $hash = password_hash($password, PASSWORD_BCRYPT);
            $ok = executeQuery('UPDATE admins SET full_name = ?, username = ?, email = ?, password = ? WHERE id = ?', [$full_name, $username, $email, $hash, $adminId]);
        } else {
            $ok = executeQuery('UPDATE admins SET full_name = ?, username = ?, email = ? WHERE id = ?', [$full_name, $username, $email, $adminId]);
        }
        if ($ok) {
            $_SESSION['admin_username'] = $username; // keep dropdown label in sync
            $_SESSION['admin_profile_flash'] = ['type' => 'success', 'message' => 'Profile updated successfully.'];
        } else {
            $_SESSION['admin_profile_flash'] = ['type' => 'danger', 'message' => 'Failed to update profile.'];
        }
    } else {
        $_SESSION['admin_profile_flash'] = ['type' => 'danger', 'message' => implode(' ', $errors)];
    }

    // Redirect back to the same page to avoid resubmission and show flash
    $redirect = $_SERVER['REQUEST_URI'] ?? '/php-enrollment-system/admin/index.php';
    header('Location: ' . $redirect);
    exit();
}

// Fetch current admin for modal defaults
$currentAdmin = fetchOne('SELECT id, username, email, full_name FROM admins WHERE id = ?', [$_SESSION['admin_id']]);
$adminProfileFlash = $_SESSION['admin_profile_flash'] ?? null;
unset($_SESSION['admin_profile_flash']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel - <?php echo htmlspecialchars($pageTitle ?? 'Dashboard'); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css">
 </head>
 <body class="d-flex flex-column min-vh-100">
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark shadow-sm">
        <div class="container">
            <a class="navbar-brand fw-semibold" href="index.php">Admin Panel</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto gap-2">
                    <li class="nav-item">
                        <a class="nav-link" href="index.php"><i class="bi bi-speedometer2"></i> Dashboard</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="users.php"><i class="bi bi-people"></i> Users</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="courses.php"><i class="bi bi-book"></i> Courses</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="enrollments.php"><i class="bi bi-card-checklist"></i> Enrollments</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="instructors.php"><i class="bi bi-person-badge"></i> Instructor</a>
                    </li>
                </ul>
                <ul class="navbar-nav">
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button" data-bs-toggle="dropdown">
                            <i class="bi bi-person-circle"></i> <?php echo htmlspecialchars($_SESSION['admin_username'] ?? 'Admin'); ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-item" href="#" data-bs-toggle="modal" data-bs-target="#adminProfileModal"><i class="bi bi-person"></i> Profile</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item text-danger" href="adminlogout.php"><i class="bi bi-box-arrow-right"></i> Logout</a></li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>
    <!-- Admin Profile Modal -->
    <div class="modal fade" id="adminProfileModal" tabindex="-1" aria-labelledby="adminProfileModalLabel" aria-hidden="true">
      <div class="modal-dialog">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title" id="adminProfileModalLabel"><i class="bi bi-person-gear me-2"></i>Edit Profile</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <form method="POST" action="">
            <input type="hidden" name="action" value="update_admin_profile">
            <div class="modal-body">
              <?php if ($adminProfileFlash): ?>
                <div class="alert alert-<?php echo $adminProfileFlash['type'] === 'success' ? 'success' : 'danger'; ?>" role="alert">
                  <?php echo htmlspecialchars($adminProfileFlash['message']); ?>
                </div>
              <?php endif; ?>
              <div class="mb-3">
                <label class="form-label">Full Name</label>
                <input type="text" class="form-control" name="full_name" value="<?php echo htmlspecialchars($currentAdmin['full_name'] ?? ''); ?>" required>
              </div>
              <div class="mb-3">
                <label class="form-label">Username</label>
                <input type="text" class="form-control" name="username" value="<?php echo htmlspecialchars($currentAdmin['username'] ?? ''); ?>" required>
              </div>
              <div class="mb-3">
                <label class="form-label">Email</label>
                <input type="email" class="form-control" name="email" value="<?php echo htmlspecialchars($currentAdmin['email'] ?? ''); ?>" required>
              </div>
              <div class="mb-3">
                <label class="form-label">New Password <span class="text-muted small">(optional)</span></label>
                <input type="password" class="form-control" name="password" placeholder="Leave blank to keep current password">
              </div>
              <div class="mb-3">
                <label class="form-label">Confirm New Password</label>
                <input type="password" class="form-control" name="confirm_password" placeholder="Re-enter new password">
              </div>
            </div>
            <div class="modal-footer">
              <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
              <button type="submit" class="btn btn-primary"><i class="bi bi-save me-1"></i>Save Changes</button>
            </div>
          </form>
        </div>
      </div>
    </div>
     <main class="container mt-4 flex-grow-1">
