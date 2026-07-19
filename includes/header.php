<?php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Define base URL - simple and reliable for XAMPP
$base_url = 'http://' . $_SERVER['HTTP_HOST'] . '/muslim_healthcare_centre';

// For debugging - uncomment next line if needed
// echo "<!-- Base URL: $base_url -->";

// Language settings
$default_lang = 'en';
$available_langs = ['en' => 'English', 'bm' => 'Bahasa Melayu'];

// Get language from session or default to English
$current_lang = $_SESSION['lang'] ?? $default_lang;

// Check if language is being changed
if (isset($_GET['lang']) && array_key_exists($_GET['lang'], $available_langs)) {
    $current_lang = $_GET['lang'];
    $_SESSION['lang'] = $current_lang;
}

// Load language file
$lang_file = __DIR__ . "/../languages/{$current_lang}.php";
if (file_exists($lang_file)) {
    $lang = include $lang_file;
} else {
    // Fallback to English if language file doesn't exist
    $lang = include __DIR__ . "/../languages/en.php";
}

// Set HTML lang attribute
$html_lang = $current_lang === 'bm' ? 'ms' : 'en';
?>
<!DOCTYPE html>
<html lang="<?php echo $html_lang; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Muslim Healthcare Centre - <?php echo $page_title ?? 'Appointment System'; ?></title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="<?php echo $base_url; ?>/assets/css/style.css">
    <link rel="stylesheet" href="<?php echo $base_url; ?>/assets/css/live_queue.css">
    <style>
        :root {
            --bg-color: #ffffff;
            --text-color: #333333;
            --card-bg: #ffffff;
            --card-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
            --primary-color: #0d6efd;
            --secondary-color: #6c757d;
        }
        
        [data-theme="dark"] {
            --bg-color: #0f0f10;
            --text-color: #ffffff;
            --muted-color: rgba(255,255,255,0.86);
            --lead-color: rgba(255,255,255,0.96);
            --card-bg: #232425;
            --card-shadow: 0 0.6rem 1.2rem rgba(0, 0, 0, 0.55);
            --primary-color: #63a9ff;
            --secondary-color: #cbd0d4;
        }

        /* Improve readability for Bootstrap utility classes in dark mode */
        [data-theme="dark"] body {
            color: var(--text-color);
            text-shadow: 0 1px 2px rgba(0,0,0,0.6);
        }

        [data-theme="dark"] .text-muted {
            color: var(--muted-color) !important;
        }

        [data-theme="dark"] .lead {
            color: var(--lead-color) !important;
        }

        [data-theme="dark"] h1, [data-theme="dark"] h2, [data-theme="dark"] h3, [data-theme="dark"] h4, [data-theme="dark"] h5, [data-theme="dark"] h6 {
            color: var(--text-color) !important;
        }

        [data-theme="dark"] .bg-light {
            background-color: #222327 !important;
            color: var(--text-color) !important;
        }

        [data-theme="dark"] .card {
            background-color: var(--card-bg) !important;
            color: var(--text-color) !important;
        }

        [data-theme="dark"] .card .text-primary,
        [data-theme="dark"] .text-primary {
            color: #66b2ff !important;
        }

        [data-theme="dark"] .icon-lg {
            background-color: rgba(255,255,255,0.03) !important;
        }

        [data-theme="dark"] .text-dark {
            color: rgba(255,255,255,0.92) !important;
        }
        
        body {
            background-color: var(--bg-color);
            color: var(--text-color);
            transition: background-color 0.3s, color 0.3s;
        }
        
        .card {
            background-color: var(--card-bg);
            box-shadow: var(--card-shadow);
            transition: background-color 0.3s, box-shadow 0.3s;
        }
        
        .theme-toggle {
            background: none;
            border: none;
            color: white;
            font-size: 1.25rem;
            cursor: pointer;
            padding: 0.5rem;
            margin-left: 1rem;
            display: flex;
            align-items: center;
        }
        
        .theme-toggle:focus {
            outline: none;
        }
        
        .theme-toggle .moon { display: none; }
        .theme-toggle .sun { display: inline; }
        
        [data-theme="dark"] .theme-toggle .moon { display: inline; }
        [data-theme="dark"] .theme-toggle .sun { display: none; }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand p-0 m-0 d-flex align-items-center" href="<?php echo $base_url; ?>/index.php" style="text-decoration: none;">
                <div class="d-flex align-items-center me-2" style="font-size: 2rem; color: white; line-height: 1;">
                    <i class="fas fa-hospital"></i>
                </div>
                <div class="d-flex flex-column text-white">
                    <span class="fw-bold" style="font-size: 1.25rem; line-height: 1.1; letter-spacing: 0.5px;">MUSLIM HEALTHCARE</span>
                    <span style="font-size: 1rem; letter-spacing: 1px; font-weight: 300;">CENTRE</span>
                </div>
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link <?php echo (basename($_SERVER['PHP_SELF']) == 'about.php') ? 'active' : ''; ?>" href="<?php echo $base_url; ?>/about.php">
                            <i class="fas fa-info-circle me-1"></i> About Us
                        </a>
                    </li>
                    <li class="nav-item d-flex align-items-center ms-2">
                        <button class="theme-toggle" id="themeToggle" aria-label="Toggle theme">
                            <i class="fas fa-sun sun"></i>
                            <i class="fas fa-moon moon"></i>
                        </button>
                    </li>
                    <?php if(!isset($_SESSION['user_id'])): ?>
                        <li class="nav-item">
                            <a class="nav-link" href="<?php echo $base_url; ?>/admin_login.php">
                                <i class="fas fa-user-shield me-1"></i> Admin
                            </a>
                        </li>
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                <i class="fas fa-user"></i> User
                            </a>
                            <ul class="dropdown-menu" aria-labelledby="userDropdown">
                                <li><a class="dropdown-item" href="<?php echo $base_url; ?>/login.php">
                                    <i class="fas fa-sign-in-alt me-2"></i>User Login
                                </a></li>
                                <li><a class="dropdown-item" href="<?php echo $base_url; ?>/register.php">
                                    <i class="fas fa-user-plus me-2"></i>User Register
                                </a></li>
                            </ul>
                        </li>
                    <?php else: ?>
                        <?php if($_SESSION['role'] === 'admin'): ?>
                            <li class="nav-item">
                                <a class="nav-link" href="<?php echo $base_url; ?>/admin/dashboard.php">Admin Dashboard</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="<?php echo $base_url; ?>/admin/doctors.php">Manage Doctors</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="<?php echo $base_url; ?>/admin/manage_patients.php">Manage Patients</a>
                            </li>
                        <?php elseif($_SESSION['role'] === 'doctor'): ?>
                            <li class="nav-item">
                                <a class="nav-link <?php echo (basename($_SERVER['PHP_SELF']) == 'dashboard.php') ? 'active' : ''; ?>" href="<?php echo $base_url; ?>/doctor/dashboard.php">My Schedule</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link <?php echo (basename($_SERVER['PHP_SELF']) == 'appointments.php') ? 'active' : ''; ?>" href="<?php echo $base_url; ?>/doctor/appointments.php">My Appointments</a>
                            </li>
                        <?php elseif($_SESSION['role'] === 'patient'): ?>
                            <li class="nav-item">
                                <a class="nav-link <?php echo (basename($_SERVER['PHP_SELF']) == 'dashboard.php') ? 'active' : ''; ?>" href="<?php echo $base_url; ?>/patient/dashboard.php">
                                    <i class="fas fa-tachometer-alt me-1"></i> My Dashboard
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link <?php echo (basename($_SERVER['PHP_SELF']) == 'book_appointment.php') ? 'active' : ''; ?>" href="<?php echo $base_url; ?>/patient/book_appointment.php">
                                    <i class="fas fa-calendar-plus me-1"></i> Book Appointment
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link <?php echo (basename($_SERVER['PHP_SELF']) == 'appointments.php') ? 'active' : ''; ?>" href="<?php echo $base_url; ?>/patient/appointments.php">
                                    <i class="far fa-calendar-alt me-1"></i> My Appointments
                                </a>
                            </li>
                        <?php endif; ?>
                        <li class="nav-item">
                            <a class="nav-link" href="<?php echo $base_url; ?>/logout.php">
                                <i class="fas fa-sign-out-alt"></i> Logout
                            </a>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>

    <script>
        // Theme toggle functionality
        const themeToggle = document.getElementById('themeToggle');
        const prefersDarkScheme = window.matchMedia('(prefers-color-scheme: dark)');
        
        // Check for saved user preference, if any, on load of the website
        const currentTheme = localStorage.getItem('theme') || (prefersDarkScheme.matches ? 'dark' : 'light');
        document.documentElement.setAttribute('data-theme', currentTheme);
        
        // Toggle theme on button click
        themeToggle.addEventListener('click', () => {
            const currentTheme = document.documentElement.getAttribute('data-theme');
            const newTheme = currentTheme === 'light' ? 'dark' : 'light';
            
            document.documentElement.setAttribute('data-theme', newTheme);
            localStorage.setItem('theme', newTheme);
        });
        
        // Listen for changes in the system theme
        prefersDarkScheme.addEventListener('change', (e) => {
            if (!localStorage.getItem('theme')) {
                document.documentElement.setAttribute('data-theme', e.matches ? 'dark' : 'light');
            }
        });
    </script>

    <div class="container mt-4">
        <?php if(isset($_SESSION['success'])): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?php 
                    echo $_SESSION['success']; 
                    unset($_SESSION['success']);
                ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <?php if(isset($_SESSION['error'])): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?php 
                    echo $_SESSION['error']; 
                    unset($_SESSION['error']);
                ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>