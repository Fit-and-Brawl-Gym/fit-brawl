// Username availability checker
(function() {
    const init = function() {
        const usernameInput = document.getElementById('usernameInput');
        const usernameMessage = document.getElementById('usernameAvailabilityMessage');

        if (!usernameInput || !usernameMessage) {
            return;
        }

        let debounceTimer;
        let lastCheckedUsername = '';

        function showMessage(message, type) {
            usernameMessage.textContent = message;
            usernameMessage.className = 'username-availability-message show ' + type;
        }

        function hideMessage() {
            usernameMessage.classList.remove('show', 'available', 'taken', 'checking');
        }

        async function checkUsernameAvailability(username) {
            // Don't check if it's the same as last checked
            if (username === lastCheckedUsername) {
                return;
            }

            // Validate minimum length
            if (username.length < 3) {
                if (username.length > 0) {
                    showMessage('Username must be at least 3 characters', 'taken');
                } else {
                    hideMessage();
                }
                return;
            }

            // Show checking status
            showMessage('Checking availability...', 'checking');
            lastCheckedUsername = username;

            try {
                const response = await fetch('../php/api/check_username.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({ username: username })
                });

                const data = await response.json();

                if (data.available) {
                    showMessage(data.message, 'available');
                } else {
                    showMessage(data.message, 'taken');
                }
            } catch (error) {
                console.error('Error checking username:', error);
                hideMessage();
            }
        }

        // Event listener with debounce
        usernameInput.addEventListener('input', (e) => {
            const username = e.target.value.trim();

            // Clear previous timer
            clearTimeout(debounceTimer);

            // If empty, hide message
            if (username.length === 0) {
                hideMessage();
                lastCheckedUsername = '';
                return;
            }

            // Debounce for 500ms to avoid too many requests
            debounceTimer = setTimeout(() => {
                checkUsernameAvailability(username);
            }, 500);
        });
    };

    // Run immediately if DOM is ready, otherwise wait
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
