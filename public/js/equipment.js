const cards = document.querySelectorAll(".equipment-card");

  cards.forEach(card => {
    card.addEventListener("click", () => {
      // Close other cards
      cards.forEach(c => c !== card && c.classList.remove("active"));
      // Toggle the clicked one
      card.classList.toggle("active");
    });
  });
        // Load equipment data
        fetch('equipment.php?api=true')
            .then(response => response.json())
            .then(data => {
                const container = document.getElementById('equipment-container');
                container.innerHTML = data.map(item => `
                    <div class="equipment-card">
                        <h3>${item.name}</h3>
                        <p>Status: <span class="status-${item.equipment_status.toLowerCase().replace(/\s+/g, '-')}">${item.equipment_status}</span></p>
                    </div>
                `).join('');
            })
            .catch(error => console.error('Error loading equipment:', error));