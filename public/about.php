<?php 
session_start();

$page_title = 'About Us | Amuning Tokobe Enterprise';
$additional_css = ['about.css'];
include 'includes/header.php'; ?>

<section class="about" aria-labelledby="about-heading">
        <h1 id="about-heading" class="about-main-title">Capturing Ideas. Freezing Moments.</h1>

        <div class="about-container">
            <!-- Our Story -->
            <div class="about-block left">
                <div class="about-text">
                    <h2>Our Story</h2>
                    <p>
                        Founded with a love for visuals and design, <strong>Amuning Tokobe Enterprise</strong> began as a small hobby among friends.
                        Through word-of-mouth and hard work, we've become a go-to hub for clients who want their prints and photos done right —
                        with heart, creativity, and attention to detail.
                    </p>
                </div>
                <div class="about-image">
                    <img src="../images/bluemask.png" alt="Our Story">
                </div>
            </div>

            <!-- Our Mission -->
            <div class="about-block right">
                <div class="about-image">
                    <img src="../images/marialeonorateresa.png" alt="Our Mission">
                </div>
                <div class="about-text">
                    <h2>Our Mission</h2>
                    <p>
                        To provide high-quality printing and photography services that inspire creativity, celebrate milestones, 
                        and bring people's visions to life — all while keeping it fun, personal, and affordable.
                    </p>
                </div>
            </div>

            <!-- Our Vision -->
            <div class="about-block left">
                <div class="about-text">
                    <h2>Our Vision</h2>
                    <p>
                        To become one of the most trusted local brands in creative printing and photography — 
                        known for originality, reliability, and great vibes.
                    </p>
                </div>
                <div class="about-image">
                    <img src="../images/wicked.png" alt="Our Vision">
                </div>
            </div>

            <!-- Our Values -->
            <div class="about-block right">
                <div class="about-image">
                    <img src="../images/selfiequeen.png" alt="Our Values">
                </div>
                <div class="about-text">
                    <h2>Our Values</h2>
                    <ul class="values-list">
                        <li><strong>Creativity</strong> – Every project deserves a personal touch.</li>
                        <li><strong>Quality</strong> – We take pride in every print and photo we produce.</li>
                        <li><strong>Affordability</strong> – Great work shouldn't break the bank.</li>
                        <li><strong>Trust</strong> – Building genuine connections with every client.</li>
                    </ul>
                </div>
            </div>
        </div>
    </section>

    <!-- About page content will go here -->
<script src="assets/js/script.js"></script>
<script>
   let index = 0;
     let images = document.querySelectorAll(".box img");
     setInterval(function() {
        images[index].classList.remove("show");  // Hide current image
        index = (index + 1) % images.length;     // Move to next (loop back to 0)
        images[index].classList.add("show");     // Show next image
    }, 4000); 
</script>
<?php include 'includes/footer.php'; ?>