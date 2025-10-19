const passwordInput = document.getElementById('passwordInput');
const confirmPasswordInput = document.getElementById('confirmPasswordInput');
const passwordRequirements = document.getElementById('passwordRequirements');
const strengthIndicator = document.getElementById('strengthIndicator');
const togglePassword = document.getElementById('togglePassword');
const toggleConfirmPassword = document.getElementById('toggleConfirmPassword');
const passwordMatchMessage = document.getElementById('passwordMatchMessage');

// Password validation patterns
const patterns = {
    length: /.{8,}/,
    uppercase: /[A-Z]/,
    lowercase: /[a-z]/,
    number: /[0-9]/,
    special: /[!@#$%^&*]/
};

// Requirement elements
const requirements = {
    length: document.getElementById('req-length'),
    uppercase: document.getElementById('req-uppercase'),
    lowercase: document.getElementById('req-lowercase'),
    number: document.getElementById('req-number'),
    special: document.getElementById('req-special')
};

function checkPasswordRequirements(password) {
    const checks = {
        length: patterns.length.test(password),
        uppercase: patterns.uppercase.test(password),
        lowercase: patterns.lowercase.test(password),
        number: patterns.number.test(password),
        special: patterns.special.test(password)
    };

    // Update requirement display
    Object.keys(checks).forEach(key => {
        if (checks[key]) {
            requirements[key].classList.add('met');
            requirements[key].querySelector('.requirement-icon').textContent = '✓';
        } else {
            requirements[key].classList.remove('met');
            requirements[key].querySelector('.requirement-icon').textContent = '✗';
        }
    });

    return checks;
}

function getPasswordStrength(password) {
    const checks = checkPasswordRequirements(password);
    const metCount = Object.values(checks).filter(Boolean).length;

    if (metCount <= 2) return 'weak';
    if (metCount <= 3) return 'medium';
    return 'strong';
}

function updateStrengthIndicator(password) {
    const strength = getPasswordStrength(password);
    const strengthBarFill = document.getElementById('strengthBarFill');
    const strengthText = document.getElementById('strengthText');

    strengthBarFill.className = `strength-bar-fill ${strength}`;
    strengthText.className = `strength-text ${strength}`;
    strengthText.textContent = `Strength: ${strength.charAt(0).toUpperCase() + strength.slice(1)}`;
}

function checkPasswordMatch() {
    if (confirmPasswordInput.value === '') {
        passwordMatchMessage.style.display = 'none';
        return;
    }

    if (passwordInput.value === confirmPasswordInput.value) {
        passwordMatchMessage.style.display = 'block';
        passwordMatchMessage.textContent = '✓ Passwords match';
        passwordMatchMessage.style.color = '#28a745';
    } else {
        passwordMatchMessage.style.display = 'block';
        passwordMatchMessage.textContent = '✗ Passwords do not match';
        passwordMatchMessage.style.color = '#dc3545';
    }
}

// Event listeners
passwordInput.addEventListener('input', (e) => {
    if (e.target.value.length > 0) {
        passwordRequirements.classList.add('show');
        strengthIndicator.classList.add('show');
        checkPasswordRequirements(e.target.value);
        updateStrengthIndicator(e.target.value);
    } else {
        passwordRequirements.classList.remove('show');
        strengthIndicator.classList.remove('show');
    }
    checkPasswordMatch();
});

confirmPasswordInput.addEventListener('input', checkPasswordMatch);

// Toggle password visibility
togglePassword.addEventListener('click', () => {
    const type = passwordInput.type === 'password' ? 'text' : 'password';
    passwordInput.type = type;
    togglePassword.classList.toggle('fa-eye');
    togglePassword.classList.toggle('fa-eye-slash');
});

toggleConfirmPassword.addEventListener('click', () => {
    const type = confirmPasswordInput.type === 'password' ? 'text' : 'password';
    confirmPasswordInput.type = type;
    toggleConfirmPassword.classList.toggle('fa-eye');
    toggleConfirmPassword.classList.toggle('fa-eye-slash');
});