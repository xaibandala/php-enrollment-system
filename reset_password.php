<?php
require_once 'includes/session.php';
require_once 'config/database.php';
require_once 'includes/mailer.php';

// Redirect to dashboard if already logged in
if (isset($_SESSION['user_id'])) {
    header('Location: dashboard.php');
    exit();
}

$error = '';
$success = '';
$validToken = false;
$token = $_GET['token'] ?? '';
$user = null;

// Validate token
if (!empty($token)) {
    // Find token in database
    $tokenData = fetchOne(
        "SELECT prt.*, u.email, u.name 
         FROM password_reset_tokens prt 
         JOIN users u ON prt.user_id = u.id 
         WHERE prt.token = ? 
         AND prt.used = 0 
         AND prt.expires_at > NOW() 
         LIMIT 1", 
        [$token]
    );
    
    if ($tokenData) {
        $validToken = true;
        $user = [
            'id' => $tokenData['user_id'],
            'email' => $tokenData['email'],
            'name' => $tokenData['name']
        ];
    } else {
        $error = 'Invalid or expired password reset link. Please request a new one.';
    }
}

// Process password reset
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $validToken) {
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    // Validate inputs
    if (empty($password) || empty($confirm_password)) {
        $error = 'Please fill in all fields';
    } elseif (strlen($password) < 8) {
        $error = 'Password must be at least 8 characters long';
    } elseif ($password !== $confirm_password) {
        $error = 'Passwords do not match';
    } else {
        // Hash the new password
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        
        // Start transaction
        $pdo->beginTransaction();
        
        try {
            // Update user's password
            $result = executeQuery(
                "UPDATE users SET password = ? WHERE id = ?",
                [$hashedPassword, $user['id']]
            );
            
            if ($result) {
                // Mark token as used
                executeQuery(
                    "UPDATE password_reset_tokens SET used = 1 WHERE token = ?",
                    [$token]
                );
                
                // Send confirmation email
                sendPasswordChangedNotification($user['email'], $user['name']);
                
                // Commit transaction
                $pdo->commit();
                
                $success = 'Your password has been reset successfully. You can now log in with your new password.';
                $validToken = false; // Prevent form from being shown again
            } else {
                throw new Exception('Failed to update password');
            }
        } catch (Exception $e) {
            $pdo->rollBack();
            $error = 'An error occurred while resetting your password. Please try again.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password - Enrollment System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <style>
        body {
            height: 100vh;
            display: flex;
            align-items: center;
            /* Gradient + background image to mirror index hero */
            background-image: linear-gradient(135deg, rgba(79,70,229,0.9) 0%, rgba(124,58,237,0.75) 35%, rgba(236,72,153,0.6) 70%, rgba(6,182,212,0.6) 100%), url('image/heroimg.jpg');
            background-size: cover;
            background-position: right center;
            background-repeat: no-repeat;
        }
        .reset-password-container {
            max-width: 500px;
            width: 100%;
            margin: 0 auto;
            padding: 30px;
            background: #ffffff;
            border-radius: 10px;
            /* Subtle gradient border */
            border: 1px solid transparent;
            background-image: linear-gradient(#ffffff, #ffffff), linear-gradient(135deg, #06B6D4, #3B82F6, #8B5CF6, #EC4899);
            background-origin: border-box;
            background-clip: padding-box, border-box;
            box-shadow: 0 12px 30px -10px rgba(0, 0, 0, 0.25);
        }
        .form-floating {
            margin-bottom: 1rem;
        }
        .password-strength {
            height: 4px;
            background-color: #e9ecef;
            margin-top: 5px;
            border-radius: 2px;
            overflow: hidden;
        }
        .password-strength-bar {
            height: 100%;
            width: 0;
            transition: width 0.3s ease;
        }
        .btn-gradient-hover {
            position: relative;
            overflow: hidden;
            transition: all 0.3s ease;
            border: none;
        }
        .btn-gradient-hover::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(135deg, #06B6D4 0%, #3B82F6 33%, #8B5CF6 66%, #EC4899 100%);
            transition: left 0.5s ease;
            z-index: -1;
        }
        .btn-gradient-hover:hover::before {
            left: 0;
        }
        .btn-gradient-hover:hover {
            color: white !important;
            transform: translateY(-2px);
            box-shadow: 0 10px 25px -5px rgba(79, 70, 229, 0.4);
        }
        .gradient-text {
            background: linear-gradient(90deg, #C084FC, #60A5FA, #34D399);
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="reset-password-container">
            <div class="text-center mb-4">
                <h2 class="gradient-text">Reset Your Password</h2>
                <?php if ($validToken): ?>
                    <p class="text-muted">Create a new password for <?php echo htmlspecialchars($user['email']); ?></p>
                <?php endif; ?>
            </div>
            
            <?php if ($error): ?>
                <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="alert alert-success">
                    <?php echo $success; ?>
                    <div class="mt-3">
                        <a href="login.php" class="btn btn-primary">
                            <i class="bi bi-box-arrow-in-right me-1"></i> Go to Login
                        </a>
                    </div>
                </div>
            <?php elseif ($validToken): ?>
                <form method="POST" action="reset_password.php?token=<?php echo htmlspecialchars($token); ?>" id="resetForm">
                    <div class="form-floating mb-3">
                        <input type="password" class="form-control" id="password" name="password" 
                               placeholder="New Password" required>
                        <label for="password">New Password</label>
                        <div class="password-strength">
                            <div class="password-strength-bar" id="passwordStrength"></div>
                        </div>
                        <small class="text-muted">Must be at least 8 characters long</small>
                    </div>
                    
                    <div class="form-floating mb-3">
                        <input type="password" class="form-control" id="confirm_password" 
                               name="confirm_password" placeholder="Confirm New Password" required>
                        <label for="confirm_password">Confirm New Password</label>
                        <div class="invalid-feedback" id="passwordMatchFeedback">
                            Passwords do not match
                        </div>
                    </div>
                    
                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-primary btn-gradient-hover">
                            <i class="bi bi-key me-1"></i> Reset Password
                        </button>
                    </div>
                </form>
                
                <div class="text-center mt-3">
                    <a href="login.php" class="text-decoration-none">
                        <i class="bi bi-arrow-left me-1"></i> Back to Login
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const passwordInput = document.getElementById('password');
        const confirmPasswordInput = document.getElementById('confirm_password');
        const passwordStrength = document.getElementById('passwordStrength');
        const form = document.getElementById('resetForm');
        
        if (passwordInput) {
            // Password strength checker
            passwordInput.addEventListener('input', function() {
                const password = this.value;
                let strength = 0;
                
                // Check length
                if (password.length > 0) strength++;
                if (password.length >= 8) strength++;
                
                // Check for uppercase
                if (/[A-Z]/.test(password)) strength++;
                
                // Check for number
                if (/[0-9]/.test(password)) strength++;
                
                // Check for special character
                if (/[^A-Za-z0-9]/.test(password)) strength++;
                
                // Update strength bar
                const width = (strength / 5) * 100;
                passwordStrength.style.width = width + '%';
                
                // Update color based on strength
                if (strength <= 1) {
                    passwordStrength.style.backgroundColor = '#dc3545'; // Red
                } else if (strength <= 2) {
                    passwordStrength.style.backgroundColor = '#fd7e14'; // Orange
                } else if (strength <= 3) {
                    passwordStrength.style.backgroundColor = '#ffc107'; // Yellow
                } else if (strength <= 4) {
                    passwordStrength.style.backgroundColor = '#198754'; // Green
                } else {
                    passwordStrength.style.backgroundColor = '#0d6efd'; // Blue
                }
            });
            
            // Password match checker
            function checkPasswordsMatch() {
                const password = passwordInput.value;
                const confirmPassword = confirmPasswordInput.value;
                
                if (password === '' || confirmPassword === '') {
                    confirmPasswordInput.classList.remove('is-invalid');
                    return false;
                }
                
                if (password !== confirmPassword) {
                    confirmPasswordInput.classList.add('is-invalid');
                    return false;
                } else {
                    confirmPasswordInput.classList.remove('is-invalid');
                    return true;
                }
            }
            
            if (confirmPasswordInput) {
                confirmPasswordInput.addEventListener('input', checkPasswordsMatch);
            }
            
            // Form validation
            if (form) {
                form.addEventListener('submit', function(e) {
                    if (!checkPasswordsMatch()) {
                        e.preventDefault();
                        return false;
                    }
                    
                    // Additional validation can be added here
                    
                    return true;
                });
            }
        }
    });
    </script>
</body>
</html>
