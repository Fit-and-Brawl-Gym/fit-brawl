document.addEventListener('DOMContentLoaded', function() {
    const toggleEditBtn = document.getElementById('toggleEditBtn');
    const cancelEditBtn = document.getElementById('cancelEditBtn');
    const editProfileSection = document.getElementById('editProfileSection');
    const profileHeader = document.querySelector('.profile-header');
    const avatarInput = document.getElementById('avatarInput');
    const avatarPreview = document.getElementById('avatarPreview');
    const removeAvatarBtn = document.getElementById('removeAvatarBtn');
    const removeAvatarFlag = document.getElementById('removeAvatarFlag');
    const currentPasswordInput = document.getElementById('current_password');
    const currentPasswordGroup = document.getElementById('currentPasswordGroup');
    const newPasswordInput = document.getElementById('new_password');
    const confirmPasswordInput = document.getElementById('confirm_password');
    const requirementsModal = document.getElementById('profilePasswordRequirements');
    const matchMessage = document.getElementById('profilePasswordMatch');
    const sameAsCurrentWarning = document.getElementById('sameAsCurrentWarning');

    console.log('User profile elements loaded:', {
        currentPasswordInput: !!currentPasswordInput,
        currentPasswordGroup: !!currentPasswordGroup,
        newPasswordInput: !!newPasswordInput,
        sameAsCurrentWarning: !!sameAsCurrentWarning,
        requirementsModal: !!requirementsModal
    });

    if (currentPasswordInput) {
        console.log('Current password input element ID:', currentPasswordInput.id);
    }

    // Toggle edit profile section
    if (toggleEditBtn) {
        toggleEditBtn.addEventListener('click', function() {
            editProfileSection.classList.add('active');
            editProfileSection.scrollIntoView({ behavior: 'smooth' });
        });
    }

    // Cancel edit
    if (cancelEditBtn) {
        cancelEditBtn.addEventListener('click', function() {
            editProfileSection.classList.remove('active');
            // Smoothly return to the top context (profile header) with offset for sticky header
            const stickyHeader = document.querySelector('header');
            const offset = stickyHeader ? Math.min(120, stickyHeader.offsetHeight || 0) : 96;
            if (profileHeader) {
                const y = profileHeader.getBoundingClientRect().top + window.pageYOffset - offset;
                window.scrollTo({ top: Math.max(0, y), behavior: 'smooth' });
            } else {
                window.scrollTo({ top: 0, behavior: 'smooth' });
            }
        });
    }

    // Avatar preview
    if (avatarInput) {
        avatarInput.addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                // Check file size (2MB limit)
                const maxSize = 2 * 1024 * 1024; // 2MB in bytes
                if (file.size > maxSize) {
                    alert('File size exceeds 2MB limit. Please choose a smaller image.');
                    avatarInput.value = ''; // Clear the input
                    return;
                }

                // Check file type
                const allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
                if (!allowedTypes.includes(file.type)) {
                    alert('Please select a valid image file (JPG, JPEG, PNG, or GIF).');
                    avatarInput.value = ''; // Clear the input
                    return;
                }

                const reader = new FileReader();
                reader.onload = function(e) {
                    avatarPreview.src = e.target.result;
                    avatarPreview.classList.remove('default-icon');
                    removeAvatarBtn.classList.add('show');
                    removeAvatarFlag.value = '0';
                };
                reader.readAsDataURL(file);
            }
        });
    }

    // Remove avatar
    if (removeAvatarBtn) {
        removeAvatarBtn.addEventListener('click', function() {
            avatarPreview.src = '../../images/account-icon.svg';
            avatarPreview.classList.add('default-icon');
            removeAvatarBtn.classList.remove('show');
            avatarInput.value = '';
            removeAvatarFlag.value = '1';
        });
    }

    // Password validation
    const form = document.querySelector('.edit-profile-form');
    if (form) {
        form.addEventListener('submit', function(e) {
            const currentPassword = document.getElementById('current_password').value;
            const newPassword = document.getElementById('new_password').value;
            const confirmPassword = document.getElementById('confirm_password').value;

            if (newPassword || confirmPassword) {
                // Check if current password is provided when changing password
                if (!currentPassword) {
                    e.preventDefault();
                    alert('Please enter your current password to change your password.');
                    return false;
                }

                if (newPassword !== confirmPassword) {
                    e.preventDefault();
                    alert('New passwords do not match!');
                    return false;
                }

                // Check if new password is same as current password
                if (newPassword === currentPassword) {
                    e.preventDefault();
                    // Show the warning in the modal instead of alert
                    if (sameAsCurrentWarning) {
                        sameAsCurrentWarning.classList.add('show');
                        showRequirements(true);
                    }
                    // Scroll to the new password field
                    if (newPasswordInput) {
                        newPasswordInput.scrollIntoView({ behavior: 'smooth', block: 'center' });
                        newPasswordInput.focus();
                    }
                    return false;
                }
            }
        });
    }

    // Show/hide current password field when new password is entered
    if (newPasswordInput && currentPasswordGroup) {
        newPasswordInput.addEventListener('input', function() {
            if (this.value.length > 0) {
                console.log('Showing current password field');
                currentPasswordGroup.style.display = 'block';
            } else if (!confirmPasswordInput.value) {
                console.log('Hiding current password field');
                currentPasswordGroup.style.display = 'none';
                if (currentPasswordInput) {
                    currentPasswordInput.value = '';
                }
            }
        });
    }

    if (confirmPasswordInput && currentPasswordGroup) {
        confirmPasswordInput.addEventListener('input', function() {
            if (this.value.length > 0) {
                currentPasswordGroup.style.display = 'block';
            } else if (!newPasswordInput.value) {
                currentPasswordGroup.style.display = 'none';
                if (currentPasswordInput) {
                    currentPasswordInput.value = '';
                }
            }
        });
    }

    // Live password requirements & match like sign-up
    function evaluatePassword(password) {
        return {
            length: password.length >= 8,
            uppercase: /[A-Z]/.test(password),
            lowercase: /[a-z]/.test(password),
            number: /[0-9]/.test(password),
            special: /[!@#$%^&*]/.test(password)
        };
    }

    function checkSameAsCurrentPassword() {
        if (!sameAsCurrentWarning || !currentPasswordInput || !newPasswordInput) {
            console.log('Missing elements:', {
                warning: !!sameAsCurrentWarning,
                current: !!currentPasswordInput,
                new: !!newPasswordInput
            });
            return;
        }
        const currentVal = currentPasswordInput.value.trim();
        const newVal = newPasswordInput.value.trim();

        console.log('Checking passwords:', {
            currentVal: currentVal ? '***' : '(empty)',
            newVal: newVal ? '***' : '(empty)',
            match: currentVal === newVal,
            bothFilled: !!(currentVal && newVal)
        });

        // Only show warning if both fields have values and they match
        if (currentVal && newVal && currentVal === newVal) {
            sameAsCurrentWarning.classList.add('show');
            console.log('Warning shown - passwords match!');
        } else {
            sameAsCurrentWarning.classList.remove('show');
            if (currentVal && newVal && currentVal !== newVal) {
                console.log('Warning hidden - passwords different');
            }
        }
    }

    function updateRequirementsUI(status) {
        if (!requirementsModal) return;
        Object.keys(status).forEach(key => {
            const item = requirementsModal.querySelector(`.requirement-item[data-req="${key}"]`);
            if (item) {
                item.classList.toggle('met', !!status[key]);
            }
        });
    }

    function showRequirements(show) {
        if (!requirementsModal) return;
        requirementsModal.classList.toggle('show', !!show);
        requirementsModal.setAttribute('aria-hidden', show ? 'false' : 'true');
    }

    function updateMatchMessage() {
        if (!matchMessage || !confirmPasswordInput) return;
        const currentVal = currentPasswordInput ? currentPasswordInput.value : '';
        const newVal = newPasswordInput ? newPasswordInput.value : '';
        const confVal = confirmPasswordInput.value;

        if (!newVal && !confVal) {
            matchMessage.classList.remove('show', 'match', 'no-match');
            matchMessage.setAttribute('aria-hidden', 'true');
            matchMessage.textContent = '';
            return;
        }

        matchMessage.classList.add('show');
        matchMessage.setAttribute('aria-hidden', 'false');

        // Check if new password matches current password
        if (newVal && currentVal && newVal === currentVal) {
            matchMessage.textContent = 'New password cannot be the same as current password';
            matchMessage.classList.add('no-match');
            matchMessage.classList.remove('match');
            return;
        }

        if (newVal && confVal && newVal === confVal) {
            matchMessage.textContent = 'Passwords match';
            matchMessage.classList.add('match');
            matchMessage.classList.remove('no-match');
        } else {
            matchMessage.textContent = 'Passwords do not match';
            matchMessage.classList.add('no-match');
            matchMessage.classList.remove('match');
        }
    }

    if (newPasswordInput) {
        newPasswordInput.addEventListener('focus', () => showRequirements(true));
        newPasswordInput.addEventListener('blur', () => {
            // Keep visible if input has value to aid user; hide if empty
            if (!newPasswordInput.value) showRequirements(false);
        });
        newPasswordInput.addEventListener('input', () => {
            const status = evaluatePassword(newPasswordInput.value || '');
            updateRequirementsUI(status);
            showRequirements(true);
            checkSameAsCurrentPassword();
            updateMatchMessage();
        });
    }

    if (currentPasswordInput) {
        console.log('Current password input listener attached');
        currentPasswordInput.addEventListener('input', () => {
            console.log('Current password input event fired, value:', currentPasswordInput.value ? '***' : '(empty)');
            checkSameAsCurrentPassword();
            updateMatchMessage();
        });
    } else {
        console.log('Current password input NOT found!');
    }

    if (confirmPasswordInput) {
        confirmPasswordInput.addEventListener('focus', updateMatchMessage);
        confirmPasswordInput.addEventListener('input', updateMatchMessage);
        confirmPasswordInput.addEventListener('blur', () => {
            const hasAny = (newPasswordInput && newPasswordInput.value) || confirmPasswordInput.value;
            if (!hasAny && matchMessage) {
                matchMessage.classList.remove('show', 'match', 'no-match');
                matchMessage.setAttribute('aria-hidden', 'true');
                matchMessage.textContent = '';
            }
        });
    }
});
