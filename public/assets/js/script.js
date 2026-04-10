// Dropdown toggle functionality
document.addEventListener('DOMContentLoaded', function() {
    const dropdownToggle = document.getElementById('dropdownToggle');
    
    if (dropdownToggle) {
        dropdownToggle.addEventListener('click', function(event) {
            event.preventDefault();
            const dropdownContent = document.getElementById('dropitDown');
            const dropdown = document.querySelector('.dropdown');
            
            // Toggle the 'show' class
            if (dropdownContent) {
                dropdownContent.classList.toggle('show');
            }
            if (dropdown) {
                dropdown.classList.toggle('open');
            }
        });
    }
});

// Close dropdown when clicking outside
document.addEventListener('click', function(event) {
    const dropdown = document.querySelector('.dropdown');
    const dropdownContent = document.getElementById('dropitDown');
    
    if (dropdown && !dropdown.contains(event.target)) {
        dropdownContent.classList.remove('show');
        dropdown.classList.remove('open');
    }
});

// Mobile menu toggle
const menuBtn = document.getElementById('menu-btn');
const navbar = document.getElementById('navbar');
    
if (menuBtn && navbar) {
    menuBtn.addEventListener('click', function() {
        navbar.classList.toggle('active');
    });
}

// Password toggle functionality for multiple fields
const passwordToggles = document.querySelectorAll('.password-toggle');

passwordToggles.forEach((toggle) => {
    const targetId = toggle.getAttribute('data-target');
    const targetInput = document.getElementById(targetId);

    if (!targetInput) {
        return;
    }

    toggle.addEventListener('click', () => {
        const isPassword = targetInput.getAttribute('type') === 'password';
        targetInput.setAttribute('type', isPassword ? 'text' : 'password');
        toggle.classList.toggle('fa-eye');
        toggle.classList.toggle('fa-eye-slash');
        toggle.setAttribute('aria-label', isPassword ? 'Hide password' : 'Show password');
    });
});

// Basic form validation helpers
const showFieldError = (input, message) => {
    const group = input.closest('.input-group');
    if (!group) return;

    let error = group.querySelector('.input-error');
    if (!error) {
        error = document.createElement('small');
        error.className = 'input-error';
        group.appendChild(error);
    }

    error.textContent = message;
    group.classList.add('has-error');
};

const clearFieldError = (input) => {
    const group = input.closest('.input-group');
    if (!group) return;

    group.classList.remove('has-error');
    const error = group.querySelector('.input-error');
    if (error) {
        error.remove();
    }
};

const attachClearOnInput = (inputs) => {
    inputs.forEach((input) => {
        input.addEventListener('input', () => clearFieldError(input));
    });
};

// Sign-in form validation
const signinForm = document.getElementById('signinForm');

if (signinForm) {
    const usernameInput = signinForm.querySelector('#username');
    const passwordInput = signinForm.querySelector('#password');
    const googleBtn = signinForm.querySelector('.google-btn');

    attachClearOnInput([usernameInput, passwordInput]);

    // Handle Google login button
    if (googleBtn) {
        googleBtn.addEventListener('click', () => {
            window.location.href = 'google_auth.php';
        });
    }

    signinForm.addEventListener('submit', (event) => {
        event.preventDefault(); // Always prevent default for AJAX

        let isValid = true;

        if (!usernameInput.value.trim()) {
            showFieldError(usernameInput, 'Email is required.');
            isValid = false;
        } else if (!usernameInput.validity.valid) {
            showFieldError(usernameInput, 'Please enter a valid email address.');
            isValid = false;
        }

        if (!passwordInput.value.trim()) {
            showFieldError(passwordInput, 'Password is required.');
            isValid = false;
        }

        if (isValid) {
            // Submit form via AJAX
            const formData = new FormData(signinForm);
            fetch('process_signin.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Sign In Successful!',
                        text: data.message,
                        confirmButtonText: 'OK'
                    }).then(() => {
                        window.location.href = data.redirect || 'index.php'; // Use redirect URL or default to index.php
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Sign In Failed',
                        text: data.message,
                        confirmButtonText: 'OK'
                    });
                }
            })
            .catch(error => {
                console.error('Error:', error);
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'An error occurred. Please try again.',
                    confirmButtonText: 'OK'
                });
            });
        }
    });
}

// Sign-up form validation
const signupForm = document.getElementById('signupForm');

// Do not attach the automatic AJAX handler if the page uses the terms modal flow
if (signupForm && !document.getElementById('termsModal')) {
    const fullNameInput = signupForm.querySelector('#full_name');
    const emailInput = signupForm.querySelector('#email');
    const passwordInput = signupForm.querySelector('#signup_password');
    const confirmInput = signupForm.querySelector('#confirm_password');
    const googleBtn = signupForm.querySelector('.google-btn');

    attachClearOnInput([fullNameInput, emailInput, passwordInput, confirmInput]);

    // Handle Google login button
    if (googleBtn) {
        googleBtn.addEventListener('click', () => {
            window.location.href = 'google_auth.php?signup=1';
        });
    }

    signupForm.addEventListener('submit', (event) => {
        event.preventDefault(); // Always prevent default for AJAX

        let isValid = true;

        if (!fullNameInput.value.trim()) {
            showFieldError(fullNameInput, 'Full name is required.');
            isValid = false;
        }

        if (!emailInput.value.trim()) {
            showFieldError(emailInput, 'Email is required.');
            isValid = false;
        } else if (!emailInput.validity.valid) {
            showFieldError(emailInput, 'Please enter a valid email address.');
            isValid = false;
        }

        if (!passwordInput.value.trim()) {
            showFieldError(passwordInput, 'Password is required.');
            isValid = false;
        } else if (passwordInput.value.length < 8) {
            showFieldError(passwordInput, 'Password must be at least 8 characters.');
            isValid = false;
        }

        if (!confirmInput.value.trim()) {
            showFieldError(confirmInput, 'Please confirm your password.');
            isValid = false;
        } else if (confirmInput.value !== passwordInput.value) {
            showFieldError(confirmInput, 'Passwords do not match.');
            isValid = false;
        }

        if (isValid) {
            // Submit form via AJAX
            const formData = new FormData(signupForm);
            fetch('process_signup.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Signup Successful!',
                        text: data.message,
                        confirmButtonText: 'OK'
                    }).then(() => {
                        window.location.href = 'signin.php';
                    });
                } else {
                    if (data.message === 'This email is already registered with Google. Please sign in using Google.') {
                        window.location.href = 'signin.php?message=already_registered_google';
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Signup Failed',
                            text: data.message,
                            confirmButtonText: 'OK'
                        });
                    }
                }
            })
            .catch(error => {
                console.error('Error:', error);
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'An error occurred. Please try again.',
                    confirmButtonText: 'OK'
                });
            });
        }
    });
}

// Note: duplicate submit handler removed — the primary handler above handles AJAX sign-in and shows SweetAlert.