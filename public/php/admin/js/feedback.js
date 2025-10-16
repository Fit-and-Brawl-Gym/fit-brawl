document.addEventListener('DOMContentLoaded', () => {
    loadFeedback();
});

async function loadFeedback() {
    const section = document.getElementById('feedbackList');
    const res = await fetch('api/admin_feedback_api.php?action=fetch');
    const data = await res.json();

    section.innerHTML = '';
    if (!data.length) {
        section.innerHTML = '<p>No feedback found.</p>';
        return;
    }

    data.forEach(fb => {
        const card = document.createElement('div');
        card.classList.add('feedback-card');
        card.innerHTML = `
      <div class="feedback-header">
        <strong>${fb.username || 'Unknown User'}</strong>
        <span>${fb.date}</span>
      </div>
      <p class="feedback-message">${fb.message}</p>
      <button class="delete-btn" data-id="${fb.id}">Delete</button>
    `;
        section.appendChild(card);
    });

    document.querySelectorAll('.delete-btn').forEach(btn => {
        btn.addEventListener('click', async e => {
            if (!confirm('Are you sure you want to delete this feedback?')) return;
            const id = e.target.dataset.id;
            await fetch('api/admin_feedback_api.php?action=delete', {
                method: 'POST',
                body: new URLSearchParams({ id })
            });
            loadFeedback();
        });
    });
}
