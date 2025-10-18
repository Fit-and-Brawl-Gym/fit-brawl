const container = document.getElementById('equipment-container');
const searchInput = document.getElementById('equipmentSearch');
const statusFilter = document.getElementById('statusFilter');
const categoryChips = document.getElementById('category-filters');

let EQUIPMENT_DATA = [];
let activeCategory = null; // no filter by default

function capitalize(s) {
  return s && s.length ? s.charAt(0).toUpperCase() + s.slice(1) : s;
}

function renderEquipment(items) {
  container.innerHTML = items.map(item => {
    // category stored in DB as comma separated string – normalize to array
    const categories = item.category ? item.category.split(',').map(c => c.trim()) : [];
    const statusClass = (item.status || '').toLowerCase().replace(/\s+/g, '-');
    return `
      <div class="equipment-card" data-id="${item.id}" data-status="${statusClass}" data-category="${categories.join(',')}">
        <div class="equipment-header">
          <div>
            <h3>${escapeHtml(item.name)}</h3>
            <div class="status-text"><span class="status-dot ${statusClass}"></span><span class="value">${escapeHtml(item.status)}</span></div>
            <div class="equipment-card-category">Category: ${escapeHtml(categories.join(', '))}</div>
          </div>
        </div>
        <div class="equipment-dropdown">
          <img src="${
            item.image_path
              ? item.image_path
              : `../../images/${item.name.toLowerCase().replace(/\s+/g, '-')}.jpg`
          }" alt="${escapeHtml(item.name)}" class="equipment-image"
          onerror="this.onerror=null; this.src='../../images/placeholder-equipment.jpg';">
          <div class="equipment-desc">${escapeHtml(item.description || '')}</div>
        </div>
      </div>
    `;
  }).join('');

  // Attach click handlers for expansion
  const cards = container.querySelectorAll('.equipment-card');
  cards.forEach(card => {
    card.addEventListener('click', () => {
      cards.forEach(c => c !== card && c.classList.remove('active'));
      card.classList.toggle('active');
    });
  });
}

function escapeHtml(unsafe) {
  return String(unsafe)
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;')
    .replace(/'/g, '&#039;');
}

function applyFilters() {
  const q = (searchInput.value || '').toLowerCase().trim();
  const status = statusFilter.value;

  const filtered = EQUIPMENT_DATA.filter(item => {
    const normalize = str => str.trim().toLowerCase().replace(/\s+/g, ' ');
    const categories = item.category ? item.category.split(',').map(c => normalize(c)) : [];

    if (activeCategory) {
      // Normalize chip category and get first word
      const chipCategory = normalize(activeCategory);
      const chipFirstWord = chipCategory.split(' ')[0];
      const hasCategory = categories.some(cat => {
        const catFirstWord = cat.split(' ')[0];
        return catFirstWord === chipFirstWord;
      });
      if (!hasCategory) return false;
    }

    if (status !== 'all' && status !== '') {
      if (!item.status || item.status.toLowerCase() !== status.toLowerCase()) {
        return false;
      }
    }

    if (q) {
      const hay = (item.name + ' ' + (item.description || '') + ' ' + (item.category || '')).toLowerCase();
      if (!hay.includes(q)) return false;
    }

    return true;
  });

  renderEquipment(filtered);
}

// Load data from API
fetch('equipment.php?api=true')
  .then(r => r.json())
  .then(data => {
    // map to expected fields
    EQUIPMENT_DATA = data.map(d => ({
      id: d.id,
      name: d.name,
      category: d.category,
      status: d.status,
      description: d.description,
      image_path: d.image_path || null
    }));
    applyFilters();
  })
  .catch(err => console.error('Error loading equipment:', err));

// Event listeners
searchInput.addEventListener('input', () => applyFilters());
statusFilter.addEventListener('change', () => applyFilters());

// Category chip handling
categoryChips.querySelectorAll('.category-chip').forEach(chip => {
  chip.addEventListener('click', () => {
    const wasActive = chip.classList.contains('active');
    categoryChips.querySelectorAll('.category-chip').forEach(c => c.classList.remove('active'));
    if (wasActive) {
      activeCategory = null;
    } else {
      chip.classList.add('active');
      activeCategory = chip.dataset.category;
    }
    applyFilters();
  });
});
