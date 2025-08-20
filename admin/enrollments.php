<?php
require_once '../includes/session.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/mailer.php';
requireAdmin();

// Handle status update OR email actions (branch inside)
if (isset($_POST['update_status']) || isset($_POST['send_email'])) {
    $enrollment_id = $_POST['enrollment_id'] ?? null;
    $status = $_POST['status'] ?? null;

    $ok = false;
    if ($enrollment_id && $status && in_array($status, ['pending','approved','rejected'], true)) {
        $ok = executeQuery(
            "UPDATE enrollments SET status = ? WHERE id = ?",
            [$status, $enrollment_id]
        ) ? true : false;
    }

// Handle sending a custom email to a student
if (isset($_POST['send_email'])) {
    $to = trim($_POST['to'] ?? '');
    $name = trim($_POST['name'] ?? '');
    $subject = trim($_POST['subject'] ?? '');
    $message = trim($_POST['message'] ?? '');

    $ok = false;
    if (filter_var($to, FILTER_VALIDATE_EMAIL) && $subject !== '' && $message !== '') {
        $ok = sendCustomEmail($to, $name, $subject, nl2br($message), $message);
    }

    $isAjax = isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
    $acceptsJson = isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false;
    if ($isAjax || $acceptsJson) {
        header('Content-Type: application/json');
        if ($ok) {
            echo json_encode(['success' => true, 'message' => 'Email sent successfully']);
        } else {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Failed to send email. Check inputs and try again.']);
        }
        exit();
    }

    if ($ok) {
        setFlash('success', 'Email sent successfully');
    } else {
        setFlash('danger', 'Failed to send email');
    }
    header('Location: enrollments.php');
    exit();
}

    // Only handle status update AJAX/redirect when update_status is present
    if (isset($_POST['update_status'])) {
        // If it's an AJAX request, return JSON and stop
        $isAjax = isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
        $acceptsJson = isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false;
        if ($isAjax || $acceptsJson) {
            header('Content-Type: application/json');
            if ($ok) {
                echo json_encode([
                    'success' => true,
                    'message' => 'Enrollment status updated successfully',
                    'enrollment_id' => (int)$enrollment_id,
                    'status' => $status,
                ]);
            } else {
                http_response_code(400);
                echo json_encode([
                    'success' => false,
                    'message' => 'Failed to update enrollment status',
                ]);
            }
            exit();
        }

        // Fallback: normal POST/redirect flow
        if ($ok) {
            setFlash('success', 'Enrollment status updated successfully');
        } else {
            setFlash('danger', 'Failed to update enrollment status');
        }
        header('Location: enrollments.php');
        exit();
    }
}

// Page header (admin layout) — only render HTML for non-AJAX flows
$pageTitle = 'Manage Enrollments';
require_once 'includes/header.php';

// Get filter parameters
$status_filter = $_GET['status'] ?? 'all';
$course_filter = $_GET['course_id'] ?? '';

// Build query
$query = "SELECT e.*, c.title as course_title, c.instructor, 
          u.name as student_name, u.email as student_email, 
          u.date_of_birth as student_dob, u.address as student_address 
          FROM enrollments e 
          JOIN courses c ON e.course_id = c.id 
          JOIN users u ON e.student_id = u.id";
          
$params = [];
$where = [];

if ($status_filter !== 'all') {
    $where[] = "e.status = ?";
    $params[] = $status_filter;
}

if (!empty($course_filter)) {
    $where[] = "e.course_id = ?";
    $params[] = $course_filter;
}

if (!empty($where)) {
    $query .= " WHERE " . implode(" AND ", $where);
}

$query .= " ORDER BY e.created_at DESC";

// Get enrollments
$enrollments = fetchAll($query, $params);

