document.addEventListener("DOMContentLoaded", () => {
    const equipmentContainer = document.getElementById("equipment-container");

    fetch("equipment.php?api=true")
        .then(response => response.json())
        .then(data => {
            equipmentContainer.innerHTML = ""; 
            if (!Array.isArray(data) || data.length === 0) {
                equipmentContainer.innerHTML = "<p>No equipment found.</p>";
                return;
            }

            data.forEach(item => {
                // Defensive defaults
                const name = item.name || "Unnamed";
                const status = item.status || "";
                const category = item.category || "";
                const image = item.image_path || "default-equipment.png";
                const description = item.description || "No description available.";

                const statusClass = getStatusClass(status);

                const card = document.createElement("div");
                card.classList.add("equipment-card");

                card.innerHTML = `
                    <div class="equipment-header">
                        <div class="equipment-title">
                            <h3>${escapeHtml(name)}</h3>
                        </div>
                        <div class="equipment-status">
                            <h4 class="label">Status</h4>
                            <span class="status-dot ${statusClass}"></span>
                            <span class="status-text">${capitalize(status)}</span>
                        </div>
                        <div class="equipment-category">
                            <h4 class="label">Category</h4>
                            <span class="category">${escapeHtml(category || "N/A")}</span>
                        </div>
                        <div class="equipment-icon">
                            <img src="../../images/${getCategoryIcon(category)}" alt="Category Icon">
                        </div>
                    </div>

                    <div class="equipment-dropdown">
                        <img src="../../images/${escapeHtml(image)}" alt="${escapeHtml(name)}" class="equipment-image">
                        <p class="equipment-desc">${escapeHtml(description)}</p>
                    </div>
                `;
                equipmentContainer.appendChild(card);

                // Attach toggle listener ONCE per created card
                card.addEventListener("click", (ev) => {
                    // prevent toggling when clicking links/buttons inside card if any
                    if (ev.target.tagName === "A" || ev.target.tagName === "BUTTON") return;

                    const cards = Array.from(document.querySelectorAll(".equipment-card"));
                    cards.forEach(c => {
                        if (c !== card) c.classList.remove("active");
                    });
                    card.classList.toggle("active");
                });
            });
        })
        .catch(error => {
            console.error("Error fetching equipment:", error);
            equipmentContainer.innerHTML = "<p>Error loading equipment data.</p>";
        });
});

function getStatusClass(status) {
    if (!status || typeof status !== "string") return "";
    switch (status.toLowerCase()) {
        case "available": return "available";
        case "maintenance": return "maintenance";
        case "out of order":
        case "out-of-order":
        case "out of order": return "out-of-order";
        default: return "";
    }
}

function getCategoryIcon(category) {
    if (!category || typeof category !== "string") return "functional-icon.svg";
    const cat = category.toLowerCase();
    if (cat.includes("cardio")) return "cardio-icon.svg";
    if (cat.includes("strength")) return "strength-icon.svg";
    if (cat.includes("core")) return "core-icon.svg";
    if (cat.includes("flexibility")) return "flexibility-icon.svg";
    return "functional-icon.svg";
}

function capitalize(text) {
    if (!text || typeof text !== "string") return "";
    const s = text.toLowerCase();
    return s.charAt(0).toUpperCase() + s.slice(1);
}

function escapeHtml(str) {
    if (!str && str !== 0) return "";
    return String(str)
        .replace(/&/g, "&amp;")
        .replace(/</g, "&lt;")
        .replace(/>/g, "&gt;")
        .replace(/"/g, "&quot;")
        .replace(/'/g, "&#039;");
}
