// Wait for DOM to be fully loaded (works with defer attribute)
(function() {
    // If DOM is already loaded, run immediately; otherwise wait for DOMContentLoaded
    const init = function() {
        const passwordInput = document.getElementById('passwordInput');
        const confirmPasswordInput = document.getElementById('confirmPasswordInput');
        const passwordRequirementsModal = document.getElementById('passwordRequirementsModal');
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
            special: /[!@#$%^&*?]/
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
            // If password is empty, reset all requirements to unmet
            if (!password || password.length === 0) {
                Object.keys(requirements).forEach(key => {
                    if (requirements[key]) {
                        requirements[key].classList.remove('met');
                        const icon = requirements[key].querySelector('.requirement-icon');
                        if (icon) icon.textContent = '✗';
                    }
                });
                return {
                    length: false,
                    uppercase: false,
                    lowercase: false,
                    number: false,
                    special: false
                };
            }

            const checks = {
                length: patterns.length.test(password),
                uppercase: patterns.uppercase.test(password),
                lowercase: patterns.lowercase.test(password),
                number: patterns.number.test(password),
                special: patterns.special.test(password)
            };

            // Update requirement display
            Object.keys(checks).forEach(key => {
                if (requirements[key]) {
                    if (checks[key]) {
                        requirements[key].classList.add('met');
                        const icon = requirements[key].querySelector('.requirement-icon');
                        if (icon) icon.textContent = '✓';
                    } else {
                        requirements[key].classList.remove('met');
                        const icon = requirements[key].querySelector('.requirement-icon');
                        if (icon) icon.textContent = '✗';
                    }
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
            if (!strengthIndicator) return;

            const strength = getPasswordStrength(password);
            const strengthBarFill = document.getElementById('strengthBarFill');
            const strengthText = document.getElementById('strengthText');

            if (strengthBarFill) strengthBarFill.className = `strength-bar-fill ${strength}`;
            if (strengthText) {
                strengthText.className = `strength-text ${strength}`;
                strengthText.textContent = `Strength: ${strength.charAt(0).toUpperCase() + strength.slice(1)}`;
            }
        }

        function checkPasswordMatch() {
            if (!confirmPasswordInput || !passwordMatchMessage) return;

            // If confirm password is empty, hide message
            if (confirmPasswordInput.value === '') {
                passwordMatchMessage.classList.remove('show', 'match', 'no-match');
                return;
            }

            // Show the message
            passwordMatchMessage.classList.add('show');

            if (passwordInput && passwordInput.value === confirmPasswordInput.value) {
                passwordMatchMessage.classList.remove('no-match');
                passwordMatchMessage.classList.add('match');
                passwordMatchMessage.textContent = 'Passwords match';
            } else {
                passwordMatchMessage.classList.remove('match');
                passwordMatchMessage.classList.add('no-match');
                passwordMatchMessage.textContent = 'Passwords do not match';
            }
        }

        // Debug: Check if elements exist
        if (!passwordInput) {
            console.error('Password input not found');
        }
        if (!passwordRequirementsModal) {
            console.error('Password requirements modal not found');
        }

        // Event listeners
        if (passwordInput && passwordRequirementsModal) {
            passwordInput.addEventListener('input', (e) => {
                // Toggle has-value class on parent
                const parentGroup = passwordInput.closest('.password-input-group');
                if (e.target.value.length > 0) {
                    if (parentGroup) parentGroup.classList.add('has-value');
                } else {
                    if (parentGroup) parentGroup.classList.remove('has-value');
                }

                // Show modal only when user types (has content)
                if (e.target.value.length > 0) {
                    passwordRequirementsModal.classList.add('show');
                    if (strengthIndicator) strengthIndicator.classList.add('show');
                } else {
                    // Hide modal when field is empty
                    passwordRequirementsModal.classList.remove('show');
                    if (strengthIndicator) strengthIndicator.classList.remove('show');
                }

                // Always check requirements, even if empty (this will reset them)
                checkPasswordRequirements(e.target.value);

                if (e.target.value.length > 0 && strengthIndicator) {
                    updateStrengthIndicator(e.target.value);
                }

                // Check if passwords match (if confirm password has been filled)
                if (confirmPasswordInput && confirmPasswordInput.value.length > 0) {
                    checkPasswordMatch();
                }
            });

            passwordInput.addEventListener('blur', () => {
                // Hide modal when leaving the field
                setTimeout(() => {
                    if (document.activeElement !== confirmPasswordInput) {
                        passwordRequirementsModal.classList.remove('show');
                    }
                }, 150);
            });
        }

        if (confirmPasswordInput && passwordRequirementsModal) {
            confirmPasswordInput.addEventListener('input', (e) => {
                // Toggle has-value class on parent
                const parentGroup = confirmPasswordInput.closest('.password-input-group');
                if (e.target.value.length > 0) {
                    if (parentGroup) parentGroup.classList.add('has-value');
                } else {
                    if (parentGroup) parentGroup.classList.remove('has-value');
                }

                checkPasswordMatch();
            });

            confirmPasswordInput.addEventListener('focus', () => {
                // Show modal if password field has content
                if (passwordInput && passwordInput.value.length > 0) {
                    passwordRequirementsModal.classList.add('show');
                }
            });

            confirmPasswordInput.addEventListener('blur', () => {
                // Hide modal when leaving confirm password field
                setTimeout(() => {
                    if (document.activeElement !== passwordInput) {
                        passwordRequirementsModal.classList.remove('show');
                    }
                }, 150);
            });
        }

        // Toggle password visibility - iOS compatible
        if (togglePassword && passwordInput) {
            togglePassword.addEventListener('click', () => {
                const isPassword = passwordInput.type === 'password';

                // Store current value and cursor position
                const currentValue = passwordInput.value;
                const cursorPosition = passwordInput.selectionStart;

                // Change type
                passwordInput.type = isPassword ? 'text' : 'password';

                // Restore value and cursor position (iOS fix)
                passwordInput.value = currentValue;
                passwordInput.setSelectionRange(cursorPosition, cursorPosition);

                // Toggle icon
                togglePassword.classList.toggle('fa-eye');
                togglePassword.classList.toggle('fa-eye-slash');

                // Refocus input to maintain UX
                passwordInput.focus();
            });
        }

        if (toggleConfirmPassword && confirmPasswordInput) {
            toggleConfirmPassword.addEventListener('click', () => {
                const isPassword = confirmPasswordInput.type === 'password';

                // Store current value and cursor position
                const currentValue = confirmPasswordInput.value;
                const cursorPosition = confirmPasswordInput.selectionStart;

                // Change type
                confirmPasswordInput.type = isPassword ? 'text' : 'password';

                // Restore value and cursor position (iOS fix)
                confirmPasswordInput.value = currentValue;
                confirmPasswordInput.setSelectionRange(cursorPosition, cursorPosition);

                // Toggle icon
                toggleConfirmPassword.classList.toggle('fa-eye');
                toggleConfirmPassword.classList.toggle('fa-eye-slash');

                // Refocus input to maintain UX
                confirmPasswordInput.focus();
            });
        }

        // Close password requirements modal when clicking outside or scrolling
        if (passwordRequirementsModal) {
            // Close on click outside
            document.addEventListener('click', (e) => {
                const isClickInsideModal = passwordRequirementsModal.contains(e.target);
                const isClickOnPasswordInput = passwordInput && passwordInput.contains(e.target);

                if (!isClickInsideModal && !isClickOnPasswordInput && passwordRequirementsModal.classList.contains('show')) {
                    passwordRequirementsModal.classList.remove('show');
                }
            });

            // Close on scroll (mobile devices)
            let scrollTimeout;
            window.addEventListener('scroll', () => {
                if (passwordRequirementsModal.classList.contains('show')) {
                    // Add slight delay to avoid closing during normal scrolling
                    clearTimeout(scrollTimeout);
                    scrollTimeout = setTimeout(() => {
                        passwordRequirementsModal.classList.remove('show');
                    }, 100);
                }
            }, { passive: true });

            // Close when user scrolls within the page
            document.addEventListener('touchmove', () => {
                if (passwordRequirementsModal.classList.contains('show')) {
                    passwordRequirementsModal.classList.remove('show');
                }
            }, { passive: true });
        }
    };

    // Run immediately if DOM is ready, otherwise wait
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        // DOM is already loaded
        init();
    }
})();
