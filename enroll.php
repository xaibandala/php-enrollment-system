<?php
require_once 'includes/session.php';
require_once 'config/database.php';
requireUser();

$course_id = $_GET['course_id'] ?? null;
$error = '';
// Flag to trigger client-side redirect with loading overlay
$loadingRedirect = false;
$redirectUrl = 'dashboard.php';

// Get course details
$course = fetchOne("SELECT * FROM courses WHERE id = ?", [$course_id]);

// Redirect if course not found
if (!$course) {
    header('Location: dashboard.php');
    exit();
}

// Check if user is already enrolled
$existingEnrollment = fetchOne(
    "SELECT * FROM enrollments WHERE student_id = ? AND course_id = ?",
    [$_COOKIE['user_id'], $course_id]
);

if ($existingEnrollment) {
    header('Location: dashboard.php');
    exit();
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $motivation = trim($_POST['motivation'] ?? '');
    $experience = trim($_POST['experience'] ?? '');
    $goals = trim($_POST['goals'] ?? '');
    
    // Validate form
    if (empty($motivation)) {
        $error = 'Please explain your motivation for joining this course';
    } else {
        // Insert enrollment
        $result = executeQuery(
            "INSERT INTO enrollments (student_id, course_id, motivation, experience, goals, status) 
             VALUES (?, ?, ?, ?, ?, 'pending')",
            [
                $_COOKIE['user_id'],
                $course_id,
                $motivation,
                $experience ?: 'No prior experience',
                $goals ?: 'Not specified'
            ]
        );
        
        if ($result) {
            // Update enrolled count
            executeQuery(
                "UPDATE courses SET enrolled = enrolled + 1 WHERE id = ?",
                [$course_id]
            );
            
            setFlash('success', 'Your enrollment application has been submitted successfully!');
            // Defer redirect to client-side to show loading animation
            $loadingRedirect = true;
        } else {
            $error = 'Failed to submit enrollment. Please try again.';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Enroll in <?php echo htmlspecialchars($course['title']); ?> - Enrollment System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <style>
        .form-label.required:after {
            content: " *";
            color: #dc3545;
        }
        body { background: #f6f8fb; }
        .card-modern { border: 0; border-radius: 1rem; box-shadow: 0 10px 30px rgba(18,38,63,.06); }
        .card-header-gradient { background: linear-gradient(135deg, #4f46e5 0%, #0ea5e9 100%); color: #fff; border-top-left-radius: 1rem !important; border-top-right-radius: 1rem !important; }
        .form-control { border-radius: .75rem; }
        .form-control:focus { box-shadow: 0 0 0 .2rem rgba(79,70,229,.15); border-color: #4f46e5; }
        .btn-modern { border-radius: .75rem; box-shadow: 0 6px 16px rgba(79,70,229,.15); }
        .course-details { background-color: #f8f9fa; border-radius: .75rem; padding: 20px; margin-bottom: 20px; }
        /* Fullscreen loading overlay */
        .loading-overlay {
            position: fixed;
            inset: 0;
            background: rgba(255, 255, 255, 0.9);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 1055;
        }
    </style>
</head>
<body>
    <?php include 'includes/navbar.php'; ?>
    <?php if ($loadingRedirect): ?>
        <div class="loading-overlay">
            <div class="text-center">
                <div class="spinner-border text-primary" role="status" aria-hidden="true"></div>
                <div class="mt-3">Submitting your application...</div>
            </div>
        </div>
        <script>
            setTimeout(function () {
                window.location.href = '<?php echo $redirectUrl; ?>';
            }, 2000);
        </script>
    <?php endif; ?>
    
    <div class="container py-5">
        <div class="row">
            <div class="col-12">
                <a href="dashboard.php" class="text-decoration-none d-inline-flex align-items-center mb-3">
                    <i class="bi bi-arrow-left me-1"></i> Back to Dashboard
                </a>
                <h2 class="mb-4">Enroll in <?php echo htmlspecialchars($course['title']); ?></h2>
            </div>
        </div>
        
        <div class="row">
            <div class="col-lg-8">
                <?php if ($error): ?>
                    <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
                <?php endif; ?>
                
                <div class="card card-modern">
                    <div class="card-header card-header-gradient">
                        <h5 class="mb-0">Application Form</h5>
                    </div>
                    <div class="card-body p-4 p-md-5">
                        
                        <form method="POST">
                            <div class="mb-4">
                                <label for="motivation" class="form-label required">Why do you want to enroll in this course?</label>
                                <p class="text-muted small mb-2">Please explain your motivation and what you hope to gain from this course.</p>
                                <textarea class="form-control" id="motivation" name="motivation" rows="4" required><?php echo htmlspecialchars($_POST['motivation'] ?? ''); ?></textarea>
                            </div>
                            
                            <div class="mb-4">
                                <label for="experience" class="form-label">Relevant Experience</label>
                                <p class="text-muted small mb-2">Briefly describe any prior experience you have in this field.</p>
                                <textarea class="form-control" id="experience" name="experience" rows="3"><?php echo htmlspecialchars($_POST['experience'] ?? ''); ?></textarea>
                            </div>
                            
                            <div class="mb-4">
                                <label for="goals" class="form-label">Learning Goals</label>
                                <p class="text-muted small mb-2">What specific skills or knowledge do you hope to acquire?</p>
                                <textarea class="form-control" id="goals" name="goals" rows="3"><?php echo htmlspecialchars($_POST['goals'] ?? ''); ?></textarea>
                            </div>
                            
                            <div class="alert alert-info shadow-sm">
                                <div class="d-flex">
                                    <i class="bi bi-info-circle-fill me-2"></i>
                                    <div>
                                        <h6 class="alert-heading">Application Review</h6>
                                        <p class="mb-0">Your application will be reviewed by our team. You'll receive a notification once a decision has been made.</p>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="d-flex justify-content-between pt-3 border-top">
                                <a href="dashboard.php" class="btn btn-outline-secondary btn-modern">Cancel</a>
                                <button type="submit" class="btn btn-primary btn-modern">
                                    <i class="bi bi-send-fill me-1"></i> Submit Application
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-4">
                <div class="course-details info-card border-0 shadow-sm">
                    <h5 class="mb-3">Course Details</h5>
                    <div class="mb-3">
                        <h6 class="text-muted mb-1">Instructor</h6>
                        <p class="mb-0"><?php echo htmlspecialchars($course['instructor']); ?></p>
                    </div>
                    <div class="mb-3">
                        <h6 class="text-muted mb-1">Duration</h6>
                        <p class="mb-0"><?php echo htmlspecialchars($course['duration']); ?></p>
                    </div>
                    <div class="mb-3">
                        <h6 class="text-muted mb-1">Category</h6>
                        <p class="mb-0"><?php echo htmlspecialchars($course['category']); ?></p>
                    </div>
                    <div class="mb-3">
                        <h6 class="text-muted mb-1">Available Spots</h6>
                        <p class="mb-0">
                            <?php 
                            $remaining = $course['capacity'] - $course['enrolled'];
                            echo "$remaining of {$course['capacity']} remaining";
                            ?>
                        </p>
                    </div>
                    <div>
                        <h6 class="text-muted mb-1">Description</h6>
                        <p class="mb-0"><?php echo nl2br(htmlspecialchars($course['description'])); ?></p>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
