<?php
$pageTitle = 'User Enrollments';
require_once 'includes/header.php';

// Validate target user id
$userId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($userId <= 0) {
    $_SESSION['flash'] = ['type' => 'danger', 'message' => 'Invalid user ID.'];
    header('Location: users.php');
    exit();
}

// Fetch user details
$user = fetchOne("SELECT id, name, email, role, registration_date FROM users WHERE id = ?", [$userId]);
if (!$user) {
    $_SESSION['flash'] = ['type' => 'danger', 'message' => 'User not found.'];
    header('Location: users.php');
    exit();
}

// Pagination setup
$perPage = 10;
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;

// Count total enrollments for this user
$countRow = fetchOne("SELECT COUNT(*) AS cnt FROM enrollments WHERE student_id = ?", [$userId]);
$total = isset($countRow['cnt']) ? (int)$countRow['cnt'] : 0;
$totalPages = max(1, (int)ceil($total / $perPage));
if ($page > $totalPages) { $page = $totalPages; }
$offset = ($page - 1) * $perPage;

// Fetch user's enrollments with course info (paged)
$enrollments = fetchAll(
    "SELECT e.id AS enrollment_id,
            e.status,
            e.created_at,
            c.id AS course_id,
            c.title AS course_title,
            c.instructor,
            c.category,
            c.duration
     FROM enrollments e
     JOIN courses c ON e.course_id = c.id
     WHERE e.student_id = ?
     ORDER BY e.created_at DESC
     LIMIT {$perPage} OFFSET {$offset}",
    [$userId]
);
?>

<nav aria-label="breadcrumb" class="mb-3">
  <ol class="breadcrumb mb-0 small">
    <li class="breadcrumb-item"><a href="index.php" class="text-decoration-none">Dashboard</a></li>
    <li class="breadcrumb-item"><a href="users.php" class="text-decoration-none">Users</a></li>
    <li class="breadcrumb-item active" aria-current="page">Enrollments</li>
  </ol>
</nav>

<div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-3">
  <div class="d-flex align-items-center gap-3">
    <div class="rounded-circle bg-primary text-white d-inline-flex align-items-center justify-content-center" style="width:44px;height:44px;font-weight:700;">
      <?php echo strtoupper(substr($user['name'], 0, 1)); ?>
    </div>
    <div>
      <div class="d-flex align-items-center gap-2">
        <h2 class="h5 mb-0">Enrollments</h2>
        <span class="badge rounded-pill bg-light text-muted border">User</span>
      </div>
      <div class="text-muted small">
        <span class="me-2"><i class="bi bi-person"></i> <?php echo htmlspecialchars($user['name']); ?></span>
        <span class="me-2"><i class="bi bi-envelope"></i> <?php echo htmlspecialchars($user['email']); ?></span>
        <span class="me-2"><i class="bi bi-person-badge"></i> <?php echo ucfirst($user['role']); ?></span>
        <span><i class="bi bi-calendar-event"></i> Joined <?php echo date('M j, Y', strtotime($user['registration_date'])); ?></span>
      </div>
    </div>
  </div>
  <div class="ms-auto">
    <a href="users.php" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left"></i> Back to Users</a>
  </div>
</div>

