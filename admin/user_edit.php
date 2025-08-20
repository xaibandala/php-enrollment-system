<?php
$pageTitle = 'Add User';
require_once 'includes/header.php';

// Check if admin is logged in
if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit();
}

// Initialize user data
$user = [
    'id' => '',
    'name' => '',
    'email' => '',
    'role' => 'user',
    'status' => 'active'
];

$isEdit = false;
$error = '';

// Check if editing existing user
if (isset($_GET['id'])) {
    $user_id = (int)$_GET['id'];
    $existingUser = fetchOne("SELECT * FROM users WHERE id = ?", [$user_id]);
    
    if ($existingUser) {
        $user = $existingUser;
        $isEdit = true;
        $pageTitle = 'Edit User';
    } else {
        $_SESSION['flash'] = ['type' => 'danger', 'message' => 'User not found'];
        header('Location: users.php');
        exit();
    }
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Sanitize input
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $role = $_POST['role'] ?? 'user';
    $status = $_POST['status'] ?? 'active';
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    // Validate input
    if (empty($name) || empty($email)) {
        $error = 'Name and email are required';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address';
    } elseif (!$isEdit && empty($password)) {
        $error = 'Password is required for new users';
    } elseif (!empty($password) && $password !== $confirm_password) {
        $error = 'Passwords do not match';
    } else {
        // Check if email already exists (for new users or when email is changed)
        $checkEmail = fetchOne(
            "SELECT id FROM users WHERE email = ? AND id != ?", 
            [$email, $user['id']]
        );
        
        if ($checkEmail) {
            $error = 'This email is already registered';
        } else {
            try {
                if ($isEdit) {
                    // Update existing user
                    if (!empty($password)) {
                        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                        $result = executeQuery(
                            "UPDATE users SET name = ?, email = ?, role = ?, status = ?, password = ? WHERE id = ?",
                            [$name, $email, $role, $status, $hashedPassword, $user['id']]
                        );
                    } else {
                        $result = executeQuery(
                            "UPDATE users SET name = ?, email = ?, role = ?, status = ? WHERE id = ?",
                            [$name, $email, $role, $status, $user['id']]
                        );
                    }
                    
                    if ($result) {
                        $_SESSION['flash'] = ['type' => 'success', 'message' => 'User updated successfully'];
                        header('Location: users.php');
                        exit();
                    } else {
                        $error = 'Failed to update user';
                    }
                } else {
                    // Create new user
                    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                    $result = executeQuery(
                        "INSERT INTO users (name, email, password, role, status) VALUES (?, ?, ?, ?, ?)",
                        [$name, $email, $hashedPassword, $role, $status]
                    );
                    
                    if ($result) {
                        $_SESSION['flash'] = ['type' => 'success', 'message' => 'User created successfully'];
                        header('Location: users.php');
                        exit();
                    } else {
                        $error = 'Failed to create user';
                    }
                }
            } catch (PDOException $e) {
                $error = 'Database error: ' . $e->getMessage();
            }
        }
    }
    
    // Update user data with submitted values
    $user = [
        'id' => $user['id'],
        'name' => $name,
        'email' => $email,
        'role' => $role,
        'status' => $status
    ];
}
?>

<div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
    <div>
        <h2 class="h3 mb-1 d-flex align-items-center gap-2">
            <i class="bi bi-person-plus text-primary"></i>
            <?php echo $isEdit ? 'Edit User' : 'Add New User'; ?>
        </h2>
        <p class="text-muted small mb-0">Manage user account details and access.</p>
    </div>
    <a href="users.php" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left me-1"></i> Back to Users
    </a>
</div>

<nav aria-label="breadcrumb" class="mb-3">
    <ol class="breadcrumb mb-0 small">
        <li class="breadcrumb-item"><a href="index.php" class="text-decoration-none">Dashboard</a></li>
        <li class="breadcrumb-item"><a href="users.php" class="text-decoration-none">Users</a></li>
        <li class="breadcrumb-item active" aria-current="page"><?php echo $isEdit ? 'Edit' : 'Add'; ?></li>
    </ol>
