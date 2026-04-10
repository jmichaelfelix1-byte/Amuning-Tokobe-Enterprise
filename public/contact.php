<?php 
session_start();

$page_title = 'Contact Us | Amuning Tokobe Enterprise';
$additional_css = ['contact.css'];
include 'includes/header.php'; ?>
        <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>

<!-- Banner Section -->
<section class="contact-banner">
    <div class="banner-content">
        <h1>Get in Touch</h1>
        <p>We'd love to hear from you! Reach out to us for any inquiries or services</p>
    </div>
</section>

<!-- Contact Information Section -->
<section class="contact-section">
    <div class="contact-container">
        <div class="contact-info">
            <div class="info-header">
                <h2>Contact Information</h2>
            </div>

            <div class="info-cards">
                <div class="info-card">
                    <div class="card-icon">
                        <i class="fa-solid fa-phone"></i>
                    </div>
                    <div class="card-content">
                        <h3>Call Us</h3>
                        <p>(02) 8356 6906</p>
                    </div>
                </div>

                <div class="info-card">
                    <div class="card-icon">
                        <i class="fa-brands fa-facebook-f"></i>
                    </div>
                    <div class="card-content">
                        <h3>Follow Us</h3>
                        <p>Amuning Tokobe Enterprise</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Map Section -->
<section class="map-section">
    <div class="map-header">
        <h2>Find Us Here</h2>
    </div>
    <div class="map-container">
        <iframe src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3861.4235274093344!2d121.17968537592624!3d14.574926177703121!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x3397c101599b82af%3A0xae3205482cb6e26b!2sAmuning%20Tokobe%20Enterprise!5e0!3m2!1sen!2sph!4v1760280055637!5m2!1sen!2sph" 
                allowfullscreen="" 
                loading="lazy" 
                referrerpolicy="no-referrer-when-downgrade">
        </iframe>
    </div>
</section>

<script src="assets/js/script.js"></script>

<?php include 'includes/footer.php'; ?>