// Get all courses for filter
$courses = fetchAll("SELECT id, title FROM courses ORDER BY title");
?>
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
            <div>
                <h2 class="h3 mb-1 d-flex align-items-center gap-2">
                    <i class="bi bi-person-lines-fill text-primary"></i>
                    Manage Enrollments
                </h2>
                <p class="text-muted small mb-0">Review applicants and update enrollment statuses.</p>
            </div>
            <div>
                <a href="#" class="btn btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#filtersModal">
                    <i class="bi bi-funnel me-1"></i> Filters
                </a>
            </div>
        </div>

        <nav aria-label="breadcrumb" class="mb-3">
            <ol class="breadcrumb mb-0 small">
                <li class="breadcrumb-item"><a href="index.php" class="text-decoration-none">Dashboard</a></li>
                <li class="breadcrumb-item active" aria-current="page">Enrollments</li>
            </ol>
        </nav>
        
        
        
        <div class="card shadow-sm border-0">
            <div class="card-body p-0">
                <?php if (empty($enrollments)): ?>
                    <div class="text-center py-5">
                        <div class="text-muted">
                            <i class="bi bi-person-lines-fill" style="font-size: 3rem;"></i>
                            <p class="mt-3 mb-0">No enrollments found</p>
                        </div>
                        <a href="enrollments.php" class="btn btn-outline-primary mt-2">Clear Filters</a>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Student</th>
                                    <th>Course</th>
                                    <th>Instructor</th>
                                    <th>Status</th>
                                    <th>Applied On</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($enrollments as $enrollment): ?>
                                    <tr>
                                        <td>
                                            <div class="fw-semibold"><?php echo htmlspecialchars($enrollment['student_name']); ?></div>
                                            <small class="text-muted"><?php echo htmlspecialchars($enrollment['student_email']); ?></small>
                                        </td>
                                        <td><?php echo htmlspecialchars($enrollment['course_title']); ?></td>
                                        <td><?php echo htmlspecialchars($enrollment['instructor']); ?></td>
                                        <td>
                                            <span class="badge bg-<?php 
                                                echo $enrollment['status'] === 'approved' ? 'success' : 
                                                    ($enrollment['status'] === 'rejected' ? 'danger' : 'warning'); 
                                            ?>">
                                                <?php echo ucfirst($enrollment['status']); ?>
                                            </span>
                                        </td>
                                        <td><?php echo date('M j, Y', strtotime($enrollment['created_at'])); ?></td>
                                        <td>
                                            <button type="button" class="btn btn-sm btn-outline-primary" title="View details" 
                                                    data-bs-toggle="modal" data-bs-target="#viewEnrollmentModal" 
                                                    data-id="<?php echo $enrollment['id']; ?>"
                                                    data-student="<?php echo htmlspecialchars($enrollment['student_name']); ?>"
                                                    data-email="<?php echo htmlspecialchars($enrollment['student_email']); ?>"
                                                    data-dob="<?php echo htmlspecialchars($enrollment['student_dob'] ?? ''); ?>"
                                                    data-address="<?php echo htmlspecialchars($enrollment['student_address'] ?? ''); ?>"
                                                    data-course="<?php echo htmlspecialchars($enrollment['course_title']); ?>"
                                                    data-status="<?php echo $enrollment['status']; ?>"
                                                    data-motivation="<?php echo htmlspecialchars($enrollment['motivation']); ?>"
                                                    data-experience="<?php echo htmlspecialchars($enrollment['experience']); ?>"
                                                    data-goals="<?php echo htmlspecialchars($enrollment['goals']); ?>">
                                                <i class="bi bi-eye"></i> View
                                            </button>
                                            <button type="button" class="btn btn-sm btn-outline-success ms-1" title="Email student"
                                                    data-bs-toggle="modal" data-bs-target="#emailStudentModal"
                                                    data-student="<?php echo htmlspecialchars($enrollment['student_name']); ?>"
                                                    data-email="<?php echo htmlspecialchars($enrollment['student_email']); ?>"
                                                    data-course="<?php echo htmlspecialchars($enrollment['course_title']); ?>">
                                                <i class="bi bi-envelope"></i> Email
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    
    <!-- Filters Modal -->
    <div class="modal fade" id="filtersModal" tabindex="-1" aria-labelledby="filtersModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="GET" action="">
                    <div class="modal-header">
                        <h5 class="modal-title" id="filtersModalLabel">Filter Enrollments</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="status" class="form-label">Status</label>
                            <select class="form-select" id="status" name="status">
                                <option value="all" <?php echo $status_filter === 'all' ? 'selected' : ''; ?>>All Statuses</option>
                                <option value="pending" <?php echo $status_filter === 'pending' ? 'selected' : ''; ?>>Pending</option>
                                <option value="approved" <?php echo $status_filter === 'approved' ? 'selected' : ''; ?>>Approved</option>
                                <option value="rejected" <?php echo $status_filter === 'rejected' ? 'selected' : ''; ?>>Rejected</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="course_id" class="form-label">Course</label>
                            <select class="form-select" id="course_id" name="course_id">
                                <option value="">All Courses</option>
                                <?php foreach ($courses as $course): ?>
                                    <option value="<?php echo $course['id']; ?>" 
                                        <?php echo $course_filter == $course['id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($course['title']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Apply Filters</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Email Student Modal -->
    <div class="modal fade" id="emailStudentModal" tabindex="-1" aria-labelledby="emailStudentModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST" action="" novalidate>
                    <div class="modal-header bg-light border-0">
                        <h5 class="modal-title d-flex align-items-center gap-2" id="emailStudentModalLabel">
                            <i class="bi bi-envelope text-primary"></i> Email Student
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="send_email" value="1">
                        <input type="hidden" name="to" id="email_to">
                        <input type="hidden" name="name" id="email_name">

                        <div class="mb-3">
                            <label class="form-label">To</label>
                            <div class="d-flex align-items-center gap-2 form-control bg-light" id="email_to_readonly" aria-live="polite"></div>
                        </div>

                        <div class="mb-3">
                            <div class="d-flex justify-content-between align-items-center">
                                <label for="email_subject" class="form-label mb-1">Subject</label>
                                <small class="text-muted" id="email_subject_counter">0/120</small>
                            </div>
                            <input type="text" class="form-control" id="email_subject" name="subject" placeholder="Subject" maxlength="120" required aria-describedby="email_subject_counter">
                        </div>

                        <div class="mb-2">
                            <div class="d-flex justify-content-between align-items-center">
                                <label for="email_message" class="form-label mb-1">Message</label>
                                <small class="text-muted" id="email_message_counter">0</small>
                            </div>
                            <textarea class="form-control" id="email_message" name="message" rows="7" placeholder="Write your message..." required aria-describedby="email_help"></textarea>
                            <div id="email_help" class="form-text d-flex justify-content-between">
                                <span>Basic formatting supported. HTML will be sent as rich text.</span>
                                <span>Tip: Press Ctrl+Enter to send</span>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal"><i class="bi bi-x-lg"></i> Cancel</button>
                        <button type="submit" class="btn btn-success"><i class="bi bi-send"></i> Send</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- View Enrollment Modal -->
    <div class="modal fade" id="viewEnrollmentModal" tabindex="-1" aria-labelledby="viewEnrollmentModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <form method="POST" action="">
                    <input type="hidden" name="enrollment_id" id="enrollment_id">
                    <div class="modal-header border-0 pb-0">
                        <div>
                            <h5 class="modal-title d-flex align-items-center gap-2" id="viewEnrollmentModalLabel">
                                <i class="bi bi-person-badge"></i> Enrollment Details
                            </h5>
                            <small class="text-muted">Review applicant information and update status</small>
                        </div>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row g-3 mb-4">
                            <div class="col-md-6">
                                <div class="card shadow-sm h-100">
                                    <div class="card-header bg-white border-0 pb-0">
                                        <h6 class="card-title mb-0 d-flex align-items-center gap-2"><i class="bi bi-person"></i> Student Information</h6>
                                    </div>
                                    <div class="card-body">
                                        <div class="mb-2 d-flex align-items-center gap-2 text-break">
                                            <i class="bi bi-person-badge text-secondary"></i>
                                            <span id="student_info" class="fw-medium"></span>
                                        </div>
                                        <div class="mb-2 d-flex align-items-center gap-2 text-break">
                                            <i class="bi bi-envelope text-secondary"></i>
                                            <span id="student_email" class="text-muted"></span>
                                        </div>
                                        <div class="mb-2 d-flex align-items-center gap-2">
                                            <i class="bi bi-calendar-event text-secondary"></i>
                                            <span id="student_dob" class="text-muted"></span>
                                        </div>
                                        <div class="d-flex align-items-start gap-2 text-break">
                                            <i class="bi bi-geo-alt text-secondary mt-1"></i>
                                            <span id="student_address" class="text-muted"></span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="card shadow-sm h-100">
                                    <div class="card-header bg-white border-0 pb-0">
                                        <h6 class="card-title mb-0 d-flex align-items-center gap-2"><i class="bi bi-journal-bookmark"></i> Course Information</h6>
                                    </div>
                                    <div class="card-body">
                                        <div class="d-flex align-items-center gap-2 text-break">
                                            <i class="bi bi-book text-secondary"></i>
                                            <span id="course_info" class="fw-medium"></span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="row g-3">
                            <div class="col-12">
                                <div class="card shadow-sm">
                                    <div class="card-header bg-white border-0 pb-0">
                                        <h6 class="card-title mb-0 d-flex align-items-center gap-2"><i class="bi bi-lightbulb"></i> Motivation</h6>
                                    </div>
                                    <div class="card-body">
                                        <div class="p-3 bg-light rounded" id="motivation"></div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="card shadow-sm h-100">
                                    <div class="card-header bg-white border-0 pb-0">
                                        <h6 class="card-title mb-0 d-flex align-items-center gap-2"><i class="bi bi-briefcase"></i> Relevant Experience</h6>
                                    </div>
                                    <div class="card-body">
                                        <div class="p-3 bg-light rounded min-h-100" id="experience"></div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="card shadow-sm h-100">
                                    <div class="card-header bg-white border-0 pb-0">
                                        <h6 class="card-title mb-0 d-flex align-items-center gap-2"><i class="bi bi-bullseye"></i> Learning Goals</h6>
                                    </div>
                                    <div class="card-body">
                                        <div class="p-3 bg-light rounded min-h-100" id="goals"></div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="mt-4">
                            <label for="view_status" class="form-label fw-semibold">Update Status</label>
                            <div class="row g-2 align-items-center">
                                <div class="col-md-6">
                                    <div class="form-floating">
                                        <select class="form-select" id="view_status" name="status" required>
                                            <option value="pending">Pending</option>
                                            <option value="approved">Approved</option>
                                            <option value="rejected">Rejected</option>
                                        </select>
                                        <label for="view_status"><i class="bi bi-flag"></i> Status</label>
                                    </div>
                                </div>
                                <div class="col-md-6 d-flex justify-content-md-end mt-2 mt-md-0">
                                    <!-- Submit button removed; footer button remains as the primary action -->
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" name="update_status" class="btn btn-primary">Update Status</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <script>
    // Handle view enrollment modal
    const viewEnrollmentModal = document.getElementById('viewEnrollmentModal');
    if (viewEnrollmentModal) {
        viewEnrollmentModal.addEventListener('show.bs.modal', function (event) {
            const button = event.relatedTarget;
            
            // Extract info from data-bs-* attributes
            const enrollmentId = button.getAttribute('data-id');
            const studentName = button.getAttribute('data-student');
            const studentEmail = button.getAttribute('data-email');
            const courseTitle = button.getAttribute('data-course');
            const status = button.getAttribute('data-status');
            const motivation = button.getAttribute('data-motivation');
            const experience = button.getAttribute('data-experience');
            const goals = button.getAttribute('data-goals');
            const studentDob = button.getAttribute('data-dob');
            const studentAddress = button.getAttribute('data-address');
            
            // Update the modal's content
            document.getElementById('enrollment_id').value = enrollmentId;
            document.getElementById('student_info').textContent = studentName;
            document.getElementById('student_email').textContent = studentEmail || 'No email provided';
            document.getElementById('student_dob').textContent = studentDob ? `Birthday: ${studentDob}` : 'Birthday: Not provided';
            document.getElementById('student_address').textContent = studentAddress ? `Address: ${studentAddress}` : 'Address: Not provided';
            document.getElementById('course_info').textContent = courseTitle;
            document.getElementById('motivation').textContent = motivation || 'Not provided';
            document.getElementById('experience').textContent = experience || 'Not provided';
            document.getElementById('goals').textContent = goals || 'Not provided';
            
            // Set the current status
            const statusSelect = document.getElementById('view_status');
            for (let i = 0; i < statusSelect.options.length; i++) {
                if (statusSelect.options[i].value === status) {
                    statusSelect.selectedIndex = i;
                    break;
                }
            }
            // Store reference to the trigger button for later UI update
            viewEnrollmentModal._triggerButton = button;
        });
    }

    // Intercept form submit and send via AJAX to avoid full page flash/white screen
    (function(){
        const form = viewEnrollmentModal ? viewEnrollmentModal.querySelector('form') : null;
        if (!form) return;

        const footerPrimaryBtn = form.querySelector('.modal-footer button[type="submit"]');
        const bodyPrimaryBtn = form.querySelector('.modal-body button[type="submit"]');

        const setLoading = (isLoading) => {
            [footerPrimaryBtn, bodyPrimaryBtn].forEach(btn => {
                if (!btn) return;
                btn.disabled = isLoading;
                const icon = btn.querySelector('i');
                if (isLoading) {
                    btn.dataset.originalHtml = btn.innerHTML;
                    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>Saving...';
                } else if (btn.dataset.originalHtml) {
                    btn.innerHTML = btn.dataset.originalHtml;
                    delete btn.dataset.originalHtml;
                }
            });
        };

        form.addEventListener('submit', async function(e){
            e.preventDefault();
            const fd = new FormData(form);
            // Ensure the flag is present so PHP branch triggers
            if (!fd.has('update_status')) fd.append('update_status', '1');

            setLoading(true);
            try {
                const res = await fetch(window.location.href, {
                    method: 'POST',
                    body: fd,
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json',
                    },
                    credentials: 'same-origin',
                });
                const ct = res.headers.get('content-type') || '';
                let data;
                if (ct.includes('application/json')) {
                    data = await res.json();
                } else {
                    const text = await res.text();
                    throw new Error(text && text.trim().startsWith('<!DOCTYPE') ? 'Server returned HTML instead of JSON (possible redirect or error). Please refresh and try again.' : (text || 'Unexpected non-JSON response'));
                }
                if (!res.ok || !data.success) throw new Error(data && data.message ? data.message : 'Request failed');

                // Update the badge in the corresponding table row
                const triggerBtn = viewEnrollmentModal._triggerButton;
                if (triggerBtn) {
                    // update its data-status attribute
                    triggerBtn.setAttribute('data-status', data.status);
                    const row = triggerBtn.closest('tr');
                    const badge = row ? row.querySelector('span.badge') : null;
                    if (badge) {
                        badge.textContent = data.status.charAt(0).toUpperCase() + data.status.slice(1);
                        badge.classList.remove('bg-success','bg-danger','bg-warning');
                        badge.classList.add(data.status === 'approved' ? 'bg-success' : (data.status === 'rejected' ? 'bg-danger' : 'bg-warning'));
                    }
                }

                // Show a temporary toast/alert at top of page if available, else use alert()
                try {
                    const alertHost = document.createElement('div');
                    alertHost.innerHTML = '<div class="alert alert-success alert-dismissible fade show position-fixed top-0 start-50 translate-middle-x mt-3 shadow" role="alert" style="z-index: 2000; min-width: 280px;">' +
                        '<i class="bi bi-check-circle me-2"></i>' + (data.message || 'Updated') +
                        '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>' +
                    '</div>';
                    const toastEl = alertHost.firstChild;
                    document.body.appendChild(toastEl);
                    // Auto-hide after ~2 seconds, then remove after fade transition (~150-200ms)
                    setTimeout(() => {
                        toastEl.classList.remove('show');
                        setTimeout(() => { if (toastEl && toastEl.parentNode) toastEl.parentNode.removeChild(toastEl); }, 200);
                    }, 2000);
                } catch(_) { /* no-op */ }

                // Close modal
                const bsModal = bootstrap.Modal.getInstance(viewEnrollmentModal) || new bootstrap.Modal(viewEnrollmentModal);
                bsModal.hide();
            } catch(err) {
                // Show inline error in modal footer
                let errBox = form.querySelector('.ajax-error');
                if (!errBox) {
                    errBox = document.createElement('div');
                    errBox.className = 'ajax-error alert alert-danger w-100 mt-2';
                    const footer = form.querySelector('.modal-footer');
                    (footer || form).prepend(errBox);
                }
                errBox.textContent = err.message || 'Failed to update enrollment status';
            } finally {
                setLoading(false);
            }
        });
    })();

    // Email modal populate
    (function(){
        const emailModal = document.getElementById('emailStudentModal');
        if (!emailModal) return;
        emailModal.addEventListener('show.bs.modal', function (event) {
            const btn = event.relatedTarget;
            const student = btn.getAttribute('data-student') || '';
            const email = btn.getAttribute('data-email') || '';
            const course = btn.getAttribute('data-course') || '';
            emailModal.querySelector('#email_to').value = email;
            emailModal.querySelector('#email_name').value = student;

            // Render recipient with avatar initials
            const host = emailModal.querySelector('#email_to_readonly');
            if (host) {
                const initials = (student || email).split(/\s+/).map(s => s[0]).join('').toUpperCase().slice(0,2);
                host.innerHTML = `<span class="rounded-circle bg-primary text-white d-inline-flex align-items-center justify-content-center" style="width:28px;height:28px;font-size:12px;">${initials}</span>
                    <span class="ms-2">${student} &lt;${email}&gt;</span>`;
            }

            const subj = emailModal.querySelector('#email_subject');
            if (subj && !subj.value) subj.value = `Regarding your application for ${course}`;

            // Update counters on open
            updateCounters();
        });

        // Counters and shortcuts
        const subjectEl = emailModal.querySelector('#email_subject');
        const messageEl = emailModal.querySelector('#email_message');
        const subjectCounter = emailModal.querySelector('#email_subject_counter');
        const messageCounter = emailModal.querySelector('#email_message_counter');

        function updateCounters(){
            if (subjectEl && subjectCounter) subjectCounter.textContent = `${subjectEl.value.length}/120`;
            if (messageEl && messageCounter) messageCounter.textContent = `${messageEl.value.length}`;
        }
        ['input','keyup'].forEach(ev => {
            subjectEl && subjectEl.addEventListener(ev, updateCounters);
            messageEl && messageEl.addEventListener(ev, updateCounters);
        });

        // Ctrl+Enter to submit
        [subjectEl, messageEl].forEach(el => {
            el && el.addEventListener('keydown', (e) => {
                if ((e.ctrlKey || e.metaKey) && e.key === 'Enter') {
                    const form = emailModal.querySelector('form');
                    if (form) form.requestSubmit();
                }
            });
        });

        // AJAX submit
        const form = emailModal.querySelector('form');
        form.addEventListener('submit', async function(e){
            e.preventDefault();
            const fd = new FormData(form);
            if (!fd.has('send_email')) fd.append('send_email','1');
            const submitBtn = form.querySelector('button[type="submit"]');
            const originalHtml = submitBtn.innerHTML;
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Sending...';
            try {
                const res = await fetch(window.location.href, {
                    method: 'POST',
                    body: fd,
                    headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
                    credentials: 'same-origin'
                });
                const data = await res.json().catch(() => ({ success: false, message: 'Unexpected response' }));
                if (!res.ok || !data.success) throw new Error(data.message || 'Failed to send');
                // Toast success
                const alertHost = document.createElement('div');
                alertHost.innerHTML = '<div class="alert alert-success alert-dismissible fade show position-fixed top-0 start-50 translate-middle-x mt-3 shadow" role="alert" style="z-index: 2000; min-width: 280px;">' +
                    '<i class="bi bi-check-circle me-2"></i>' + (data.message || 'Email sent') +
                    '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>' +
                '</div>';
                const toastEl = alertHost.firstChild; document.body.appendChild(toastEl);
                setTimeout(() => { toastEl.classList.remove('show'); setTimeout(() => { toastEl.remove(); }, 200); }, 2000);
                // Close modal
                (bootstrap.Modal.getInstance(emailModal) || new bootstrap.Modal(emailModal)).hide();
                form.reset();
                updateCounters();
            } catch(err) {
                let errBox = form.querySelector('.ajax-error');
                if (!errBox) { errBox = document.createElement('div'); errBox.className = 'ajax-error alert alert-danger w-100 mt-2'; form.querySelector('.modal-footer').prepend(errBox); }
                errBox.textContent = err.message || 'Failed to send email';
            } finally {
                submitBtn.disabled = false; submitBtn.innerHTML = originalHtml;
            }
        });
    })();
    </script>
<?php require_once 'includes/footer.php'; ?>
