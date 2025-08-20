<?php
require_once __DIR__ . '/includes/session.php';
require_once __DIR__ . '/config/database.php';
requireUser();
// Fetch logged-in user and their enrollments
$userId = isset($_COOKIE['user_id']) ? (int)$_COOKIE['user_id'] : 0;
$user = fetchOne("SELECT id, name FROM users WHERE id = ?", [$userId]) ?: ['id' => $userId, 'name' => '[Name]'];
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
     WHERE e.student_id = ? AND e.status = 'approved'
     ORDER BY e.created_at DESC",
    [$userId]
);
// Derive registration status
$hasApproved = false; $hasPending = false;
foreach ($enrollments as $en) { if ($en['status'] === 'approved') { $hasApproved = true; } if ($en['status'] === 'pending') { $hasPending = true; } }
$registrationStatus = $hasApproved ? 'Enrolled' : ($hasPending ? 'Pending' : 'No Enrollments');
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Certificate of Registration (COR)</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
  <style>
    :root {
      --ink: #1f2937;
      --muted: #6b7280;
      --border: #dde1e7;
      --accent: #0ea5e9;
      --bg: #f5f7fb;
    }
    html, body { height: 100%; }
    body {
      margin: 0;
      font-family: system-ui, -apple-system, Segoe UI, Roboto, Ubuntu, Cantarell, 'Helvetica Neue', Arial, 'Noto Sans', sans-serif;
      color: var(--ink);
      background: var(--bg);
      -webkit-print-color-adjust: exact;
      print-color-adjust: exact;
    }
    /* Page canvas for screen preview */
    .page {
      box-sizing: border-box;
      width: 210mm; /* A4 width */
      min-height: 297mm; /* A4 height */
      margin: 16px auto;
      background: white;
      box-shadow: 0 10px 30px rgba(18,38,63,.12);
      position: relative;
      padding: 18mm 16mm; /* inner page margins */
    }
    /* Watermark */
    .page::after {
      content: 'OFFICIAL';
      position: absolute;
      inset: 0;
      display: block;
      font-size: 90mm;
      font-weight: 800;
      color: #0ea5e9;
      opacity: .05;
      letter-spacing: 6px;
      transform: rotate(-24deg);
      transform-origin: center;
      text-align: center;
      line-height: 297mm;
      pointer-events: none;
      user-select: none;
    }
    /* Header */
    header[role="banner"] {
      display: grid;
      grid-template-columns: 64px 1fr auto;
      gap: 16px;
      align-items: center;
      border-bottom: 2px solid var(--border);
      padding-bottom: 12px;
      margin-bottom: 12px;
    }
    .logo {
      width: 64px; height: 64px;
      border-radius: 8px;
      background: linear-gradient(135deg, #4f46e5 0%, #0ea5e9 100%);
      display: grid; place-items: center;
      color: #fff; font-weight: 800;
      letter-spacing: .5px;
    }
    .school h1 { font-size: 18px; margin: 0; }
    .school p { margin: 2px 0 0; color: var(--muted); font-size: 12px; }
    .meta {
      text-align: right;
      font-size: 12px;
    }
    .meta div { margin-bottom: 4px; }
    .meta .label { color: var(--muted); margin-right: 6px; }

    /* Title */
    .title {
      text-align: center;
      margin: 10px 0 14px;
    }
    .title h2 { margin: 0; font-size: 20px; letter-spacing: .5px; }
    .title .term { color: var(--muted); font-size: 13px; }

    /* Student details */
    .grid {
      display: grid;
      grid-template-columns: repeat(2, 1fr);
      gap: 8px 16px;
      margin: 12px 0 16px;
    }
    .grid .field { font-size: 13px; }
    .grid .label { color: var(--muted); display: inline-block; width: 140px; }
    .badge {
      display: inline-block;
      padding: 2px 8px;
      font-size: 12px;
      border-radius: 12px;
      background: #d1fae5;
      color: #065f46;
      border: 1px solid #a7f3d0;
    }

    /* Subjects table */
    table {
      width: 100%;
      border-collapse: collapse;
      margin-top: 6px;
      font-size: 12.5px;
    }
    caption { text-align: left; color: var(--muted); font-size: 12px; margin-bottom: 6px; }
    thead th {
      text-align: left;
      border-bottom: 2px solid var(--border);
      padding: 8px 8px;
      background: #f9fafb;
    }
    tbody td {
      border-bottom: 1px solid var(--border);
      padding: 8px 8px;
      vertical-align: top;
    }
    tfoot td {
      padding: 8px;
      border-top: 2px solid var(--border);
      font-weight: 600;
    }
    .units { text-align: center; width: 60px; }

    /* Signatures */
    .signatures {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 18mm;
      margin-top: 14mm;
    }
    .sig {
      border-top: 1px dashed var(--border);
      padding-top: 8mm;
      text-align: center;
      font-size: 12px;
    }
    .sig .name { font-weight: 700; }
    .sig .role { color: var(--muted); }

    /* Verification */
    .verify {
      display: grid; grid-template-columns: 1fr auto; gap: 16px;
      align-items: center;
      margin-top: 10mm;
      padding: 10px 12px;
      border: 1px dashed var(--border);
      background: #fcfcfd;
      border-radius: 6px;
      font-size: 12px;
    }
    .verify a { color: var(--accent); text-decoration: none; }
    .verify a:hover { text-decoration: underline; }
    .qr {
      width: 96px; height: 96px; border: 1px solid var(--border);
      border-radius: 4px;
      display: grid; place-items: center; color: var(--muted); font-size: 11px;
      background:
        linear-gradient(90deg, transparent 47%, var(--border) 48%, var(--border) 52%, transparent 53%),
        linear-gradient(0deg, transparent 47%, var(--border) 48%, var(--border) 52%, transparent 53%);
      background-size: 16px 16px;
    }

    /* Toolbar */
    .toolbar {
      display: flex; justify-content: flex-end; gap: 8px;
      width: 210mm; margin: 12px auto 0;
    }
    .btn {
      appearance: none; border: 1px solid var(--border); background: white;
      border-radius: 6px; padding: 8px 12px; font: inherit; cursor: pointer;
    }
    .btn-primary { border-color: #93c5fd; background: #dbeafe; color: #1e40af; }

    /* Responsive tweaks */
    .table-wrap { width: 100%; overflow-x: auto; }
    .table-wrap table { min-width: 600px; }

    @media (max-width: 768px) {
      .page { width: 100%; margin: 8px 0; padding: 12px; box-shadow: none; }
      header[role="banner"] { grid-template-columns: 48px 1fr; gap: 12px; }
      .logo { width: 48px; height: 48px; border-radius: 6px; }
      .school h1 { font-size: 16px; }
      .school p { font-size: 11px; }
      .meta { text-align: left; font-size: 11px; }
      .grid { grid-template-columns: 1fr; gap: 6px 10px; }
    }

    /* Print rules */
    @page { size: A4; margin: 12mm; }
    @media print {
      body { background: #fff; margin: 0; }
      .toolbar, .no-print, nav, .navbar, header .print-hide { display: none !important; }
      .page { box-shadow: none; margin: 0; width: auto; min-height: auto; padding: 12mm; }
      .page::after { display: none; }

      /* Force responsive table columns to show in print */
      .d-md-table-cell, .d-lg-table-cell { display: table-cell !important; }

      /* Tighter spacing for print */
      header[role="banner"] { margin-bottom: 8px; padding-bottom: 8px; border-bottom-width: 1px; }
      .title h2 { font-size: 18px; margin: 4px 0 6px; }
      .grid { grid-template-columns: 1fr; gap: 4px 8px; margin: 8px 0 10px; }
      .meta { font-size: 11px; }

      /* Table typography and spacing */
      table { font-size: 11.5px; margin-top: 4px; }
      thead th { padding: 6px 6px; }
      tbody td { padding: 6px 6px; }
      caption { margin-bottom: 4px; font-size: 11px; }

      /* Avoid breaking rows/signatures across pages */
      table, thead, tbody, tfoot, tr, td, th { page-break-inside: avoid !important; }
      .signatures { page-break-inside: avoid; gap: 12mm; margin-top: 12mm; }
      .verify { page-break-inside: avoid; }
    }
  </style>
</head>
<body>
  <?php include __DIR__ . '/includes/navbar.php'; ?>

  <div class="toolbar no-print" role="group" aria-label="Actions">
    <button class="btn btn-primary" onclick="window.print()" aria-label="Print Certificate">Print COR</button>
  </div>

  <main class="page" role="main" aria-labelledby="doc-title">
    <header role="banner" aria-label="School Header">
      <div class="logo" aria-hidden="true">LOGO</div>
      <div class="school">
        <h1 id="doc-title">EduEnroll</h1>
        <p>[Address]</p>
      </div>
      <div class="meta" aria-label="Document Metadata">
        <div><span class="label">COR No.:</span><strong>[2025-000001]</strong></div>
        <div><span class="label">Date of Issue:</span><strong>[August 20, 2025]</strong></div>
        <div><span class="label">Status:</span><span class="badge" aria-label="Registration Status"><?php echo htmlspecialchars($registrationStatus); ?></span></div>
      </div>
    </header>

    <section class="title" aria-label="Document Title">
      <h2>Certificate of Registration</h2>
    </section>

    <section aria-labelledby="student-section">
      <h3 id="student-section" style="position:absolute; left:-9999px;">Student Information</h3>
      <div class="grid">
        <div class="field"><span class="label">Student Name:</span><strong><?php echo htmlspecialchars($user['name']); ?></strong></div>
        <div class="field"><span class="label">Student ID:</span><strong><?php echo htmlspecialchars((string)$user['id']); ?></strong></div>
      </div>
    </section>

    <section aria-labelledby="subjects-section">
      <h3 id="subjects-section" style="position:absolute; left:-9999px;">Subjects</h3>
      <div class="table-wrap">
      <table role="table" aria-describedby="subjects-caption">
        <caption id="subjects-caption">List of enrolled subjects for the term</caption>
        <thead>
          <tr>
            <th scope="col">Course</th>
            <th scope="col" class="d-none d-md-table-cell">Instructor</th>
            <th scope="col" class="d-none d-lg-table-cell">Category</th>
            <th scope="col" class="d-none d-lg-table-cell">Duration</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($enrollments)): ?>
            <tr>
              <td colspan="4" style="text-align:center; color:#6b7280;">No enrollments found.</td>
            </tr>
          <?php else: ?>
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
            </tr>
            <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
      </div>
    </section>

    <section class="signatures" aria-label="Signatures">
      <div class="sig" aria-label="Student Signature Panel">
        <div class="name"><?php echo htmlspecialchars($user['name']); ?></div>
        <div class="role">Student</div>
      </div>
      <div class="sig" aria-label="Registrar Signature Panel">
        <div class="name">Registrar</div>
        <div class="role">University Registrar</div>
      </div>
    </section>

    <section class="verify" aria-label="Verification">
      <div>
        To verify the authenticity and validity of this COR, visit
        <a href="#" rel="noopener" aria-label="Verification Link">https://your-university.example/verify</a>
        and enter the COR No. <strong>2025-000001</strong>.
        <div style="margin-top:6px; color: var(--muted); font-size: 11px; border-top: 1px dashed var(--border); padding-top: 6px;">
          Note: This document is valid only if issued by the Registrar's Office. Subject to confirmation upon request.
        </div>
      </div>
      <div class="qr" role="img" aria-label="QR code placeholder">QR</div>
    </section>
  </main>

  <script>
    // Optional: focus the print button for quick access
    window.addEventListener('DOMContentLoaded', function(){
      var btn = document.querySelector('.toolbar .btn');
      if (btn) btn.focus();
    });
  </script>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