<div class="card shadow-sm border-0">
  <div class="card-body p-0">
    <?php if (empty($enrollments)): ?>
      <div class="text-center py-5">
        <div class="text-muted">
          <i class="bi bi-mortarboard" style="font-size: 3rem;"></i>
          <p class="mt-3 mb-1 fw-semibold">No enrollments found</p>
          <p class="text-muted small mb-0">This user has not applied to or been approved for any courses.</p>
        </div>
        <a href="users.php" class="btn btn-outline-primary mt-3"><i class="bi bi-arrow-left"></i> Back to Users</a>
      </div>
    <?php else: ?>
      <div class="table-responsive">
        <table class="table table-striped table-hover table-sm align-middle mb-0">
          <thead class="table-light">
            <tr>
              <th>Course</th>
              <th class="d-none d-md-table-cell">Instructor</th>
              <th class="d-none d-lg-table-cell">Category</th>
              <th class="d-none d-lg-table-cell">Duration</th>
              <th>Status</th>
              <th class="d-none d-md-table-cell">Applied</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($enrollments as $en): ?>
              <tr>
                <td>
                  <div class="fw-semibold"><?php echo htmlspecialchars($en['course_title']); ?></div>
                  <div class="text-muted small d-md-none">
                    <span class="me-2"><?php echo htmlspecialchars($en['instructor']); ?></span>
                    <span>(<?php echo htmlspecialchars($en['category']); ?>)</span>
                  </div>
                </td>
                <td class="d-none d-md-table-cell"><?php echo htmlspecialchars($en['instructor']); ?></td>
                <td class="d-none d-lg-table-cell"><?php echo htmlspecialchars($en['category']); ?></td>
                <td class="d-none d-lg-table-cell"><?php echo htmlspecialchars($en['duration']); ?></td>
                <td>
                  <?php
                    $badge = 'secondary';
                    if ($en['status'] === 'approved') $badge = 'success';
                    elseif ($en['status'] === 'pending') $badge = 'warning';
                    elseif ($en['status'] === 'rejected') $badge = 'danger';
                  ?>
                  <span class="badge rounded-pill bg-<?php echo $badge; ?>"><?php echo ucfirst($en['status']); ?></span>
                </td>
                <td class="d-none d-md-table-cell"><?php echo date('M j, Y', strtotime($en['created_at'])); ?></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
      <?php
        if ($total > 0) {
          $start = $offset + 1;
          $end = $offset + count($enrollments);
        } else {
          $start = 0; $end = 0;
        }
      ?>
      <div class="d-flex flex-wrap justify-content-between align-items-center p-3 border-top gap-2">
        <div class="text-muted small">
          Showing <?php echo (int)$start; ?>–<?php echo (int)$end; ?> of <?php echo (int)$total; ?>
        </div>
        <?php if ($totalPages > 1): ?>
        <nav aria-label="Enrollment pagination">
          <ul class="pagination pagination-sm mb-0">
            <?php
              $prevPage = max(1, $page - 1);
              $nextPage = min($totalPages, $page + 1);
            ?>
            <li class="page-item <?php echo $page <= 1 ? 'disabled' : ''; ?>">
              <a class="page-link" href="user_enrollments.php?id=<?php echo (int)$userId; ?>&page=<?php echo (int)$prevPage; ?>" aria-label="Previous" tabindex="<?php echo $page <= 1 ? '-1' : '0'; ?>">&laquo;</a>
            </li>
            <?php
              // Windowed pagination (max 5 numbers)
              $window = 5;
              $half = (int)floor($window / 2);
              $startPage = max(1, $page - $half);
              $endPage = min($totalPages, $startPage + $window - 1);
              if ($endPage - $startPage + 1 < $window) {
                $startPage = max(1, $endPage - $window + 1);
              }
              for ($p = $startPage; $p <= $endPage; $p++):
            ?>
              <li class="page-item <?php echo $p === $page ? 'active' : ''; ?>">
                <a class="page-link" href="user_enrollments.php?id=<?php echo (int)$userId; ?>&page=<?php echo (int)$p; ?>"><?php echo (int)$p; ?></a>
              </li>
            <?php endfor; ?>
            <li class="page-item <?php echo $page >= $totalPages ? 'disabled' : ''; ?>">
              <a class="page-link" href="user_enrollments.php?id=<?php echo (int)$userId; ?>&page=<?php echo (int)$nextPage; ?>" aria-label="Next" tabindex="<?php echo $page >= $totalPages ? '-1' : '0'; ?>">&raquo;</a>
            </li>
          </ul>
        </nav>
        <?php endif; ?>
      </div>
    <?php endif; ?>
  </div>
</div>

<?php require_once 'includes/footer.php'; ?>