</nav>

<?php if ($error): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="bi bi-exclamation-triangle me-2"></i>
        <?php echo htmlspecialchars($error); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<div class="row justify-content-center">
    <div class="col-md-8">
        <div class="card shadow-sm border-0">
            <div class="card-header bg-white border-0 pb-0">
                <h6 class="mb-2">User Details</h6>
                <p class="text-muted small mb-0">Fill in the required information below.</p>
            </div>
            <div class="card-body">
                <form method="POST" action="" novalidate>
                    <div class="mb-3">
                        <label for="name" class="form-label fw-semibold">Full Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="name" name="name"
                               placeholder="e.g., Jane Doe"
                               value="<?php echo htmlspecialchars($user['name']); ?>" required>
                    </div>

                    <div class="mb-3">
                        <label for="email" class="form-label fw-semibold">Email Address <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="bi bi-envelope"></i></span>
                            <input type="email" class="form-control" id="email" name="email"
                                   placeholder="name@example.com"
                                   value="<?php echo htmlspecialchars($user['email']); ?>" required>
                        </div>
                    </div>

                    <div class="row g-3 mb-1">
                        <div class="col-md-6">
                            <label for="role" class="form-label fw-semibold">Role <span class="text-danger">*</span></label>
                            <select class="form-select" id="role" name="role" required>
                                <option value="user" <?php echo $user['role'] === 'user' ? 'selected' : ''; ?>>User</option>
                                <option value="admin" <?php echo $user['role'] === 'admin' ? 'selected' : ''; ?>>Administrator</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label for="status" class="form-label fw-semibold">Status <span class="text-danger">*</span></label>
                            <select class="form-select" id="status" name="status" required>
                                <option value="active" <?php echo $user['status'] === 'active' ? 'selected' : ''; ?>>Active</option>
                                <option value="inactive" <?php echo $user['status'] === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                            </select>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="password" class="form-label fw-semibold">
                            <?php echo $isEdit ? 'New Password' : 'Password'; ?>
                            <span class="text-danger"><?php echo $isEdit ? '' : '*'; ?></span>
                            <?php if ($isEdit): ?><small class="text-muted">(Leave blank to keep current password)</small><?php endif; ?>
                        </label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="bi bi-lock"></i></span>
                            <input type="password" class="form-control" id="password" name="password" <?php echo $isEdit ? '' : 'required'; ?>>
                        </div>
                    </div>

                    <div class="mb-4" id="confirm-password-group" style="display: <?php echo $isEdit ? 'none' : 'block'; ?>">
                        <label for="confirm_password" class="form-label fw-semibold">Confirm Password <?php echo $isEdit ? '' : '<span class="text-danger">*</span>'; ?></label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="bi bi-lock-fill"></i></span>
                            <input type="password" class="form-control" id="confirm_password" name="confirm_password" <?php echo $isEdit ? '' : 'required'; ?>>
                        </div>
                    </div>

                    <div class="d-flex justify-content-between align-items-center pt-2 mt-2 border-top">
                        <a href="users.php" class="btn btn-light border">
                            <i class="bi bi-x-lg me-1"></i>
                            Cancel
                        </a>
                        <button type="submit" class="btn btn-primary">
                            <i class="bi <?php echo $isEdit ? 'bi-check-lg' : 'bi-plus-lg'; ?> me-1"></i>
                            <?php echo $isEdit ? 'Update User' : 'Add User'; ?>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
// Show/hide confirm password field based on password field
const passwordField = document.getElementById('password');
const confirmPasswordGroup = document.getElementById('confirm-password-group');

if (passwordField) {
    passwordField.addEventListener('input', function() {
        if (this.value.trim() !== '') {
            confirmPasswordGroup.style.display = 'block';
            document.getElementById('confirm_password').setAttribute('required', 'required');
        } else {
            confirmPasswordGroup.style.display = 'none';
            document.getElementById('confirm_password').removeAttribute('required');
        }
    });
}
</script>

<?php require_once 'includes/footer.php'; ?>
