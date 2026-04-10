<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Amuning Tokobe Enterprise</title>

   

    <link rel="stylesheet" href="index.css">


</head>
<body>

    <section class="header">
    <div class="header-container">
        <a href="index.html" class="logopic" ><img src="..\images\amuninglogogrey.png"></a>
        <a href="index.html" class="logo">amuning tokobe enterprise</a>

        <nav class="navbar">
            <a href="index.html">Home</a>
            <a href="#gal">gallery</a>
            <a href="about.html">about us</a>
            <a href="service.html">services</a>
            <a href="contact.html">contact us</a>
            <a href="signin.html" class="sign">sign in</a>
        </nav>

        <div id="menu-btn" class="fas fa-bars"></div>
    </div>

    </section>

         
    <section class="footer">

        <div class="box-container">
            <div class="box">
                <h3>quick links</h3>
                <a href="index.html"> <i class="fas fa-angle-right"></i> home</a>
                <a href="gallery.html"> <i class="fas fa-angle-right"></i> gallery</a>
                <a href="about.html"> <i class="fas fa-angle-right"></i> about us</a>
                <a href="service.html"> <i class="fas fa-angle-right"></i> services</a>
                <a href="contact.html"> <i class="fas fa-angle-right"></i> contact us</a>
                <a href="signin.html"> <i class="fas fa-angle-right"></i> sign in</a>
            </div>

            <div class="box">
                <h3>extra links</h3>
                <a href="#"> <i class="fas fa-angle-right"></i> ask questions</a>
                <a href="#"> <i class="fas fa-angle-right"></i> about us</a>
                <a href="#"> <i class="fas fa-angle-right"></i> privacy policy</a>
                <a href="#"> <i class="fas fa-angle-right"></i> terms of users</a>
            </div>

            <div class="box">
                <h3>contact information</h3>
                <a href="#"> <i class="fas fa-phone"></i> +696969696696 </a>
                <a href="#"> <i class="fas fa-phone"></i> +696969669696 </a>
                <a href="#"> <i class="fas fa-envelope"></i> wowamuning@gmail.com </a>
                <a href="#"> <i class="fas fa-map"></i> antipolo city, rizal </a>
            </div>

            <div class="box">
                <h3>follow us</h3>
                <a href="#"> <i class="fab fa-facebook-f"></i> facebook </a>
            </div>

        </div>

        <div class="credit"> created by <span>starlink</span> | all rights reserved! </div>
    </section>


<script>
   let index = 0;
     let images = document.querySelectorAll(".box img");
     setInterval(function() {
        images[index].classList.remove("show");  // Hide current image
        index = (index + 1) % images.length;     // Move to next (loop back to 0)
        images[index].classList.add("show");     // Show next image
    }, 4000); 
</script>
 
    
</body>
</html>