document.addEventListener('DOMContentLoaded', function() {
    const accountDropdown = document.querySelector('.account-dropdown');

    if (accountDropdown) {
        const accountIcon = accountDropdown.querySelector('.account-icon');

        // Toggle dropdown on click
        accountIcon.addEventListener('click', function(e) {
            e.stopPropagation();
            accountDropdown.classList.toggle('active');
        });

        // Close dropdown when clicking outside
        document.addEventListener('click', function(e) {
            if (!accountDropdown.contains(e.target)) {
                accountDropdown.classList.remove('active');
            }
        });
    }
});
