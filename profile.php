<?php
require_once 'includes/session.php';
require_once 'config/database.php';
// Use user auth guard instead of admin-only guard
requireUser();

$error = '';
$success = '';

// Get current user data
$user = fetchOne("SELECT * FROM users WHERE id = ?", [$_COOKIE['user_id']]);
// Compute user initials (used for potential avatar/modern header display)
$initials = '';
$nameParts = preg_split('/\s+/', trim($user['name'] ?? ''));
if (is_array($nameParts)) {
    foreach ($nameParts as $part) {
        if ($part !== '') { $initials .= strtoupper(substr($part, 0, 1)); }
    }
}
$initials = substr($initials, 0, 2);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $date_of_birth = $_POST['date_of_birth'] ?? '';
    $address = trim($_POST['address'] ?? '');
    $city = trim($_POST['city'] ?? '');
    $state = trim($_POST['state'] ?? '');
    $postal_code = trim($_POST['postal_code'] ?? '');
    $country = trim($_POST['country'] ?? '');
    $current_password = $_POST['current_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    // Validate inputs
    if (empty($name) || empty($email) || empty($date_of_birth) || empty($address) || 
        empty($city) || empty($state) || empty($postal_code) || empty($country)) {
        $error = 'All fields are required';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address';
    } elseif ($email !== $user['email'] && fetchOne("SELECT id FROM users WHERE email = ? AND id != ?", [$email, $user['id']])) {
        $error = 'Email is already in use by another account';
    } elseif (strtotime($date_of_birth) > strtotime('-13 years')) {
        $error = 'You must be at least 13 years old';
    } else {
        // Check if password is being changed
        $password_changed = false;
        $update_fields = [];
        $params = [];
        
        if (!empty($current_password) || !empty($new_password) || !empty($confirm_password)) {
            // Verify current password
            if (!password_verify($current_password, $user['password'])) {
                $error = 'Current password is incorrect';
            } elseif (strlen($new_password) < 8) {
                $error = 'New password must be at least 8 characters long';
            } elseif ($new_password !== $confirm_password) {
                $error = 'New password and confirmation do not match';
            } else {
                $password_changed = true;
                $update_fields[] = 'password = ?';
                $params[] = password_hash($new_password, PASSWORD_DEFAULT);
            }
        }
        
        if (empty($error)) {
            // Prepare update query
            $update_fields = [
                'name = ?',
                'email = ?',
                'date_of_birth = ?',
                'address = ?',
                'city = ?',
                'state = ?',
                'postal_code = ?',
                'country = ?'
            ];
            
            $params = [
                $name,
                $email,
                $date_of_birth,
                $address,
                $city,
                $state,
                $postal_code,
                $country
            ];
            $params[] = $user['id']; // For WHERE clause
            
            $query = "UPDATE users SET " . implode(', ', $update_fields) . " WHERE id = ?";
            
            if (executeQuery($query, $params)) {
                // Update cookies
                setcookie('user_name', $name, time() + (86400 * 30), '/'); // 30 days
                setcookie('user_email', $email, time() + (86400 * 30), '/');
                
                $success = 'Profile updated successfully';
                // Also set flash for post-redirect message on dashboard
                if (function_exists('setFlash')) {
                    setFlash('success', 'Profile updated successfully');
                }
                
                // Refresh user data
                $user = fetchOne("SELECT * FROM users WHERE id = ?", [$_COOKIE['user_id']]);
            } else {
                $error = 'Failed to update profile. Please try again.';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile - Enrollment System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <style>
      body { background: #f6f8fb; }
      .card-modern { border: 0; border-radius: 1rem; box-shadow: 0 10px 30px rgba(18, 38, 63, 0.06); }
      .card-header-gradient {
        background: linear-gradient(135deg, #4f46e5 0%, #0ea5e9 100%);
        color: #fff;
        border-top-left-radius: 1rem !important;
        border-top-right-radius: 1rem !important;
      }
      .section-title { font-weight: 600; }
      .list-icon { width: 38px; height: 38px; display: inline-flex; align-items: center; justify-content: center; border-radius: 0.75rem; background: #eef2ff; color: #4f46e5; }
      .btn-modern { border-radius: .75rem; box-shadow: 0 6px 16px rgba(79,70,229,.2); }
      .btn-outline-primary { border-radius: .75rem; }
      .form-control { border-radius: .75rem; }
      .form-control:focus { box-shadow: 0 0 0 .2rem rgba(79,70,229,.15); border-color: #4f46e5; }
      .avatar-initials { width: 56px; height: 56px; border-radius: 50%; background: #eef2ff; color: #4f46e5; display: inline-flex; align-items: center; justify-content: center; font-weight: 700; }
      .info-card { border-radius: .75rem; }
    </style>
</head>
<body>
    <?php include 'includes/navbar.php'; ?>
    
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-12 col-xl-10 col-xxl-8">
                <div class="card card-modern">
                    <div class="card-header card-header-gradient">
                        <div class="d-flex align-items-center gap-3">
                            <div class="avatar-initials" aria-hidden="true"><?php echo htmlspecialchars($initials ?: 'U'); ?></div>
                            <div>
                                <h5 class="mb-0">My Profile</h5>
                                <small class="opacity-75">Manage your personal information and password</small>
                            </div>
                        </div>
                    </div>
                    <div class="card-body p-4 p-md-5">
                        <?php if ($error): ?>
                            <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
                        <?php endif; ?>
                        
                        <?php $isEditMode = isset($_GET['edit']) && $_GET['edit'] === 'true'; ?>
                        
                        <?php if (!$isEditMode): ?>
                            <!-- View Mode -->
                            <div class="mb-4">
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <h5 class="section-title mb-0">Personal Information</h5>
                                    <a href="?edit=true" class="btn btn-outline-primary btn-modern">
                                        <i class="bi bi-pencil-square me-1"></i> Edit Profile
                                    </a>
                                </div>
                                
                                <div class="card info-card border-0 shadow-sm">
                                    <div class="card-body p-4">
                                        <div class="row g-4 align-items-start">
                                            <div class="col-sm-4 col-md-3 text-muted">Full Name</div>
                                            <div class="col-sm-8 col-md-9 fw-medium"><?php echo htmlspecialchars($user['name']); ?></div>

                                            <div class="col-sm-4 col-md-3 text-muted">Email</div>
                                            <div class="col-sm-8 col-md-9"><span class="badge text-bg-light border"><i class="bi bi-envelope me-1"></i><?php echo htmlspecialchars($user['email']); ?></span></div>

                                            <div class="col-sm-4 col-md-3 text-muted">Date of Birth</div>
                                            <div class="col-sm-8 col-md-9"><?php echo date('F j, Y', strtotime($user['date_of_birth'])); ?></div>

                                            <div class="col-sm-4 col-md-3 text-muted">Address</div>
                                            <div class="col-sm-8 col-md-9">
                                                <?php echo htmlspecialchars($user['address']); ?><br>
                                                <?php echo htmlspecialchars($user['city']) . ', ' . htmlspecialchars($user['state']); ?><br>
                                                <?php echo htmlspecialchars($user['postal_code']) . ', ' . htmlspecialchars($user['country']); ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                        <?php else: ?>
                            <!-- Edit Mode -->
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h5>Edit Profile</h5>
                                <a href="?" class="btn btn-outline-secondary">
                                    <i class="bi bi-x-lg me-1"></i> Cancel
                                </a>
                            </div>
                            
                            <form method="POST" action="profile.php">
                                <div class="mb-3">
                                    <label for="name" class="form-label">Full Name</label>
                                    <input type="text" class="form-control" id="name" name="name" 
                                           value="<?php echo htmlspecialchars($user['name']); ?>" required>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="email" class="form-label">Email address</label>
                                    <input type="email" class="form-control" id="email" name="email" 
                                           value="<?php echo htmlspecialchars($user['email']); ?>" required>
                                </div>
                                
                                <div class="row g-3 mb-3">
                                    <div class="col-md-6">
                                        <label for="date_of_birth" class="form-label">Date of Birth</label>
                                        <input type="date" class="form-control" id="date_of_birth" name="date_of_birth" 
                                               value="<?php echo htmlspecialchars($user['date_of_birth']); ?>" required
                                               max="<?php echo date('Y-m-d', strtotime('-13 years')); ?>">
                                    </div>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="address" class="form-label">Street Address</label>
                                    <input type="text" class="form-control" id="address" name="address" 
                                           value="<?php echo htmlspecialchars($user['address']); ?>" required>
                                </div>
                                
                                <div class="row g-3 mb-3">
                                    <div class="col-md-6">
                                        <label for="city" class="form-label">City</label>
                                        <input type="text" class="form-control" id="city" name="city" 
                                               value="<?php echo htmlspecialchars($user['city']); ?>" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="state" class="form-label">State/Province</label>
                                        <input type="text" class="form-control" id="state" name="state" 
                                               value="<?php echo htmlspecialchars($user['state']); ?>" required>
                                    </div>
                                </div>
                                
                                <div class="row g-3 mb-3">
                                    <div class="col-md-6">
                                        <label for="postal_code" class="form-label">Postal Code</label>
                                        <input type="text" class="form-control" id="postal_code" name="postal_code" 
                                               value="<?php echo htmlspecialchars($user['postal_code']); ?>" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="country" class="form-label">Country</label>
                                        <input type="text" class="form-control" id="country" name="country" 
                                               value="<?php echo htmlspecialchars($user['country']); ?>" required>
                                    </div>
                                </div>
                        <?php endif; ?>
                            
                            <div class="card mb-4 border-0 shadow-sm info-card">
                                <div class="card-header bg-light border-0">
                                    <h6 class="mb-0">Change Password</h6>
                                    <small class="text-muted">Leave blank to keep current password</small>
                                </div>
                                <div class="card-body">
                                    <div class="mb-3">
                                        <label for="current_password" class="form-label">Current Password</label>
                                        <input type="password" class="form-control" id="current_password" name="current_password">
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="new_password" class="form-label">New Password</label>
                                        <input type="password" class="form-control" id="new_password" name="new_password">
                                        <div class="form-text">At least 8 characters long</div>
                                    </div>
                                    
                                    <div class="mb-0">
                                        <label for="confirm_password" class="form-label">Confirm New Password</label>
                                        <input type="password" class="form-control" id="confirm_password" name="confirm_password">
                                    </div>
                                </div>
                            </div>
                            
                            <?php if ($isEditMode): ?>
                                <div class="d-flex justify-content-between mt-4">
                                    <a href="?" class="btn btn-outline-secondary">
                                        <i class="bi bi-x-lg me-1"></i> Cancel
                                    </a>
                                    <button type="submit" class="btn btn-primary btn-modern">
                                        <i class="bi bi-save me-1"></i> Save Changes
                                    </button>
                                </div>
                            <?php else: ?>
                                <div class="mt-4">
                                    <a href="dashboard.php" class="btn btn-outline-secondary">
                                        <i class="bi bi-arrow-left me-1"></i> Back to Dashboard
                                    </a>
                                </div>
                            <?php endif; ?>
                            
                            <?php if ($isEditMode): ?>
                                </form>
                            <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Loading Overlay -->
    <div id="loadingOverlay" style="position: fixed; inset: 0; background: rgba(255,255,255,0.85); display: none; z-index: 1050;">
        <div class="d-flex flex-column justify-content-center align-items-center h-100">
            <div class="spinner-border text-primary mb-3" role="status" aria-hidden="true" style="width: 3rem; height: 3rem;"></div>
            <div class="text-primary fw-semibold">Processing your changes...</div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
      // Show loading overlay helper
      function showLoadingOverlay() {
        var overlay = document.getElementById('loadingOverlay');
        if (overlay) overlay.style.display = 'block';
      }

      document.addEventListener('DOMContentLoaded', function () {
        // Show overlay when form is submitted (Edit Mode only)
        var form = document.querySelector('form[method="POST"]');
        if (form) {
          form.addEventListener('submit', function () {
            showLoadingOverlay();
          });
        }

        // If update succeeded server-side, show overlay with success text and redirect after 2s
        var hasSuccess = <?php echo json_encode(!empty($success)); ?>;
        if (hasSuccess) {
          showLoadingOverlay();
          var msgEl = document.querySelector('#loadingOverlay .fw-semibold');
          if (msgEl) {
            msgEl.textContent = 'Saved! Redirecting...';
          }
          setTimeout(function () {
            window.location.href = 'dashboard.php';
          }, 2000);
        }
      });
    </script>
</body>
</html>
