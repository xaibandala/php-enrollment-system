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

$course = [
    'id' => '',
    'title' => '',
    'description' => '',
    'instructor' => '',
    'duration' => '',
    'category' => '',
    'capacity' => 20
];

$isEdit = false;
$error = '';

// Check if editing existing course
if (isset($_GET['id'])) {
    $course_id = $_GET['id'];
    $existingCourse = fetchOne("SELECT * FROM courses WHERE id = ?", [$course_id]);
    
    if ($existingCourse) {
        $course = $existingCourse;
        $isEdit = true;
    } else {
        header('Location: courses.php');
        exit();
    }
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Sanitize input
    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $duration = trim($_POST['duration'] ?? '');
    $category = trim($_POST['category'] ?? '');
    $capacity = (int)($_POST['capacity'] ?? 20);
    
    // Validate input
    if (empty($title) || empty($description) || empty($duration) || empty($category)) {
        $error = 'All fields are required';
    } elseif ($capacity < 1) {
        $error = 'Capacity must be at least 1';
    } else {
        if ($isEdit) {
            // Update existing course
            $result = executeQuery(
                "UPDATE courses SET 
                    title = ?, 
                    description = ?, 
                    duration = ?, 
                    category = ?, 
                    capacity = ?,
                    updated_at = CURRENT_TIMESTAMP 
                WHERE id = ?",
                [$title, $description, $duration, $category, $capacity, $course['id']]
            );
            
            if ($result) {
                setFlash('success', 'Course updated successfully');
                header('Location: courses.php');
                exit();
            } else {
                $error = 'Failed to update course';
            }
        } else {
            // Insert new course
            // Since instructor assignment is handled elsewhere and the column is NOT NULL,
            // set a default placeholder for now.
            $instructor = 'Unassigned';
            $result = executeQuery(
                "INSERT INTO courses (title, description, instructor, duration, category, capacity) 
                 VALUES (?, ?, ?, ?, ?, ?)",
                [$title, $description, $instructor, $duration, $category, $capacity]
            );
            
            if ($result) {
                setFlash('success', 'Course created successfully');
                header('Location: courses.php');
                exit();
            } else {
                $error = 'Failed to create course';
            }
        }
    }
    
    // Update course data with submitted values
    $course = [
        'id' => $course['id'],
        'title' => $title,
        'description' => $description,
        // Preserve existing instructor; assignment is handled elsewhere
        'instructor' => $course['instructor'] ?? 'Unassigned',
        'duration' => $duration,
        'category' => $category,
        'capacity' => $capacity
    ];
}
?>

<?php
// Set page title for admin layout and include shared header
$pageTitle = $isEdit ? 'Edit Course' : 'Add New Course';
require_once 'includes/header.php';
?>

<div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-4">
    <div>
        <h2 class="h3 mb-1 d-flex align-items-center gap-2">
            <i class="bi bi-book-half text-primary"></i>
            <?php echo $isEdit ? 'Edit Course' : 'Add New Course'; ?>
        </h2>
        <p class="text-muted small mb-0">Manage course details and capacity.</p>
    </div>
    <div class="d-flex gap-2">
        <a href="courses.php" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i>
            Back to Courses
        </a>
    </div>
    
    <nav aria-label="breadcrumb" class="w-100 mt-2">
        <ol class="breadcrumb mb-0 small">
            <li class="breadcrumb-item"><a href="index.php" class="text-decoration-none">Dashboard</a></li>
            <li class="breadcrumb-item"><a href="courses.php" class="text-decoration-none">Courses</a></li>
            <li class="breadcrumb-item active" aria-current="page"><?php echo $isEdit ? 'Edit' : 'Add'; ?></li>
        </ol>
    </nav>
    
    
</div>

<?php if ($error): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="bi bi-exclamation-triangle me-2"></i>
        <?php echo htmlspecialchars($error); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<div class="card shadow-sm border-0">
    <div class="card-header bg-white border-0 pb-0">
        <h6 class="mb-2">Course Details</h6>
        <p class="text-muted small mb-0">Provide the basic information for this course.</p>
    </div>
    <div class="card-body">
        <form method="POST" novalidate>
            <div class="row g-3 mb-2">
                <div class="col-md-8">
                    <label for="title" class="form-label fw-semibold">Course Title <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="title" name="title"
                           placeholder="Enter a clear, concise title"
                           value="<?php echo htmlspecialchars($course['title']); ?>" required>
                </div>
                <div class="col-md-4">
                    <label for="category" class="form-label fw-semibold">Category <span class="text-danger">*</span></label>
                    <select class="form-select" id="category" name="category" required>
                        <option value="" disabled <?php echo empty($course['category']) ? 'selected' : ''; ?>>Select a category</option>
                        <option value="Web Development" <?php echo $course['category'] === 'Web Development' ? 'selected' : ''; ?>>Web Development</option>
                        <option value="Data Science" <?php echo $course['category'] === 'Data Science' ? 'selected' : ''; ?>>Data Science</option>
                        <option value="Mobile Development" <?php echo $course['category'] === 'Mobile Development' ? 'selected' : ''; ?>>Mobile Development</option>
                        <option value="Design" <?php echo $course['category'] === 'Design' ? 'selected' : ''; ?>>Design</option>
                        <option value="Business" <?php echo $course['category'] === 'Business' ? 'selected' : ''; ?>>Business</option>
                        <option value="Other" <?php echo $course['category'] === 'Other' ? 'selected' : ''; ?>>Other</option>
                    </select>
                </div>
            </div>

            <div class="mb-3">
                <label for="description" class="form-label fw-semibold">Course Description <span class="text-danger">*</span></label>
                <textarea class="form-control" id="description" name="description" rows="5"
                          placeholder="Summarize the course goals, topics, and outcomes" required><?php echo htmlspecialchars($course['description']); ?></textarea>
                <div class="form-text">Aim for 1–3 concise paragraphs.</div>
            </div>

            <div class="row g-3">
                <div class="col-md-6">
                    <label for="duration" class="form-label fw-semibold">Duration <span class="text-danger">*</span></label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-hourglass"></i></span>
                        <input type="text" class="form-control" id="duration" name="duration"
                               placeholder="e.g., 8 weeks" value="<?php echo htmlspecialchars($course['duration']); ?>" required>
                    </div>
                </div>
                <div class="col-md-6">
                    <label for="capacity" class="form-label fw-semibold">Capacity <span class="text-danger">*</span></label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-people"></i></span>
                        <input type="number" class="form-control" id="capacity" name="capacity" min="1"
                               value="<?php echo (int)$course['capacity']; ?>" required>
                    </div>
                </div>
            </div>

            <div class="d-flex justify-content-between align-items-center pt-4 mt-3 border-top">
                <a href="courses.php" class="btn btn-light border">
                    <i class="bi bi-x-lg me-1"></i>
                    Cancel
                </a>
                <button type="submit" class="btn btn-primary">
                    <i class="bi <?php echo $isEdit ? 'bi-check-lg' : 'bi-plus-lg'; ?> me-1"></i>
                    <?php echo $isEdit ? 'Update Course' : 'Add Course'; ?>
                </button>
            </div>
        </form>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
