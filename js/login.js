document.addEventListener('DOMContentLoaded', () => {
    const loginForm = document.getElementById('loginForm');
    const loginMessage = document.getElementById('loginMessage');

    loginForm.addEventListener('submit', (e) => {
        e.preventDefault();
        const username = document.getElementById('username').value;
        const password = document.getElementById('password').value;

        // Simple hardcoded authentication
        if (username === 'admin' && password === 'Password@123') {
            localStorage.setItem('adminAuthenticated', 'true');
            window.location.href = 'admin.html';
        } else {
            loginMessage.textContent = 'Invalid username or password';
            loginMessage.style.color = 'red';
        }
    });
});