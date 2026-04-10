const container = document.getElementById("container");
const signUpBtn = document.getElementById("signUpBtn");
const signInBtn = document.getElementById("signInBtn");
const loginForm = document.getElementById("loginForm");
const signupForm = document.getElementById("signupForm");

signUpBtn.addEventListener("click", () => {
    container.classList.add("active");
    setTimeout(() => {
        loginForm.style.display = "none";
        signupForm.style.display = "flex";
    }, 300);
});

signInBtn.addEventListener("click", () => {
    container.classList.remove("active");
    setTimeout(() => {
        signupForm.style.display = "none";
        loginForm.style.display = "flex";
    }, 300);
});

signupForm.querySelector("form").addEventListener("submit", (e) => {
    e.preventDefault();

    const firstName = signupForm.querySelector("input[placeholder='First Name']").value;
    const lastName = signupForm.querySelector("input[placeholder='Last Name']").value;
    const email = signupForm.querySelector("input[placeholder='Email']").value;
    const password = signupForm.querySelector("input[placeholder='Password']").value;

    if (firstName && lastName && email && password) {
        localStorage.setItem("username", `${firstName} ${lastName}`);
        Swal.fire({
            icon: 'success',
            title: 'Signup Successful!',
            text: `You've successfully signed up, welcome ${firstName}!`,
            confirmButtonText: 'Continue'
        }).then(() => {
            window.location.href = "index.html";
        });
    } else {
        Swal.fire({
            icon: 'error',
            title: 'Missing Information',
            text: 'Please fill in all fields.',
            confirmButtonText: 'OK'
        });
    }
});

loginForm.querySelector("form").addEventListener("submit", (e) => {
    e.preventDefault();

    const username = loginForm.querySelector("input[placeholder='User Name']").value;
    const password = loginForm.querySelector("input[placeholder='Password']").value;

    if (username && password) {
        localStorage.setItem("username", username);
        Swal.fire({
            icon: 'success',
            title: 'Login Successful!',
            text: `You've successfully signed in! Welcome, ${username}!`,
            confirmButtonText: 'Continue'
        }).then(() => {
            window.location.href = "index.html";
        });
    } else {
        Swal.fire({
            icon: 'error',
            title: 'Missing Information',
            text: 'Please enter your username and password.',
            confirmButtonText: 'OK'
        });
    }
});

