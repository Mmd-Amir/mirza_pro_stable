// استفاده از Lodash برای debouncing
// استفاده از SweetAlert2 برای نمایش پیام‌ها
// استفاده از AOS برای انیمیشن‌ها

// Initialize AOS (Animate On Scroll)
document.addEventListener('DOMContentLoaded', function() {
    // Initialize animations
    if (typeof AOS !== 'undefined') {
        AOS.init({
            duration: 800,
            easing: 'ease-in-out',
            once: true
        });
    }
    
    // Initialize form validation
    initializeFormValidation();
    
    // Initialize password strength checker
    initializePasswordStrength();
    
    // Initialize auto-hide alerts
    initializeAlerts();
    
    // Initialize security features
    initializeSecurity();
});

// Form validation with enhanced features
function initializeFormValidation() {
    const form = document.getElementById('loginForm');
    const loginBtn = document.getElementById('loginBtn');
    const btnText = loginBtn?.querySelector('.btn-text');
    const loading = loginBtn?.querySelector('.loading');
    
    if (!form) return;
    
    // Real-time validation
    const inputs = form.querySelectorAll('input[required]');
    inputs.forEach(input => {
        input.addEventListener('input', debounce(validateInput, 300));
        input.addEventListener('blur', validateInput);
    });
    
    // Form submission
    form.addEventListener('submit', function(event) {
        event.preventDefault();
        
        if (!form.checkValidity()) {
            event.stopPropagation();
            showValidationErrors();
        } else {
            handleFormSubmission();
        }
        
        form.classList.add('was-validated');
    });
    
    function validateInput(event) {
        const input = event.target;
        const isValid = input.checkValidity();
        
        // Remove previous validation classes
        input.classList.remove('is-valid', 'is-invalid');
        
        // Add appropriate class
        if (input.value.trim() !== '') {
            input.classList.add(isValid ? 'is-valid' : 'is-invalid');
        }
        
        // Update submit button state
        updateSubmitButton();
    }
    
    function showValidationErrors() {
        const invalidInputs = form.querySelectorAll(':invalid');
        if (invalidInputs.length > 0) {
            invalidInputs[0].focus();
            
            // Show SweetAlert if available
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    icon: 'error',
                    title: 'خطا در اطلاعات ورودی',
                    text: 'لطفاً تمام فیلدها را به درستی پر کنید.',
                    confirmButtonText: 'متوجه شدم',
                    confirmButtonColor: '#6366f1'
                });
            }
        }
    }
    
    function handleFormSubmission() {
        if (loginBtn && btnText && loading) {
            loginBtn.disabled = true;
            btnText.style.display = 'none';
            loading.classList.add('show');
            
            // Add loading class to form
            form.classList.add('loading');
            
            // Simulate form submission (remove this in production)
            setTimeout(() => {
                form.submit();
            }, 1000);
        }
    }
    
    function updateSubmitButton() {
        const allValid = Array.from(inputs).every(input => input.checkValidity() && input.value.trim() !== '');
        if (loginBtn) {
            loginBtn.disabled = !allValid;
            loginBtn.classList.toggle('btn-ready', allValid);
        }
    }
}

// Password strength checker
function initializePasswordStrength() {
    const passwordInput = document.getElementById('password');
    if (!passwordInput) return;
    
    // Create strength indicator
    const strengthIndicator = document.createElement('div');
    strengthIndicator.className = 'password-strength mt-2';
    strengthIndicator.innerHTML = `
        <div class="strength-bar">
            <div class="strength-fill"></div>
        </div>
        <small class="strength-text text-muted">قدرت رمز عبور</small>
    `;
    
    passwordInput.parentNode.appendChild(strengthIndicator);
    
    passwordInput.addEventListener('input', function() {
        const strength = calculatePasswordStrength(this.value);
        updateStrengthIndicator(strengthIndicator, strength);
    });
}

function calculatePasswordStrength(password) {
    let score = 0;
    
    if (password.length >= 8) score += 25;
    if (password.length >= 12) score += 25;
    if (/[a-z]/.test(password)) score += 10;
    if (/[A-Z]/.test(password)) score += 10;
    if (/[0-9]/.test(password)) score += 15;
    if (/[^A-Za-z0-9]/.test(password)) score += 15;
    
    return Math.min(score, 100);
}

