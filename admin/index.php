<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include required files
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../config/database.php';

// Debug session
error_log('Admin index accessed. Session data: ' . print_r($_SESSION, true));

// Check admin access
if (!isset($_SESSION['admin_id'])) {
    error_log('No admin_id in session. Redirecting to login.');
    header('Location: adminlogin.php');
    exit();
}

// Allow only 'admin'
if (!isset($_SESSION['admin_role']) || $_SESSION['admin_role'] !== 'admin') {
    error_log('User is not allowed. Role: ' . ($_SESSION['admin_role'] ?? 'not set'));
    header('Location: adminlogin.php');
    exit();
}

$pageTitle = 'Dashboard';
require_once 'includes/header.php';

// Get counts for the dashboard
$usersCount = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
$instructorsCount = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'instructor'")->fetchColumn();
$coursesCount = $pdo->query("SELECT COUNT(*) FROM courses")->fetchColumn();
$enrollmentsCount = $pdo->query("SELECT COUNT(*) FROM enrollments")->fetchColumn();
?>

<style>
/* Scoped hover animation for dashboard statistic cards */
.hover-card { transition: transform .2s ease, box-shadow .2s ease; cursor: pointer; }
.hover-card:hover { transform: translateY(-3px); box-shadow: 0 .5rem 1rem rgba(0,0,0,.15) !important; }
.hover-card .btn { transition: transform .2s ease; }
.hover-card:hover .btn { transform: translateY(-1px); }
/* Responsive improvements for Recent Activity table */
.activity-description { white-space: normal; word-break: break-word; }
</style>

<div class="row mb-3">
    <div class="col-12">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
            <div>
                <h1 class="h2 mb-1 d-flex align-items-center gap-2">
                    <i class="bi bi-speedometer2 text-primary"></i>
                    Dashboard
                </h1>
                <p class="text-muted small mb-0">Welcome back, <?php echo htmlspecialchars($_SESSION['admin_username']); ?>!</p>
            </div>
        </div>
        <nav aria-label="breadcrumb" class="mt-2">
            <ol class="breadcrumb mb-0 small">
                <li class="breadcrumb-item"><a href="index.php" class="text-decoration-none">Home</a></li>
                <li class="breadcrumb-item active" aria-current="page">Dashboard</li>
            </ol>
        </nav>
    </div>
    
</div>

<div class="row">
    <!-- Users Card -->
    <div class="col-md-4 mb-4">
        <div class="card hover-card shadow-sm border-0 rounded-3 text-white bg-primary position-relative">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="card-title text-white-50">Users</h6>
                        <h2 class="mb-0 fw-semibold"><?php echo $usersCount; ?></h2>
                    </div>
                    <i class="bi bi-people display-6 opacity-50"></i>
                </div>
                <a href="users.php" class="btn btn-light btn-sm mt-3">View Users</a>
                <a href="users.php" class="stretched-link" aria-label="Go to Users"></a>
            </div>
        </div>
    </div>

    <!-- Courses Card -->
    <div class="col-md-4 mb-4">
        <div class="card hover-card shadow-sm border-0 rounded-3 text-white bg-success position-relative">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="card-title text-white-50">Courses</h6>
                        <h2 class="mb-0 fw-semibold"><?php echo $coursesCount; ?></h2>
                    </div>
                    <i class="bi bi-book display-6 opacity-50"></i>
                </div>
                <a href="courses.php" class="btn btn-light btn-sm mt-3">View Courses</a>
                <a href="courses.php" class="stretched-link" aria-label="Go to Courses"></a>
            </div>
        </div>
    </div>

    <!-- Enrollments Card -->
    <div class="col-md-4 mb-4">
        <div class="card hover-card shadow-sm border-0 rounded-3 text-white bg-info position-relative">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="card-title text-white-50">Enrollments</h6>
                        <h2 class="mb-0 fw-semibold"><?php echo $enrollmentsCount; ?></h2>
                    </div>
                    <i class="bi bi-card-checklist display-6 opacity-50"></i>
                </div>
                <a href="enrollments.php" class="btn btn-light btn-sm mt-3">View Enrollments</a>
                <a href="enrollments.php" class="stretched-link" aria-label="Go to Enrollments"></a>
            </div>
        </div>
    </div>
</div>

<!-- Recent Activity -->
<div class="row">
    <!-- Instructors Card -->
    <div class="col-md-4 mb-4">
        <div class="card hover-card shadow-sm border-0 rounded-3 text-white bg-warning position-relative">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="card-title text-white-50">Instructors</h6>
                        <h2 class="mb-0 fw-semibold"><?php echo $instructorsCount; ?></h2>
                    </div>
                    <i class="bi bi-person-badge display-6 opacity-50"></i>
                </div>
                <a href="instructors.php" class="btn btn-light btn-sm mt-3">View Instructors</a>
                <a href="instructors.php" class="stretched-link" aria-label="Go to Instructors"></a>
            </div>
        </div>
    </div>
</div>

