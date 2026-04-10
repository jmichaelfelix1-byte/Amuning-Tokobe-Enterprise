document.addEventListener('DOMContentLoaded', function() {
    // Toggle dropdowns on click
    document.querySelectorAll('.dropbtn').forEach(button => {
        button.addEventListener('click', function(e) {
            e.stopPropagation();
            const dropdown = this.closest('.dropdown');
            const isOpen = dropdown.classList.contains('show');
            
            // Close all other dropdowns
            document.querySelectorAll('.dropdown').forEach(d => {
                if (d !== dropdown) {
                    d.classList.remove('show');
                }
            });
            
            // Toggle current dropdown
            if (!isOpen) {
                dropdown.classList.add('show');
            } else {
                dropdown.classList.remove('show');
            }
        });
    });

    // Close dropdowns when clicking outside
    document.addEventListener('click', function(e) {
        if (!e.target.closest('.dropdown')) {
            document.querySelectorAll('.dropdown').forEach(dropdown => {
                dropdown.classList.remove('show');
            });
        }
    });

    // Close dropdown when clicking on a dropdown item
    document.querySelectorAll('.dropdown-item').forEach(item => {
        item.addEventListener('click', function() {
            this.closest('.dropdown').classList.remove('show');
        });
    });

    // Handle mobile menu toggle
    const menuToggle = document.getElementById('menuToggle');
    const navLinks = document.querySelector('.nav-links');
    
    if (menuToggle && navLinks) {
        menuToggle.addEventListener('click', function() {
            navLinks.classList.toggle('active');
            this.classList.toggle('active');
        });
    }
});