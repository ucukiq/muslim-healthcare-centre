<?php
// Set page title
$page_title = "Home";

// Include config file
require_once 'includes/config.php';

// Include header
require_once 'includes/header.php';
?>

<div class="jumbotron jumbotron-fluid bg-primary text-white py-5">
    <div class="container text-center">
        <h1 class="display-4">Welcome to Muslim Healthcare Centre</h1>
        <p class="lead">Your trusted partner in healthcare services</p>
        <?php if(!isset($_SESSION['user_id'])): ?>
            <div class="mt-4">
                <a href="login.php" class="btn btn-light btn-lg me-3">Login</a>
                <a href="register.php" class="btn btn-outline-light btn-lg">Register as Patient</a>
            </div>
        <?php else: ?>
            <div class="mt-4">
                <a href="<?php echo strtolower($_SESSION['role']); ?>/dashboard.php" class="btn btn-light btn-lg">Go to Dashboard</a>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Services Carousel Section -->
<section class="py-5 bg-light">
    <div class="container">
        <div class="text-center mb-5">
            <h2 class="fw-bold">Our Medical Services</h2>
            <p class="lead text-muted">Comprehensive healthcare services for you and your family</p>
        </div>
        
        <div class="position-relative">
            <div class="services-slider swiper-container">
                <div class="swiper-wrapper pb-4">
                    <!-- Service 1 -->
                    <div class="swiper-slide">
                        <div class="card h-100 border-0 shadow-sm">
                            <div class="card-body text-center p-4">
                                <div class="icon-lg bg-primary bg-opacity-10 text-primary rounded-circle d-inline-flex align-items-center justify-content-center mb-4">
                                    <i class="fas fa-heartbeat fa-2x"></i>
                                </div>
                                <h4>Cardiology</h4>
                                <p class="text-muted">Expert care for your heart health with advanced diagnostic and treatment options.</p>
                                <a href="https://en.wikipedia.org/wiki/Cardiology" target="_blank" rel="noopener noreferrer" class="btn btn-outline-primary btn-sm">Learn More</a>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Service 2 -->
                    <div class="swiper-slide">
                        <div class="card h-100 border-0 shadow-sm">
                            <div class="card-body text-center p-4">
                                <div class="icon-lg bg-primary bg-opacity-10 text-primary rounded-circle d-inline-flex align-items-center justify-content-center mb-4">
                                    <i class="fas fa-brain fa-2x"></i>
                                </div>
                                <h4>Neurology</h4>
                                <p class="text-muted">Specialized care for disorders of the nervous system and brain conditions.</p>
                                <a href="https://en.wikipedia.org/wiki/Neurology" target="_blank" rel="noopener noreferrer" class="btn btn-outline-primary btn-sm">Learn More</a>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Service 3 -->
                    <div class="swiper-slide">
                        <div class="card h-100 border-0 shadow-sm">
                            <div class="card-body text-center p-4">
                                <div class="icon-lg bg-primary bg-opacity-10 text-primary rounded-circle d-inline-flex align-items-center justify-content-center mb-4">
                                    <i class="fas fa-bone fa-2x"></i>
                                </div>
                                <h4>Orthopedics</h4>
                                <p class="text-muted">Comprehensive care for bones, joints, ligaments, tendons, and muscles.</p>
                                <a href="https://en.wikipedia.org/wiki/Orthopedic_surgery" target="_blank" rel="noopener noreferrer" class="btn btn-outline-primary btn-sm">Learn More</a>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Service 4 -->
                    <div class="swiper-slide">
                        <div class="card h-100 border-0 shadow-sm">
                            <div class="card-body text-center p-4">
                                <div class="icon-lg bg-primary bg-opacity-10 text-primary rounded-circle d-inline-flex align-items-center justify-content-center mb-4">
                                    <i class="fas fa-baby fa-2x"></i>
                                </div>
                                <h4>Pediatrics</h4>
                                <p class="text-muted">Specialized healthcare for infants, children, and adolescents.</p>
                                <a href="https://en.wikipedia.org/wiki/Pediatrics" target="_blank" rel="noopener noreferrer" class="btn btn-outline-primary btn-sm">Learn More</a>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Service 5 -->
                    <div class="swiper-slide">
                        <div class="card h-100 border-0 shadow-sm">
                            <div class="card-body text-center p-4">
                                <div class="icon-lg bg-primary bg-opacity-10 text-primary rounded-circle d-inline-flex align-items-center justify-content-center mb-4">
                                    <i class="fas fa-eye fa-2x"></i>
                                </div>
                                <h4>Ophthalmology</h4>
                                <p class="text-muted">Expert eye care services including vision tests and eye disease treatment.</p>
                                <a href="https://en.wikipedia.org/wiki/Ophthalmology" target="_blank" rel="noopener noreferrer" class="btn btn-outline-primary btn-sm">Learn More</a>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Navigation buttons -->
                <div class="swiper-button-next"></div>
                <div class="swiper-button-prev"></div>
                
                <!-- Pagination -->
                <div class="swiper-pagination position-relative mt-4"></div>
            </div>
        </div>
    </div>
</section>

<div class="container my-5">
    <div class="row">
        <div class="col-md-4 mb-4">
            <div class="card h-100">
                <div class="card-body text-center">
                    <div class="text-primary mb-3">
                        <i class="fas fa-user-md fa-3x"></i>
                    </div>
                    <h3 class="card-title">Expert Doctors</h3>
                    <p class="card-text">Our team of experienced and qualified doctors are here to provide you with the best healthcare services.</p>
                </div>
            </div>
        </div>
        <div class="col-md-4 mb-4">
            <div class="card h-100">
                <div class="card-body text-center">
                    <div class="text-primary mb-3">
                        <i class="fas fa-calendar-check fa-3x"></i>
                    </div>
                    <h3 class="card-title">Easy Appointments</h3>
                    <p class="card-text">Book your appointments online easily and get instant confirmation. No more waiting in long queues.</p>
                </div>
            </div>
        </div>
        <div class="col-md-4 mb-4">
            <div class="card h-100">
                <div class="card-body text-center">
                    <div class="text-primary mb-3">
                        <i class="fas fa-heartbeat fa-3x"></i>
                    </div>
                    <h3 class="card-title">Quality Care</h3>
                    <p class="card-text">We are committed to providing high-quality healthcare services to all our patients with compassion and respect.</p>
                </div>
            </div>
        </div>
    </div>

    <div class="row mt-5">
        <div class="col-md-6">
            <h2>Our Services</h2>
            <div class="list-group">
                <div class="list-group-item">
                    <h5 class="mb-1">General Consultation</h5>
                    <p class="mb-1">Comprehensive health check-ups and consultations with our expert physicians.</p>
                </div>
                <div class="list-group-item">
                    <h5 class="mb-1">Specialized Care</h5>
                    <p class="mb-1">Specialized treatment and care for various health conditions.</p>
                </div>
                <div class="list-group-item">
                    <h5 class="mb-1">Preventive Healthcare</h5>
                    <p class="mb-1">Regular check-ups and screenings to prevent health issues before they occur.</p>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <h2>Working Hours</h2>
            <div class="card">
                <div class="card-body">
                    <ul class="list-unstyled">
                        <li class="mb-2"><strong>Monday - Friday:</strong> 8:00 AM - 5:00 PM</li>
                        <li class="mb-2"><strong>Saturday - Sunday:</strong> Closed</li>
                    </ul>
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>
                        Note: Appointments can only be scheduled during working hours on weekdays.
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
// Include footer
require_once 'includes/footer.php';
?>
