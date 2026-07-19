<?php
// Initialize the session
session_start();

// Include config file
require_once "includes/config.php";

$page_title = "About Us - Muslim Healthcare Centre";
?>

<?php include('includes/header.php'); ?>

<style>
    .developer-card {
        transition: transform 0.6s;
        transform-style: preserve-3d;
        position: relative;
    }
    .developer-card:hover {
        transform: rotateY(180deg);
    }
    .card-front, .card-back {
        backface-visibility: hidden;
        -webkit-backface-visibility: hidden;
        position: absolute;
        width: 100%;
        height: 100%;
    }
    .card-back {
        transform: rotateY(180deg);
        padding: 1.25rem;
        background: #f8f9fa;
        border-radius: 0.375rem;
    }
    .skills-list {
        text-align: left;
    }
    .skills-list h5 {
        color: #0d6efd;
        margin-bottom: 1rem;
    }
    .skills-list ul {
        list-style: none;
        padding-left: 0;
    }
    .skills-list li {
        margin-bottom: 0.5rem;
        position: relative;
        padding-left: 1.5rem;
    }
    .skills-list li:before {
        content: "→";
        position: absolute;
        left: 0;
        color: #0d6efd;
    }
</style>

<!-- Hero Section -->
<div class="bg-primary bg-gradient text-white py-5">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-lg-8">
                <h1 class="display-4 fw-bold mb-3">About Muslim Healthcare Centre</h1>
                <p class="lead mb-0">Providing compassionate and comprehensive healthcare services to our community since 2010.</p>
            </div>
        </div>
    </div>
</div>

<!-- Our Story -->
<section class="py-5">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-lg-6">
                <h2 class="fw-bold mb-4">Our Story</h2>
                <p class="lead">Established in 2010, Muslim Healthcare Centre was founded with a vision to provide accessible, high-quality healthcare services to our community while upholding Islamic values and principles.</p>
                <p>What started as a small clinic has grown into a comprehensive healthcare facility serving thousands of patients each year. Our journey has been guided by a commitment to excellence in patient care, medical expertise, and community service.</p>
            </div>
            <div class="col-lg-6">
                <img src="images/muslim_healthcentre_care_clinic.png.jpeg" alt="Our Clinic" class="img-fluid rounded shadow">
            </div>
        </div>
    </div>
</section>

<!-- Our Mission & Vision -->
<section class="bg-light py-5">
    <div class="container">
        <div class="row g-4">
            <div class="col-md-6">
                <div class="card h-100 border-0 shadow-sm">
                    <div class="card-body text-center p-4">
                        <div class="icon-lg bg-primary bg-opacity-10 text-primary rounded-circle d-inline-flex align-items-center justify-content-center mb-4">
                            <i class="fas fa-bullseye fa-2x"></i>
                        </div>
                        <h3>Our Mission</h3>
                        <p class="text-muted">To provide compassionate, high-quality healthcare services that meet the physical, emotional, and spiritual needs of our community while maintaining the highest standards of medical excellence.</p>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card h-100 border-0 shadow-sm">
                    <div class="card-body text-center p-4">
                        <div class="icon-lg bg-primary bg-opacity-10 text-primary rounded-circle d-inline-flex align-items-center justify-content-center mb-4">
                            <i class="fas fa-eye fa-2x"></i>
                        </div>
                        <h3>Our Vision</h3>
                        <p class="text-muted">To be the leading healthcare provider known for excellence in patient care, medical innovation, and community service while upholding Islamic values and ethics.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Our Values -->
<section class="py-5">
    <div class="container">
        <div class="text-center mb-5">
            <h2 class="fw-bold">Our Core Values</h2>
            <p class="lead text-muted">Guiding principles that shape our approach to healthcare</p>
        </div>
        <div class="row g-4">
            <div class="col-md-4">
                <div class="card h-100 border-0 shadow-sm">
                    <div class="card-body text-center p-4">
                        <div class="icon-lg bg-primary bg-opacity-10 text-primary rounded-circle d-inline-flex align-items-center justify-content-center mb-3">
                            <i class="fas fa-heartbeat fa-2x"></i>
                        </div>
                        <h4>Compassion</h4>
                        <p class="text-muted">We treat every patient with kindness, empathy, and respect, understanding that healing involves both body and soul.</p>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card h-100 border-0 shadow-sm">
                    <div class="card-body text-center p-4">
                        <div class="icon-lg bg-primary bg-opacity-10 text-primary rounded-circle d-inline-flex align-items-center justify-content-center mb-3">
                            <i class="fas fa-star fa-2x"></i>
                        </div>
                        <h4>Excellence</h4>
                        <p class="text-muted">We are committed to the highest standards of medical care, continuous learning, and professional development.</p>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card h-100 border-0 shadow-sm">
                    <div class="card-body text-center p-4">
                        <div class="icon-lg bg-primary bg-opacity-10 text-primary rounded-circle d-inline-flex align-items-center justify-content-center mb-3">
                            <i class="fas fa-hand-holding-heart fa-2x"></i>
                        </div>
                        <h4>Integrity</h4>
                        <p class="text-muted">We uphold the highest ethical standards, ensuring honesty, transparency, and trust in all our interactions.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Meet the Developers -->
