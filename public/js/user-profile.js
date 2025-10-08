document.addEventListener('DOMContentLoaded', function() {
    const toggleEditBtn = document.getElementById('toggleEditBtn');
    const cancelEditBtn = document.getElementById('cancelEditBtn');
    const editProfileSection = document.getElementById('editProfileSection');
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
        });
    }

    // Avatar preview
    if (avatarInput) {
        avatarInput.addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
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
            avatarPreview.src = '../../images/profile-icon.svg';
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
