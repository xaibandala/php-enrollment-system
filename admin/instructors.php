<?php
$pageTitle = 'Manage Instructors';
// Start session and load DB before handling POST, but avoid emitting HTML yet
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../config/database.php';

// Restrict to admin role only
if (!isset($_SESSION['admin_role']) || $_SESSION['admin_role'] !== 'admin') {
    header('Location: adminlogin.php');
    exit();
}

// Helpers
function flash_set($type, $message) {
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

// Handle: Add new instructor
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_instructor') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');

    if ($name === '' || $email === '') {
        flash_set('danger', 'Please fill in all instructor fields.');
        header('Location: instructors.php');
        exit();
    }

    // Check if email exists
    $existing = fetchOne('SELECT id FROM users WHERE email = ?', [$email]);
    if ($existing) {
        flash_set('danger', 'An account with this email already exists.');
        header('Location: instructors.php');
        exit();
    }

    // Auto-generate a secure temporary password
    try {
        $tempPassword = bin2hex(random_bytes(4)); // 8 hex bytes (~8 chars)
    } catch (Exception $e) {
        // Fallback if random_bytes is unavailable
        $tempPassword = substr(str_shuffle('ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnopqrstuvwxyz23456789'), 0, 8);
    }
    $hash = password_hash($tempPassword, PASSWORD_BCRYPT);
    $ok = executeQuery(
        'INSERT INTO users (name, email, password, role, date_of_birth, address, city, state, postal_code, country, registration_date)
         VALUES (?, ?, ?, "instructor", CURDATE(), "", "", "", "", "", NOW())',
        [$name, $email, $hash]
    );

    if ($ok) {
        // Include the temporary password in the flash for admin to share with the instructor
        flash_set('success', 'Instructor added successfully. Temporary password: ' . htmlspecialchars($tempPassword));
    } else {
        flash_set('danger', 'Failed to add instructor.');
    }
    header('Location: instructors.php');
    exit();
}

// Handle: Assign instructor to a subject (course)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'assign_instructor') {
    $course_id = (int)($_POST['course_id'] ?? 0);
    $instructor_id = (int)($_POST['instructor_id'] ?? 0);

    if ($course_id <= 0 || $instructor_id <= 0) {
        flash_set('danger', 'Please select both course and instructor.');
        header('Location: instructors.php');
        exit();
    }

    // Fetch instructor name from users
    $inst = fetchOne('SELECT name FROM users WHERE id = ? AND role = "instructor"', [$instructor_id]);
    if (!$inst) {
        flash_set('danger', 'Selected instructor not found.');
        header('Location: instructors.php');
        exit();
    }

    // Update courses.instructor (schema stores instructor as text)
    $ok = executeQuery('UPDATE courses SET instructor = ? WHERE id = ?', [$inst['name'], $course_id]);
    if ($ok) {
        flash_set('success', 'Instructor assigned to course successfully.');
    } else {
        flash_set('danger', 'Failed to assign instructor to course.');
    }
    header('Location: instructors.php');
    exit();
}

// Fetch data for display
$instructors = fetchAll('SELECT id, name, email, role, registration_date FROM users WHERE role = "instructor" ORDER BY name ASC');
$courses = fetchAll('SELECT id, title, instructor FROM courses ORDER BY title ASC');

// Optional: count courses per instructor using current schema (match by name)
$courseCounts = [];
foreach ($instructors as $i) {
    $countRow = fetchOne('SELECT COUNT(*) AS cnt FROM courses WHERE instructor = ?', [$i['name']]);
    $courseCounts[$i['id']] = (int)($countRow['cnt'] ?? 0);
}

// Collect subject titles per instructor (by matching name in current schema)
$subjectsByInstructor = [];
foreach ($instructors as $i) {
    $subjects = fetchAll('SELECT title FROM courses WHERE instructor = ? ORDER BY title ASC', [$i['name']]);
    $subjectsByInstructor[$i['id']] = array_map(function($row) { return $row['title']; }, $subjects);
}

// Retrieve and clear flash
$flash = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);

// After processing, render header (outputs HTML) and the rest of the page
require_once 'includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2 class="mb-0"><i class="bi bi-person-badge me-2"></i>Manage Instructors</h2>
        <small class="text-muted">Create instructors and assign them to subjects</small>
    </div>
</div>

<?php if ($flash): ?>
    <div class="alert alert-<?php echo $flash['type'] === 'success' ? 'success' : 'danger'; ?> alert-dismissible fade show" role="alert">
        <?php echo htmlspecialchars($flash['message']); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    <script>
      // Auto-dismiss only success alerts after 2 seconds
      window.addEventListener('load', function () {
        var el = document.querySelector('.alert.alert-success');
        if (el) {
          setTimeout(function () {
            try {
              if (window.bootstrap && window.bootstrap.Alert) {
                var bsAlert = new window.bootstrap.Alert(el);
                bsAlert.close();
              } else {
                // Fallback if Bootstrap API not available
                el.classList.remove('show');
                setTimeout(function(){ el.remove(); }, 150);
              }
            } catch (e) {
              el.classList.remove('show');
              setTimeout(function(){ el.remove(); }, 150);
            }
          }, 2000);
        }
      });
    </script>
<?php endif; ?>

