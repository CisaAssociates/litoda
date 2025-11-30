function togglePassword() {
    const passwordInput = document.getElementById('password');
    const toggleBtn = document.querySelector('.password-toggle');
    
    if (passwordInput.type === 'password') {
        passwordInput.type = 'text';
        toggleBtn.innerHTML = '<i class="fa-regular fa-eye-slash"></i>';
    } else {
        passwordInput.type = 'password';
        toggleBtn.innerHTML = '<i class="fa-regular fa-eye"></i>';
    }
}

// Form submission with loading animation
document.getElementById('loginForm')?.addEventListener('submit', function(e) {
    const submitBtn = document.getElementById('loginBtn');
    if (submitBtn) {
        submitBtn.classList.add('loading');
        submitBtn.style.pointerEvents = 'none';
        submitBtn.disabled = true;
        
        // Original button text
        const originalText = submitBtn.textContent;
        submitBtn.textContent = 'Logging in...';
        
        // Remove loading state after form processes (fallback)
        setTimeout(() => {
            submitBtn.classList.remove('loading');
            submitBtn.style.pointerEvents = 'auto';
            submitBtn.disabled = false;
            submitBtn.textContent = originalText;
        }, 3000);
    }
});

// Auto-hide messages after 5 seconds
setTimeout(() => {
    const errorMsg = document.querySelector('.error-message');
    const successMsg = document.querySelector('.success-message');
    
    if (errorMsg && window.getComputedStyle(errorMsg).display !== 'none') {
        errorMsg.style.opacity = '0';
        errorMsg.style.transition = 'opacity 0.5s ease';
        setTimeout(() => {
            errorMsg.style.display = 'none';
        }, 500);
    }
    
    if (successMsg && window.getComputedStyle(successMsg).display !== 'none') {
        successMsg.style.opacity = '0';
        successMsg.style.transition = 'opacity 0.5s ease';
        setTimeout(() => {
            successMsg.style.display = 'none';
        }, 500);
    }
}, 5000);

// Smooth focus animations
document.querySelectorAll('.form-input').forEach(input => {
    input.addEventListener('focus', function() {
        this.parentElement?.classList.add('focused');
    });
    
    input.addEventListener('blur', function() {
        if (this.value === '') {
            this.parentElement?.classList.remove('focused');
        }
    });
});

// Clear error message when user starts typing
document.querySelectorAll('.form-input').forEach(input => {
    input.addEventListener('input', function() {
        const errorMsg = document.querySelector('.error-message');
        if (errorMsg) {
            errorMsg.style.display = 'none';
        }
    });
});

// Prevent double submission
let isSubmitting = false;
document.getElementById('loginForm')?.addEventListener('submit', function(e) {
    if (isSubmitting) {
        e.preventDefault();
        return false;
    }
    isSubmitting = true;
    
    // Reset after 3 seconds as fallback
    setTimeout(() => {
        isSubmitting = false;
    }, 3000);
});