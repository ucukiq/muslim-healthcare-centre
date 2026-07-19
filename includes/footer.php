    </div>

    <footer class="bg-dark text-white mt-5 py-4">
        <div class="container">
            <div class="row">
                <div class="col-md-6">
                    <h5>Muslim Healthcare Centre</h5>
                    <p>Providing quality healthcare services to our community.</p>
                </div>
<div class="col-md-3">
                    <h5>Contact Us</h5>
                    <address>
                        <i class="fas fa-envelope me-2"></i> <a href="mailto:ucukiq@gmail.com" class="text-white text-decoration-none">ucukiq@gmail.com</a><br>
                        <i class="fas fa-phone me-2"></i> <a href="tel:+60175885869" class="text-white text-decoration-none">+6017-588-5869</a><br>
                        <i class="fas fa-map-marker-alt me-2"></i> 123 Healthcare Street, Kuala Lumpur, Malaysia
                    </address>
                </div>
            </div>
            <hr class="bg-light">
            <div class="text-center">
                <p class="mb-0">&copy; <?php echo date('Y'); ?> Muslim Healthcare Centre. All rights reserved.</p>
            </div>
        </div>
    </footer>

    <!-- Bootstrap JS and dependencies -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Swiper JS -->
    <link rel="stylesheet" href="https://unpkg.com/swiper/swiper-bundle.min.css" />
    <script src="https://unpkg.com/swiper/swiper-bundle.min.js"></script>
    
    <!-- Initialize Swiper -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            var swiper = new Swiper('.services-slider', {
                slidesPerView: 1,
                spaceBetween: 20,
                loop: true,
                autoplay: {
                    delay: 3000,
                    disableOnInteraction: false,
                },
                pagination: {
                    el: '.swiper-pagination',
                    clickable: true,
                },
                navigation: {
                    nextEl: '.swiper-button-next',
                    prevEl: '.swiper-button-prev',
                },
                breakpoints: {
                    640: {
                        slidesPerView: 2,
                    },
                    992: {
                        slidesPerView: 3,
                    },
                    1200: {
                        slidesPerView: 4,
                    }
                }
            });
        });
    </script>
    
    <!-- Custom JS -->
    <script src="<?php echo $base_url; ?>/assets/js/main.js"></script>
    
    <!-- AI Chat Widget -->
    <?php require_once 'ai_chat_widget.php'; ?>
</body>
</html>
