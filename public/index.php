<?php 
session_start();
$page_title = 'Amuning Tokobe Enterprise - Home';
$additional_css = ['index.css'];
include 'includes/header.php'; ?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
</head>
<body>
    <section class="services">
        <div class="box-container">
            <div class="box">
                <div class="hero-text">
                    <div class="motto">
                        CAPTURE THE MOMENT, PRINT THE MEMORY
                    </div>
                    <div class="submotto">
                        <a>Bring your celebrations to life with our photobooth and printing services</a>
                    </div>
                </div>
                <img src="..\images\mod.jpg" alt="image" class="show" width="100%" height="25%">
                <img src="..\images\fivegirls.jpg" alt="image"  width="100%" height="25%">
                <img src="..\images\foursisters.jpg" alt="image" width="100%" height="25%">
                <img src="..\images\por.jpg" alt="image"  width="100%" height="25%">
                <img src="..\images\str8.jpg" alt="image"  width="100%" height="25%">
                <img src="..\images\blackpink.jpg" alt="image"  width="100%" height="25%">
                <img src="..\images\3self.jpg" alt="image"  width="100%" height="25%">
            </div>
        </div>

        <div class="about">
            <h1>About the Enterprise</h1>
            <p>Amuning Tokobe Enterprise is a local printing and photo service provider dedicated to capturing your special moments with creativity and quality.
                    Our goal is to be your one-stop shop for all printing needs, from custom photobooth experiences to professional photo prints.
                Discover how we can help preserve and showcase your memories.</p>
            <a href="about.php">Find Out More</a>        
        </div>

        <div class="gradbg">
            <div class="bento-grid-container">
                <div class="bento-grid">
                    <div class="item">
                        <p>test</p>
                        <img src="..\images\papertexture.avif"
                        alt="">
                    </div>
                    <div class="item">
                        <p>test</p>
                        <img src="..\images\browncamjpg.jpg"
                        alt="">
                    </div>
                    <div class="item">
                        <p>test</p>
                        <img src="..\images\beptex.jpg"
                        alt="">
                    </div>
                    <div class="item">
                        <p>test</p>
                        <img src="..\images\printer.jpg"
                        alt="">
                    </div>
                    <div class="item">
                        <p>test</p>
                        <img src="..\images\printerb.jpg"
                        alt="">
                    </div>
                    <div class="item">
                        <p>test</p>
                        <img src="..\images\pbtexture.jpg"
                        alt="">
                    </div>
                    <div class="item">
                        <p>test</p>
                        <img src="..\images\browncam2.webp"
                        alt="">
                    </div>
                    <div class="item">
                        <p>test</p>
                        <img src="..\images\brownphotob.jpg"
                        alt="">
                    </div>
                    <div class="item">
                        <p>test</p>
                        <img src="..\images\bptexture.jpg"
                        alt="">
                    </div>
                </div>
            </div>
        </div>

        <div class="third-box" id="gal">
            <div class="carousel">
                <div class="group">
                    <div class="card" style="background-image: url('../images/familee.jpg')"></div>
                    <div class="card" style="background-image: url('../images/smayl.jpg')"></div>
                    <div class="card" style="background-image: url('../images/hug.jpg')"></div>
                    <div class="card" style="background-image: url('../images/cool.jpg')"></div>
                    <div class="card" style="background-image: url('../images/awesome.jpg')"></div>
                    <div class="card" style="background-image: url('../images/girlpeace.jpg')"></div>
                </div>
                <div aria-hidden class="group">
                    <div class="card" style="background-image: url('../images/familee.jpg')"></div>
                    <div class="card" style="background-image: url('../images/smayl.jpg')"></div>
                    <div class="card" style="background-image: url('../images/hug.jpg')"></div>
                    <div class="card" style="background-image: url('../images/cool.jpg')"></div>
                    <div class="card" style="background-image: url('../images/awesome.jpg')"></div>
                    <div class="card" style="background-image: url('../images/girlpeace.jpg')"></div>
                </div>
            </div>
        </div>
    </section>
</body>
</html>

<script>
   let index = 0;
     let images = document.querySelectorAll(".box img");
     setInterval(function() {
        images[index].classList.remove("show");  // Hide current image
        index = (index + 1) % images.length;     // Move to next (loop back to 0)
        images[index].classList.add("show");     // Show next image
    }, 4000);

    // Gallery navigation active state
    const galleryLink = document.getElementById('galleryLink');
    const gallerySection = document.getElementById('gal');
    const homeLink = document.getElementById('homeLink');

    if (galleryLink && gallerySection) {
        // Function to check if gallery section is in viewport
        function isGalleryInView() {
            const rect = gallerySection.getBoundingClientRect();
            return rect.top <= 100 && rect.bottom >= 100;
        }

        // Function to update gallery link active state
        function updateGalleryActiveState() {
            if (isGalleryInView()) {
                galleryLink.classList.add('active');
                // Remove active from Home when gallery is active
                if (homeLink) {
                    homeLink.classList.remove('active');
                }
            } else {
                galleryLink.classList.remove('active');
                // Restore Home active state when gallery is not active
                if (homeLink) {
                    homeLink.classList.add('active');
                }
            }
        }

        // Smooth scroll to gallery when link is clicked
        galleryLink.addEventListener('click', function(e) {
            e.preventDefault();
            gallerySection.scrollIntoView({
                behavior: 'smooth',
                block: 'start'
            });

            // Add active class to gallery and remove from home
            galleryLink.classList.add('active');
            if (homeLink) {
                homeLink.classList.remove('active');
            }
            setTimeout(() => {
                updateGalleryActiveState();
            }, 1000);
        });

        // Update active state on scroll
        window.addEventListener('scroll', updateGalleryActiveState);

        // Initial check
        updateGalleryActiveState();
    }
</script>

<script src="assets/js/script.js"></script>
<?php include 'includes/footer.php'; ?>