<div class="card shadow-sm border-0 mb-4">
    <div class="card-header bg-white border-0">
        <h5 class="card-title mb-0">Recent Activity</h5>
    </div>
    <div class="card-body">
        <?php
        // Pagination params
        $perPage = 10;
        $page = isset($_GET['ra_page']) ? max(1, (int)$_GET['ra_page']) : 1;
        $offset = ($page - 1) * $perPage;

        // Build a unified recent activity feed from existing tables (base union SQL)
        $activityUnion = "
            (
                SELECT 
                    'Admin Login' AS action,
                    CONCAT('Admin ', a.username, ' logged in from ', ala.ip_address) AS description,
                    ala.attempt_time AS event_time
                FROM admin_login_attempts ala
                LEFT JOIN admins a ON ala.admin_id = a.id
                WHERE ala.success = 1
            )
            UNION ALL
            (
                SELECT 
                    'User Registered' AS action,
                    CONCAT('New user: ', u.name, ' (', u.email, ')') AS description,
                    u.registration_date AS event_time
                FROM users u
            )
            UNION ALL
            (
                SELECT 
                    'Course Created' AS action,
                    CONCAT('Course: ', c.title) AS description,
                    c.created_at AS event_time
                FROM courses c
            )
            UNION ALL
            (
                SELECT 
                    'Enrollment Created' AS action,
                    CONCAT('Enrollment: ', u.name, ' -> ', c.title) AS description,
                    e.created_at AS event_time
                FROM enrollments e
                JOIN users u ON e.student_id = u.id
                JOIN courses c ON e.course_id = c.id
            )
            UNION ALL
            (
                SELECT 
                    'Enrollment Updated' AS action,
                    CONCAT('Enrollment updated: ', u.name, ' -> ', c.title, ' (', e.status, ')') AS description,
                    e.updated_at AS event_time
                FROM enrollments e
                JOIN users u ON e.student_id = u.id
                JOIN courses c ON e.course_id = c.id
                WHERE e.updated_at IS NOT NULL
            )
        ";

        // Total count
        $totalActivities = 0;
        try {
            $countSql = "SELECT COUNT(*) AS cnt FROM (" . $activityUnion . ") t";
            $totalActivities = (int)$pdo->query($countSql)->fetchColumn();
        } catch (Throwable $e) {
            error_log('Failed to count recent activities: ' . $e->getMessage());
        }
        $totalPages = max(1, (int)ceil($totalActivities / $perPage));
        if ($page > $totalPages) { $page = $totalPages; $offset = ($page - 1) * $perPage; }

        // Page data
        $recentActivities = [];
        try {
            $recentSql = "SELECT * FROM (" . $activityUnion . ") t ORDER BY event_time DESC LIMIT {$perPage} OFFSET {$offset}";
            $stmt = $pdo->query($recentSql);
            if ($stmt) {
                $recentActivities = $stmt->fetchAll(PDO::FETCH_ASSOC);
            }
        } catch (Throwable $e) {
            error_log('Failed to load recent activities: ' . $e->getMessage());
        }
        ?>

        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="d-none d-sm-table-cell">#</th>
                        <th>Action</th>
                        <th>Description</th>
                        <th class="d-none d-sm-table-cell">Date</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($recentActivities)): ?>
                        <tr>
                            <td colspan="4" class="text-center text-muted">No recent activity found.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($recentActivities as $idx => $act): ?>
                            <tr>
                                <td class="d-none d-sm-table-cell"><?php echo (int)($offset + $idx + 1); ?></td>
                                <td>
                                    <?php 
                                        $badgeClass = 'secondary';
                                        if (stripos($act['action'], 'login') !== false) $badgeClass = 'success';
                                        if (stripos($act['action'], 'created') !== false) $badgeClass = 'primary';
                                        if (stripos($act['action'], 'updated') !== false) $badgeClass = 'warning';
                                    ?>
                                    <span class="badge bg-<?php echo $badgeClass; ?>"><?php echo htmlspecialchars($act['action']); ?></span>
                                </td>
                                <td class="activity-description"><?php echo htmlspecialchars($act['description']); ?></td>
                                <td class="d-none d-sm-table-cell"><?php echo htmlspecialchars(date('Y-m-d H:i:s', strtotime($act['event_time']))); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <?php
          // Summary range
          if ($totalActivities > 0) {
            $start = $offset + 1;
            $end = $offset + count($recentActivities);
          } else { $start = 0; $end = 0; }
        ?>
        <div class="d-flex flex-wrap justify-content-between align-items-center p-3 border-top gap-2">
          <div class="text-muted small">
            Showing <?php echo (int)$start; ?>–<?php echo (int)$end; ?> of <?php echo (int)$totalActivities; ?>
          </div>
          <?php if ($totalPages > 1): ?>
          <nav aria-label="Recent Activity pagination">
            <ul class="pagination pagination-sm mb-0">
              <?php $prev = max(1, $page - 1); $next = min($totalPages, $page + 1); ?>
              <li class="page-item <?php echo $page <= 1 ? 'disabled' : ''; ?>">
                <a class="page-link" href="index.php?ra_page=<?php echo (int)$prev; ?>" aria-label="Previous" tabindex="<?php echo $page <= 1 ? '-1' : '0'; ?>">&laquo;</a>
              </li>
              <?php
                $window = 5; $half = (int)floor($window/2);
                $startPage = max(1, $page - $half);
                $endPage = min($totalPages, $startPage + $window - 1);
                if ($endPage - $startPage + 1 < $window) { $startPage = max(1, $endPage - $window + 1); }
                for ($p=$startPage; $p<=$endPage; $p++):
              ?>
                <li class="page-item <?php echo $p === $page ? 'active' : ''; ?>">
                  <a class="page-link" href="index.php?ra_page=<?php echo (int)$p; ?>"><?php echo (int)$p; ?></a>
                </li>
              <?php endfor; ?>
              <li class="page-item <?php echo $page >= $totalPages ? 'disabled' : ''; ?>">
                <a class="page-link" href="index.php?ra_page=<?php echo (int)$next; ?>" aria-label="Next" tabindex="<?php echo $page >= $totalPages ? '-1' : '0'; ?>">&raquo;</a>
              </li>
            </ul>
          </nav>
          <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
