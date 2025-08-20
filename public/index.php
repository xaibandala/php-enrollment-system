<?php
// public/index.php
?><!doctype html>
<html lang="en" data-bs-theme="light">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>PHPMailer MVP</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
  <div class="container py-5">
    <div class="row justify-content-center">
      <div class="col-lg-8">
        <div class="card shadow-sm">
          <div class="card-body p-4">
            <h1 class="h3 mb-3">Send an Email (PHPMailer)</h1>
            <form id="mailForm" action="send.php" method="post" enctype="multipart/form-data" class="needs-validation" novalidate>
              <div class="row g-3">
                <div class="col-md-6">
                  <label class="form-label">To Email</label>
                  <input type="email" class="form-control" name="to" placeholder="recipient@example.com" required>
                  <div class="invalid-feedback">Please enter a valid email.</div>
                </div>
                <div class="col-md-6">
                  <label class="form-label">Subject</label>
                  <input type="text" class="form-control" name="subject" placeholder="Hello from PHPMailer" required>
                  <div class="invalid-feedback">Subject is required.</div>
                </div>
                <div class="col-12">
                  <label class="form-label">Message</label>
                  <textarea class="form-control" name="message" rows="6" placeholder="Write your message..." required></textarea>
                  <div class="invalid-feedback">Message is required.</div>
                </div>
                <div class="col-md-6">
                  <label class="form-label">Attachment (optional)</label>
                  <input type="file" class="form-control" name="attachment">
                </div>
                <div class="col-md-6">
                  <label class="form-label">CC (optional)</label>
                  <input type="email" class="form-control" name="cc" placeholder="cc@example.com">
                </div>
              </div>
              <div class="d-flex align-items-center gap-3 mt-4">
                <button class="btn btn-primary" type="submit">Send</button>
                <div id="status" class="text-muted"></div>
              </div>
            </form>
          </div>
        </div>
        <p class="text-center small text-muted mt-3">Built with Bootstrap 5 + PHPMailer</p>
      </div>
    </div>
  </div>

  <script>
  // Bootstrap validation
  (() => {
    'use strict'
    const form = document.getElementById('mailForm');
    form.addEventListener('submit', (event) => {
      if (!form.checkValidity()) {
        event.preventDefault();
        event.stopPropagation();
      }
      form.classList.add('was-validated');
    }, false)
  })();
  </script>
</body>
</html>