<div class="row">
    <div class="col-lg-5">
        <div class="card mb-4">
            <div class="card-header"><i class="bi bi-person-plus me-2"></i>Add New Instructor</div>
            <div class="card-body">
                <form method="POST" action="">
                    <input type="hidden" name="action" value="add_instructor">
                    <div class="mb-3">
                        <label class="form-label">Name</label>
                        <input type="text" class="form-control" name="name" placeholder="e.g., Jane Doe" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Email</label>
                        <input type="email" class="form-control" name="email" placeholder="name@example.com" required>
                        <div class="form-text">A temporary password will be auto-generated.</div>
                    </div>
                    <button type="submit" class="btn btn-primary"><i class="bi bi-plus-circle me-1"></i>Add Instructor</button>
                </form>
            </div>
        </div>

        <div class="card mb-4">
            <div class="card-header"><i class="bi bi-journal-check me-2"></i>Assign Instructor to Subject</div>
            <div class="card-body">
                <form method="POST" action="">
                    <input type="hidden" name="action" value="assign_instructor">
                    <div class="mb-3">
                        <label class="form-label">Subject (Course)</label>
                        <select class="form-select" name="course_id" required>
                            <option value="">Select a course</option>
                            <?php foreach ($courses as $c): ?>
                                <option value="<?php echo $c['id']; ?>">
                                    <?php echo htmlspecialchars($c['title']); ?>
                                    <?php if (!empty($c['instructor'])): ?>
                                        (Current: <?php echo htmlspecialchars($c['instructor']); ?>)
                                    <?php endif; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Instructor</label>
                        <select class="form-select" name="instructor_id" required>
                            <option value="">Select an instructor</option>
                            <?php foreach ($instructors as $i): ?>
                                <option value="<?php echo $i['id']; ?>"><?php echo htmlspecialchars($i['name']); ?> (<?php echo htmlspecialchars($i['email']); ?>)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-success"><i class="bi bi-check2-circle me-1"></i>Assign</button>
                </form>
            </div>
        </div>
    </div>

    <div class="col-lg-7">
        <div class="card mb-4">
            <div class="card-header"><i class="bi bi-people me-2"></i>Instructors</div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover table-sm align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Name</th>
                                <th class="d-none d-md-table-cell">Email</th>
                                <th class="d-none d-lg-table-cell">Joined</th>
                                <th class="d-none d-md-table-cell">Assigned Subjects</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($instructors)): ?>
                                <tr>
                                    <td colspan="4" class="text-center py-4 text-muted"><i class="bi bi-info-circle me-1"></i>No instructors found</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($instructors as $i): ?>
                                    <tr>
                                        <td>
                                            <div class="fw-semibold text-break"><?php echo htmlspecialchars($i['name']); ?></div>
                                            <?php $subs = $subjectsByInstructor[$i['id']] ?? []; ?>
                                            <?php if (!empty($subs)): ?>
                                                <!-- Desktop/tablet: show badges -->
                                                <div class="d-none d-sm-flex flex-wrap gap-1 mt-1">
                                                    <?php
                                                    $maxBadges = 5;
                                                    $shown = 0;
                                                    foreach ($subs as $t) {
                                                        if ($shown >= $maxBadges) break;
                                                        echo '<span class="badge bg-light text-dark border">' . htmlspecialchars($t) . '</span>';
                                                        $shown++;
                                                    }
                                                    $remaining = max(0, count($subs) - $shown);
                                                    if ($remaining > 0) {
                                                        echo '<span class="badge bg-secondary">+' . (int)$remaining . ' more</span>';
                                                    }
                                                    ?>
                                                </div>
                                                <!-- Mobile: compact list -->
                                                <div class="d-sm-none small text-muted mt-1 text-truncate" style="max-width: 100%;">
                                                    <?php echo htmlspecialchars(implode(', ', array_slice($subs, 0, 3))); ?><?php if (count($subs) > 3) { echo '…'; } ?>
                                                </div>
                                            <?php else: ?>
                                                <div class="small text-muted"><i class="bi bi-dash-circle me-1"></i>No assigned subjects</div>
                                            <?php endif; ?>
                                            <!-- Mobile-only extra details -->
                                            <div class="d-md-none small text-muted mt-1 text-break"><?php echo htmlspecialchars($i['email']); ?></div>
                                            <div class="d-lg-none small text-muted">Joined: <?php echo date('M j, Y', strtotime($i['registration_date'])); ?></div>
                                        </td>
                                        <td class="d-none d-md-table-cell text-break"><?php echo htmlspecialchars($i['email']); ?></td>
                                        <td class="d-none d-lg-table-cell"><?php echo date('M j, Y', strtotime($i['registration_date'])); ?></td>
                                        <td class="d-none d-md-table-cell"><span class="badge bg-secondary"><?php echo (int)($courseCounts[$i['id']] ?? 0); ?></span></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="card mb-5">
            <div class="card-header"><i class="bi bi-journals me-2"></i>Subjects and Assigned Instructors</div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover table-sm align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Subject</th>
                                <th>Instructor</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($courses)): ?>
                                <tr>
                                    <td colspan="2" class="text-center py-4 text-muted"><i class="bi bi-info-circle me-1"></i>No courses found</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($courses as $c): ?>
                                    <tr>
                                        <td class="text-break"><?php echo htmlspecialchars($c['title']); ?></td>
                                        <td class="text-break"><?php echo $c['instructor'] ? htmlspecialchars($c['instructor']) : '<span class="text-muted"><i class="bi bi-dash-circle me-1"></i>Unassigned</span>'; ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
