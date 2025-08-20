<style>
  .navbar-gradient { background: linear-gradient(135deg, #4f46e5 0%, #0ea5e9 100%); }
  .navbar .dropdown-menu { border-radius: .75rem; box-shadow: 0 10px 30px rgba(18,38,63,.08); }
  .navbar .dropdown-item { border-radius: .5rem; }
  .avatar-pill { width: 32px; height: 32px; border-radius: 9999px; background: #eef2ff; color: #4f46e5; font-weight: 700; display: inline-flex; align-items: center; justify-content: center; font-size: .85rem; }
  .navbar-brand { font-weight: 700; letter-spacing: .2px; }
  .nav-link { font-weight: 500; }
</style>
<nav class="navbar navbar-expand-lg navbar-dark navbar-gradient shadow-sm">
    <div class="container">
        <a class="navbar-brand" href="/php-enrollment-system/dashboard.php">Enrollment System</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" 
                aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav me-auto">
                <li class="nav-item">
                    <a class="nav-link" href="/php-enrollment-system/dashboard.php">
                        <i class="bi bi-speedometer2 me-1"></i> Dashboard
                    </a>
                </li>
            </ul>
            <ul class="navbar-nav">
                <li class="nav-item dropdown">
                    <?php 
                        // Prefer student/user cookie when present; fallback to admin session, then generic label
                        $displayName = ($_COOKIE['user_name'] ?? null) ?: ($_SESSION['admin_username'] ?? 'User');
                        // Compute initials for avatar pill
                        $initials = '';
                        $parts = preg_split('/\s+/', trim((string)$displayName));
                        if (is_array($parts)) {
                            foreach ($parts as $p) { if ($p !== '') { $initials .= strtoupper(substr($p, 0, 1)); } }
                        }
                        $initials = substr($initials, 0, 2);
                    ?>
                    <a class="nav-link dropdown-toggle d-flex align-items-center" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <span class="avatar-pill me-2" aria-hidden="true"><?php echo htmlspecialchars($initials ?: 'U'); ?></span>
                        <span><?php echo htmlspecialchars((string)$displayName); ?></span>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="navbarDropdown">
                        <li>
                            <a class="dropdown-item" href="/php-enrollment-system/profile.php">
                                <i class="bi bi-person me-2"></i> Profile
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item" href="/php-enrollment-system/cor.php">
                                <i class="bi bi-file-text me-2"></i> View COR
                            </a>
                        </li>
                        <li><hr class="dropdown-divider"></li>
                        <li>
                            <a class="dropdown-item text-danger" href="/php-enrollment-system/logout.php">
                                <i class="bi bi-box-arrow-right me-2"></i> Logout
                            </a>
                        </li>
                    </ul>
                </li>
            </ul>
        </div>
    </div>
</nav>
