// Modal functionality
document.addEventListener('DOMContentLoaded', function() {
    // Get modal elements
    const termsModal = document.getElementById('terms-modal');
    const privacyModal = document.getElementById('privacy-modal');
    const openTermsBtn = document.getElementById('open-terms-modal');
    const openPrivacyBtn = document.getElementById('open-privacy-modal');
    const closeButtons = document.querySelectorAll('.modal-close');

    // Function to open modal
    function openModal(modal) {
        modal.classList.remove('hidden');
        document.body.style.overflow = 'hidden'; // Prevent background scrolling
    }

    // Function to close modal
    function closeModal(modal) {
        modal.classList.add('hidden');
        document.body.style.overflow = ''; // Restore scrolling
    }

    // Event listeners for opening modals
    if (openTermsBtn) {
        openTermsBtn.addEventListener('click', function(e) {
            e.preventDefault();
            openModal(termsModal);
        });
    }

    if (openPrivacyBtn) {
        openPrivacyBtn.addEventListener('click', function(e) {
            e.preventDefault();
            openModal(privacyModal);
        });
    }

    // Event listeners for closing modals
    closeButtons.forEach(button => {
        button.addEventListener('click', function() {
            const modal = button.closest('.modal-overlay');
            closeModal(modal);
        });
    });

    // Close modal when clicking outside the content
    [termsModal, privacyModal].forEach(modal => {
        modal.addEventListener('click', function(e) {
            if (e.target === modal) {
                closeModal(modal);
            }
        });
    });

    // Close modal on Escape key
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            [termsModal, privacyModal].forEach(modal => {
                if (!modal.classList.contains('hidden')) {
                    closeModal(modal);
                }
            });
        }
    });

    // Password toggle functionality
    const passwordToggles = document.querySelectorAll('.password-toggle');
    passwordToggles.forEach(toggle => {
        toggle.addEventListener('click', function() {
            const input = this.previousElementSibling;
            const type = input.getAttribute('type') === 'password' ? 'text' : 'password';
            input.setAttribute('type', type);
            this.innerHTML = type === 'password' ? '<i class="fas fa-eye"></i>' : '<i class="fas fa-eye-slash"></i>';
        });
    });

    // Password strength meter
    const passwordInput = document.getElementById('password');
    const strengthBar = document.querySelector('.strength-bar');
    const strengthText = document.querySelector('.strength-text');

    if (passwordInput && strengthBar && strengthText) {
        passwordInput.addEventListener('input', function() {
            const password = this.value;
            let strength = 0;
            let feedback = [];

            if (password.length >= 8) strength++;
            if (/[a-z]/.test(password)) strength++;
            if (/[A-Z]/.test(password)) strength++;
            if (/[0-9]/.test(password)) strength++;
            if (/[^A-Za-z0-9]/.test(password)) strength++;

            switch(strength) {
                case 0:
                case 1:
                    strengthBar.style.width = '20%';
                    strengthBar.style.backgroundColor = '#dc3545';
                    strengthText.textContent = 'Very Weak';
                    strengthText.style.color = '#dc3545';
                    break;
                case 2:
                    strengthBar.style.width = '40%';
                    strengthBar.style.backgroundColor = '#fd7e14';
                    strengthText.textContent = 'Weak';
                    strengthText.style.color = '#fd7e14';
                    break;
                case 3:
                    strengthBar.style.width = '60%';
                    strengthBar.style.backgroundColor = '#ffc107';
                    strengthText.textContent = 'Fair';
                    strengthText.style.color = '#ffc107';
                    break;
                case 4:
                    strengthBar.style.width = '80%';
                    strengthBar.style.backgroundColor = '#20c997';
                    strengthText.textContent = 'Good';
                    strengthText.style.color = '#20c997';
                    break;
                case 5:
                    strengthBar.style.width = '100%';
                    strengthBar.style.backgroundColor = '#28a745';
                    strengthText.textContent = 'Strong';
                    strengthText.style.color = '#28a745';
                    break;
            }
        });
    }

    // Form validation
    const registerForm = document.getElementById('registerForm');
    if (registerForm) {
        registerForm.addEventListener('submit', function(e) {
            const password = document.getElementById('password').value;
            const confirmPassword = document.getElementById('confirm_password').value;

            if (password !== confirmPassword) {
                e.preventDefault();
                alert('Passwords do not match!');
                return false;
            }
        });
    }
});
