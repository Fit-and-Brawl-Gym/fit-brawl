fetch('feedback.php?api=true')
    .then(response => response.json())
    .then(data => {
        const container = document.getElementById('feedback-section');
        container.innerHTML = data.map((item, index) => {
            const cardClass = index % 2 === 0 ? 'left' : 'right';
            const avatar = item.avatar
                ? `../../uploads/avatars/${item.avatar}`
                : '../../images/profile-icon.svg';
            return `
                <div class="feedback-card ${cardClass}">
                    ${cardClass === 'left' ? `<img src="${avatar}" alt="${item.username}">` : ''}
                    <div class="bubble">
                        <h3>${item.username}</h3>
                        <p>${item.message}</p>
                    </div>
                    ${cardClass === 'right' ? `<img src="${avatar}" alt="${item.username}">` : ''}
                </div>
            `;
        }).join('');
    })
    .catch(error => console.error('Error loading feedback:', error));
