<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include required files
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../config/database.php';

// Check admin access
if (!isAdmin()) {
    header('Location: /php-enrollment-system/login.php');
    exit();
}

$pageTitle = 'Manage Courses';
require_once 'includes/header.php';

// Database functions
function fetchAll($sql, $params = []) {
    global $pdo;
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function fetchOne($sql, $params = []) {
    global $pdo;
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

function executeQuery($sql, $params = []) {
    global $pdo;
    $stmt = $pdo->prepare($sql);
    return $stmt->execute($params);
}

// Handle course deletion
if (isset($_GET['delete'])) {
    $course_id = $_GET['delete'];
    
    // Check if there are any enrollments for this course
    $enrollments = fetchOne("SELECT COUNT(*) as count FROM enrollments WHERE course_id = ?", [$course_id]);
    
    if ($enrollments && $enrollments['count'] > 0) {
        $_SESSION['flash'] = ['type' => 'danger', 'message' => 'Cannot delete course with existing enrollments'];
    } else {
        $result = executeQuery("DELETE FROM courses WHERE id = ?", [$course_id]);
        if ($result) {
            $_SESSION['flash'] = ['type' => 'success', 'message' => 'Course deleted successfully'];
        } else {
            $_SESSION['flash'] = ['type' => 'danger', 'message' => 'Failed to delete course'];
        }
    }
    
    header('Location: courses.php');
    exit();
}

// Get all courses
$courses = fetchAll("SELECT * FROM courses ORDER BY created_at DESC");
?>

<?php 
// Flash message handling
if (isset($_SESSION['flash'])) {
    $flash = $_SESSION['flash'];
    unset($_SESSION['flash']);
}
?>

<div class="mb-4">
        <?php if (isset($flash)): ?>
            <div class="alert alert-<?php echo $flash['type'] === 'success' ? 'success' : 'danger'; ?> alert-dismissible fade show mb-3" role="alert">
                <i class="bi <?php echo $flash['type'] === 'success' ? 'bi-check-circle' : 'bi-exclamation-triangle'; ?> me-2"></i>
                <?php echo htmlspecialchars($flash['message']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-2">
            <div>
                <h2 class="h3 mb-1 d-flex align-items-center gap-2">
                    <i class="bi bi-journal-bookmark text-primary"></i>
                    Manage Courses
                </h2>
                <p class="text-muted small mb-0">Create, edit, and manage available courses.</p>
            </div>
            <a href="course_edit.php" class="btn btn-primary">
                <i class="bi bi-plus-lg me-1"></i> Add New Course
            </a>
        </div>

        <nav aria-label="breadcrumb" class="mb-3">
            <ol class="breadcrumb mb-0 small">
                <li class="breadcrumb-item"><a href="index.php" class="text-decoration-none">Dashboard</a></li>
                <li class="breadcrumb-item active" aria-current="page">Courses</li>
            </ol>
        </nav>

        <div class="card shadow-sm border-0">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Title</th>
                                <th class="d-none d-md-table-cell">Instructor</th>
                                <th class="d-none d-lg-table-cell">Category</th>
                                <th>Enrolled</th>
                                <th class="d-none d-sm-table-cell">Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($courses)): ?>
                                <tr>
                                    <td colspan="6" class="text-center py-5">
                                        <div class="text-muted">
                                            <i class="bi bi-journal-text" style="font-size: 2rem;"></i>
                                            <p class="mt-2 mb-0">No courses found</p>
                                        </div>
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($courses as $course): ?>
                                    <tr>
                                        <td>
                                            <div class="fw-semibold"><?php echo htmlspecialchars($course['title']); ?></div>
                                            <small class="text-muted"><?php echo htmlspecialchars($course['duration']); ?></small>
                                            <div class="d-md-none mt-1">
                                                <small class="text-muted me-2"><i class="bi bi-person me-1"></i><?php echo htmlspecialchars($course['instructor']); ?></small>
                                                <span class="badge bg-secondary align-middle">
                                                    <i class="bi bi-tag me-1"></i><?php echo htmlspecialchars($course['category']); ?>
                                                </span>
                                            </div>
                                        </td>
                                        <td class="d-none d-md-table-cell"><?php echo htmlspecialchars($course['instructor']); ?></td>
                                        <td class="d-none d-lg-table-cell">
                                            <span class="badge bg-secondary">
                                                <?php echo htmlspecialchars($course['category']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php 
                                            $enrolled = $course['enrolled'];
                                            $capacity = $course['capacity'];
                                            $percentage = $capacity > 0 ? ($enrolled / $capacity) * 100 : 0;
                                            ?>
                                            <div class="d-flex align-items-center">
                                                <div class="progress flex-grow-1 me-2" style="height: 6px;">
                                                    <div class="progress-bar bg-success" role="progressbar" 
                                                         style="width: <?php echo $percentage; ?>%" 
                                                         aria-valuenow="<?php echo $percentage; ?>" 
                                                         aria-valuemin="0" 
                                                         aria-valuemax="100"></div>
                                                </div>
                                                <small class="text-nowrap"><?php echo "$enrolled/$capacity"; ?></small>
                                            </div>
                                        </td>
                                        <td class="d-none d-sm-table-cell">
                                            <?php if ($course['enrolled'] >= $course['capacity']): ?>
                                                <span class="badge bg-danger">Full</span>
                                            <?php else: ?>
                                                <span class="badge bg-success">Open</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <div class="btn-group btn-group-sm">
                                                <a href="course_edit.php?id=<?php echo $course['id']; ?>" 
                                                   class="btn btn-outline-primary" title="Edit">
                                                    <i class="bi bi-pencil"></i>
                                                </a>
                                                <a href="#" 
                                                   onclick="confirmDelete(<?php echo $course['id']; ?>)" 
                                                   class="btn btn-outline-danger" title="Delete">
                                                    <i class="bi bi-trash"></i>
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
</div>

<script>
function confirmDelete(courseId) {
    if (confirm('Are you sure you want to delete this course? This action cannot be undone.')) {
        window.location.href = `courses.php?delete=${courseId}`;
    }
}
</script>

<?php require_once 'includes/footer.php'; ?>
