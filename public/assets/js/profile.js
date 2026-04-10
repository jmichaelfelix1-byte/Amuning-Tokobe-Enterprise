// Profile Dropdown Toggle
document.addEventListener('DOMContentLoaded', function() {
    const profileToggle = document.getElementById('profileToggle');
    const profileDropdown = document.getElementById('profileDropdown');

    // Debug: Check if elements exist
    console.log('Profile toggle element:', profileToggle);
    console.log('Profile dropdown element:', profileDropdown);

    if (profileToggle && profileDropdown) {
        // Toggle dropdown on click
        profileToggle.addEventListener('click', function(e) {
            e.stopPropagation();
            const isVisible = profileDropdown.classList.contains('show');
            console.log('Profile dropdown clicked, currently visible:', isVisible);

            if (isVisible) {
                profileDropdown.classList.remove('show');
            } else {
                profileDropdown.classList.add('show');
            }
        });

        // Close dropdown when clicking outside
        document.addEventListener('click', function(e) {
            if (!profileToggle.contains(e.target) && !profileDropdown.contains(e.target)) {
                profileDropdown.classList.remove('show');
            }
        });

        // Close dropdown when clicking on a menu item
        const menuItems = profileDropdown.querySelectorAll('.dropdown-item');
        menuItems.forEach(item => {
            item.addEventListener('click', function() {
                profileDropdown.classList.remove('show');
            });
        });

        console.log('Profile dropdown functionality initialized');
    } else {
        console.warn('Profile dropdown elements not found');
    }

    document.getElementById('mobile').addEventListener('input', function () {
    // Remove non-numeric characters
    this.value = this.value.replace(/[^0-9]/g, '');

    // Limit to 11 digits
    if (this.value.length > 11) {
        this.value = this.value.slice(0, 11);
    }
    });

});
