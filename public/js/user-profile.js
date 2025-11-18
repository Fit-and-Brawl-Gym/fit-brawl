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
    const currentPasswordWarning = document.getElementById('currentPasswordWarning');

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
                console.log('Avatar file selected:', file.name, 'Size:', file.size, 'bytes', 'Type:', file.type);

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
                    console.log('Avatar preview loaded successfully');
                    avatarPreview.src = e.target.result;
                    avatarPreview.classList.remove('default-icon');
                    removeAvatarBtn.classList.add('show');
                    removeAvatarFlag.value = '0';
                };
                reader.onerror = function(error) {
                    console.error('Error reading file:', error);
                    alert('Error reading file. Please try again.');
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
            const currentPassword = currentPasswordInput ? currentPasswordInput.value.trim() : '';
            const newPassword = newPasswordInput ? newPasswordInput.value.trim() : '';
            const confirmPassword = confirmPasswordInput ? confirmPasswordInput.value.trim() : '';

            if (currentPasswordWarning) {
                currentPasswordWarning.textContent = '';
                currentPasswordWarning.classList.remove('show');
            }

            if (sameAsCurrentWarning) {
                sameAsCurrentWarning.classList.remove('show');
            }

            // Check if avatar file is selected
            const avatarFile = avatarInput && avatarInput.files && avatarInput.files[0];
            const removeAvatarRequested = removeAvatarFlag && removeAvatarFlag.value === '1';

            // Log what's being submitted for debugging
            console.log('Form submission:', {
                hasAvatarFile: !!avatarFile,
                avatarFileName: avatarFile ? avatarFile.name : 'none',
                removeAvatar: removeAvatarRequested,
                hasNewPassword: !!newPassword
            });

            if (newPassword || confirmPassword) {
                // Ensure current password field is visible when attempting to change password
                if (currentPasswordGroup) {
                    currentPasswordGroup.style.display = 'block';
                }

                if (!currentPassword) {
                    e.preventDefault();
                    if (currentPasswordWarning) {
                        currentPasswordWarning.textContent = 'Please enter your current password to change your password.';
                        currentPasswordWarning.classList.add('show');
                    }
                    if (currentPasswordInput) {
                        currentPasswordInput.focus();
                    }
                    return false;
                }

                if (newPassword !== confirmPassword) {
                    e.preventDefault();
                    if (matchMessage) {
                        matchMessage.textContent = 'Passwords do not match';
                        matchMessage.classList.add('show', 'no-match');
                        matchMessage.classList.remove('match');
                        matchMessage.setAttribute('aria-hidden', 'false');
                    }
                    if (confirmPasswordInput) {
                        confirmPasswordInput.focus();
                    }
                    return false;
                }

                if (newPassword === currentPassword) {
                    e.preventDefault();
                    if (sameAsCurrentWarning) {
                        sameAsCurrentWarning.classList.add('show');
                        showRequirements(true);
                    }
                    if (newPasswordInput) {
                        newPasswordInput.scrollIntoView({ behavior: 'smooth', block: 'center' });
                        newPasswordInput.focus();
                    }
                    return false;
                }
            }

            // Show loading indicator
            const submitBtn = form.querySelector('.btn-save');
            if (submitBtn) {
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Saving...';
            }
        });
    }

    // Show/hide current password field when new password is entered
    if (newPasswordInput && currentPasswordGroup) {
        newPasswordInput.addEventListener('input', function() {
            if (this.value.length > 0) {
                currentPasswordGroup.style.display = 'block';
            } else if (!confirmPasswordInput.value) {
                currentPasswordGroup.style.display = 'none';
                if (currentPasswordInput) {
                    currentPasswordInput.value = '';
                }
                if (currentPasswordWarning) {
                    currentPasswordWarning.textContent = '';
                    currentPasswordWarning.classList.remove('show');
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
                if (currentPasswordWarning) {
                    currentPasswordWarning.textContent = '';
                    currentPasswordWarning.classList.remove('show');
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
        if (!sameAsCurrentWarning || !currentPasswordInput || !newPasswordInput) return;
        const currentVal = currentPasswordInput.value.trim();
        const newVal = newPasswordInput.value.trim();

        if (currentVal && newVal && currentVal === newVal) {
            sameAsCurrentWarning.classList.add('show');
            showRequirements(true);
        } else {
            sameAsCurrentWarning.classList.remove('show');
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
            // Hide the requirements modal when user is done typing
            showRequirements(false);
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
        currentPasswordInput.addEventListener('input', () => {
            if (currentPasswordWarning && currentPasswordInput.value.trim()) {
                currentPasswordWarning.textContent = '';
                currentPasswordWarning.classList.remove('show');
            }
            checkSameAsCurrentPassword();
            updateMatchMessage();
        });
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
