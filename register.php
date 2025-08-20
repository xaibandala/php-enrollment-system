<?php
require_once 'includes/session.php';
require_once 'config/database.php';

// Redirect to dashboard if already logged in
if (isset($_SESSION['user_id'])) {
    header('Location: dashboard.php');
    exit();
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $date_of_birth = $_POST['date_of_birth'] ?? '';
    $address = trim($_POST['address'] ?? '');
    $city = trim($_POST['city'] ?? '');
    $state = trim($_POST['state'] ?? '');
    $postal_code = trim($_POST['postal_code'] ?? '');
    $country = trim($_POST['country'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    // Validate inputs
    if (empty($name) || empty($email) || empty($date_of_birth) || empty($address) || 
        empty($city) || empty($state) || empty($postal_code) || empty($country) || 
        empty($password) || empty($confirm_password)) {
        $error = 'All fields are required';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address';
    } elseif (strtotime($date_of_birth) > strtotime('-13 years')) {
        $error = 'You must be at least 13 years old to register';
    } elseif (strlen($password) < 8) {
        $error = 'Password must be at least 8 characters long';
    } elseif ($password !== $confirm_password) {
        $error = 'Passwords do not match';
    } else {
        // Check if email already exists
        $existingUser = fetchOne("SELECT id FROM users WHERE email = ?", [$email]);
        
        if ($existingUser) {
            $error = 'An account with this email already exists';
        } else {
            // Hash password
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            
            // Insert new user with all fields
            try {
                $stmt = $pdo->prepare(
                    "INSERT INTO users (name, email, password, date_of_birth, address, city, state, postal_code, country, role) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'student')"
                );
                
                $result = $stmt->execute([
                    $name, 
                    $email, 
                    $hashedPassword, 
                    $date_of_birth, 
                    $address, 
                    $city, 
                    $state, 
                    $postal_code, 
                    $country
                ]);
                
                if ($result) {
                    // Redirect to login with success message
                    header('Location: login.php?registered=1');
                    exit();
                } else {
                    $error = 'Failed to create account. Error: ' . implode(', ', $stmt->errorInfo());
                }
            } catch (PDOException $e) {
                $error = 'Database error: ' . $e->getMessage();
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
    <title>Register - Enrollment System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <style>
        body {
            min-height: 100vh;
            display: flex;
            align-items: center;
            padding: 20px 0;
            /* Gradient + background image to mirror index hero */
            background-image: linear-gradient(135deg, rgba(79,70,229,0.9) 0%, rgba(124,58,237,0.75) 35%, rgba(236,72,153,0.6) 70%, rgba(6,182,212,0.6) 100%), url('image/heroimg.jpg');
            background-size: cover;
            background-position: right center;
            background-repeat: no-repeat;
        }
        .register-container {
            max-width: 1000px;
            width: 100%;
            margin: 0 auto;
            padding: 30px;
            background: #ffffff;
            border-radius: 12px;
            /* Subtle gradient border */
            border: 1px solid transparent;
            background-image: linear-gradient(#ffffff, #ffffff), linear-gradient(135deg, #06B6D4, #3B82F6, #8B5CF6, #EC4899);
            background-origin: border-box;
            background-clip: padding-box, border-box;
            box-shadow: 0 12px 30px -10px rgba(0, 0, 0, 0.25);
        }
        .form-section {
            padding: 15px;
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
        .password-requirements {
            font-size: 0.8rem;
            margin-top: 0.25rem;
            color: #6c757d;
        }
        .requirement {
            margin-bottom: 0.25rem;
        }
        .requirement.valid {
            color: #198754;
        }
        .requirement.valid::before {
            content: "✓ ";
        }
        .btn-gradient-hover {
            position: relative;
            overflow: hidden;
            transition: all 0.3s ease;
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
        /* Gradient text utility for heading */
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
        <div class="register-container">
            <div class="row">
                <div class="col-lg-6">
                    <div class="text-center mb-4">
                        <h2 class="gradient-text">Create an Account</h2>
                        <p class="text-muted">Join our learning community</p>
                    </div>
                    
                    <?php if ($error): ?>
                        <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
                    <?php endif; ?>
                </div>
            </div>
            
            <form method="POST" action="register.php" id="registrationForm">
                <div class="row">
                    <div class="col-lg-6">
                        <div class="form-section">
                            <div class="form-floating mb-3">
                                <input type="text" class="form-control" id="name" name="name" 
                                       placeholder="John Doe" value="<?php echo htmlspecialchars($_POST['name'] ?? ''); ?>" required>
                                <label for="name">Full Name</label>
                            </div>
                            
                            <div class="form-floating mb-3">
                                <input type="email" class="form-control" id="email" name="email" 
                                       placeholder="name@example.com" value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" required>
                                <label for="email">Email address</label>
                            </div>

                            <div class="form-floating mb-3">
                                <input type="date" class="form-control" id="date_of_birth" name="date_of_birth" 
                                       value="<?php echo htmlspecialchars($_POST['date_of_birth'] ?? ''); ?>" required
                                       max="<?php echo date('Y-m-d', strtotime('-13 years')); ?>">
                                <label for="date_of_birth">Date of Birth</label>
                            </div>

                            <div class="form-floating mb-3">
                                <input type="text" class="form-control" id="address" name="address" 
                                       placeholder="123 Main St" value="<?php echo htmlspecialchars($_POST['address'] ?? ''); ?>" required>
                                <label for="address">Street Address</label>
                            </div>

                            <div class="row g-3 mb-3">
                                <div class="col-md-6">
                                    <div class="form-floating">
                                        <input type="text" class="form-control" id="city" name="city" 
                                               placeholder="City" value="<?php echo htmlspecialchars($_POST['city'] ?? ''); ?>" required>
                                        <label for="city">City</label>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-floating">
                                        <input type="text" class="form-control" id="state" name="state" 
                                               placeholder="State/Province" value="<?php echo htmlspecialchars($_POST['state'] ?? ''); ?>" required>
                                        <label for="state">State/Province</label>
                                    </div>
                                </div>
                            </div>

                            <div class="row g-3 mb-3">
                                <div class="col-md-6">
                                    <div class="form-floating">
                                        <input type="text" class="form-control" id="postal_code" name="postal_code" 
                                               placeholder="Postal Code" value="<?php echo htmlspecialchars($_POST['postal_code'] ?? ''); ?>" required>
                                        <label for="postal_code">Postal Code</label>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-floating">
                                        <input type="text" class="form-control" id="country" name="country" 
                                               placeholder="Country" value="<?php echo htmlspecialchars($_POST['country'] ?? ''); ?>" required>
                                        <label for="country">Country</label>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-6">
                        <div class="form-section d-flex flex-column" style="height: 100%;">
                
                            <div class="form-floating mb-3">
                                <input type="password" class="form-control" id="password" name="password" 
                                       placeholder="Password" required>
                                <label for="password">Password</label>
                                <div class="password-strength">
                                    <div class="password-strength-bar" id="passwordStrength"></div>
                                </div>
                                <div class="password-requirements">
                                    <div class="requirement" id="length">At least 8 characters</div>
                                    <div class="requirement" id="uppercase">At least one uppercase letter</div>
                                    <div class="requirement" id="number">At least one number</div>
                                </div>
                            </div>
                            
                            <div class="form-floating mb-3">
                                <input type="password" class="form-control" id="confirm_password" 
                                       name="confirm_password" placeholder="Confirm Password" required>
                                <label for="confirm_password">Confirm Password</label>
                                <div class="invalid-feedback" id="passwordMatchFeedback">
                                    Passwords do not match
                                </div>
                            </div>
                            
                            <div class="mt-auto">
                                <div class="d-grid gap-2">
                                    <button type="submit" class="btn btn-primary btn-lg btn-gradient-hover" id="submitBtn">
                                        Register
                                    </button>
                                </div>
                                
                                <div class="text-center mt-3">
                                    <p class="text-muted mb-1">
                                        Already have an account? <a href="login.php" class="text-decoration-none">Sign in</a>
                                    </p>
                                    <p class="text-muted small mb-0">
                                        By creating an account, you agree to our <a href="#" class="text-decoration-none">Terms of Service</a> 
                                        and <a href="#" class="text-decoration-none">Privacy Policy</a>.
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const passwordInput = document.getElementById('password');
        const confirmPasswordInput = document.getElementById('confirm_password');
        const passwordStrength = document.getElementById('passwordStrength');
        const submitBtn = document.getElementById('submitBtn');
        
        // Password strength checker
        passwordInput.addEventListener('input', function() {
            const password = this.value;
            let strength = 0;
            let color = '';
            
            // Check length
            const hasMinLength = password.length >= 8;
            document.getElementById('length').classList.toggle('valid', hasMinLength);
            
            // Check uppercase
            const hasUppercase = /[A-Z]/.test(password);
            document.getElementById('uppercase').classList.toggle('valid', hasUppercase);
            
            // Check number
            const hasNumber = /[0-9]/.test(password);
            document.getElementById('number').classList.toggle('valid', hasNumber);
            
            // Calculate strength
            if (password.length > 0) strength++;
            if (password.length >= 8) strength++;
            if (hasUppercase) strength++;
            if (hasNumber) strength++;
            
            // Update strength bar
            const width = (strength / 4) * 100;
            passwordStrength.style.width = width + '%';
            
            // Update color based on strength
            if (strength <= 1) {
                color = '#dc3545'; // Red
            } else if (strength <= 2) {
                color = '#ffc107'; // Yellow
            } else if (strength <= 3) {
                color = '#198754'; // Green
            } else {
                color = '#0d6efd'; // Blue
            }
            
            passwordStrength.style.backgroundColor = color;
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
        
        confirmPasswordInput.addEventListener('input', checkPasswordsMatch);
        
        // Form validation
        document.getElementById('registrationForm').addEventListener('submit', function(e) {
            if (!checkPasswordsMatch()) {
                e.preventDefault();
                return false;
            }
            
            // Additional validation can be added here
            
            // Disable button to prevent double submission
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Creating Account...';
            
            return true;
        });
    });
    </script>
</body>
</html>