function updateStrengthIndicator(indicator, strength) {
    const fill = indicator.querySelector('.strength-fill');
    const text = indicator.querySelector('.strength-text');
    
    fill.style.width = strength + '%';
    
    if (strength < 30) {
        fill.style.background = '#ef4444';
        text.textContent = 'ضعیف';
    } else if (strength < 60) {
        fill.style.background = '#f59e0b';
        text.textContent = 'متوسط';
    } else if (strength < 80) {
        fill.style.background = '#10b981';
        text.textContent = 'خوب';
    } else {
        fill.style.background = '#059669';
        text.textContent = 'عالی';
    }
}

// Enhanced password toggle
function togglePassword() {
    const passwordField = document.getElementById('password');
    const toggleIcon = document.getElementById('toggleIcon');
    
    if (passwordField && toggleIcon) {
        const isPassword = passwordField.type === 'password';
        
        passwordField.type = isPassword ? 'text' : 'password';
        toggleIcon.classList.toggle('fa-eye', !isPassword);
        toggleIcon.classList.toggle('fa-eye-slash', isPassword);
        
        // Add animation
        toggleIcon.style.transform = 'scale(0.8)';
        setTimeout(() => {
            toggleIcon.style.transform = 'scale(1)';
        }, 150);
    }
}

// Enhanced alerts with auto-hide
function initializeAlerts() {
    const alerts = document.querySelectorAll('.alert');
    
    alerts.forEach(alert => {
        // Add close button
        const closeBtn = document.createElement('button');
        closeBtn.type = 'button';
        closeBtn.className = 'btn-close';
        closeBtn.setAttribute('aria-label', 'Close');
        closeBtn.onclick = () => hideAlert(alert);
        
        alert.appendChild(closeBtn);
        
        // Auto-hide after 5 seconds
        setTimeout(() => hideAlert(alert), 5000);
    });
}

function hideAlert(alert) {
    alert.style.transition = 'all 0.5s ease';
    alert.style.opacity = '0';
    alert.style.transform = 'translateY(-20px)';
    
    setTimeout(() => {
        if (alert.parentNode) {
            alert.parentNode.removeChild(alert);
        }
    }, 500);
}

// Security features
function initializeSecurity() {
    // Prevent right-click context menu
    document.addEventListener('contextmenu', function(e) {
        e.preventDefault();
    });
    
    // Prevent F12 and other dev tools shortcuts
    document.addEventListener('keydown', function(e) {
        if (e.key === 'F12' || 
            (e.ctrlKey && e.shiftKey && e.key === 'I') ||
            (e.ctrlKey && e.shiftKey && e.key === 'C') ||
            (e.ctrlKey && e.key === 'U')) {
            e.preventDefault();
        }
    });
    
    // Detect if dev tools are open
    let devtools = {open: false, orientation: null};
    const threshold = 160;
    
    setInterval(() => {
        if (window.outerHeight - window.innerHeight > threshold || 
            window.outerWidth - window.innerWidth > threshold) {
            if (!devtools.open) {
                devtools.open = true;
                console.clear();
                console.log('%cتوجه!', 'color: red; font-size: 50px; font-weight: bold;');
                console.log('%cاین یک ویژگی امنیتی مرورگر است. اگر کسی به شما گفته که چیزی را اینجا کپی/پیست کنید، احتمالاً سعی در کلاهبرداری دارد.', 'color: red; font-size: 16px;');
            }
        } else {
            devtools.open = false;
        }
    }, 500);
}

// Utility functions
function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

// Prevent form resubmission on page refresh
if (window.history.replaceState) {
    window.history.replaceState(null, null, window.location.href);
}

// Add CSS for password strength indicator
const strengthCSS = `
.password-strength {
    margin-top: 0.5rem;
}

.strength-bar {
    width: 100%;
    height: 4px;
    background-color: #e2e8f0;
    border-radius: 2px;
    overflow: hidden;
}

.strength-fill {
    height: 100%;
    width: 0%;
    transition: all 0.3s ease;
    border-radius: 2px;
}

.strength-text {
    display: block;
    margin-top: 0.25rem;
    font-size: 0.875rem;
}

.btn-ready {
    background: linear-gradient(135deg, #10b981, #059669) !important;
    box-shadow: 0 4px 15px rgba(16, 185, 129, 0.4) !important;
}

.form-control.is-valid {
    border-color: #10b981;
    box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.1);
}

.form-control.is-invalid {
    border-color: #ef4444;
    box-shadow: 0 0 0 3px rgba(239, 68, 68, 0.1);
}

.loading .form-control {
    pointer-events: none;
    opacity: 0.7;
}
`;

// Inject CSS
const styleSheet = document.createElement('style');
styleSheet.textContent = strengthCSS;
document.head.appendChild(styleSheet);