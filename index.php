<?php
/**
 * Landing page for the Enrollment System
 */
session_start();

// Handle logout
if (isset($_GET['logout']) && $_GET['logout'] == '1') {
    // Unset all session variables
    $_SESSION = array();
    
    // Delete the session cookie
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    
    // Destroy the session
    session_destroy();
    
    // Redirect to index without the logout parameter
    header('Location: index.php');
    exit();
}

// Redirect to dashboard if already logged in (session or cookie)
if (isset($_SESSION['user_id']) || (isset($_COOKIE['user_id']) && !empty($_COOKIE['user_id']))) {
    header('Location: dashboard.php');
    exit();
}

// Include database configuration
require_once 'config/database.php';

// Fetch featured courses
$featuredCourses = [];
try {
    $stmt = $pdo->query("SELECT * FROM courses LIMIT 6");
    $featuredCourses = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // Log error but don't show it to users
    error_log("Error fetching courses: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Enrollment System - Get Started</title>
    <script type="module" src="/@vite/client"></script>
    <script type="module" src="/src/main.jsx"></script>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <style>
        html, body {
            height: 100%;
            margin: 0;
            padding: 0;
            scroll-behavior: smooth;
        }
        .page-container {
            display: flex;
            flex-direction: column;
            min-height: 100vh;
        }
        .hero {
            background: linear-gradient(135deg, #4F46E5 0%, #7C3AED 35%, #EC4899 70%, #06B6D4 100%);
            flex: 1;
            display: flex;
            align-items: center;
        }
        .feature-card {
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        .feature-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1);
        }
        /* Subtle gradient border for feature cards */
        .feature-card {
            border: 1px solid transparent;
            background: linear-gradient(#ffffff, #ffffff) padding-box,
                        linear-gradient(135deg, #06B6D4, #3B82F6, #8B5CF6, #EC4899) border-box;
        }
        .content-section {
            flex: 1;
            display: flex;
            align-items: center;
        }
        .btn-gradient-hover {
            position: relative;
            overflow: hidden;
            transition: all 0.3s ease;
        }
        .btn-gradient-hover::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(135deg, #06B6D4 0%, #3B82F6 33%, #8B5CF6 66%, #EC4899 100%);
            transition: left 0.5s ease;
            z-index: -1;
        }
        .btn-gradient-hover:hover::before {
            left: 0;
        }
        .btn-gradient-hover:hover {
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 10px 25px -5px rgba(79, 70, 229, 0.4);
        }
        /* Gradient underline hover for navbar links */
        .nav-gradient-link {
            background-image: linear-gradient(90deg, #06B6D4, #3B82F6, #8B5CF6, #EC4899);
            background-size: 0% 2px;
            background-position: 0 100%;
            background-repeat: no-repeat;
            transition: background-size 0.3s ease, color 0.3s ease;
        }
        .nav-gradient-link:hover {
            background-size: 100% 2px;
            color: #4F46E5;
        }
        /* Gradient pill for course tags */
        .tag-pill {
            background: linear-gradient(135deg, #06B6D4, #3B82F6, #8B5CF6);
            color: #ffffff !important;
            box-shadow: 0 4px 10px -3px rgba(59, 130, 246, 0.35);
        }
        /* Gradient text utility */
        .gradient-text {
            background: linear-gradient(90deg, #C084FC, #60A5FA, #34D399);
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
        }
    </style>
</head>
<body class="font-sans antialiased text-gray-800">
    <div id="root"></div>
    <!-- Navigation -->
    <nav class="bg-white shadow-sm">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16">
                <div class="flex items-center">
                    <div class="flex-shrink-0 flex items-center">
                        <i class="fas fa-graduation-cap text-indigo-600 text-2xl"></i>
                        <span class="ml-2 text-xl font-bold text-gray-900">EduEnroll</span>
                    </div>
                </div>
                <div class="flex items-center space-x-4">
                    <a href="login.php" class="text-gray-700 hover:text-indigo-600 px-3 py-2 text-sm font-medium btn-gradient-hover rounded-md nav-gradient-link">Sign In</a>
                    <a href="register.php" class="bg-indigo-600 text-white px-4 py-2 rounded-md text-sm font-medium hover:bg-indigo-700 btn-gradient-hover">Get Started</a>
                </div>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <div class="hero text-white content-section relative">
        <!-- Background Image on Right 30% -->
        <div class="absolute inset-0 lg:left-[70%] lg:w-[30%] w-full h-full">
            <img 
                src="image/heroimg.jpg" 
                alt="Education Illustration" 
                class="w-full h-full object-cover opacity-30 lg:opacity-70"
                loading="lazy"
            >
            <div class="absolute inset-0 bg-gradient-to-r from-transparent to-indigo-900/20 lg:hidden"></div>
        </div>
        
        <!-- Content -->
        <div class="relative z-10 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-20 md:py-32">
            <div class="lg:grid lg:grid-cols-2 lg:gap-8 items-center h-full">
                <div class="mb-10 lg:mb-0">
                    <h1 class="text-4xl md:text-5xl font-bold leading-tight mb-6">
                        Welcome to Our<br>
                        <span class="gradient-text">Enrollment System</span>
                    </h1>
                    <p class="text-xl text-indigo-100 mb-8">
                        Streamline your course enrollment process with our intuitive platform.
                        Join thousands of students who trust us for their educational journey.
                    </p>
                    <div class="flex flex-col sm:flex-row space-y-4 sm:space-y-0 sm:space-x-4">
                        <a href="register.php" class="bg-white text-indigo-700 px-8 py-3 rounded-lg font-semibold text-center hover:bg-gray-100 transition duration-300 btn-gradient-hover">
                            Create Account
                        </a>
                        <a href="#features" class="bg-indigo-700 text-white px-8 py-3 rounded-lg font-semibold text-center hover:bg-indigo-800 transition duration-300 btn-gradient-hover">
                            Learn More
                        </a>
                    </div>
                </div>
                <div class="hidden lg:block"></div>
            </div>
        </div>
    </div>

    <!-- Featured Courses Section -->
    <div class="py-16 bg-white content-section">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-16">
                <h2 class="text-3xl font-extrabold text-gray-900 sm:text-4xl">
                    Featured Courses
                </h2>
                <p class="mt-4 text-xl text-gray-600">
                    Explore our most popular courses
                </p>
            </div>
            <?php if (!empty($featuredCourses)): ?>
                <div class="grid md:grid-cols-3 gap-8">
                    <?php foreach ($featuredCourses as $course): ?>
                        <div class="group bg-white rounded-lg shadow-md overflow-hidden border border-gray-200 hover:shadow-xl transition-all duration-300 transform hover:-translate-y-1">
                            <div class="p-6">
                                <div class="flex items-center justify-between mb-4 transition-all duration-300 group-hover:opacity-90">
                                    <span class="px-3 py-1 text-sm font-semibold rounded-full tag-pill">
                                        <?php echo htmlspecialchars($course['category']); ?>
                                    </span>
                                    <span class="text-sm text-gray-500"><?php echo $course['duration']; ?></span>
                                </div>
                                <h3 class="text-xl font-bold text-gray-900 mb-2 transition-all duration-300 group-hover:text-indigo-600"><?php echo htmlspecialchars($course['title']); ?></h3>
                                <p class="text-gray-600 mb-4 line-clamp-3 transition-all duration-300 group-hover:text-gray-700"><?php echo htmlspecialchars($course['description']); ?></p>
                                <div class="flex items-center justify-between mt-4">
                                    <span class="text-sm text-gray-500">
                                        <i class="fas fa-user-tie mr-1"></i>
                                        <?php echo htmlspecialchars($course['instructor']); ?>
                                    </span>
                                    <span class="text-sm text-gray-500">
                                        <i class="fas fa-users mr-1"></i>
                                        <?php echo $course['enrolled']; ?>/<?php echo $course['capacity']; ?> students
                                    </span>
                                </div>
                                <div class="mt-6 transform transition-transform duration-200 hover:scale-105">
                                    <a href="<?php echo isset($_SESSION['user_id']) ? 'enroll.php?course_id=' . $course['id'] : 'register.php'; ?>" class="w-full flex items-center justify-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-gradient-to-r from-indigo-600 to-indigo-700 hover:from-indigo-700 hover:to-indigo-800 transition-all duration-300 transform hover:scale-105 hover:shadow-lg btn-gradient-hover">
                                        <span class="inline-flex items-center">
                                            <span><?php echo isset($_SESSION['user_id']) ? 'Enroll Now' : 'Register to Enroll'; ?></span>
                                            <svg class="ml-2 w-4 h-4 transition-transform duration-300 transform group-hover:translate-x-1" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"></path>
                                            </svg>
                                        </span>
                                    </a>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="text-center py-12">
                    <p class="text-gray-500">No courses available at the moment. Please check back later.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Features Section -->
    <div id="features" class="py-16 bg-gray-50 content-section">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-16">
                <h2 class="text-3xl font-extrabold text-gray-900 sm:text-4xl">
                    Why Choose Our Platform?
                </h2>
                <p class="mt-4 text-xl text-gray-600">
                    We provide the best tools to manage your educational journey
                </p>
            </div>
            <div class="grid md:grid-cols-3 gap-8">
                <div class="bg-white p-8 rounded-xl shadow-sm feature-card">
                    <div class="w-14 h-14 bg-indigo-100 rounded-lg flex items-center justify-center mb-6">
                        <i class="fas fa-book-reader text-indigo-600 text-2xl"></i>
                    </div>
                    <h3 class="text-xl font-bold mb-3">Easy Course Selection</h3>
                    <p class="text-gray-600">Browse and select courses with our intuitive interface designed for students.</p>
                </div>
                <div class="bg-white p-8 rounded-xl shadow-sm feature-card">
                    <div class="w-14 h-14 bg-indigo-100 rounded-lg flex items-center justify-center mb-6">
                        <i class="fas fa-clock text-indigo-600 text-2xl"></i>
                    </div>
                    <h3 class="text-xl font-bold mb-3">24/7 Access</h3>
                    <p class="text-gray-600">Access your courses and materials anytime, anywhere with our cloud-based platform.</p>
                </div>
                <div class="bg-white p-8 rounded-xl shadow-sm feature-card">
                    <div class="w-14 h-14 bg-indigo-100 rounded-lg flex items-center justify-center mb-6">
                        <i class="fas fa-headset text-indigo-600 text-2xl"></i>
                    </div>
                    <h3 class="text-xl font-bold mb-3">Dedicated Support</h3>
                    <p class="text-gray-600">Our support team is always ready to help you with any questions or issues.</p>
                </div>
            </div>
        </div>
    </div>

    <!-- CTA Section -->
    <div class="bg-gradient-to-r from-indigo-700 via-purple-700 to-pink-600 content-section">
        <div class="max-w-7xl mx-auto py-12 px-4 sm:px-6 lg:py-16 lg:px-8 lg:flex lg:items-center lg:justify-between">
            <h2 class="text-3xl font-extrabold tracking-tight text-white sm:text-4xl">
                <span class="block">Ready to get started?</span>
                <span class="block text-indigo-200">Create your account today.</span>
            </h2>
            <div class="mt-8 flex lg:mt-0 lg:flex-shrink-0">
                <div class="inline-flex rounded-md shadow">
                    <a href="register.php" class="inline-flex items-center justify-center px-5 py-3 border border-transparent text-base font-medium rounded-md text-indigo-600 bg-white hover:bg-indigo-50 btn-gradient-hover">
                        Get started
                    </a>
                </div>
                <div class="ml-3 inline-flex rounded-md shadow">
                    <a href="login.php" class="inline-flex items-center justify-center px-5 py-3 border border-transparent text-base font-medium rounded-md text-white bg-indigo-600 bg-opacity-60 hover:bg-opacity-70 btn-gradient-hover">
                        Sign in
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer class="bg-white">
        <div class="max-w-7xl mx-auto py-12 px-4 overflow-hidden sm:px-6 lg:px-8">
            <p class="mt-8 text-center text-base text-gray-500">
                &copy; <?php echo date('Y'); ?> Enrollment System. All rights reserved.
            </p>
        </div>
    </footer>
    </div>
</body>
</html>