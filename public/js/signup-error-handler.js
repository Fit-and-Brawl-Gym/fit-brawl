// Sign-up error handler - Scrolls to error/success messages
document.addEventListener('DOMContentLoaded', function() {
    // Check if there's an error or success message
    const errorBox = document.getElementById('errorMessageBox');
    const successBox = document.getElementById('successMessageBox');

    if (errorBox || successBox) {
        // Small delay to ensure page is fully rendered
        setTimeout(function() {
            const messageBox = errorBox || successBox;

            // Scroll the message into view smoothly
            messageBox.scrollIntoView({
                behavior: 'smooth',
                block: 'nearest',
                inline: 'nearest'
            });

            // Add a pulsing animation to draw attention
            messageBox.style.animation = 'slideInDown 0.4s ease-out, pulse 1s ease-in-out 0.4s 2';

            console.log('Scrolled to message:', errorBox ? 'error' : 'success');
        }, 100);
    }
});

// Add pulse animation via CSS
const style = document.createElement('style');
style.textContent = `
    @keyframes pulse {
        0%, 100% {
            transform: scale(1);
        }
        50% {
            transform: scale(1.02);
        }
    }
`;
document.head.appendChild(style);
