<?php
require_once 'includes/session.php';
require_once 'config/database.php';
requireUser();

// Get user ID from cookie
$user_id = $_COOKIE['user_id'];

// Get user's enrollments
$enrollments = fetchAll(
    "SELECT e.*, c.title as course_title, c.instructor, c.duration 
     FROM enrollments e 
     JOIN courses c ON e.course_id = c.id 
     WHERE e.student_id = ? 
     ORDER BY e.created_at DESC", 
    [$user_id]
);

// Get available courses (courses the user hasn't enrolled in yet)
$availableCourses = fetchAll(
    "SELECT * FROM courses c 
     WHERE c.id NOT IN (
         SELECT course_id FROM enrollments WHERE student_id = ?
     ) AND c.enrolled < c.capacity
     ORDER BY c.title",
    [$user_id]
);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Enrollment System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <style>
        body { background: #f6f8fb; }
        .card-modern { border: 0; border-radius: 1rem; box-shadow: 0 10px 30px rgba(18,38,63,.06); transition: transform .2s ease; margin-bottom: 20px; }
        .card-modern:hover { transform: translateY(-4px); box-shadow: 0 14px 34px rgba(18,38,63,.08); }
        .card-header-gradient { background: linear-gradient(135deg, #4f46e5 0%, #0ea5e9 100%); color: #fff; border-top-left-radius: 1rem !important; border-top-right-radius: 1rem !important; }
        .status-badge { position: absolute; top: 10px; right: 10px; }
        .table thead th { white-space: nowrap; }
        /* Modern alerts */
        .alert-modern { border: 0; border-radius: .75rem; box-shadow: 0 10px 24px rgba(18,38,63,.06); }
        .alert-modern-success { background: linear-gradient(135deg, #dcfce7 0%, #a7f3d0 100%); color: #065f46; }
        .alert-modern-danger { background: linear-gradient(135deg, #fee2e2 0%, #fecaca 100%); color: #7f1d1d; }
        .alert-modern .btn-close { filter: invert(30%); }
    </style>
</head>
<body>
    <?php include 'includes/navbar.php'; ?>
    
    <div class="container py-5">
        <?php 
        $flashes = getFlash();
        if (!empty($flashes)):
            foreach ($flashes as $flash):
        ?>
            <?php $isSuccess = ($flash['type'] === 'success'); ?>
            <div class="alert alert-<?php echo $isSuccess ? 'success' : 'danger'; ?> alert-dismissible fade show alert-modern <?php echo $isSuccess ? 'alert-modern-success' : 'alert-modern-danger'; ?>" role="alert">
                <div class="d-flex align-items-start">
                    <i class="bi <?php echo $isSuccess ? 'bi-check-circle-fill' : 'bi-exclamation-triangle-fill'; ?> me-2"></i>
                    <div><?php echo htmlspecialchars($flash['message']); ?></div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php 
            endforeach;
        endif; 
        ?>
        
        <div class="row mb-4">
            <div class="col-12">
                <h2>Welcome, <?php echo htmlspecialchars($_COOKIE['user_name']); ?>!</h2>
                <p class="text-muted">Manage your course enrollments and view available courses.</p>
            </div>
        </div>
        
        <div class="row">
            <div class="col-md-8">
                <div class="card card-modern">
                    <div class="card-header card-header-gradient">
                        <h5 class="mb-0">My Enrollments</h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($enrollments)): ?>
                            <div class="text-center py-4">
                                <i class="bi bi-journal-text" style="font-size: 3rem; color: #6c757d;"></i>
                                <p class="mt-2">You haven't enrolled in any courses yet.</p>
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Course</th>
                                            <th>Instructor</th>
                                            <th>Duration</th>
                                            <th>Status</th>
                                            <th>Enrolled On</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($enrollments as $enrollment): ?>
                                            <tr>
                                                <td>
                                                    <strong><?php echo htmlspecialchars($enrollment['course_title']); ?></strong>
                                                    <?php if ($enrollment['status'] === 'pending'): ?>
                                                        <span class="badge bg-warning text-dark">Pending</span>
                                                    <?php elseif ($enrollment['status'] === 'approved'): ?>
                                                        <span class="badge bg-success">Approved</span>
                                                    <?php else: ?>
                                                        <span class="badge bg-danger">Rejected</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td><?php echo htmlspecialchars($enrollment['instructor']); ?></td>
                                                <td><?php echo htmlspecialchars($enrollment['duration']); ?></td>
                                                <td>
                                                    <?php if ($enrollment['status'] === 'pending'): ?>
                                                        <span class="badge bg-warning text-dark">Under Review</span>
                                                    <?php elseif ($enrollment['status'] === 'approved'): ?>
                                                        <span class="badge bg-success">Enrolled</span>
                                                    <?php else: ?>
                                                        <span class="badge bg-danger">Not Accepted</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td><?php echo date('M j, Y', strtotime($enrollment['created_at'])); ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <div class="col-md-4">
                <div class="card card-modern">
                    <div class="card-header card-header-gradient">
                        <h5 class="mb-0">Available Courses</h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($availableCourses)): ?>
                            <div class="text-center py-4">
                                <i class="bi bi-check-circle" style="font-size: 3rem; color: #198754;"></i>
                                <p class="mt-2">You've enrolled in all available courses!</p>
                            </div>
                        <?php else: ?>
                            <div class="list-group">
                                <?php foreach ($availableCourses as $course): ?>
                                    <a href="enroll.php?course_id=<?php echo $course['id']; ?>" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                                        <div>
                                            <h6 class="mb-1"><?php echo htmlspecialchars($course['title']); ?></h6>
                                            <small class="text-muted"><?php echo htmlspecialchars($course['instructor']); ?></small>
                                        </div>
                                        <span class="badge bg-primary rounded-pill">
                                            <?php echo $course['capacity'] - $course['enrolled']; ?> spots left
                                        </span>
                                    </a>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
      // Auto-dismiss alerts after a short delay without page refresh
      (function () {
        var autoDismissMs = 4000; // 4s base delay
        document.addEventListener('DOMContentLoaded', function () {
          var alerts = document.querySelectorAll('.alert.alert-dismissible');
          alerts.forEach(function (el, idx) {
            setTimeout(function () {
              try {
                var instance = bootstrap.Alert.getOrCreateInstance(el);
                instance.close();
              } catch (e) {
                // Fallback if Bootstrap API is unavailable
                el.classList.remove('show');
                el.parentNode && el.parentNode.removeChild(el);
              }
            }, autoDismissMs + (idx * 400)); // stagger dismiss slightly if multiple
          });
        });
      })();
    </script>
</body>
</html>