<section class="py-5">
    <div class="container">
        <div class="text-center mb-5">
            <h2 class="fw-bold">Meet the Developers</h2>
            <p class="lead text-muted">The talented team behind this healthcare platform</p>
        </div>
        <div class="row justify-content-center">
            <div class="col-md-5 mb-4">
                <div class="card h-100 border-0 shadow-sm developer-card" style="min-height: 400px;">
                    <div class="card-front h-100">
                        <div class="card-body text-center p-4">
                            <div class="mx-auto mb-4" style="width: 120px; height: 120px; overflow: hidden; border-radius: 50%;">
                                <img src="images/Ahmad Asif bin Ahmad Kamal Nizam.png.jpeg" alt="Ahmad Asif bin Ahmad Kamal Nizam" class="img-fluid h-100 w-100" style="object-fit: cover;">
                            </div>
                            <h4 class="mb-2">Ahmad Asif bin Ahmad Kamal Nizam</h4>
                            <p class="text-primary mb-3">Lead Developer</p>
                            <p class="text-muted mb-4">With over 5 years of experience in web development, Ahmad specializes in creating user-friendly healthcare applications that prioritize patient experience and data security.</p>
                            <p class="small text-muted">(Hover or tap to see my social media and contact info)</p>
                            <div class="d-flex justify-content-center gap-3">
                                <a href="#" class="text-dark"><i class="fab fa-github fa-lg"></i></a>
                                <a href="#" class="text-primary"><i class="fab fa-linkedin fa-lg"></i></a>
                                <a href="#" class="text-info"><i class="fab fa-twitter fa-lg"></i></a>
                            </div>
                        </div>
                    </div>
                    <div class="card-back h-100 p-4">
                        <h5 class="text-center mb-4">Connect With Me</h5>
                        <div class="contact-info text-center">
                            <div class="mb-4">
                                <i class="fab fa-instagram fa-2x text-danger mb-2 d-block"></i>
                                <a href="https://instagram.com/ezizao" target="_blank" class="text-decoration-none">
                                    @ezizao
                                </a>
                            </div>
                            <div class="mb-4">
                                <i class="fas fa-phone-alt fa-2x text-success mb-2 d-block"></i>
                                <a href="tel:+01111008873" class="text-decoration-none">
                                    +01 111-008873
                                </a>
                            </div>
                            <div class="social-links mt-4">
                                <a href="#" class="text-dark me-3"><i class="fab fa-github fa-lg"></i></a>
                                <a href="#" class="text-primary me-3"><i class="fab fa-linkedin fa-lg"></i></a>
                                <a href="#" class="text-info"><i class="fab fa-twitter fa-lg"></i></a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-5 mb-4">
                <div class="card h-100 border-0 shadow-sm developer-card" style="min-height: 400px;">
                    <div class="card-front h-100">
                        <div class="card-body text-center p-4">
                            <div class="mx-auto mb-4" style="width: 120px; height: 120px; overflow: hidden; border-radius: 50%;">
                                <img src="images/Mohammad Iqram Muzaffar bin Bakhtiar.png.jpeg" alt="Mohammad Iqram Muzaffar bin Bakhtiar" class="img-fluid h-100 w-100" style="object-fit: cover;">
                            </div>
                            <h4 class="mb-2">Mohammad Iqram Muzaffar bin Bakhtiar</h4>
                            <p class="text-primary mb-3">UI/UX Designer & Developer</p>
                            <p class="text-muted mb-4">A creative UI/UX designer and developer with a passion for crafting beautiful, intuitive healthcare interfaces. Specializing in user-centered design that enhances accessibility and delivers exceptional user experiences.</p>
                            <p class="small text-muted">(Hover or tap to see my social media and contact info)</p>
                            <div class="d-flex justify-content-center gap-3">
                                <a href="#" class="text-dark"><i class="fab fa-github fa-lg"></i></a>
                                <a href="#" class="text-primary"><i class="fab fa-linkedin fa-lg"></i></a>
                                <a href="#" class="text-info"><i class="fab fa-dribbble fa-lg"></i></a>
                            </div>
                        </div>
                    </div>
                    <div class="card-back h-100 p-4">
                        <h5 class="text-center mb-4">Connect With Me</h5>
                        <div class="contact-info text-center">
                            <div class="mb-4">
                                <i class="fab fa-instagram fa-2x text-danger mb-2 d-block"></i>
                                <a href="https://instagram.com/ucukiq" target="_blank" class="text-decoration-none">
                                    @ucukiq
                                </a>
                            </div>
                            <div class="mb-4">
                                <i class="fas fa-phone-alt fa-2x text-success mb-2 d-block"></i>
                                <a href="tel:+60175885869" class="text-decoration-none">
                                    +60 17-588 5869
                                </a>
                            </div>
                            <div class="social-links mt-4">
                                <a href="#" class="text-dark me-3"><i class="fab fa-github fa-lg"></i></a>
                                <a href="#" class="text-primary me-3"><i class="fab fa-linkedin fa-lg"></i></a>
                                <a href="#" class="text-info"><i class="fab fa-dribbble fa-lg"></i></a>
                            </div>
                        </div>
                    </div>
            </div>
        </div>
    </div>
</section>

<?php include('includes/footer.php'); ?>
