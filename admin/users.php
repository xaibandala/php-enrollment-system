<?php
$pageTitle = 'Manage Users';
require_once 'includes/header.php';

// Auth is handled in includes/header.php

// Handle user deletion
if (isset($_GET['delete'])) {
    $user_id = (int)$_GET['delete'];
    
    // Prevent deleting own account
    if ($user_id !== $_SESSION['admin_id']) {
        $result = executeQuery("DELETE FROM users WHERE id = ?", [$user_id]);
        if ($result) {
            $_SESSION['flash'] = ['type' => 'success', 'message' => 'User deleted successfully'];
        } else {
            $_SESSION['flash'] = ['type' => 'danger', 'message' => 'Failed to delete user'];
        }
    } else {
        $_SESSION['flash'] = ['type' => 'danger', 'message' => 'You cannot delete your own account'];
    }
    
    header('Location: users.php');
    exit();
}

// (Removed) Status update handling: the `users` table has no `status` column.

// Get all users from the real `users` table
$users = fetchAll("SELECT id, name, email, role, registration_date, date_of_birth, address, city, state, postal_code, country FROM users ORDER BY registration_date DESC");

// Get flash message if exists
if (isset($_SESSION['flash'])) {
    $flash = $_SESSION['flash'];
    unset($_SESSION['flash']);
}
?>

<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <div>
        <h2 class="h3 mb-1 d-flex align-items-center gap-2">
            <i class="bi bi-people text-primary"></i>
            Manage Users
        </h2>
        <p class="text-muted small mb-0">Create, edit, and manage user accounts.</p>
    </div>
</div>

<nav aria-label="breadcrumb" class="mb-3">
    <ol class="breadcrumb mb-0 small">
        <li class="breadcrumb-item"><a href="index.php" class="text-decoration-none">Dashboard</a></li>
        <li class="breadcrumb-item active" aria-current="page">Users</li>
    </ol>
    
</nav>

<?php if (isset($flash)): ?>
    <div class="alert alert-<?php echo $flash['type'] === 'success' ? 'success' : 'danger'; ?> alert-dismissible fade show" role="alert">
        <i class="bi <?php echo $flash['type'] === 'success' ? 'bi-check-circle' : 'bi-exclamation-triangle'; ?> me-2"></i>
        <?php echo htmlspecialchars($flash['message']); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<div class="card shadow-sm border-0">
    <div class="card-body p-0">
        <?php if (empty($users)): ?>
            <div class="text-center py-5">
                <div class="text-muted">
                    <i class="bi bi-people" style="font-size: 3rem;"></i>
                    <p class="mt-3 mb-0">No users found</p>
                </div>
                <a href="users.php" class="btn btn-outline-primary">Refresh</a>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Name</th>
                            <th class="d-none d-md-table-cell">Email</th>
                            <th class="d-none d-md-table-cell">Birthdate</th>
                            <th class="d-none d-md-table-cell">Full Address</th>
                            <th class="d-none d-lg-table-cell">Role</th>
                            <th class="d-none d-lg-table-cell">Joined</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users as $user): ?>
                            <tr>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="flex-shrink-0 me-2">
                                            <div class="avatar-sm" style="width: 36px; height: 36px; background-color: #6c757d; color: #fff; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: 600;">
                                                <?php echo strtoupper(substr($user['name'], 0, 1)); ?>
                                            </div>
                                        </div>
                                        <div class="flex-grow-1">
                                            <div class="fw-semibold">
                                                <a href="user_enrollments.php?id=<?php echo (int)$user['id']; ?>" class="text-decoration-none">
                                                    <?php echo htmlspecialchars($user['name']); ?>
                                                </a>
                                            </div>
                                            <div class="d-md-none small text-muted"><?php echo htmlspecialchars($user['email']); ?></div>
                                            <?php 
                                                $dobMobile = isset($user['date_of_birth']) && !empty($user['date_of_birth']) ? date('M j, Y', strtotime($user['date_of_birth'])) : '-';
                                                $addrPartsMobile = [];
                                                foreach (['address','city','state','postal_code','country'] as $k) {
                                                    if (isset($user[$k]) && trim((string)$user[$k]) !== '') { $addrPartsMobile[] = $user[$k]; }
                                                }
                                                $addrMobile = !empty($addrPartsMobile) ? implode(', ', array_map('htmlspecialchars', $addrPartsMobile)) : '-';
                                            ?>
                                            <div class="d-md-none small text-muted">DOB: <?php echo htmlspecialchars($dobMobile); ?></div>
                                            <div class="d-md-none small text-muted text-wrap">Address: <?php echo $addrMobile; ?></div>
                                        </div>
                                    </div>
                                </td>
                                <td class="d-none d-md-table-cell"><?php echo htmlspecialchars($user['email']); ?></td>
                                <td class="d-none d-md-table-cell">
                                    <?php 
                                        $dob = isset($user['date_of_birth']) && !empty($user['date_of_birth']) ? date('M j, Y', strtotime($user['date_of_birth'])) : '-';
                                        echo htmlspecialchars($dob);
                                    ?>
                                </td>
                                <td class="d-none d-md-table-cell">
                                    <?php 
                                        $parts = [];
                                        foreach (['address','city','state','postal_code','country'] as $key) {
                                            if (isset($user[$key]) && trim((string)$user[$key]) !== '') {
                                                $parts[] = $user[$key];
                                            }
                                        }
                                        $fullAddress = !empty($parts) ? implode(', ', array_map('htmlspecialchars', $parts)) : '-';
                                        echo $fullAddress;
                                    ?>
                                </td>
                                <td class="d-none d-lg-table-cell">
                                    <span class="badge bg-<?php echo $user['role'] === 'admin' ? 'primary' : 'secondary'; ?>">
                                        <?php echo ucfirst($user['role']); ?>
                                    </span>
                                </td>
                                <td class="d-none d-lg-table-cell"><?php echo date('M j, Y', strtotime($user['registration_date'])); ?></td>
                                <td>
                                    <div class="btn-group btn-group-sm">
                                        <button type="button" 
                                                class="btn btn-outline-danger" title="Delete"
                                                onclick="confirmDelete(<?php echo $user['id']; ?>, '<?php echo addslashes(htmlspecialchars($user['name'])); ?>')">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
function confirmDelete(userId, userName) {
    if (confirm(`Are you sure you want to delete the user "${userName}"? This action cannot be undone.`)) {
        window.location.href = `users.php?delete=${userId}`;
    }
}
</script>

<?php require_once 'includes/footer.php'; ?>
