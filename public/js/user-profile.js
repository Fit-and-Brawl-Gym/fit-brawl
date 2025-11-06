document.addEventListener('DOMContentLoaded', function() {
    const toggleEditBtn = document.getElementById('toggleEditBtn');
    const cancelEditBtn = document.getElementById('cancelEditBtn');
    const editProfileSection = document.getElementById('editProfileSection');
    const profileHeader = document.querySelector('.profile-header');
    const avatarInput = document.getElementById('avatarInput');
    const avatarPreview = document.getElementById('avatarPreview');
    const removeAvatarBtn = document.getElementById('removeAvatarBtn');
    const removeAvatarFlag = document.getElementById('removeAvatarFlag');

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
            const newPassword = document.getElementById('new_password').value;
            const confirmPassword = document.getElementById('confirm_password').value;

            if (newPassword || confirmPassword) {
                if (newPassword !== confirmPassword) {
                    e.preventDefault();
                    alert('Passwords do not match!');
                    return false;
                }
            }
        });
    }
});
